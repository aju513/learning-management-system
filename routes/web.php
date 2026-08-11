<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UiKitController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'active'])->group(function (): void {
    Route::get('/password/change', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password/change', [PasswordController::class, 'update'])->name('password.update');

    Route::get('/', DashboardController::class)->middleware('can:dashboard.view')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/users', [UserController::class, 'index'])->middleware('can:users.manage')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('can:users.create')->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->middleware('can:users.create')->name('users.store');
    Route::patch('/users/bulk-status', [UserController::class, 'bulkStatus'])->middleware('can:users.change-status')->name('users.bulk-status');
    Route::delete('/users/bulk', [UserController::class, 'bulkDestroy'])->middleware('can:users.delete')->name('users.bulk-destroy');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->middleware('can:users.edit')->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->middleware('can:users.edit')->name('users.update');
    Route::patch('/users/{user}/status', [UserController::class, 'status'])->middleware('can:users.change-status')->name('users.status');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('can:users.delete')->name('users.destroy');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('can:users.show')->name('users.show');

    Route::get('/roles', [RoleController::class, 'index'])->middleware('can:roles.manage')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('can:roles.create')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('can:roles.create')->name('roles.store');
    Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->middleware('can:roles.edit')->name('roles.edit');
    Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('can:roles.edit')->name('roles.update');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('can:roles.delete')->name('roles.destroy');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('can:roles.show')->name('roles.show');
    Route::get('/permissions', PermissionController::class)->name('permissions.index');
    Route::get('/activity-log', ActivityLogController::class)->name('activity.index');
    Route::get('/ui-kit', UiKitController::class)->name('ui-kit');
});
