<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', WelcomeController::class)->name('home');

// Admin Routes - Protected by role middleware
Route::middleware(['auth', 'role:admin,moderator'])->prefix('admin')->name('admin.')->group(function () {
    // User Management
    Route::resource('users', UserController::class);
    Route::post('users/{user}/assign-roles', [UserController::class, 'assignRoles'])
        ->name('users.assign-roles')
        ->middleware('permission:assign-roles');

    // Role Management
    Route::resource('roles', RoleController::class);

});
