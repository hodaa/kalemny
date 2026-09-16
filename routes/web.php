<?php

use App\Http\Controllers\CallController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/calls', [CallController::class, 'index'])->name('calls.index');
    Route::post('/calls', [CallController::class, 'store'])->name('calls.store');
    Route::get('/calls/active', [CallController::class, 'active'])->name('calls.active');
    Route::post('/calls/{call}/accept', [CallController::class, 'accept'])->name('calls.accept');
    Route::post('/calls/{call}/decline', [CallController::class, 'decline'])->name('calls.decline');
    Route::post('/calls/{call}/cancel', [CallController::class, 'cancel'])->name('calls.cancel');
    Route::post('/calls/{call}/end', [CallController::class, 'end'])->name('calls.end');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
