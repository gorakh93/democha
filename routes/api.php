<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test', function () {
    return ['success' => true,'message' => 'This is a test endpoint'];
});

Route::post('/login', [apiController::class,'login']);
Route::post('/register', [apiController::class,'register']);