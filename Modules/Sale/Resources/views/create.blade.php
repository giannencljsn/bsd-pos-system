@extends('layouts.app')

@section('title', 'Create Sale')

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item"><a href="{{ route('sales.index') }}">Sales</a></li>
        <li class="breadcrumb-item active">Add</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid mb-4">
        <div class="row">
            <div class="col-12">
                <livewire:search-product />
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        @include('utils.alerts')
                        <form id="sale-form" action="{{ route('sales.store') }}" method="POST">
                            @csrf

                            <div class="form-row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="reference">Reference <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="reference" required readonly
                                            value="SL">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="from-group">
                                        <div class="form-group">
                                            <label for="customer_id">Customer <span class="text-danger">*</span></label>
                                            <select class="form-control" name="customer_id" id="customer_id" required>
                                                @foreach(\Modules\People\Entities\Customer::all() as $customer)
                                                    <option value="{{ $customer->id }}">{{ $customer->customer_name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="from-group">
                                        <div class="form-group">
                                            <label for="date">Date <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="date" required
                                                value="{{ \Carbon\Carbon::now()->format('Y-m-d') }}">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <livewire:product-cart :cartInstance="'sale'" />

                            <div class="form-row">
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="status">Status <span class="text-danger">*</span></label>
                                        <select class="form-control" name="status" id="status" required>
                                            <option value="Pending">Pending</option>
                                            <option value="Shipped">Shipped</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="payment_status">Payment Status <span
                                                class="text-danger">*</span></label>
                                        <select class="form-control" name="payment_status" id="payment_status" required>
                                            <option value="Paid">Paid</option>
                                            <option value="Unpaid">Unpaid</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="from-group">
                                        <div class="form-group">
                                            <label for="payment_method">Payment Method <span
                                                    class="text-danger">*</span></label>
                                            <select class="form-control" name="payment_method" id="payment_method" required>
                                                <option value="Cash">Cash</option>
                                                <option value="Credit Card">Credit Card</option>
                                                <option value="Bank Transfer">Bank Transfer</option>
                                                <option value="Cheque">Cheque</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label for="paid_amount">Amount Received <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <input id="paid_amount" type="text" class="form-control" name="paid_amount"
                                                required>
                                            <div class="input-group-append">
                                                <button id="getTotalAmount" class="btn btn-primary" type="button">
                                                    <i class="bi bi-check-square"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="note">Note (If Needed)</label>
                                <textarea name="note" id="note" rows="5" class="form-control"></textarea>
                            </div>

                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    Create Sale <i class="bi bi-check"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="{{ asset('js/jquery-mask-money.js') }}"></script>
    <script>
        $(document).ready(function () {
            $('#paid_amount').maskMoney({
                prefix: '{{ settings()->currency->symbol }}',
                thousands: '{{ settings()->currency->thousand_separator }}',
                decimal: '{{ settings()->currency->decimal_separator }}',
                allowZero: true,
            });

            $('#getTotalAmount').click(function () {
                $('#paid_amount').maskMoney('mask', {{ Cart::instance('sale')->total() }});
            });

            $('#sale-form').submit(function () {
                var paid_amount = $('#paid_amount').maskMoney('unmasked')[0];
                $('#paid_amount').val(paid_amount);
            });
        });
    </script>


    <!-- IndexedDB Offline Functionality -->

    <script>
        const DB_NAME = 'SaleOfflineDB';
        const DB_VERSION = 1;
        const STORE_NAME = 'sales';

        function openDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onupgradeneeded = event => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
                        console.log('IndexedDB: Object store created:', STORE_NAME);
                    }
                };

                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }

        async function saveOfflineSale(data) {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                const store = tx.objectStore(STORE_NAME);
                const request = store.add(data);

                request.onsuccess = () => resolve(true);
                request.onerror = () => reject(request.error);
            });
        }

        async function getAllOfflineSales() {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readonly');
                const store = tx.objectStore(STORE_NAME);
                const request = store.getAll();

                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }

        async function clearOfflineSales() {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                const store = tx.objectStore(STORE_NAME);
                const request = store.clear();

                request.onsuccess = () => resolve(true);
                request.onerror = () => reject(request.error);
            });
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const saleForm = document.getElementById('sale-form');

            saleForm.addEventListener('submit', async function (e) {
                e.preventDefault();

                // Gather form data as object
                const formData = new FormData(saleForm);
                const dataObj = {};
                formData.forEach((val, key) => dataObj[key] = val);

                // Convert paid_amount masked string to number
                const paidAmountMasked = dataObj.paid_amount || '';
                const unmaskedPaidAmount = $('#paid_amount').maskMoney('unmasked')[0] || 0;
                dataObj.paid_amount = unmaskedPaidAmount;

                if (!navigator.onLine) {
                    // Save offline
                    try {
                        await saveOfflineSale(dataObj);
                        alert('You are offline. Sale saved locally and will sync when back online.');
                        saleForm.reset();
                    } catch (err) {
                        alert('Failed to save sale offline: ' + err);
                    }
                } else {
                    // Online, submit via AJAX (optional) or normal submit
                    try {
                        // Use fetch for AJAX submit
                        const token = document.querySelector('input[name=_token]').value;
                        const response = await fetch(saleForm.action, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(dataObj),
                        });

                        if (response.ok) {
                            alert('Sale submitted successfully!');
                            saleForm.reset();
                        } else {
                            const errorText = await response.text();
                            alert('Submit failed: ' + errorText);
                        }
                    } catch (error) {
                        alert('Submit error: ' + error.message);
                    }
                }
            });
        });
    </script>
    <script>
        async function syncOfflineSales() {
            if (!navigator.onLine) return;

            const offlineSales = await getAllOfflineSales();
            if (offlineSales.length === 0) return;

            try {
                const token = document.querySelector('input[name=_token]').value;
                const response = await fetch('/api/sync-offline-sales', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ records: offlineSales }),
                });

                if (response.ok) {
                    await clearOfflineSales();
                    console.log('Offline sales synced successfully!');
                    alert('Offline sales synced to server.');
                    location.reload();
                } else {
                    console.error('Sync failed:', await response.text());
                }
            } catch (err) {
                console.error('Error syncing offline sales:', err);
            }
        }

        window.addEventListener('online', syncOfflineSales);

        // Optional: periodic sync every 30 seconds
        setInterval(syncOfflineSales, 30000);

        // Also try to sync on page load if online
        document.addEventListener('DOMContentLoaded', () => {
            if (navigator.onLine) {
                syncOfflineSales();
            }
        });
    </script>

@endpush