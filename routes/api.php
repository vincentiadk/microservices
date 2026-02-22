<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\CatalogController;

// Hanya aktif jika mode MONO atau SEARCH
if (config('services.mode') === 'MONO' || config('services.mode') === 'SEARCH') {
    Route::get('/search', [SearchController::class, 'search']);
}

// Hanya aktif jika mode MONO atau CRUD
if (config('services.mode') === 'MONO' || config('services.mode') === 'CRUD') {
    Route::get('/catalogs/{id}', [CatalogController::class, 'show']);
    Route::post('/catalogs', [CatalogController::class, 'store']);
    Route::post('/catalogs/{id}', [CatalogController::class, 'update']);
    Route::delete('/catalogs/{id}', [CatalogController::class, 'destroy']); // ganti POST jadi DELETE (lebih RESTful)
}
