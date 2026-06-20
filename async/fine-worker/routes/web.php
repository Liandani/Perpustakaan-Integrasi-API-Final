<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RabbitMQConsumerController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/consume-message', [RabbitMQConsumerController::class, 'consume']);
