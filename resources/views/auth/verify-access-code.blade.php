@extends('layouts.app')

@section('title', 'Verifikasi Akses')

@section('content')
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
            <div class="card">
                <div class="card-header text-center">
                    <h4>Verifikasi Akses</h4>
                </div>
                <div class="card-body">
                    <p class="text-center text-muted">Silakan masukkan kode verifikasi 8 digit</p>
                    
                    <form method="POST" action="{{ route('verify.access.code.submit') }}">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="access_code" class="form-label">Kode Verifikasi</label>
                            <input 
                                type="text" 
                                class="form-control form-control-lg text-center" 
                                id="access_code" 
                                name="access_code" 
                                maxlength="8" 
                                placeholder="00000000"
                                autofocus
                                required
                            >
                            @error('access_code')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Verifikasi
                            </button>
                        </div>
                    </form>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">Kode verifikasi terdiri dari 8 digit angka</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection