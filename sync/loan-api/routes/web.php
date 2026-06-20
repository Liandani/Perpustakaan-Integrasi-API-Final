<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\RabbitMQController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/send-message', [RabbitMQController::class, 'send']);

Route::get('/loans', [LoanController::class, 'index']);
Route::post('/loans', [LoanController::class, 'store']);
Route::get('/loans/history', [LoanController::class, 'history']);
Route::post('/loans/return', [LoanController::class, 'returnBook']);
Route::get('/loans/{id}', [LoanController::class, 'show']);
Route::put('/loans/{id}', [LoanController::class, 'update']);
Route::delete('/loans/{id}', [LoanController::class, 'destroy']);
