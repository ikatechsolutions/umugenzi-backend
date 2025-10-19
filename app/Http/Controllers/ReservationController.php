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
     * Store a newly created resource in storage.
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

    public function validateTicket(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $codeUnique = $request->input('code');

        try {
            DB::beginTransaction();

            // 1. Verrouiller la ligne et la trouver
            $ticket = Ticketinstance::where('code_unique', $codeUnique)
                ->lockForUpdate()
                ->first();

            if (!$ticket) {
                DB::rollBack();
                return response()->json(['message' => 'Ticket non trouvé.'], 404);
            }

            // 2. Vérification du statut actuel
            if ($ticket->statut_validation == 1) {
                DB::rollBack();
                // Si vous voulez enregistrer qui a validé et quand, ajoutez ces colonnes
                return response()->json([
                    'message' => 'Ticket déjà validé.', 
                    'validated_at' => $ticket->updated_at
                ], 409); // 409 Conflict
            }

            // 3. Validation (Mise à jour à 1)
            $ticket->statut_validation = 1;
            $ticket->save();
            
            DB::commit();

            return response()->json([
                'message' => 'Validation réussie.',
                'ticket_info' => $ticket->only('code_unique', 'statut_validation')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
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
}
