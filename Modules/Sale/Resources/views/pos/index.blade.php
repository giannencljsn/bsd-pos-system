@extends('layouts.app')

@section('title', 'POS')

@section('third_party_stylesheets')

@endsection

@section('breadcrumb')
    <ol class="breadcrumb border-0 m-0">
        <li class="breadcrumb-item"><a href="{{ route('home') }}">Home</a></li>
        <li class="breadcrumb-item active">POS</li>
    </ol>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @include('utils.alerts')
            </div>
            <div class="col-lg-7">
                <livewire:search-product />
                <livewire:pos.product-list :categories="$product_categories" />
            </div>
            <div class="col-lg-5">
                <livewire:pos.checkout :cart-instance="'sale'" :customers="$customers" />
            </div>
        </div>
    </div>
@endsection

@push('page_scripts')
    <script src="{{ asset('js/jquery-mask-money.js') }}"></script>
    <script>
        // Existing jQuery maskMoney and modal code below...
        $(document).ready(function () {
            window.addEventListener('showCheckoutModal', event => {
                $('#checkoutModal').modal('show');

                $('#paid_amount').maskMoney({
                    prefix: '{{ settings()->currency->symbol }}',
                    thousands: '{{ settings()->currency->thousand_separator }}',
                    decimal: '{{ settings()->currency->decimal_separator }}',
                    allowZero: false,
                });

                $('#total_amount').maskMoney({
                    prefix: '{{ settings()->currency->symbol }}',
                    thousands: '{{ settings()->currency->thousand_separator }}',
                    decimal: '{{ settings()->currency->decimal_separator }}',
                    allowZero: true,
                });

                $('#paid_amount').maskMoney('mask');
                $('#total_amount').maskMoney('mask');

                $('#checkout-form').submit(function () {
                    var paid_amount = $('#paid_amount').maskMoney('unmasked')[0];
                    $('#paid_amount').val(paid_amount);
                    var total_amount = $('#total_amount').maskMoney('unmasked')[0];
                    $('#total_amount').val(total_amount);
                });
            });
        });
    </script>

    <script>
        // Initialize IndexedDB for offline POS caching
        const DB_NAME = 'POSDB';
        const DB_VERSION = 3;
        const STORE_PRODUCTS = 'products';

        function openDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, DB_VERSION);

                request.onupgradeneeded = event => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_PRODUCTS)) {
                        db.createObjectStore(STORE_PRODUCTS, { keyPath: 'id' });
                        console.log('IndexedDB: Object store created:', STORE_PRODUCTS);
                    }
                };

                request.onsuccess = () => {
                    console.log('IndexedDB: Database opened successfully');
                    resolve(request.result);
                };

                request.onerror = () => {
                    console.error('IndexedDB: Failed to open database', request.error);
                    reject(request.error);
                };
            });
        }

        // Open DB on page load
        document.addEventListener('DOMContentLoaded', () => {
            openDB().catch(e => console.error('IndexedDB initialization error:', e));
        });


        //Save offline data
        async function saveOfflineData(data) {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_PRODUCTS, 'readwrite');
                const store = tx.objectStore(STORE_PRODUCTS);
                const request = store.add({ ...data, synced: false });

                request.onsuccess = () => {
                    resolve(true);
                };

                request.onerror = () => {
                    reject(request.error);
                };
            });
        }

        //Get all offline data
        async function getAllOfflineData() {
            const db = await openDB();

            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_PRODUCTS, 'readonly');
                const store = tx.objectStore(STORE_PRODUCTS);
                const request = store.getAll();

                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }
        async function getAllOfflineSales() {
            const db = await openDB();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_PRODUCTS, 'readonly');
                const store = tx.objectStore(STORE_PRODUCTS);
                const request = store.getAll();

                request.onsuccess = () => resolve(request.result);
                request.onerror = () => reject(request.error);
            });
        }
        //Clear offline data
        async function clearOfflineData() {
            const db = await openDB();

            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_PRODUCTS, 'readwrite');
                const store = tx.objectStore(STORE_PRODUCTS);
                const request = store.clear();

                request.onsuccess = () => {
                    console.log("Offline data cleared.");
                    resolve(true);
                };

                request.onerror = () => reject(request.error);
            });
        }
    </script>
    <!-- Indexeddb End -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const offlineSyncBtn = document.getElementById('offlineSyncBtn');
            function updateOfflineSyncBtnVisibility() {
                if (!navigator.onLine) {
                    offlineSyncBtn.style.display = 'inline-block';  // show button if offline


                } else {
                    offlineSyncBtn.style.display = 'none'; // hide button if online
                }
            }

            // Initial check
            updateOfflineSyncBtnVisibility();

            // Listen to connection changes dynamically
            window.addEventListener('online', updateOfflineSyncBtnVisibility);
            window.addEventListener('offline', updateOfflineSyncBtnVisibility);


            //What offline sync does to data logic
            offlineSyncBtn.addEventListener('click', async () => {
                try {
                    const form = document.getElementById('checkout-form');
                    const formData = new FormData(form);

                    const dataObj = {};
                    formData.forEach((value, key) => {
                        dataObj[key] = value;
                    });

                    // Assign a unique id (e.g. timestamp)
                    dataObj.id = Date.now();

                    await saveOfflineData(dataObj);

                    alert('Data saved offline successfully.');
                } catch (error) {
                    console.error('Failed to save offline data:', error);
                    alert('Failed to save offline data.');
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
                    await clearOfflineData();
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
