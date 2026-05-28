<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\DevelopmentController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CreditController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\DatabaseImportController;
use Illuminate\Support\Facades\Auth;

// Redirect root URL
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// Guest Routes (Only accessible if not authenticated)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes (Only accessible if logged in)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Clients CRUD resource routes protected by auth
    Route::resource('clients', ClientController::class)->except(['create', 'edit', 'show']);

    // Licenses CRUD resource routes protected by auth
    Route::resource('licenses', LicenseController::class)->except(['create', 'edit', 'show']);

    // License remote system control (proxy to avoid CORS)
    Route::get('/licenses/{license}/system-status', [LicenseController::class, 'systemStatus'])->name('licenses.system-status');
    Route::post('/licenses/{license}/system-toggle', [LicenseController::class, 'systemToggle'])->name('licenses.system-toggle');

    // Developments CRUD resource routes protected by auth
    Route::resource('developments', DevelopmentController::class)->except(['create', 'edit', 'show']);

    // Payments CRUD
    Route::resource('payments', PaymentController::class)->only(['index', 'store', 'destroy']);

    // Reports
    Route::get('/reports/estado-cuenta', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/estado-cuenta/{client}', [ReportController::class, 'show'])->name('reports.show');

    // Database Import
    Route::get('/db-import', [DatabaseImportController::class, 'index'])->name('db-import.index');
    Route::post('/db-import', [DatabaseImportController::class, 'import'])->name('db-import.import');

    // Loans (Préstamos)
    Route::resource('loans', LoanController::class)->except(['create', 'edit', 'show']);

    // Credits (Créditos a pagar)
    Route::resource('credits', CreditController::class)->except(['create', 'edit']);
    Route::post('/credits/{credit}/payments', [CreditController::class, 'storePayment'])->name('credits.payments.store');
    Route::delete('/credits/{credit}/payments/{creditPayment}', [CreditController::class, 'destroyPayment'])->name('credits.payments.destroy');

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
