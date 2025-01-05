<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('config', [ConfigurationController::class, 'systemConfiguration']);

    Route::apiResource('account', AccountController::class);
    Route::apiResource('role', RoleController::class);

    Route::get('/role/{role}/permissions', [RoleController::class, 'getPermissions']);
    Route::post('/role/{role}/permissions', [RoleController::class, 'updatePermissions']);
    Route::apiResource('users', UserController::class);

    Route::patch('account/{account}/activate', [AccountController::class, 'activate']);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    $user = $request->user();
    $user->getPermissionsViaRoles();
    return $user;
});
