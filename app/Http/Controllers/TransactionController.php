<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class TransactionController extends Controller
{
    /**
     * Menampilkan halaman utama
     */
    public function index(): View
    {
        $totalAmount = Transaction::getTotalAmount();
        $transactions = Transaction::orderBy('created_at', 'desc')->get();
        
        return view('transactions.index', compact('totalAmount', 'transactions'));
    }

    /**
     * Menyimpan transaksi baru
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'amount' => 'required|string|min:1', // Ubah dari integer ke string untuk menangani format dengan titik/koma
            'description' => 'nullable|string|max:255'
        ]);

        // Hapus titik dari amount untuk mendapatkan nilai numerik (untuk format ribuan seperti 1.000.000)
        // Tapi tetap jaga desimal jika ada (misalnya 645.500 untuk 645,500)
        $cleanAmount = str_replace('.', '', $request->amount);
        
        // Ganti koma desimal jika digunakan sebagai pemisah desimal
        $cleanAmount = str_replace(',', '.', $cleanAmount);
        
        // Validasi bahwa hasilnya adalah angka
        if (!is_numeric($cleanAmount) || $cleanAmount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah uang harus berupa angka positif'
            ], 422);
        }

        $transaction = Transaction::create([
            'amount' => (float)$cleanAmount, // Gunakan float untuk mendukung desimal
            'description' => $request->description ?? 'Transaksi masuk'
        ]);

        $totalAmount = Transaction::getTotalAmount();

        return response()->json([
            'success' => true,
            'transaction' => $transaction,
            'total_amount' => $totalAmount
        ]);
    }

    /**
     * Mendapatkan total terbaru
     */
    public function getTotal(): JsonResponse
    {
        $totalAmount = Transaction::getTotalAmount();
        
        return response()->json([
            'total_amount' => $totalAmount
        ]);
    }
    
    /**
     * Mendapatkan transaksi dengan pagination
     */
    public function getAllTransactions(Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $transactions = Transaction::orderBy('created_at', 'desc')
            ->paginate(5, ['*'], 'page', $page);
        
        return response()->json([
            'transactions' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
                'from' => $transactions->firstItem(),
            ]
        ]);
    }
    
    /**
     * Menghapus transaksi
     */
    public function destroy($id): JsonResponse
    {
        $transaction = Transaction::find($id);
        
        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ], 404);
        }
        
        $transactionAmount = $transaction->amount;
        $transaction->delete();
        
        $totalAmount = Transaction::getTotalAmount();
        
        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus',
            'total_amount' => $totalAmount
        ]);
    }

    /**
     * Menghapus beberapa transaksi sekaligus (bulk delete)
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        Transaction::whereIn('id', $request->ids)->delete();

        $totalAmount = Transaction::getTotalAmount();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi yang dipilih telah berhasil dihapus.',
            'total_amount' => $totalAmount
        ]);
    }
}
