<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\PermissionManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/permissions')->name('admin.permissions.')->middleware(['can:users.view'])->group(function () {

    Route::resource('users', UserManagementController::class);
    Route::post('users/{user}/roles', [UserManagementController::class, 'syncRoles'])->name('users.syncRoles');
    Route::post('users/{user}/permissions', [UserManagementController::class, 'syncPermissions'])->name('users.syncPermissions');

    Route::resource('roles', RoleManagementController::class);
    Route::post('roles/{role}/permissions', [RoleManagementController::class, 'syncPermissions'])->name('roles.syncPermissions');

    Route::resource('permissions', PermissionManagementController::class)->only(['index', 'show']);

});
