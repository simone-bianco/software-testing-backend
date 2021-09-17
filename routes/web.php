<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FirstLoginController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\TwoFAController;
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

//Route::get('/', function () {
//    return Inertia::render('Welcome', [
//        'canLogin' => Route::has('login'),
//        'canRegister' => Route::has('register'),
//        'laravelVersion' => Application::VERSION,
//        'phpVersion' => PHP_VERSION,
//    ]);
//});

Route::group(['middleware' => ['throttle:30,1'], 'name' => 'guester'], function () {
    Route::get('/', function () {
        if (request()->user()->responsible) {
            return redirect()->to(route('dashboard.index'));
        }
        return Inertia::render('Auth/Login', [
            'canResetPassword' => true,
            'status' => '',
            'error' => '',
        ]);
    });
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
