<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('collection.index');
});

Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
Route::get('/collection/{id}', [CollectionController::class, 'show'])->name('collection.show');

Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::post('/settings/sync', [SettingsController::class, 'sync'])->name('settings.sync');
