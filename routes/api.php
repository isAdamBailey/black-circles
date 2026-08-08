<?php

use App\Http\Controllers\Api\V1\CollectionController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\MoodController;
use App\Http\Controllers\Api\V1\VibeController;
use App\Http\Controllers\CollectionController as WebCollectionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('api.home');

    Route::get('/moods/{mood}/suggest', [MoodController::class, 'suggest'])->name('api.moods.suggest');

    Route::post('/vibe/suggest', [VibeController::class, 'suggest'])->name('api.vibe.suggest');

    Route::get('/collection', [CollectionController::class, 'index'])->name('api.collection.index');
    Route::get('/collection/search', [WebCollectionController::class, 'search'])->name('api.collection.search');
    Route::get('/collection/random', [CollectionController::class, 'random'])->name('api.collection.random');
    Route::get('/collection/{id}', [CollectionController::class, 'show'])->name('api.collection.show');
});
