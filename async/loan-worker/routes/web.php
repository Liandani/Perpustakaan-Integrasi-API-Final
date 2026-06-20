<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RabbitMQController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/send-message', [RabbitMQController::class, 'send']);
