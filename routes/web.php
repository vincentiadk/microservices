<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebSearchController;

Route::get('/search', [WebSearchController::class, 'index']);
Route::post('/search', [WebSearchController::class, 'index']);
