<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

class RoleController extends Controller
{
    // public function __construct()
    // {
        
    //     $this->middleware('auth:sanctum');
    //     $this->middleware('can:view roles')->only(['index', 'show']);
    //     $this->middleware('can:create roles')->only('store');
    //     $this->middleware('can:edit roles')->only('update');
    //     $this->middleware('can:delete roles')->only('destroy');
    // }

    /**
     * Display a listing of the resource.
     */

    /**
     * @OA\Get(
     * path="/api/roles",
     * summary="Récupérer toutes les rôles",
     * @OA\Response(
     * response=200,
     * description="Liste des rôles",
     * )
     * )
     */
    public function index()
    {
        $roles = Role::with('permissions')->get(); // Inclut les permissions associées à chaque rôle
        return response()->json($roles, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */

    /**
     * @OA\Post(
     * path="/api/roles",
     * summary="Crée un nouveau rôle",
     * description="Crée un nouveau rôle avec la possibilité de lui attribuer immédiatement des permissions.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"name"},
     * @OA\Property(
     * property="name",
     * type="string",
     * description="Le nom du rôle.",
     * example="rédacteur"
     * ),
     * @OA\Property(
     * property="permissions",
     * type="array",
     * @OA\Items(
     * type="string",
     * example="voir article"
     * ),
     * description="Un tableau de noms de permissions à attribuer au rôle."
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Rôle créé avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="rédacteur"),
     * @OA\Property(property="guard_name", type="string", example="web"),
     * @OA\Property(
     * property="permissions",
     * type="array",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=101),
     * @OA\Property(property="name", type="string", example="voir article")
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Erreur de validation. Le nom du rôle peut déjà exister ou les permissions ne sont pas valides.",
     * )
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array', // Tableau d'IDs ou de noms de permissions
            'permissions.*' => 'string|exists:permissions,name', // Chaque élément doit être une permission existante
        ]);

        $role = Role::create(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->givePermissionTo($request->permissions);
        }

        return response()->json($role->load('permissions'), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return response()->json($role->load('permissions'), Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */

    /**
     * @OA\Put(
     *     path="/api/roles/{id}",
     *     summary="Modifier rôle",
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
     *         description="Rôle est mise à jour"
     *     )
     * )
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->update(['name' => $request->name]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions); // Synchronise les permissions
        }

        return response()->json($role->load('permissions'), Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */

    /**
     * @OA\Delete(
     *     path="/api/roles/{id}",
     *     summary="Supprimer le rôle",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Rôle est supprimé"
     *     )
     * )
     */
    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Assign a role to a user.
     * Cette méthode n'est pas standard pour un contrôleur de ressource, mais utile pour l'administration.
     */

    /**
     * @OA\Post(
     * path="/api/users/assign-role",
     * summary="Assigne un rôle à un utilisateur",
     * description="Cette fonction assigne un rôle existant à un utilisateur spécifié par son ID.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"user_id", "role_name"},
     * @OA\Property(property="user_id", type="integer", example=1),
     * @OA\Property(property="role_name", type="string", example="admin")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Rôle assigné avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rôle assigné avec succès")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Utilisateur ou Rôle non trouvé"
     * ),
     * @OA\Response(
     * response=422,
     * description="Erreur de validation",
     * )
     * )
     */
    public function assignRoleToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_name' => 'required|exists:roles,name',
        ]);

        $user = \App\Models\User::find($request->user_id);
        $user->assignRole($request->role_name);

        return response()->json(['message' => 'Rôle assigné avec succès'], Response::HTTP_OK);
    }

    /**
     * Revoke a role from a user.
     * Cette méthode n'est pas standard pour un contrôleur de ressource, mais utile pour l'administration.
     */

    /**
     * @OA\Post(
     * path="/api/users/revoke-role",
     * summary="Révoque un rôle d'un utilisateur",
     * description="Cette fonction retire un rôle existant d'un utilisateur spécifié par son ID.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"user_id", "role_name"},
     * @OA\Property(property="user_id", type="integer", example=1),
     * @OA\Property(property="role_name", type="string", example="admin")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Rôle révoqué avec succès",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Rôle révoqué avec succès")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="Utilisateur ou Rôle non trouvé"
     * ),
     * @OA\Response(
     * response=422,
     * description="Erreur de validation"
     * )
     * )
     */
    public function revokeRoleFromUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_name' => 'required|exists:roles,name',
        ]);

        $user = \App\Models\User::find($request->user_id);
        $user->removeRole($request->role_name);

        return response()->json(['message' => 'Rôle révoqué avec succès'], Response::HTTP_OK);
    }
}