<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/games', [GameController::class, 'listGames']);
Route::get('/games/{id}', [GameController::class, 'showGame'])->whereNumber('id');
Route::post('/games', [GameController::class, 'createGame']);
Route::post('/step/{id}', [GameController::class, 'createStep'])->whereNumber('id');
