<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ForgetPasswordController;
use App\Http\Controllers\CurrencyController;


Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']); 
    Route::post('/login', [AuthController::class, 'login']); 
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
    Route::middleware('auth:sanctum')->put('/update-profile', [AuthController::class, 'updateProfile']);


    

    Route::post('/forgot-password', [ForgetPasswordController::class, 'sendOtp']);
    Route::post('/reset-password', [ForgetPasswordController::class, 'verifyOtpAndResetPassword']);

   
    
    Route::middleware(['web'])->group(function () {
        Route::get('/google', [AuthController::class, 'redirectToGoogle']);
        Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
        Route::get('/facebook', [AuthController::class, 'redirectToFacebook']);
        Route::get('/facebook/callback', [AuthController::class, 'handleFacebookCallback']);

    });


});



Route::post('/upload-detect', [CurrencyController::class, 'uploadAndDetect']);
