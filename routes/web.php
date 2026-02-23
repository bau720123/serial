<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\SerialAdminController;

Route::get('/', function () {
    return view('welcome');
});

// 後台序號列表
Route::get('/admin/serials', [SerialAdminController::class, 'index'])->name('後台序號列表');

// 後台序號匯出
Route::get('/admin/serials/export', [SerialAdminController::class, 'export'])->name('後台序號匯出');

// 後台序號匯出（新增的 AJAX 方式）
Route::get('/admin/serials/export_ajx', [SerialAdminController::class, 'export_ajx'])->name('後台序號匯出AJAX');
