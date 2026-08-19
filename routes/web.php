<?php

use App\Http\Controllers\Admin\LearningMaterialImageController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Auth\DemoLoginController;
use App\Http\Controllers\Auth\PortalController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/portal');
Route::post('/admin/demo-login', DemoLoginController::class)
    ->middleware(['guest', 'throttle:demo-login'])
    ->name('admin.demo-login');

Route::get('/portal', PortalController::class)->middleware(['auth', 'active'])->name('portal.home');

Route::prefix('account')->name('account.')->middleware(['auth', 'active'])->group(function (): void {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');
});

Route::redirect('/admin/profile', '/account/profile');
Route::redirect('/admin/password/change', '/account/password');

Route::get('/learning-material-images/{learning_material_image}', [LearningMaterialImageController::class, 'show'])
    ->middleware(['auth', 'active'])
    ->name('learning-material-images.show');

require __DIR__.'/portals/super-admin.php';
require __DIR__.'/portals/admin.php';
require __DIR__.'/portals/instructor.php';
require __DIR__.'/portals/trainee.php';
