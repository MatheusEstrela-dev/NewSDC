<?php

use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index')
        ->middleware('can:system.logs.view')
        ->name('logs.index');

    Route::get('health-dashboard', function () {
        return view('health-dashboard');
    })
        ->middleware('can:system.logs.view')
        ->name('health.dashboard');

});
