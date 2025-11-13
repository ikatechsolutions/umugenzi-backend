<?php

namespace App\Http\Controllers;

use App\Models\Ticketdistribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Ticket;
use App\Models\Ticketinstance;

class TicketdistributionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ticketdistributions = Ticketdistribution::with('user', 'ticket');

        return response()->jsnon($ticketdistributions);
    }


    /**
     * @OA\Post(
     * path="/api/ticketdistributions",
     * summary="Crée plusieurs distributions de tickets pour un utilisateur et gère le stock de manière atomique.",
     * description="Valide les données, effectue toutes les distributions dans une seule transaction DB, en verrouillant le stock de chaque type de ticket pour éviter les problèmes de concurrence.",
     *
     * @OA\RequestBody(
     * required=true,
     * description="Liste des tickets à distribuer à l'utilisateur.",
     * @OA\JsonContent(
     * @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur (doit exister dans la table 'users')."),
     * @OA\Property(property="distributions", type="array", description="Liste des distributions de tickets.",
     * @OA\Items(
     * @OA\Property(property="ticket_id", type="integer", description="ID du ticket/type de stock à distribuer."),
     * @OA\Property(property="quantite_attribue", type="integer", format="int32", minimum=1, description="Quantité de ce type de ticket à attribuer."),
     * )
     * ),
     * required={"user_id", "distributions"},
     * )
     * ),
     *
     * @OA\Response(
     * response=201,
     * description="Toutes les distributions ont été créées avec succès.",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="Toutes les distributions ont été traitées avec succès.")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Stock insuffisant pour au moins un des types de tickets.",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Stock insuffisant pour le ticket ID 5 (restant: 2).")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Erreur de validation des données fournies.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="The given data was invalid."),
     * @OA\Property(property="errors", type="object")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Erreur interne du serveur lors du traitement de la transaction.",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Erreur lors du traitement de la distribution en masse.")
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        // 1. Validation de base des données
        $request->validate([
            'user_id' => 'required|exists:users,id',
            // Valide que 'distributions' est un tableau et chaque élément respecte les règles
            'distributions' => 'required|array|min:1',
            'distributions.*.ticket_id' => 'required|exists:tickets,id|integer',
            'distributions.*.quantite_attribue' => 'required|integer|min:1',
            // Options d'invité (si conservées)
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        $userId = $request->user_id;
        $distributions = $request->distributions;

        try {
            DB::beginTransaction();
            
            // On s'assure d'avoir la liste des IDs de tickets
            $ticketIds = collect($distributions)->pluck('ticket_id')->unique()->all();

            // 2. Verrouillage du stock pour TOUS les tickets concernés en UNE SEULE requête
            // Cela réduit le risque de blocage (deadlock) par rapport à des verrous multiples dans une boucle
            $stocks = Ticket::whereIn('id', $ticketIds)
                ->lockForUpdate() // Verrouillage du stock pour la durée de la transaction
                ->get()
                ->keyBy('id'); // Facilite la recherche par ID

            // 3. Boucle sur chaque distribution
            foreach ($distributions as $distribution) {
                $ticketId = $distribution['ticket_id'];
                $quantity = $distribution['quantite_attribue'];

                // Vérification et préparation des objets
                if (!isset($stocks[$ticketId])) {
                     // Devrait être géré par 'exists:tickets,id', mais vérification de sécurité
                     DB::rollBack();
                     return response()->json(['error' => "Ticket ID {$ticketId} introuvable."], 404);
                }

                $stock = $stocks[$ticketId];

                // 3a. Vérification de la disponibilité du stock
                if ($stock->quantite_initiales < $quantity) {
                    DB::rollBack(); // Annuler si le stock est insuffisant
                    return response()->json([
                        'error' => "Stock insuffisant pour le ticket ID {$ticketId} (restant: {$stock->quantite_initiales})."
                    ], 400);
                }
                
                // 3b. Détermination du prix et calcul du montant (Optionnel: si vous utilisez le prix)
                // $stock->load('typeticket'); // Non nécessaire si vous faites le calcul ailleurs ou n'en avez pas besoin ici
                // $prixUnitaire = $stock->typeticket->prix;
                // $montantTotal = $quantity * $prixUnitaire;

                // 3c. Création de la ligne de Distribution
                $ticketdistribution = Ticketdistribution::create([
                    'ticket_id' => $ticketId,
                    'user_id' => $userId,
                    'quantite_attribue' => $quantity, 
                    'quantite_vendue' => 0,
                    'quantite_restante' => $quantity,
                ]);

                // 3d. Mise à jour de la quantité de stock (sur l'objet verrouillé)
                $stock->quantite_sorties += $quantity;
                $stock->quantite_initiales -= $quantity;
                // La sauvegarde finale sera faite après la boucle pour chaque stock modifié.
                
                // 3e. Création des instances de Ticket
                Ticketinstance::createDistributionTicketInstances($ticketdistribution, $quantity);
            }

            // 4. Sauvegarder toutes les mises à jour de stock APRES la boucle de validation
            // Ceci est critique pour s'assurer que si une seule distribution échoue (étape 3a), rien n'est écrit.
            foreach ($stocks as $stock) {
                // On ne sauvegarde que si l'objet a été réellement modifié dans la boucle
                if ($stock->isDirty()) { 
                    $stock->save();
                }
            }

            // 5. Si tout a réussi, valider la transaction
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Toutes les distributions ont été traitées avec succès.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Annuler toutes les opérations en cas d'erreur
            \Log::error("Erreur de distribution en masse: " . $e->getMessage()); 

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement de la distribution en masse.',
                // 'details' => $e->getMessage() // À ne pas afficher en production
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticketdistribution $ticketdistribution)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Ticketdistribution $ticketdistribution)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Ticketdistribution $ticketdistribution)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticketdistribution $ticketdistribution)
    {
        //
    }
}
