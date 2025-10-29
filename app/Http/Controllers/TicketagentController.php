<?php

namespace App\Http\Controllers;


use App\Models\Ticketagent;
use Illuminate\Http\Request;
use App\Http\Requests\AssignTicketsRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class TicketagentController extends Controller
{
    /**
     * Attribue des tickets à un utilisateur spécifique.
     *
     * @OA\Post(
     * path="/api/tickets/assign",
     * tags={"Attribution des Tickets"},
     * summary="Attribue un ensemble de tickets à un utilisateur.",
     * description="Cette méthode lit l'ID de l'utilisateur et une liste d'IDs de tickets pour créer des entrées dans la table 'ticketagent'.",
     * @OA\RequestBody(
     * required=true,
     * description="Données d'attribution (user_id et ticketinstance_ids)",
     * @OA\JsonContent(
     * required={"user_id","ticketinstance_ids"},
     * @OA\Property(property="user_id", type="integer", example=5, description="ID de l'utilisateur à qui attribuer les tickets."),
     * @OA\Property(
     * property="ticketinstance_ids",
     * type="array",
     * description="Liste des IDs des tickets à attribuer.",
     * @OA\Items(type="integer", example=101)
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Tickets attribués avec succès.",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Tickets attribués avec succès à l'utilisateur."),
     * @OA\Property(property="user_id", type="integer", example=5),
     * @OA\Property(property="assigned_tickets", type="array", @OA\Items(type="integer", example=101))
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Utilisateur non trouvé."
     * ),
     * @OA\Response(
     * response=422,
     * description="Erreur de validation des données (champs manquants ou invalides)."
     * ),
     * )
     */
    public function assign(AssignTicketsRequest $request)
    {
        // 1. Récupérer l'utilisateur
        $user = User::findOrFail($request->user_id);

        // 2. Attacher les tickets à l'utilisateur
        // La méthode attach() va insérer les nouvelles entrées dans la table pivot (ticketuser)
        // en utilisant les IDs des tickets passés.
        $user->ticketinstances()->attach($request->ticketinstance_ids);

        // Si vous voulez être sûr de n'ajouter que les tickets qui n'existent pas encore, 
        // vous pouvez utiliser attach() ou syncWithoutDetaching(). attach() est plus simple ici.

        return response()->json([
            'message' => 'Tickets attribués avec succès à l\'utilisateur.',
            'user_id' => $user->id,
            'assigned_tickets' => $request->ticketinstance_ids,
        ], 201);
    }

    /**
     * @OA\Get(
     * path="/api/users/assigned-tickets",
     * tags={"Attribution des Tickets"},
     * summary="Lister tous les utilisateurs et leurs tickets attribués.",
     * description="Récupère une liste de tous les utilisateurs qui ont au moins un ticket attribué, avec les détails de ces tickets.",
     * @OA\Response(
     * response=200,
     * description="Liste des utilisateurs et de leurs tickets.",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(type="string")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Aucun utilisateur n'a de ticket attribué."
     * )
     * )
     */
    public function getAllUsersWithAssignedTickets()
    {
        // On récupère uniquement les utilisateurs qui ont au moins un ticket attribué
        // en utilisant has().
        // On utilise with() (Eager Loading) pour charger les relations
        // afin d'éviter le problème de N+1 requêtes.
        $usersWithTickets = User::has('ticketinstances')
            ->with([
                // Charger les tickets attribués à l'utilisateur
                'ticketinstances', 
                
                // Charger les relations imbriquées de chaque ticket
                // (comme nous l'avons fait dans les fonctions précédentes)
                'ticketinstances.reservation.ticket.typeticket.evenement'
            ])
            ->get();

        if ($usersWithTickets->isEmpty()) {
            return response()->json(['message' => 'Aucun utilisateur n\'a de ticket attribué pour le moment.'], 404);
        }

        return response()->json($usersWithTickets);
    }

    /**
     * @OA\Get(
     * path="/api/me/assigned-tickets",
     * tags={"Attribution des Tickets"},
     * summary="Récupérer les tickets attribués à l'utilisateur connecté.",
     * description="Récupère la liste des Ticketinstance attribuées à l'utilisateur qui fait l'appel (authentifié).",
     * security={
     * {"bearerAuth": {}}
     * },
     * @OA\Response(
     * response=200,
     * description="Liste des tickets attribués à l'utilisateur.",
     * @OA\JsonContent(type="array", @OA\Items(type="string"))
     * ),
     * @OA\Response(
     * response=401,
     * description="Non authentifié."
     * ),
     * @OA\Response(
     * response=404,
     * description="Aucun ticket attribué à cet utilisateur."
     * )
     * )
     */
    public function getMyAssignedTickets()
    {
        // Récupère l'utilisateur actuellement authentifié (via token API, session, etc.)
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        // On charge les relations 'ticketinstances' et leurs relations imbriquées
        // sur l'objet $user déjà récupéré.
        $user->load([
            'ticketinstances',
            'ticketinstances.reservation.ticket.typeticket.evenement'
        ]);

        // On ne retourne que la liste des tickets
        $myTickets = $user->ticketinstances;

        if ($myTickets->isEmpty()) {
            return response()->json([
                'message' => 'Vous n\'avez aucun ticket attribué.',
                'tickets' => []
            ], 404); // 404 ou 200 avec un tableau vide, au choix.
        }

        return response()->json($myTickets);
    }
}
