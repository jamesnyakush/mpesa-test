<?php

use App\Http\Controllers\MpesaC2BController;
use App\Http\Controllers\MpesaSTKPUSHController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/v1/mpesatest/stk/push', [MpesaSTKPUSHController::class, 'STKPush']);
Route::post('/v1/confirm', [MpesaSTKPUSHController::class, 'STKConfirm']);
Route::post('/v1/callback/query', [MpesaSTKPUSHController::class, 'query']);

Route::post('/c2b/simulate', [MPESAC2BController::class, 'simulate']);
Route::post('/register-urls', [MPESAC2BController::class, 'registerURLS']);
Route::post('/validation', [MPESAC2BController::class, 'validation']);
Route::post('/confirmation', [MPESAC2BController::class, 'confirmation']);
