<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User; 
use Illuminate\Support\Facades\Hash; 
use Spatie\Permission\Models\Role;

class LoginController extends Controller
{

    /**
     * @OA\Post(
     * path="/api/login",
     * summary="Connecte un utilisateur",
     * description="Authentifie un utilisateur avec son email et son mot de passe et retourne un jeton d'accès et l'objet utilisateur avec ses rôles.",
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"email", "password"},
     * @OA\Property(property="email", type="string", format="email", example="utilisateur@exemple.com"),
     * @OA\Property(property="password", type="string", format="password", example="motdepasse123")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Connexion réussie",
     * @OA\JsonContent(
     * @OA\Property(property="token", type="string", description="Jeton d'authentification pour les requêtes API."),
     * @OA\Property(
     * property="user",
     * type="object",
     * description="Les informations de l'utilisateur.",
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="Nom de l'utilisateur"),
     * @OA\Property(property="email", type="string", example="utilisateur@exemple.com"),
     * @OA\Property(
     * property="roles",
     * type="array",
     * description="Les rôles assignés à l'utilisateur.",
     * @OA\Items(
     * @OA\Property(property="id", type="integer", example=1),
     * @OA\Property(property="name", type="string", example="admin")
     * )
     * )
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Identifiants invalides",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Les identifiants fournis sont incorrects.")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Erreur interne du serveur. Impossible de créer le jeton."
     * )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        $user = $request->user();

        // Créer un token d'API
        $token = $user->createToken($request->email)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('roles'), // Charger la relation des rôles
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()) {
            $request->user()->currentAccessToken()->delete();
            return response()->json(['message' => 'Déconnexion réussie'], 200);
        }
        return response()->json(['message' => 'Aucun utilisateur authentifié pour la déconnexion.'], 401);
    }

    /**
     * @OA\Post(
     *     path="/api/register",
     *     summary="Register a new user",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone", "adresse", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User registered successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:255|unique:users,phone',
            'adresse' => 'required|string|max:255',
            'photo' => 'nullable|string|max:255',
            'password' => 'required|string|min:8|confirmed', // 'confirmed' nécessite un champ password_confirmation
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'adresse' => $request->adresse,
            'photo' => $request->photo,
            'password' => Hash::make($request->password),
        ]);

        // Assigner le rôle 'client' par défaut
        $clientRole = Role::where('name', 'client')->first();
        if ($clientRole) {
            $user->assignRole($clientRole);
        } else {
            // Gérer le cas où le rôle 'client' n'existe pas (log, créer, etc.)
            // Pour l'instant, nous allons juste loguer une erreur.
            \Log::error("Le rôle 'client' n'existe pas lors de l'enregistrement de l'utilisateur.");
        }

        // Vous pouvez choisir de générer un token ici ou laisser l'utilisateur se connecter après l'enregistrement
        $token = $user->createToken('registration_token')->plainTextToken;

        return response()->json(['message' => 'Compte créé avec succès!', 'user' => $user->load('roles'), 'token' => $token], 201);
    }


    /**
     * @OA\Post(
     *     path="/api/register-manager",
     *     summary="Register a new manager",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "email", "phone", "adresse", "password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="adresse", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Manager registered successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error"
     *     )
     * )
     */
    public function registerManager(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'required|string|max:255|unique:users,phone',
            'adresse' => 'required|string|max:255',
            'photo' => 'nullable|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'adresse' => $request->adresse,
            'photo' => $request->photo,
            'password' => Hash::make($request->password),
        ]);

        // Assigner le rôle 'client' par défaut
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $user->assignRole($managerRole);
        } else {
            // Gérer le cas où le rôle 'client' n'existe pas (log, créer, etc.)
            // Pour l'instant, nous allons juste loguer une erreur.
            \Log::error("Le rôle 'manager' n'existe pas lors de l'enregistrement de l'utilisateur.");
        }

        // Vous pouvez choisir de générer un token ici ou laisser l'utilisateur se connecter après l'enregistrement
        $token = $user->createToken('registration_token')->plainTextToken;

        return response()->json(['message' => 'Compte créé avec succès!', 'user' => $user->load('roles'), 'token' => $token], 201);
    }
}