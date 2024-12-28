<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\GroupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('config', [ConfigurationController::class, 'systemConfiguration']);

    Route::apiResource('account', AccountController::class);
    Route::patch('account/{account}/activate', [AccountController::class, 'activate']);
});

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});
