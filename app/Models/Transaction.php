<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'amount',
        'description'
    ];

    protected $casts = [
        'amount' => 'decimal:2', // Mengizinkan angka desimal dengan 2 digit di belakang koma
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Menghitung total semua transaksi
     */
    public static function getTotalAmount()
    {
        return (float) self::sum('amount');  // Mengembalikan nilai desimal
    }
}
