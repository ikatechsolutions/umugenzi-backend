<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Ticket;
use App\Models\Ticketinstance;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Http\Request;
use PDF;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = Reservation::get();

        return response()->json($reservations);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     * path="/api/reservations",
     * summary="Crée une nouvelle réservation de ticket et gère le stock.",
     * description="Valide les données, verrouille le stock pour éviter les problèmes de concurrence, vérifie la disponibilité, crée la réservation, met à jour le stock et génère les instances de tickets. Utilise une transaction de base de données.",
     *
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"ticket_id", "quantite"},
     * @OA\Property(property="ticket_id", type="integer", description="ID du ticket à réserver (doit exister dans la table 'tickets')."),
     * @OA\Property(property="quantite", type="integer", format="int32", minimum=1, description="Quantité de tickets à réserver."),
     * )
     * ),
     *
     * @OA\Response(
     * response=201,
     * description="Réservation créée avec succès.",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="Réservation créée avec succès. 5 tickets générés."),
     * @OA\Property(property="reservation_id", type="integer", description="ID de la nouvelle réservation.")
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
     * @OA\Property(property="message", type="string", example="Erreur lors du traitement de la réservation.")
     * )
     * )
     * )
     */
    public function store(Request $request)
    {
        // 1. Validation de base des données
        $data = $request->validate([
            'ticket_id' => 'required|exists:tickets,id',
            'quantite' => 'required|integer|min:1',
            // Validation conditionnelle pour l'invité
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            // user_id est requis seulement si non authentifié (pour l'API)
            // Note: Si vous utilisez l'ID de l'utilisateur connecté, ce champ n'est pas nécessaire dans le formulaire.
        ]);

        $quantity = $data['quantite'];
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
            $reservation = Reservation::create([
                // Mappage avec votre schéma initial
                'ticket_id' => $ticketStockId,
                'email' => $data['email'], 
                'quantite' => $quantity,
            ]);

            // 5. Mise à jour de la quantité de stock
            $stock->quantite_sorties += $quantity;
            $stock->quantite_initiales -= $quantity;
            $stock->save();

            // 6. Création des instances de Ticket avec génération automatique des codes (Insertion en masse)
            Ticketinstance::createTicketInstances($reservation, $quantity);

            // 7. Si tout a réussi, valider la transaction
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Réservation créée avec succès. ' . $quantity . ' tickets générés.',
                'reservation_id' => $reservation->id,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack(); // Annuler en cas d'erreur
            // Loggez l'erreur pour le débogage
            \Log::error("Erreur de réservation: " . $e->getMessage()); 

            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors du traitement de la réservation.',
                'details' => $e->getMessage() // À ne pas afficher en production
            ], 500);
        }
    }

    public function download(Ticketinstance $ticketInstance)
    {
        // Assurez-vous que les relations sont chargées pour éviter des requêtes inutiles
        $ticketInstance->load('reservation.ticket.typeticket.evenement');

        // Préparation des données pour la vue
        $data = [
            'ticketInstance' => $ticketInstance,
        ];
        
        // Génération du PDF à partir de la vue Blade
        $pdf = PDF::loadView('welcome', $data);

        $filename = 'ticket-' . $ticketInstance->code_unique . '.pdf';
        
        // Forcer le téléchargement du fichier
        return $pdf->download($filename);
    }

    public function validateTicket(Request $request, int $evenementId)
    {
        // Validation des données : le code unique est requis
        $request->validate(['code' => 'required|string']);

        $codeUnique = $request->input('code');

        try {
            DB::beginTransaction();

            // 1. Verrouiller la ligne et la trouver (avec les relations nécessaires)
            $ticket = Ticketinstance::where('code_unique', $codeUnique)
                ->with('reservation.ticket.typeticket')
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                DB::rollBack();
                return response()->json(['message' => 'Ticket non trouvé.'], 404);
            }
            
            // 2. VERIFICATION CRITIQUE : Le ticket appartient-il à cet événement ?
            $ticketEvenementId = optional($ticket->reservation->ticket->typeticket)->evenement_id;
            
            if ($ticketEvenementId !== $evenementId) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Ticket invalide pour cet événement.',
                    'detail' => 'Événement attendu: ' . $evenementId . ', Trouvé: ' . $ticketEvenementId
                ], 403); 
            }

            // --- LOGIQUE DE DOUBLE SCAN ---

            // A. Cas : Le ticket est DÉJÀ validé (Scan n°3 ou plus)
            if ($ticket->statut_validation == 1) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Ticket déjà validé et utilisé.', 
                    'statut' => 'USED',
                    'validated_at' => $ticket->updated_at
                ], 409); 
            }
            
            // B. Cas : Le ticket est PRÊT pour la validation (Scan n°2)
            if ($ticket->statut_payment == 1) {
                // Le paiement est déjà vérifié, procéder à la validation d'entrée
                
                $ticket->statut_validation = 1;
                // Optionnel : enregistrer la date de validation
                // $ticket->validated_at = Carbon::now(); 
                $ticket->save();
                
                DB::commit();

                return response()->json([
                    'message' => '✅ VALIDATION D\'ENTRÉE RÉUSSIE.',
                    'statut' => 'VALIDATED', // Indique la validation finale
                    'type' => $ticket->reservation->ticket->typeticket->nom
                ], 200);

            } 
            
            // C. Cas : Le ticket n'est PAS payé (Scan n°1)
            else { 
                // Le statut_payment est à 0, on le met à 1 (Vérification du paiement)
                
                $ticket->statut_payment = 1;
                $ticket->save();

                DB::commit();

                return response()->json([
                    'message' => '✅ PAIEMENT VÉRIFIÉ. Scanner une deuxième fois pour l\'entrée.',
                    'statut' => 'PAYMENT_VERIFIED', // Indique que le paiement est OK, mais pas encore entré
                    'code_unique' => $ticket->code_unique,
                ], 202); // 202 Accepted (Accepté, mais traitement incomplet)
            }

        } catch (\Exception $e) {
            DB::rollBack();
            // Loggez l'erreur pour le débogage en production
            // \Log::error("Erreur de validation: " . $e->getMessage()); 
            return response()->json(['message' => 'Erreur critique lors de la validation.'], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        return response()->json($reservation);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Reservation $reservation)
    {
        $ticket = $reservation->ticket_id;
        $ticketn = Ticket::findOrFail($ticket);
        $nombre =  $reservation->quantite;

        if($ticketn->quantite < $request->quantite) {
            return response()->json(['error' => 'Quantite de tickets non disponible.'], 400);
        }

        $reservation->user_id = $request->user_id;
        $reservation->ticket_id = $request->ticket_id;
        $reservation->quantite = $request->quantite;
        $reservation->update();

        $ticketn->quantite = $nombre + $ticketn->quantite - $request->quantite;
        $ticketn->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Reservation Updated'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Reservation deleted'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/ticket-all",
     *     summary="Récupérer toutes les ticket instances",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des ticket instances",
     *         @OA\JsonContent(type="array", @OA\Items(type="string"))
     *     )
     * )
     */
    public function allTicket()
    {
        $ticketInstance = Ticketinstance::with('reservation.ticket.typeticket.evenement')->get();
        

        return response()->json($ticketInstance);
    }
    
    /**
     * @OA\Get(
     * path="/api/ticket-by-event/{evenementId}",
     * summary="Récupérer toutes les instances de tickets pour un événement donné",
     * @OA\Parameter(
     * name="evenementId",
     * in="path",
     * description="ID de l'événement",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Liste des instances de tickets pour l'événement",
     * @OA\JsonContent(type="array", @OA\Items(type="string")) 
     * ),
     * @OA\Response(
     * response=404,
     * description="Aucune instance de ticket trouvée pour cet événement"
     * )
     * )
     */
    public function getTicketsByEventId($evenementId)
    {
        // On utilise la relation 'with' pour charger les relations nécessaires
        // (reservation, ticket, typeticket et evenement) pour éviter le problème de N+1 requêtes.
        // On utilise 'whereHas' pour filtrer les Ticketinstance
        // où la relation imbriquée 'reservation.ticket.typeticket.evenement' a l'ID correspondant.
        $ticketInstances = Ticketinstance::with([
            'reservation.ticket.typeticket.evenement'
        ])
        ->whereHas('reservation.ticket.typeticket.evenement', function ($query) use ($evenementId) {
            $query->where('id', $evenementId);
        })
        ->get();

        if ($ticketInstances->isEmpty()) {
            return response()->json(['message' => 'Aucune instance de ticket trouvée pour cet événement.'], 404);
        }

        return response()->json($ticketInstances);
    }

    /**
     * @OA\Get(
     * path="/api/ticket-by-event-and-payment-status/{evenementId}/{isPaid}",
     * summary="Récupérer les instances de tickets par événement et statut de paiement",
     * description="Récupère les instances de tickets (Ticketinstance) pour un événement donné, filtrées par leur statut de paiement (1 pour Payé, 0 pour Non Payé).",
     * @OA\Parameter(
     * name="evenementId",
     * in="path",
     * description="ID de l'événement",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * format="int64"
     * )
     * ),
     * @OA\Parameter(
     * name="isPaid",
     * in="path",
     * description="Statut de paiement : 1 pour Payé, 0 pour Non Payé",
     * required=true,
     * @OA\Schema(
     * type="integer",
     * enum={0, 1}
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Liste des instances de tickets correspondant aux critères",
     * @OA\JsonContent(type="array", @OA\Items(type="string"))
     * ),
     * @OA\Response(
     * response=400,
     * description="Statut de paiement invalide (doit être 0 ou 1)"
     * ),
     * @OA\Response(
     * response=404,
     * description="Aucune instance de ticket trouvée pour cet événement et ce statut"
     * )
     * )
     */
    public function getTicketsByEventAndPaymentStatus(int $evenementId, int $isPaid)
    {
        // 1. Validation du paramètre de paiement
        if (!in_array($isPaid, [0, 1])) {
            return response()->json([
                'message' => 'Le statut de paiement doit être 0 (Non Payé) ou 1 (Payé).'
            ], 400); // 400 Bad Request
        }

        // 2. Requête combinée
        $ticketInstances = Ticketinstance::with([
            'reservation.ticket.typeticket.evenement'
        ])
        // Filtre 1: Par ID d'événement (utilise whereHas pour naviguer dans les relations)
        ->whereHas('reservation.ticket.typeticket.evenement', function ($query) use ($evenementId) {
            $query->where('id', $evenementId);
        })
        // Filtre 2: Par statut de paiement (filtre direct sur la colonne de la table ticketinstances)
        ->where('statut_payment', $isPaid)
        ->get();

        // 3. Gestion des résultats
        if ($ticketInstances->isEmpty()) {
            $statusDescription = ($isPaid === 1) ? 'Payés' : 'Non Payés';
            return response()->json([
                'message' => "Aucune instance de ticket '{$statusDescription}' trouvée pour l'événement ID : {$evenementId}."
            ], 404);
        }

        return response()->json($ticketInstances);
    }
}
