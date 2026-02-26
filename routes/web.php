<?php

use App\Http\Controllers\CollectionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('collection.index');
});

Route::get('/collection', [CollectionController::class, 'index'])->name('collection.index');
Route::get('/collection/search', [CollectionController::class, 'search'])->name('collection.search');
Route::get('/collection/{id}', [CollectionController::class, 'show'])->name('collection.show');
