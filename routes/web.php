<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FirstLoginController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ResponsibleController;
use App\Http\Controllers\TwoFAController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::group(['middleware' => ['throttle:30,1']], function () {
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

Route::get('/fake-login', function () {
    return Inertia::render('Auth/FakeLogin', []);
});

Route::group(['middleware' => ['auth', 'role', 'throttle:30,1'], 'name' => 'qr'], function () {
    Route::post('/qr/completeRegistration', [FirstLoginController::class, 'completeRegistration'])
        ->name('2fa.completeRegistration');

    Route::get('/qr/register', [FirstLoginController::class, 'register'])
        ->name('2fa.register');
});

Route::group(['middleware' => ['auth', 'first_login', 'role', 'throttle:30,1'], 'name' => 'backoffice'], function () {
    Route::get('/qr/authenticate', [TwoFAController::class, 'authenticate'])
        ->name('2fa.authenticate');

    Route::post('/qr/completeLogin', [TwoFAController::class, 'completeLogin'])
        ->name('2fa.completeLogin');
});

Route::group(['middleware' => ['auth', 'first_login', 'role', '2fa', 'throttle:30,1'], 'name' => 'backoffice'], function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard.index');

    Route::get('/prenotazioni', [ReservationController::class, 'index'])
        ->name('reservations.index');

    Route::get('/responsabile/crea', [ResponsibleController::class, 'create'])
        ->name('responsible.create');

    Route::post('/responsabile/salva', [ResponsibleController::class, 'store'])
        ->name('responsible.store');

    Route::get('/register', function () {
        echo "Pagina non ancora implementata";
    })->name('register');

    Route::post('/prenotazioni', [ReservationController::class, 'index'])
        ->name('reservations.index');

    Route::post('/prenotazioni/reservations-polling', [ReservationController::class, 'reservationsChanged'])
        ->name('reservations.poll');

    Route::post('/prenotazione/salva', [ReservationController::class, 'store'])
        ->name('reservations.store');

    Route::put('/prenotazione/{reservation}/update', [ReservationController::class, 'update'])
        ->name('reservations.update');

    Route::post('/prenotazione/busy-times', [ReservationController::class, 'getBusyTimes'])
        ->name('reservations.busytimes');

    Route::get('/prenotazione/{reservation}/edit', [ReservationController::class, 'edit'])
        ->name('reservations.edit');

    Route::get('/prenotazione/{reservation}/richiamo', [ReservationController::class, 'create'])
        ->name('reservations.create');
});
