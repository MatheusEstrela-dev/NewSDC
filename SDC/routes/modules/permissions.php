<?php

use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\RoleManagementController;
use App\Http\Controllers\Admin\PermissionManagementController;
use App\Http\Controllers\Admin\UserTokenController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/permissions')->name('admin.permissions.')->middleware(['can:users.view'])->group(function () {

    // Leitura sempre liberada (index/show/create form/edit form): usuario
    // pode navegar mesmo com troca de e-mail pendente.
    Route::resource('users', UserManagementController::class)->only(['index', 'show', 'create', 'edit']);

    // Escrita sob email.verified: bloqueia quando ha pedido pending de
    // troca do proprio e-mail (admin nao mexe em outros users ate validar).
    Route::middleware('email.verified')->group(function () {
        Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
        Route::match(['put', 'patch'], 'users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');

        Route::post('users/{user}/reactivate', [UserManagementController::class, 'reactivate'])->name('users.reactivate')->withTrashed();
        Route::post('users/{user}/roles', [UserManagementController::class, 'syncRoles'])->name('users.syncRoles');
        Route::post('users/{user}/permissions', [UserManagementController::class, 'syncPermissions'])->name('users.syncPermissions');
    });

    Route::post('users/{user}/tokens', [UserTokenController::class, 'store'])->name('users.tokens.store');
    Route::delete('users/{user}/tokens/{tokenId}', [UserTokenController::class, 'destroy'])->name('users.tokens.destroy');

    Route::resource('roles', RoleManagementController::class);
    Route::post('roles/{role}/permissions', [RoleManagementController::class, 'syncPermissions'])->name('roles.syncPermissions');

    Route::resource('permissions', PermissionManagementController::class)->only(['index', 'show']);

});
