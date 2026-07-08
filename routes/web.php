<?php

use App\Http\Controllers\Admin\DoctorController;
use App\Http\Controllers\AssistantController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');

    Route::get('assistant', [AssistantController::class, 'index'])->name('assistant');
    Route::post('assistant/message', [AssistantController::class, 'message'])->name('assistant.message');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('doctors', DoctorController::class)->only(['index', 'store', 'update', 'destroy']);
    });
});

require __DIR__.'/settings.php';
