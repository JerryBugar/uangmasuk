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
                    <button type="submit" id="submit-btn" class="btn btn-success w-100">
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

        <div id="bulk-action-bar" class="d-flex justify-content-between align-items-center mb-2 d-none">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="select-all-checkbox">
                <label class="form-check-label" for="select-all-checkbox">Pilih Semua</label>
            </div>
            <button id="bulk-delete-btn" class="btn btn-danger btn-sm">
                <i class="bi bi-trash"></i> Hapus yang Dipilih (<span id="selected-count">0</span>)
            </button>
        </div>

        <div id="transactions-list">
            <!-- Transaksi akan dimuat di sini -->
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    let selectedIds = [];

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
            // Tidak menampilkan error
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
                document.getElementById('bulk-action-bar').classList.remove('d-none');
                html += '<div class="list-group">';
                
                data.transactions.forEach((transaction, index) => {
                    const itemNumber = data.pagination.from + index;
                    const date = new Date(transaction.created_at);
                    const isChecked = selectedIds.includes(transaction.id);

                    html += `
                        <div class="list-group-item transaction-item" id="transaction-${transaction.id}">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <input class="form-check-input transaction-checkbox" type="checkbox" value="${transaction.id}" ${isChecked ? 'checked' : ''}>
                                </div>
                                <div class="col">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">${itemNumber}. ${formatRupiah(transaction.amount)}</h6>
                                        <div>
                                            <small>${date.toLocaleString('id-ID')}</small>
                                            <button class="btn btn-sm btn-danger ms-2" onclick="deleteTransaction(${transaction.id})">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <p class="mb-1">${transaction.description || '<i>Tidak ada deskripsi</i>'}</p>
                                </div>
                            </div>
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
                document.getElementById('bulk-action-bar').classList.add('d-none');
                currentPage = 1;
            }
            
            transactionsList.innerHTML = html;
            updateBulkActionUI();
        } catch (error) {
            // Tidak menampilkan error
        }
    }

    function updateBulkActionUI() {
        const selectedCount = document.getElementById('selected-count');
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        const totalVisibleCheckboxes = document.querySelectorAll('.transaction-checkbox').length;
        const totalSelectedVisible = document.querySelectorAll('.transaction-checkbox:checked').length;

        selectedCount.textContent = selectedIds.length;

        if (totalVisibleCheckboxes > 0 && totalSelectedVisible === totalVisibleCheckboxes) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (totalSelectedVisible > 0 || (selectedIds.length > 0 && totalSelectedVisible === 0) ) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }

    async function bulkDeleteTransactions() {
        console.log('Attempting to bulk delete transactions with IDs:', selectedIds);
        if (selectedIds.length === 0) {
            return;
        }

        try {
            const response = await fetch('{{ route("transactions.bulkDeletePost") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: selectedIds })
            });

            const data = await response.json();

            if (data.success) {
                selectedIds = [];
                loadTransactions(currentPage);
                refreshTotal();
            }
        } catch (error) {
            // Tidak menampilkan error
        }
    }

    // Fungsi untuk menghapus transaksi tunggal
    async function deleteTransaction(id) {
        console.log('Attempting to delete single transaction with ID:', id);
        
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
                selectedIds = selectedIds.filter(selectedId => selectedId !== id);
                loadTransactions(currentPage);
                refreshTotal();
            }
        } catch (error) {
            // Tidak menampilkan error
        }
    }
    
    // Fungsi untuk membersihkan format Rupiah menjadi angka
    function cleanRupiahFormat(input) {
        let cleanValue = input.replace(/\./g, '');
        cleanValue = cleanValue.replace(/,/g, '.');
        return cleanValue;
    }

    // Submit form transaksi
    document.getElementById('transaction-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submit-btn');
        const originalBtnHTML = submitBtn.innerHTML;
        
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...';

        const amountInput = document.getElementById('amount');
        let amountValue = amountInput.value;
        amountValue = cleanRupiahFormat(amountValue);
        
        if (!/^\d*\.?\d*$/.test(amountValue) || parseFloat(amountValue) <= 0) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHTML;
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
                refreshTotal();
                loadTransactions();
            }
        } catch (error) {
            // Tidak menampilkan error
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHTML;
        }
    });

    // Event listener untuk bulk actions
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('select-all-checkbox');
        const transactionsList = document.getElementById('transactions-list');
        const bulkDeleteBtn = document.getElementById('bulk-delete-btn');

        selectAllCheckbox.addEventListener('change', function() {
            const visibleCheckboxes = document.querySelectorAll('.transaction-checkbox');
            visibleCheckboxes.forEach(checkbox => {
                const id = parseInt(checkbox.value);
                if (this.checked) {
                    checkbox.checked = true;
                    if (!selectedIds.includes(id)) {
                        selectedIds.push(id);
                    }
                } else {
                    checkbox.checked = false;
                    selectedIds = selectedIds.filter(selectedId => selectedId !== id);
                }
            });
            updateBulkActionUI();
        });

        transactionsList.addEventListener('change', function(e) {
            if (e.target.classList.contains('transaction-checkbox')) {
                const id = parseInt(e.target.value);
                if (e.target.checked) {
                    if (!selectedIds.includes(id)) {
                        selectedIds.push(id);
                    }
                } else {
                    selectedIds = selectedIds.filter(selectedId => selectedId !== id);
                }
                updateBulkActionUI();
            }
        });

        bulkDeleteBtn.addEventListener('click', bulkDeleteTransactions);

        // Inisialisasi awal
        refreshTotal();
        loadTransactions(currentPage);
        
        setInterval(refreshTotal, 30000);
        setInterval(() => loadTransactions(currentPage), 60000);
    });
</script>
@endsection>