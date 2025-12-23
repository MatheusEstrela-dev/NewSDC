<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::prefix('pae')->name('pae.')->group(function () {

    Route::get('/', function () {
        return Inertia::render('Pae');
    })->name('index');

});
