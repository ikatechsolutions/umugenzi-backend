<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Groupe;
use Illuminate\Http\Request;

class GameController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/games",
     * summary="Récupérer toutes les games",
     * @OA\Response(
     * response=200,
     * description="Liste des games",
     * )
     * )
     */
    public function index()
    {
        $games = Game::orderBy('id', 'desc')->get();

        return response()->json($games);
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
     *     path="/api/games",
     *     summary="Crée nouveau jeu",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"groupe_id", "candidat", "phone", "gift_id"},
     *             @OA\Property(property="groupe_id", type="text"),
     *             @OA\Property(property="candidat", type="text"),
     *             @OA\Property(property="phone", type="text"),
     *             @OA\Property(property="gift_id", type="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Jeu est crée"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $game = new Game();
        $game->groupe_id = $request->groupe_id;
        $game->candidat = $request->candidat;
        $game->phone = $request->phone;
        $game->gift_id = $request->gift_id;
        $game->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Super'
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Game $game)
    {
        return response()->json($game);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Game $game)
    {
        //
    }

    /**
     * @OA\Put(
     *     path="/api/games/{id}",
     *     summary="Modifier Jeu",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"groupe_id", "candidat", "phone", "gift_id"},
     *             @OA\Property(property="groupe_id", type="text"),
     *             @OA\Property(property="candidat", type="text"),
     *             @OA\Property(property="phone", type="text"),
     *             @OA\Property(property="gift_id", type="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Jeu est mise à jour"
     *     )
     * )
     */
    public function update(Request $request, Game $game)
    {
        $game->groupe_id = $request->groupe_id;
        $game->candidat = $request->candidat;
        $game->phone = $request->phone;
        $game->gift_id = $request->gift_id;
        $game->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Super'
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/games/{id}",
     *     summary="Supprimer le Jeu",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Jeu est supprimé"
     *     )
     * )
     */
    public function destroy(Game $game)
    {
        $game->delete();
        return response()->json([
            'status' => 'success',
            'message' => 'Super'
        ]);
    }


    /**
     * @OA\Get(
     * path="/api/groupes/{groupe}/games",
     * summary="Récupérer tous les joueurs d'un groupe",
     * description="Récupère la liste de tous les joueurs associés à un groupe spécifique.",
     * @OA\Parameter(
     * name="groupe",
     * in="path",
     * required=true,
     * description="ID du groupe",
     * @OA\Schema(
     * type="integer"
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Liste des joueurs du groupe",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=101),
     * @OA\Property(property="nom", type="string", example="Nom du joueur"),
     * @OA\Property(property="groupe_id", type="integer", example=1)
     * )
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Groupe non trouvé"
     * )
     * )
     */
    public function gameByGroup(Groupe $groupe)
    {
        $games = Game::where('groupe_id', $groupe->id)->get();

        return response()->json($games);
    }
}
