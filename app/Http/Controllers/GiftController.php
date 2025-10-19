<?php

namespace App\Http\Controllers;

use App\Models\Gift;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class GiftController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/gifts",
     * summary="Récupérer toutes les cadeaux",
     * @OA\Response(
     * response=200,
     * description="Liste des cadeaux",
     * )
     * )
     */

    public function index()
    {
        $gifts = Gift::get();

        return response()->json($gifts, Response::HTTP_OK);
    }

    /**
     * @OA\Post(
     *     path="/api/gifts",
     *     summary="Ajouter un cadeau",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom"},
     *             @OA\Property(property="nom", type="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cadeau est crée"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $gift = new Gift();
        $gift->nom = $request->nom;
        $gift->save();

        return response()->json([
            'status'=> 'success',
            'message'=> "Gift Created",
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Gift $gift)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * @OA\Put(
     *     path="/api/gifts/{id}",
     *     summary="Mettre à jour le cadeau",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom"},
     *             @OA\Property(property="nom", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cadeau est mise à jour"
     *     ),
    *      @OA\Response(
    *          response=404,
    *          description="Gift not found"
    *      )
     * )
     */

    public function update(Request $request, Gift $gift)
    {
        $gift->nom = $request->nom;
        $gift->update();

        return response()->json([
            'status'=> 'success',
            'message'=> "Gift Updated",
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/gifts/{id}",
     *     summary="Supprimer le cadeau",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Cadeau est supprimé"
     *     )
     * )
     */
    public function destroy(Gift $gift)
    {
        $gift->delete();
        return response()->json("Gift Deleted");
    }
}
