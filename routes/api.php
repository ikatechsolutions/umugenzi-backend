<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\EvenementController;
use App\Http\Controllers\TypeticketController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\GroupeController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GiftController;
use App\Http\Controllers\HistoriqueeventController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/register', [LoginController::class, 'register']);
Route::post('/register-manager', [LoginController::class, 'registerManager']);
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::middleware('auth:sanctum')->group(function () {
    // Vos routes API protégées par Sanctum iront ici

    //logout
    Route::post('/logout', [LoginController::class, 'logout']);

    //get auth user
    Route::get('/user', function (Request $request) {
        return $request->user()->load('roles', 'permissions'); // Charger les rôles/permissions de l'utilisateur connecté
    });

    Route::get('/evenement-manager', [EvenementController::class, 'eventManager']);
Route::post('historiques/valider/{evenement}', [HistoriqueeventController::class, 'store']);
});

Route::apiResource('categories',CategoryController::class);
Route::apiResource('evenements',EvenementController::class);
Route::get('/events/today', [EvenementController::class, 'eventsToday']);
Route::patch('/evenements/{id}/validate', [EvenementController::class, 'EventValidation']);
Route::apiResource('typetickets',TypeticketController::class);
Route::apiResource('tickets',TicketController::class);
Route::apiResource('reservations',ReservationController::class);

Route::post('/validate-ticket/{evenementId}', [ReservationController::class, 'validateTicket']);
Route::get('/ticket-all', [ReservationController::class, 'allTicket']);

Route::apiResource('groupes',GroupeController::class);
Route::get('/groupe/{groupeId}/tirage', [GroupeController::class, 'effectuerTirageAuSort']);
Route::apiResource('games',GameController::class);
Route::get('/groupes/{groupe}/games', [GameController::class, 'gameByGroup']);

Route::get('/evenements/category/{id}', [EvenementController::class, 'filterEvent']);

Route::get('/count-game', [GroupeController::class, 'countGameDay']);
Route::get('/groupe-agent', [GroupeController::class, 'gameAgent']);

//Historiques des événements
Route::get('historiques', [HistoriqueeventController::class, 'index']);

// Routes pour les Rôles
Route::apiResource('roles', RoleController::class);
Route::post('users/assign-role', [RoleController::class, 'assignRoleToUser']);
Route::post('users/revoke-role', [RoleController::class, 'revokeRoleFromUser']);

// Routes pour les Permissions
Route::apiResource('permissions', PermissionController::class);

// Routes pour les Utilisateurs (protégées par des permissions)
Route::apiResource('users', UserController::class);
Route::post('storeAdmin', [UserController::class, 'storeAdmin']);
Route::put('update-user/{user}', [ProfileController::class, 'update']);

//Route pour les cadeaux
Route::apiResource('gifts', GiftController::class);
    


