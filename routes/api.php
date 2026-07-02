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

Route::post('/profile-image-upload', [apiController::class,'profileImageUpload']);
Route::post('/bill-file-upload', [apiController::class,'billFileUpload']);

Route::post('/get-gst-no', [apiController::class,'getGstNo']);
Route::post('/graph-data', [apiController::class,'HomePageGraphData']);
Route::post('/bill-breakdown', [apiController::class,'BillBreakDown']);

Route::post('/product-image', [apiController::class,'getProductImage']);

Route::post('/saving-offers', [apiController::class,'saving_offer']);

Route::post('/offerImgUpload', [apiController::class,'offerImageUpload']);

Route::post('/gen-month-bill-pdf', [apiController::class, 'generateMonthlyBillsPDF']);
Route::post('/get-month-bill-pdf-list', [apiController::class, 'getMonthlyBillsPDFList']);

Route::post('/get-coins', [apiController::class,'getCoins']);


