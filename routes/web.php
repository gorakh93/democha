<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('privacy');
});

Route::get('/new', function () {
    return 'This is a new route';
});


Route::get('/privacy-policy', function () {
    return view('privacy');
});