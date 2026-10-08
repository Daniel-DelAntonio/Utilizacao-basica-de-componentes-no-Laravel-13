<?php

use App\Http\Controllers\ExportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Route::inertia('/lista', 'Lista')->name('lista');

Route::get('/', [UserController::class, 'index'])->name('usuarios.index');
Route::resource('users', UserController::class)->only([
    'index',
    'create',
    'store',
    'edit',
    'update',
    'destroy',
]);
Route::get('/formUsuario', [UserController::class, 'create'])->name('formUsuario');
Route::post('/storeUsuario', [UserController::class, 'store'])->name('storeUsuario');

Route::get('/export/pdf', [ExportController::class, 'pdf'])->name('exports.pdf');
Route::get('/export/csv', [ExportController::class, 'csv'])->name('exports.csv');
Route::get('/export/xlsx', [ExportController::class, 'xlsx'])->name('exports.xlsx');
