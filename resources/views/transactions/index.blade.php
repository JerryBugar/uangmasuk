@extends('layouts.app')

@section('title', 'Dashboard Uang Masuk')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="text-muted">Total Uang Masuk</h4>
            <h5 id="last-update" class="text-muted">Terakhir diperbarui: <span id="update-time">-</span></h5>
        </div>
        <div class="card bg-light mb-4">
            <div class="card-body text-center">
                <h2 class="total-amount" id="total-amount">Rp 0</h2>
                <p class="text-muted">Jumlah total uang masuk</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Tambah Uang Masuk Baru</h4>
        <form id="transaction-form">
            @csrf
            <div class="row">
                <div class="col-md-8 mb-3">
                    <label for="amount" class="form-label">Jumlah Uang (Rupiah)</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="text" class="form-control" id="amount" name="amount" placeholder="Contoh: 100000 (akan otomatis diformat jadi 100.000)" required value="">
                    </div>
                    <div class="form-text">Masukkan jumlah uang (akan otomatis diformat ke format Rupiah)</div>
                </div>
                <div class="col-md-4 mb-3">
                    <label for="submit-btn" class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-plus-lg"></i> Tambahkan
                    </button>
                </div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi (opsional)</label>
                <input type="text" class="form-control" id="description" name="description" placeholder="Contoh: Gaji bulanan, Penjualan, dll">
            </div>
        </form>
    </div>
</div>

<hr class="my-4">

<div class="row">
    <div class="col-12">
        <h4 class="mb-3">Riwayat Transaksi</h4>
        <div id="transactions-list">
            <!-- Transaksi akan dimuat di sini -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Format angka ke format Rupiah
    function formatRupiah(angka) {
        if (angka === null || angka === undefined || angka === '') return 'Rp 0';
        const number = parseFloat(angka);
        if (isNaN(number)) return 'Rp 0';
        
        return 'Rp ' + number.toLocaleString('id-ID', {
            minimumFractionDigits: Number.isInteger(number) ? 0 : 2,
            maximumFractionDigits: 2
        }).replace(/,/g, '.');
    }

    // Ambil total terbaru
    async function refreshTotal() {
        try {
            const response = await fetch('{{ route("transactions.total") }}');
            const data = await response.json();
            document.getElementById('total-amount').textContent = formatRupiah(data.total_amount);
            
            const now = new Date();
            document.getElementById('update-time').textContent = now.toLocaleString('id-ID');
        } catch (error) {
            console.error('Error fetching total:', error);
        }
    }

    let currentPage = 1;
    
    // Muat daftar transaksi dengan pagination
    async function loadTransactions(page = 1) {
        try {
            const response = await fetch(`{{ route("transactions.data") }}?page=${page}`);
            const data = await response.json();
            
            currentPage = data.pagination.current_page;
            
            const transactionsList = document.getElementById('transactions-list');
            let html = '';
            
            if (data.transactions && data.transactions.length > 0) {
                html += '<div class="list-group">';
                
                data.transactions.forEach(transaction => {
                    const date = new Date(transaction.created_at);
                    html += `
                        <div class="list-group-item transaction-item" id="transaction-${transaction.id}">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${formatRupiah(transaction.amount)}</h6>
                                <div>
                                    <small>${date.toLocaleString('id-ID')}</small>
                                    <button class="btn btn-sm btn-danger ms-2" onclick="deleteTransaction(${transaction.id})">
                                        <i class="bi bi-trash"></i> Hapus
                                    </button>
                                </div>
                            </div>
                            <p class="mb-1">${transaction.description}</p>
                        </div>
                    `;
                });
                
                html += '</div>';
                
                html += '<nav aria-label="Navigasi halaman" class="mt-3"><ul class="pagination justify-content-center">';
                
                if (data.pagination.current_page > 1) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransactions(${data.pagination.current_page - 1}); return false;">Previous</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><span class="page-link">Previous</span></li>`;
                }
                
                for (let i = 1; i <= data.pagination.last_page; i++) {
                    if (i === data.pagination.current_page) {
                        html += `<li class="page-item active"><span class="page-link">${i}</span></li>`;
                    } else {
                        html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransactions(${i}); return false;">${i}</a></li>`;
                    }
                }
                
                if (data.pagination.current_page < data.pagination.last_page) {
                    html += `<li class="page-item"><a class="page-link" href="#" onclick="loadTransactions(${data.pagination.current_page + 1}); return false;">Next</a></li>`;
                } else {
                    html += `<li class="page-item disabled"><span class="page-link">Next</span></li>`;
                }
                
                html += '</ul></nav>';
            } else {
                html = '<p class="text-muted">Belum ada transaksi</p>';
                currentPage = 1;
            }
            
            transactionsList.innerHTML = html;
        } catch (error) {
            console.error('Error loading transactions:', error);
        }
    }

    // Fungsi untuk menghapus transaksi
    async function deleteTransaction(id) {
        if (!confirm('Apakah Anda yakin ingin menghapus transaksi ini?')) {
            return;
        }
        
        try {
            const response = await fetch(`/transactions/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                }
            });
            
            const data = await response.json();
            
            if (data.success) {
                const transactionElement = document.getElementById(`transaction-${id}`);
                if (transactionElement) {
                    transactionElement.remove();
                }
                
                document.getElementById('total-amount').textContent = formatRupiah(data.total_amount);
                
                alert('Transaksi berhasil dihapus!');
            } else {
                alert(data.message || 'Gagal menghapus transaksi');
            }
        } catch (error) {
            console.error('Error deleting transaction:', error);
            alert('Terjadi kesalahan saat menghapus transaksi');
        }
    }
    
    // Fungsi untuk membersihkan format Rupiah menjadi angka
    function cleanRupiahFormat(input) {
        // Hapus pemisah ribuan (titik) dan ganti pemisah desimal (koma) dengan titik
        let cleanValue = input.replace(/\./g, '');
        cleanValue = cleanValue.replace(/,/g, '.');
        return cleanValue;
    }

    // Submit form transaksi
    document.getElementById('transaction-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const amountInput = document.getElementById('amount');
        let amountValue = amountInput.value;
        
        // Bersihkan format Rupiah menjadi angka biasa
        amountValue = cleanRupiahFormat(amountValue);
        
        if (!/^\d+(\.\d+)?$/.test(amountValue) || parseFloat(amountValue) <= 0) {
            alert('Jumlah uang harus berupa angka positif');
            return;
        }
        
        const formData = new FormData();
        formData.append('amount', amountValue);
        formData.append('description', document.getElementById('description').value);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        
        try {
            const response = await fetch('{{ route("transactions.store") }}', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.reset();
                // Setelah reset, cleave akan mengosongkan input, jadi kita tidak perlu set manual
                
                refreshTotal();
                loadTransactions();
                
                alert('Transaksi berhasil ditambahkan!');
            } else {
                alert(data.message || 'Gagal menambahkan transaksi');
            }
        } catch (error) {
            console.error('Error submitting form:', error);
            alert('Terjadi kesalahan saat menambahkan transaksi');
        }
    });

    // Inisialisasi
    document.addEventListener('DOMContentLoaded', function() {
        refreshTotal();
        loadTransactions(currentPage);
        
        setInterval(refreshTotal, 30000);
        setInterval(() => loadTransactions(currentPage), 60000);
    });
</script>
@endsection>