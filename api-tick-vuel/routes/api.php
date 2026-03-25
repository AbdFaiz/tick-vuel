<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'dashboard']);

    // Ticket
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/ticket', [TicketController::class, 'store']);
    Route::get('/ticket/{code}', [TicketController::class, 'show']);
    Route::put('/ticket/{code}', [TicketController::class, 'update']);
    Route::delete('/ticket/{code}', [TicketController::class, 'destroy']);

    // Ticket Reply
    Route::post('/ticket-reply/{code}', [TicketController::class, 'storeReply']);
    Route::put('/ticket-reply/{id}', [TicketController::class, 'updateReply']);
    Route::delete('/ticket-reply/{id}', [TicketController::class, 'destroyReply']);
});
