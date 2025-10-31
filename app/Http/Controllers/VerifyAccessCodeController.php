<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;

class VerifyAccessCodeController extends Controller
{
    public function showVerificationForm()
    {
        // Hapus status verifikasi sebelum menampilkan form (jika ada)
        Session::forget('access_code_verified');
        
        return view('auth.verify-access-code');
    }

    public function verifyAccessCode(Request $request)
    {
        $request->validate([
            'access_code' => 'required|string|size:8'
        ], [
            'access_code.required' => 'Kode verifikasi harus diisi',
            'access_code.string' => 'Kode verifikasi harus berupa teks',
            'access_code.size' => 'Kode verifikasi harus terdiri dari 8 digit'
        ]);

        // Generate key for rate limiting based on IP
        $key = 'verify_access_code_' . $request->ip();
        
        // Check if user is blocked for 1 day after 3 failed attempts
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            return redirect()->route('verify.access.code')
                ->withErrors(['access_code' => 'Akses ditangguhkan. Silakan coba lagi dalam ' . ceil($seconds/60) . ' menit.']);
        }

        // Get the access code from environment variable
        $envCode = env('ACCESS_CODE', null);
        
        if (!$envCode) {
            return redirect()->route('verify.access.code')
                ->withErrors(['access_code' => 'Konfigurasi kode verifikasi tidak ditemukan.']);
        }

        // Check if input matches the environment code
        if ($request->access_code == $envCode) {
            // Reset attempts and mark as verified
            RateLimiter::clear($key);
            Session::put('access_code_verified', true);
            
            return redirect()->intended('/');
        } else {
            // Increment failed attempts
            RateLimiter::hit($key, 86400); // 86400 seconds = 1 day
            
            return redirect()->route('verify.access.code')
                ->withErrors(['access_code' => 'Kode verifikasi salah.']);
        }
    }
}
