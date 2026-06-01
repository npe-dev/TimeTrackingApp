<?php

use Illuminate\Support\Facades\Route;

// Catch-all route: serve the Vue SPA for any non-API route
Route::get('/{any?}', function () {
    return view('app');
})->where('any', '.*');
