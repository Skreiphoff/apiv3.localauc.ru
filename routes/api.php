<?php

use App\Http\Controllers\APIController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

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


Route::middleware(['throttle:login'])->post('/auth/signin', [AuthController::class,'signin']);
Route::middleware(['throttle:refresh'])->post('/auth/register', [AuthController::class,'register']);
Route::middleware(['throttle:refresh','jwtauth:refreshing'])->post('/auth/refresh', [AuthController::class,'refresh']);
Route::middleware(['throttle:api','jwtauth'])->post('/auth/signout', [AuthController::class,'signout']);
Route::middleware(['throttle:api','jwtauth'])->post('/data', [APIController::class,'index'])
    ->middleware('compatibility');

Route::fallback(function (){
    abort(404, 'API resource not found');
});


