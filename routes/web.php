<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\VerifyAccessCodeController;

// Route untuk verifikasi kode akses
Route::get('/verify-access', [VerifyAccessCodeController::class, 'showVerificationForm'])->name('verify.access.code');
Route::post('/verify-access', [VerifyAccessCodeController::class, 'verifyAccessCode'])->name('verify.access.code.submit');

// Route yang dilindungi oleh middleware verifikasi kode
Route::group(['middleware' => ['verify.access.code']], function () {
    Route::get('/', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    Route::get('/transactions/total', [TransactionController::class, 'getTotal'])->name('transactions.total');
    Route::get('/transactions/data', [TransactionController::class, 'getAllTransactions'])->name('transactions.data');
    Route::get('/transactions/ids', [TransactionController::class, 'getAllTransactionIds'])->name('transactions.allIds');
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy'])->name('transactions.destroy');
    Route::delete('/transactions/bulk-delete', [TransactionController::class, 'bulkDelete'])->name('transactions.bulkDelete');
    Route::post('/transactions/bulk-delete', [TransactionController::class, 'bulkDelete'])->name('transactions.bulkDeletePost');
});
