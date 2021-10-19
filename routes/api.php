<?php

use App\Http\Controllers\TestApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AndroidApiController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AndroidApiController::class, 'loginPost']);
Route::post('register', [AndroidApiController::class, 'registerPost']);

//metto a disposizione le API di test solo in ambiente di produzione
if (App::environment() !== 'production') {
    Route::get('getTwoFACodeBySecret/{secret}', [TestApiController::class, 'getTwoFACodeBySecret']);
    Route::post('firstLoginTestSetup', [TestApiController::class, 'firstLoginTestSetup']);
    Route::post('firstLoginTestTeardown', [TestApiController::class, 'firstLoginTestTeardown']);
}

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('reservation', [AndroidApiController::class,'reservationPost']);
    Route::get('get-structures-by-region/{region}', [AndroidApiController::class,'getStructuresByRegion']);
    Route::get('get-last-reservation-by-patient-email/{email}', [AndroidApiController::class, 'getLastReservationByPatientEmail']);
});
