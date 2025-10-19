<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @OA\Info(
 * version="1.0.0",
 * title="API de gestion des catégories",
 * description="Documentation pour l'API de gestion des catégories de votre application.",
 * @OA\Contact(
 * email="votre_email@example.com"
 * ),
 * @OA\License(
 * name="Apache 2.0",
 * url="http://www.apache.org/licenses/LICENSE-2.0.html"
 * )
 * )
 *
 * @OA\Tag(
 * name="Categories",
 * description="Opérations sur les catégories"
 * )
 */
class CategoryController extends Controller
{
    /**
     * @OA\Get(
     * path="/api/categories",
     * summary="Récupérer toutes les catégories",
     * @OA\Response(
     * response=200,
     * description="Liste des catégories",
     * )
     * )
     */

    public function index()
    {
        $categories = Category::get();

        return response()->json($categories, Response::HTTP_OK);
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
     *     path="/api/categories",
     *     summary="Ajouter nouvelle categorie",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nom"},
     *             @OA\Property(property="nom", type="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Categorie est crée"
     *     )
     * )
     */

    public function store(Request $request)
    {
        $categorie = new Category();
        $categorie->nom = $request->nom;
        $categorie->save();

        return response()->json([
            'status'=> 'success',
            'message'=> "Category Created",
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Category $category)
    {
        return response()->json($categorie);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Category $category)
    {
        return response()->json($categorie);
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Mettre à jour la categorie",
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
     *         description="Categorie est mise à jour"
     *     ),
    *      @OA\Response(
    *          response=404,
    *          description="Category not found"
    *      )
     * )
     */

    public function update(Request $request, Category $category)
    {
        $category->nom = $request->nom;
        $category->update();

        return response()->json([
            'status'=> 'success',
            'message'=> "Category Updated",
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Supprimer la categorie",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Categorie est supprimé"
     *     )
     * )
     */
    public function destroy(Category $category)
    {
        $category->delete();
        return response()->json("Category Deleted");
    }
}
