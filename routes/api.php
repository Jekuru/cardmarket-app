<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsersController;
use App\Http\Controllers\CardsController;
use App\Http\Controllers\CollectionsController;
use App\Http\Controllers\CardssalesController;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('users')->group(function(){
    Route::put('/register', [UsersController::class, 'register']);
    Route::get('/login', [UsersController::class, 'login']);
    Route::post('/resetpassword', [UsersController::class, 'resetpassword']);
    // NO HACE FALTA CRUD!!!
});

Route::prefix('cards')->group(function(){
    Route::put('/register', [CardsController::class, 'register'])->middleware('check-admin');
    // NO HACE FALTA CRUD!!!
});

Route::prefix('collections')->group(function(){
    Route::put('/register', [CollectionsController::class, 'register'])->middleware('check-admin');
    // NO HACE FALTA CRUD!!!
});

Route::prefix('cardssales')->group(function(){
    Route::put('/sell', [CardssalesController::class, 'sell'])->middleware('check-seller');

});