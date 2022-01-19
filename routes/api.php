<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UsersController;

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
    // CRUD??
});

Route::prefix('cards')->group(function(){
    Route::put('/register', [UsersController::class, 'register']);
    // CRUD??
})->middleware('check-admin');