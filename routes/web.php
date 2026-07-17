<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\apiController;

Route::get('/', function () {
    return view('privacy');
});

Route::get('/new', function () {
    return 'This is a new route';
});


Route::get('/privacy-policy', function () {
    return view('privacy');
});

Route::get('/spend/it/delete-account/{userid?}', [apiController::class,'DeleteAccount']);