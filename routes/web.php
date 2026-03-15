<?php

use App\Http\Controllers\CollectionController;
use App\Http\Controllers\MoodController;
use App\Http\Controllers\VibeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MoodController::class, 'index'])->name('home');
Route::get('/mood/{mood}', [MoodController::class, 'suggest'])->name('mood.suggest');
Route::post('/vibe', [VibeController::class, 'suggest'])->name('vibe.suggest');

Route::get('/random', [CollectionController::class, 'random'])->name('collection.random');
Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
Route::get('/collection/search', [CollectionController::class, 'search'])->name('collection.search');
Route::get('/collection/{id}', [CollectionController::class, 'show'])->name('collection.show');
