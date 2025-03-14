<?php

use App\Http\Controllers\MpesaC2BController;
use App\Http\Controllers\MpesaSTKPUSHController;

Route::post('/trial/stk/push', [MpesaSTKPUSHController::class, 'STKPush']);
Route::post('/confirm', [MpesaSTKPUSHController::class, 'STKConfirm']);
Route::post('/callback/query', [MpesaSTKPUSHController::class, 'query']);

Route::post('/c2b/simulate', [MPESAC2BController::class, 'simulate']);
Route::post('/register-urls', [MPESAC2BController::class, 'registerURLS']);
Route::post('/validation', [MPESAC2BController::class, 'validation']);
Route::post('/confirmation', [MPESAC2BController::class, 'confirmation']);
