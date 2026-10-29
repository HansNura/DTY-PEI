<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Inventory\DtyRollController;
use App\Http\Controllers\Logistics\CustomerController;
use App\Http\Controllers\Logistics\FleetController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
});

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/status', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    });

Route::middleware(['auth', 'role:admin_gudang'])
    ->prefix('warehouse')
    ->name('warehouse.')
    ->group(function () {
        Route::get('/rolls', [DtyRollController::class, 'index'])->name('rolls.index');
        Route::get('/rolls/create', [DtyRollController::class, 'create'])->name('rolls.create');
        Route::post('/rolls', [DtyRollController::class, 'store'])->name('rolls.store');
        Route::get('/rolls/{roll}/edit', [DtyRollController::class, 'edit'])->name('rolls.edit');
        Route::put('/rolls/{roll}', [DtyRollController::class, 'update'])->name('rolls.update');
        Route::patch('/rolls/{roll}/cancel', [DtyRollController::class, 'cancel'])->name('rolls.cancel');
        Route::get('/rolls/{roll}/label', [DtyRollController::class, 'label'])->name('rolls.label');
    });

Route::middleware(['auth', 'role:logistik'])
    ->prefix('logistics')
    ->name('logistics.')
    ->group(function () {
        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
        Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
        Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
        Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
        Route::patch('/customers/{customer}/status', [CustomerController::class, 'toggleActive'])->name('customers.toggle-active');

        Route::get('/fleets', [FleetController::class, 'index'])->name('fleets.index');
        Route::get('/fleets/create', [FleetController::class, 'create'])->name('fleets.create');
        Route::post('/fleets', [FleetController::class, 'store'])->name('fleets.store');
        Route::get('/fleets/{fleet}/edit', [FleetController::class, 'edit'])->name('fleets.edit');
        Route::put('/fleets/{fleet}', [FleetController::class, 'update'])->name('fleets.update');
        Route::patch('/fleets/{fleet}/status', [FleetController::class, 'toggleActive'])->name('fleets.toggle-active');
    });
