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
     * summary="Crée une nouvelle distribution de ticket et gère le stock.",
     * description="Valide les données, verrouille le stock pour éviter les problèmes de concurrence, vérifie la disponibilité, crée la distribution et met à jour le stock. Utilise une transaction de base de données.",
     *
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"user_id", "ticket_id", "quantite_attribue"},
     * @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur (doit exister dans la table 'users')."),
     * @OA\Property(property="ticket_id", type="integer", description="ID du ticket à distribue (doit exister dans la table 'tickets')."),
     * @OA\Property(property="quantite_attribue", type="integer", format="int32", minimum=1, description="Quantité de tickets à distribue."),
     * )
     * ),
     *
     * @OA\Response(
     * response=201,
     * description="Distribution créée avec succès.",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="Distribution créée avec succès.")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Quantité de tickets non disponible (stock insuffisant).",
     * @OA\JsonContent(
     * @OA\Property(property="error", type="string", example="Quantité de tickets non disponible (stock restant: 2).")
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
     * @OA\Property(property="message", type="string", example="Erreur lors du traitement de la distribution.")
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        // 1. Validation de base des données
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'ticket_id' => 'required|exists:tickets,id',
            'quantite_attribue' => 'required|integer|min:1',
            // Validation conditionnelle pour l'invité
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            // user_id est requis seulement si non authentifié (pour l'API)
            // Note: Si vous utilisez l'ID de l'utilisateur connecté, ce champ n'est pas nécessaire dans le formulaire.
        ]);

        $quantity = $data['quantite_attribue'];
        $ticketStockId = $data['ticket_id'];

        try {
            DB::beginTransaction();

            // 2. Verrouillage du stock et vérification de la disponibilité
            // Utilisation de lockForUpdate() pour éviter les problèmes de concurrence (double-réservation)
            $stock = Ticket::where('id', $ticketStockId)
                ->lockForUpdate() 
                ->firstOrFail();

            if ($stock->quantite_initiales < $quantity) {
                DB::rollBack(); // Annuler si le stock est insuffisant
                return response()->json(['error' => 'Quantité de tickets non disponible (stock restant: ' . $stock->quantite_initiales . ').'], 400);
            }
            
            // 3. Détermination du prix et calcul du montant
            // Chargement de la relation typeticket pour obtenir le prix
            $stock->load('typeticket'); 
            $prixUnitaire = $stock->typeticket->prix;
            $montantTotal = $quantity * $prixUnitaire;

            // 4. Création de la ligne de Reservation
            $ticketdistribution = Ticketdistribution::create([
                // Mappage avec votre schéma initial
                'ticket_id' => $ticketStockId,
                'user_id' => $request->user_id,
                'quantite_attribue' => $request->quantite_attribue, 
                'quantite_vendue' => 0,
                'quantite_restante' => $request->quantite_attribue,
            ]);

            // 5. Mise à jour de la quantité de stock
            $stock->quantite_sorties += $quantity;
            $stock->quantite_initiales -= $quantity;
            $stock->save();

            // 6. Création des instances de Ticket avec génération automatique des codes (Insertion en masse)
            Ticketinstance::createDistributionTicketInstances($ticketdistribution, $quantity);

            // 7. Si tout a réussi, valider la transaction
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Distribution créée avec succès.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Annuler en cas d'erreur
            // Loggez l'erreur pour le débogage
            \Log::error("Erreur de distribution: " . $e->getMessage()); 

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement de la distribution.',
                'details' => $e->getMessage() // À ne pas afficher en production
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
