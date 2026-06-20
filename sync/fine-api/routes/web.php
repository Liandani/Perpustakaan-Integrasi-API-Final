<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FineController;
use App\Http\Controllers\RabbitMQConsumerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/consume-message', [RabbitMQConsumerController::class, 'consume']);

Route::get('/fines', [FineController::class, 'index']);
Route::get('/fines/{id}', [FineController::class, 'show']);
Route::post('/fines/check', [FineController::class, 'checkFine']);
Route::get('/fines/loan/{loan_id}', [FineController::class, 'getByLoan']);

Route::post('/fines', [FineController::class, 'store']);
Route::put('/fines/{id}', [FineController::class, 'update']);
Route::delete('/fines/{id}', [FineController::class, 'destroy']);
