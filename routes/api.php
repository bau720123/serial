<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SerialController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/serials_insert', [SerialController::class, 'serials_insert'])
    ->name('批次新增序號')
    ->middleware('api.logger');

Route::post('/serials_additional_insert', [SerialController::class, 'serials_additional_insert'])
    ->name('批次追加序號')
    ->middleware('api.logger');

Route::post('/serials_redeem', [SerialController::class, 'serials_redeem'])
    ->name('核銷序號')
    ->middleware('api.logger');

Route::post('/serials_cancel', [SerialController::class, 'serials_cancel'])
    ->name('註銷序號')
    ->middleware('api.logger');
