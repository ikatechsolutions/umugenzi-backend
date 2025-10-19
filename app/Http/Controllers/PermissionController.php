<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Response;

class PermissionController extends Controller
{
    // public function __construct()
    // {
    //     // Appliquer des middlewares pour sécuriser l'accès aux opérations de permission
    //     $this->middleware('auth:sanctum');
    //     $this->middleware('can:view permissions')->only(['index', 'show']);
    //     $this->middleware('can:create permissions')->only('store');
    //     $this->middleware('can:edit permissions')->only('update');
    //     $this->middleware('can:delete permissions')->only('destroy');
    // }

    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     * path="/api/permissions",
     * summary="Récupérer toutes les permissions",
     * @OA\Response(
     * response=200,
     * description="Liste des permissions",
     * )
     * )
     */
    public function index()
    {
        $permissions = Permission::all();
        return response()->json($permissions, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     *     path="/api/permissions",
     *     summary="Crée nouvelle permission",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission est crée"
     *     )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
        ]);

        $permission = Permission::create(['name' => $request->name]);
        return response()->json($permission, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        return response()->json($permission, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/api/permissions/{id}",
     *     summary="Modifier permission",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="text")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Permission est mise à jour"
     *     )
     * )
     */
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update(['name' => $request->name]);
        return response()->json($permission, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/api/permissions/{id}",
     *     summary="Supprimer la permission",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Permission est supprimé"
     *     )
     * )
     */
    public function destroy(Permission $permission)
    {
        $permission->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}