<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;

Route::get('/', [TransactionController::class, 'index']);
Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
Route::get('/transactions/total', [TransactionController::class, 'getTotal'])->name('transactions.total');
Route::get('/transactions/data', [TransactionController::class, 'getAllTransactions'])->name('transactions.data');
Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
