<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/ticket/{ticketInstance}/download', [ReservationController::class, 'download'])
    ->name('ticket.download');