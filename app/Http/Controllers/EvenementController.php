<?php

namespace App\Http\Controllers;

use App\Models\Evenement;
use App\Models\Typeticket;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Storage;

class EvenementController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/evenements",
     *     summary="Récupérer toutes les evenements",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des evenements",
     *         @OA\JsonContent(type="array", @OA\Items(type="string"))
     *     )
     * )
     */
    public function index()
    {
        $evenements = Evenement::with('user','categorie', 'typetickets', 'typetickets.tickets')
        ->get();

        return response()->json($evenements);
    }

    /**
     * @OA\Get(
     *     path="/api/evenement-manager",
     *     summary="Toutes les evenements validés du manager",
     *     @OA\Response(
     *         response=200,
     *         description="Liste des evenements",
     *         @OA\JsonContent(type="array", @OA\Items(type="string"))
     *     )
     * )
     */
    public function eventManager()
    {
        $evenements = Evenement::with('user','categorie', 'typetickets', 'typetickets.tickets')
        ->where('user_id', auth()->id())
        ->where('statut_validation', 1)
        ->get();

        return response()->json($evenements);
    }

    /**
     * @OA\Post(
     * path="/api/evenements",
     * summary="Ajouter un evenement et ses types de tickets",
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * required={"titre", "description", "place", "date_event", "heure", "image", "user_id", "categorie_id", "typetickets"},
     * @OA\Property(property="titre", type="string", description="Titre de l'événement"),
     * @OA\Property(property="description", type="string", description="Description de l'événement"),
     * @OA\Property(property="place", type="string", description="Lieu de l'événement"),
     * @OA\Property(property="date_event", type="string", format="date", description="Date de l'événement (YYYY-MM-DD)"),
     * @OA\Property(property="heure", type="string", description="Heure de l'événement"),
     * @OA\Property(property="image", type="string", format="binary", description="Fichier image"),
     * @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur"),
     * @OA\Property(property="categorie_id", type="integer", description="ID de la catégorie"),
     * @OA\Property(
     * property="typetickets",
     * type="string",
     * description="JSON stringified array of ticket types.",
     * example="[{""nom"":""VIP"",""prix"":150.0,""quantite"":10},{""nom"":""Standard"",""prix"":50.0,""quantite"":100}]"
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Événement et tickets créés avec succès."
     * ),
     * @OA\Response(
     * response=422,
     * description="Données de validation manquantes ou invalides."
     * ),
     * @OA\Response(
     * response=500,
     * description="Erreur serveur lors de la création."
     * )
     * )
     */
    public function store(Request $request)
    {
        // Utilisation d'une transaction pour garantir que toutes les opérations
        // se terminent avec succès.
        DB::beginTransaction();

        try {
            // Décodage de la chaîne JSON en tableau
            $typetickets = json_decode($request->input('typetickets'), true);

            // Fusionner le tableau décodé avec le reste de la requête
            $request->merge(['typetickets' => $typetickets]);
            
            $validatedData = $request->validate([
                'titre' => 'required|string|max:255',
                'description' => 'required|string',
                'place' => 'required|string',
                'date_event' => 'required|date',
                'heure' => 'required|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'user_id' => 'required|integer|exists:users,id',
                'categorie_id' => 'required|integer|exists:categories,id',
                'typetickets' => 'required|array',
                'typetickets.*.nom' => 'required|string|max:255',
                'typetickets.*.prix' => 'required|numeric|min:0',
                'typetickets.*.quantite' => 'required|integer|min:1',
            ]);

            
            $evenement = new Evenement();
            $evenement->titre = $validatedData['titre'];
            $evenement->description = $validatedData['description'];
            $evenement->place = $validatedData['place'];
            $evenement->date_event = $validatedData['date_event'];
            $evenement->heure = $validatedData['heure'];

            $filePhoto = $request->file('image')->store('image_events', 'public');
            $evenement->image = $filePhoto;

            $evenement->user_id = $validatedData['user_id'];
            $evenement->categorie_id = $validatedData['categorie_id'];
            $evenement->save();

            foreach ($validatedData['typetickets'] as $typeticketData) {
                $typeticket = new Typeticket();
                $typeticket->nom = $typeticketData['nom'];
                $typeticket->prix = $typeticketData['prix'];
                $typeticket->evenement_id = $evenement->id;
                $typeticket->save();

                $ticket = new Ticket();
                $ticket->typeticket_id = $typeticket->id;
                $ticket->quantite = $typeticketData['quantite'];
                $ticket->quantite_sorties = 0;
                $ticket->quantite_initiales = $typeticketData['quantite'];
                $ticket->save();
            }

            // Si tout s'est bien passé, on valide la transaction
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Événement et tickets créés avec succès.'
            ], 201);

        } catch (\Exception $e) {
            // En cas d'erreur, on annule la transaction
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur lors de la création de l\'événement ou des tickets.',
                'details' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * @OA\Patch(
     * path="/api/evenements/{id}/validate",
     * summary="Valider un événement (changer son statut à 1)",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de l'événement à valider.",
     * @OA\Schema(type="integer")
     * ),
     * @OA\Response(
     * response=200,
     * description="Événement validé avec succès.",
     * @OA\JsonContent(
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="Evenement est validé.")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Événement non trouvé."
     * ),
     * @OA\Response(
     * response=500,
     * description="Erreur serveur lors de la validation."
     * )
     * )
     */
    public function EventValidation($id)
    {
        try {
            $evenement = Evenement::findOrFail($id);
            
            if ($evenement->status == 1) {
                 return response()->json([
                    'status' => 'info',
                    'message' => 'L\'événement est déjà validé.'
                ], 200);
            }

            // Mise à jour du statut
            $evenement->statut_validation = 1;
            $evenement->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Evenement est validé.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Événement non trouvé.'
            ], 404);
            
        } catch (\Exception $e) {
            // Erreur 500 (serveur ou base de données)
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur serveur lors de la validation de l\'événement.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/evenements/{id}",
     *     summary="Détailles de l'evenement",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Détailles de l'evenement"
     *     )
     * )
     */
    public function show(Evenement $evenement)
    {
        $evenement->load('user', 'categorie', 'typetickets', 'typetickets.tickets');
        return response()->json($evenement);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Evenement $evenement)
    {
        return response()->json($evenement);
    }

    /**
     * @OA\Post(
     * path="/api/evenements/{id}",
     * summary="Mettre à jour un événement existant, son image et ses types de tickets",
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID de l'événement à mettre à jour",
     * @OA\Schema(type="integer")
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="_method", type="string", default="PUT", description="Champ requis pour simuler la méthode PUT en 'multipart/form-data'"),
     * * @OA\Property(property="titre", type="string", description="Titre de l'événement (Optionnel)"),
     * @OA\Property(property="description", type="string", description="Description de l'événement (Optionnel)"),
     * @OA\Property(property="place", type="string", description="Lieu de l'événement (Optionnel)"),
     * @OA\Property(property="date_event", type="string", format="date", description="Date de l'événement (YYYY-MM-DD) (Optionnel)"),
     * @OA\Property(property="heure", type="string", description="Heure de l'événement (Optionnel)"),
     * @OA\Property(property="image", type="string", format="binary", description="Nouveau Fichier image (Optionnel, remplace l'ancien)"),
     * @OA\Property(property="user_id", type="integer", description="ID de l'utilisateur (Optionnel)"),
     * @OA\Property(property="categorie_id", type="integer", description="ID de la catégorie (Optionnel)"),
     * @OA\Property(
     * property="typetickets",
     * type="string",
     * description="JSON stringifié (obligatoire pour la mise à jour des tickets). **Tous les anciens tickets seront supprimés et remplacés par cette liste**.",
     * example="[{""nom"":""VIP M.A.J."",""prix"":180.0,""quantite"":5},{""nom"":""Standard M.A.J."",""prix"":60.0,""quantite"":150}]"
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Événement et tickets mis à jour avec succès."
     * ),
     * @OA\Response(
     * response=404,
     * description="Événement non trouvé."
     * ),
     * @OA\Response(
     * response=422,
     * description="Données de validation manquantes ou invalides."
     * ),
     * @OA\Response(
     * response=500,
     * description="Erreur serveur lors de la mise à jour."
     * )
     * )
     */
    public function update(Request $request, $id)
    {
        // 1. Trouver l'événement
        $evenement = Evenement::find($id);

        if (!$evenement) {
            return response()->json([
                'status' => 'error',
                'message' => 'Événement non trouvé.'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // 2. Préparer les données pour la validation (typetickets)
            $typetickets = json_decode($request->input('typetickets'), true);
            $request->merge(['typetickets' => $typetickets]);

            // 3. Définir les règles de validation
            $validatedData = $request->validate([
                'titre' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'place' => 'sometimes|string',
                'date_event' => 'sometimes|date',
                'heure' => 'sometimes|string',
                // 'sometimes' : l'image est optionnelle, mais si elle est présente, elle doit suivre les règles
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif,svg|max:2048', 
                'user_id' => 'sometimes|integer|exists:users,id',
                'categorie_id' => 'sometimes|integer|exists:categories,id',
                
                // Nous rendons 'typetickets' obligatoire pour garantir un ensemble complet de données
                'typetickets' => 'required|array', 
                'typetickets.*.nom' => 'required|string|max:255',
                'typetickets.*.prix' => 'required|numeric|min:0',
                'typetickets.*.quantite' => 'required|integer|min:1',
            ]);

            // 4. Mise à jour des champs de l'événement
            // La méthode fill() est préférable à l'affectation champ par champ pour les données validées
            $evenement->fill(collect($validatedData)->except(['image', 'typetickets'])->toArray());

            // 5. Gestion de l'image (si une nouvelle image est fournie)
            if ($request->hasFile('image')) {
                if ($evenement->image) {
                    Storage::disk('public')->delete($evenement->image);
                }
                
                $filePhoto = $request->file('image')->store('image_events', 'public');
                
                $evenement->image = $filePhoto;
            }

            $evenement->save();

            // 6. Mise à jour des Typetickets (Suppression et Recréation)
            
            // Suppression des anciens tickets et types de tickets
            Typeticket::where('evenement_id', $evenement->id)->each(function ($typeticket) {
                // Supprime d'abord les tickets de stock
                Ticket::where('typeticket_id', $typeticket->id)->delete();
                // Supprime ensuite le type de ticket
                $typeticket->delete();
            });

            // Création des nouveaux types de tickets et tickets associés
            foreach ($validatedData['typetickets'] as $typeticketData) {
                $typeticket = new Typeticket();
                $typeticket->nom = $typeticketData['nom'];
                $typeticket->prix = $typeticketData['prix'];
                $typeticket->evenement_id = $evenement->id;
                $typeticket->save();

                $ticket = new Ticket();
                $ticket->typeticket_id = $typeticket->id;
                $ticket->quantite = $typeticketData['quantite'];
                $ticket->save();
            }

            // 7. Valider la transaction
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Événement et tickets mis à jour avec succès.',
                'data' => $evenement
            ], 200);

        } catch (\Exception $e) {
            // 8. Annuler la transaction en cas d'erreur
            DB::rollBack();
            // Si c'est une erreur de validation (422), Laravel la gère automatiquement
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                throw $e;
            }
            return response()->json([
                'status' => 'error',
                'message' => 'Erreur serveur lors de la mise à jour de l\'événement ou des tickets.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/evenements/{id}",
     *     summary="Supprimer un evenement",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Evenement est supprimé"
     *     )
     * )
     */
    public function destroy(Evenement $evenement)
    {
        $evenement->delete();
        return response()->json("Event Deleted");
    }
}
