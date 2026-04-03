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

Route::post('/get-profile', [apiController::class,'getProfile']);
Route::post('/update-profile', [apiController::class,'updateProfile']);

Route::post('/get-address', [apiController::class,'getAddress']);
Route::post('/get-addressById', [apiController::class,'getAddressById']);

Route::post('/add-address', [apiController::class,'add_address']);
Route::post('/update-address', [apiController::class,'update_address']);

Route::post('/get-bills', [apiController::class,'get_bills']);

Route::post('/save-bills', [apiController::class,'save_bills']);