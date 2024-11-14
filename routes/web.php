<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::inertia('/explore', 'Explore')->name('explore');

// 'verified' and password.confirm middleware can be used to protect routes that need full user verification but for now, i'll put it at my rentals to demonstrate functionality

Route::middleware(['auth'])->group(function() {
    // profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');   
    Route::patch('/profile', [ProfileController::class, 'updateInfo'])->name('profile.info');
    Route::put('/profile', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // my rentals
    Route::inertia('/my-rentals', 'MyRentals')->middleware('verified')->name('my-rentals');
});

require __DIR__ . '/auth.php';