<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Offline POS with Bootstrap & IndexedDB</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            padding: 20px;
        }

        .product-card {
            cursor: pointer;
        }

        .badge-stock {
            position: absolute;
            top: 10px;
            left: 10px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1 class="mb-4">POS System (Offline Demo)</h1>

        <div id="status" class="mb-3"></div>

        <div class="row" id="product-list">
            <!-- Products will be inserted here -->
        </div>

        <h3 class="mt-4">Cart</h3>
        <table class="table table-striped" id="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <!-- Cart items here -->
            </tbody>
        </table>

        <div class="text-right font-weight-bold" id="cart-total">Total: ₱0.00</div>

        <button class="btn btn-danger mt-3" id="clear-cart">Clear Cart</button>
    </div>

    <!-- Bootstrap & jQuery JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // ===== Offline status display =====
        const statusDiv = document.getElementById('status');
        function updateOnlineStatus() {
            if (navigator.onLine) {
                statusDiv.textContent = 'Status: Online';
                statusDiv.className = 'alert alert-success';
            } else {
                statusDiv.textContent = 'Status: Offline';
                statusDiv.className = 'alert alert-warning';
            }
        }
        window.addEventListener('online', updateOnlineStatus);
        window.addEventListener('offline', updateOnlineStatus);
        updateOnlineStatus();

        // ===== IndexedDB setup =====
        let db;
        const DB_NAME = 'pos_db';
        const STORE_NAME = 'cart_items';

        function openDB() {
            return new Promise((resolve, reject) => {
                const request = indexedDB.open(DB_NAME, 1);
                request.onerror = () => reject('Failed to open DB');
                request.onsuccess = () => {
                    db = request.result;
                    resolve(db);
                };
                request.onupgradeneeded = e => {
                    db = e.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        db.createObjectStore(STORE_NAME, { keyPath: 'id' });
                    }
                };
            });
        }

        function getCartItems() {
            return new Promise((resolve) => {
                const tx = db.transaction(STORE_NAME, 'readonly');
                const store = tx.objectStore(STORE_NAME);
                const items = [];
                store.openCursor().onsuccess = e => {
                    const cursor = e.target.result;
                    if (cursor) {
                        items.push(cursor.value);
                        cursor.continue();
                    } else {
                        resolve(items);
                    }
                };
            });
        }

        function saveCartItem(item) {
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                const store = tx.objectStore(STORE_NAME);
                const request = store.put(item);
                request.onsuccess = () => resolve();
                request.onerror = () => reject('Failed to save item');
            });
        }

        function deleteCartItem(id) {
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                const store = tx.objectStore(STORE_NAME);
                const request = store.delete(id);
                request.onsuccess = () => resolve();
                request.onerror = () => reject('Failed to delete item');
            });
        }

        function clearCart() {
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                const store = tx.objectStore(STORE_NAME);
                const request = store.clear();
                request.onsuccess = () => resolve();
                request.onerror = () => reject('Failed to clear cart');
            });
        }

        // ===== Sample products =====
        const products = [
            { id: 1, name: 'Oppo A3', price: 80000, stock: 50, image: 'https://via.placeholder.com/200?text=Oppo+A3' },
            { id: 2, name: 'Samsung Galaxy S21', price: 90000, stock: 30, image: 'https://via.placeholder.com/200?text=Samsung+S21' },
            { id: 3, name: 'iPhone 13', price: 120000, stock: 20, image: 'https://via.placeholder.com/200?text=iPhone+13' }
        ];

        // ===== Render product list =====
        function renderProducts() {
            const container = document.getElementById('product-list');
            container.innerHTML = '';
            products.forEach(p => {
                const div = document.createElement('div');
                div.className = 'col-md-4 mb-3';
                div.innerHTML = `
      <div class="card product-card position-relative">
        <img src="${p.image}" class="card-img-top" alt="${p.name}">
        <div class="badge badge-info badge-stock">Stock: ${p.stock}</div>
        <div class="card-body">
          <h5 class="card-title">${p.name}</h5>
          <p class="card-text font-weight-bold">₱${p.price.toLocaleString()}</p>
          <button class="btn btn-primary btn-block add-to-cart" data-id="${p.id}">Add to Cart</button>
        </div>
      </div>
    `;
                container.appendChild(div);
            });
        }

        // ===== Cart management =====
        async function renderCart() {
            const cartItems = await getCartItems();
            const tbody = document.querySelector('#cart-table tbody');
            tbody.innerHTML = '';

            let total = 0;
            cartItems.forEach(item => {
                total += item.price * item.quantity;
                const tr = document.createElement('tr');
                tr.innerHTML = `
      <td>${item.name}</td>
      <td>₱${item.price.toLocaleString()}</td>
      <td><input type="number" class="form-control form-control-sm quantity" data-id="${item.id}" min="1" value="${item.quantity}"></td>
      <td><button class="btn btn-sm btn-danger remove-item" data-id="${item.id}"><i class="bi bi-trash"></i></button></td>
    `;
                tbody.appendChild(tr);
            });

            document.getElementById('cart-total').textContent = `Total: ₱${total.toLocaleString()}`;

            // Attach event listeners to quantity inputs and remove buttons
            document.querySelectorAll('.quantity').forEach(input => {
                input.onchange = async function () {
                    const id = parseInt(this.dataset.id);
                    let qty = parseInt(this.value);
                    if (qty < 1) qty = 1;
                    this.value = qty;
                    const cartItems = await getCartItems();
                    const item = cartItems.find(i => i.id === id);
                    if (item) {
                        item.quantity = qty;
                        await saveCartItem(item);
                        renderCart();
                    }
                };
            });

            document.querySelectorAll('.remove-item').forEach(btn => {
                btn.onclick = async function () {
                    const id = parseInt(this.dataset.id);
                    await deleteCartItem(id);
                    renderCart();
                };
            });
        }

        // ===== Add product to cart =====
        async function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return alert('Product not found');

            const cartItems = await getCartItems();
            let cartItem = cartItems.find(i => i.id === productId);
            if (cartItem) {
                if (cartItem.quantity < product.stock) {
                    cartItem.quantity++;
                } else {
                    return alert('No more stock available');
                }
            } else {
                cartItem = { id: product.id, name: product.name, price: product.price, quantity: 1 };
                cartItems.push(cartItem);
            }
            await saveCartItem(cartItem);
            renderCart();
        }

        // ===== Clear cart =====
        document.getElementById('clear-cart').onclick = async () => {
            if (confirm('Clear cart?')) {
                await clearCart();
                renderCart();
            }
        };

        // ===== Setup =====
        (async () => {
            await openDB();
            renderProducts();
            renderCart();

            // Add to cart button handlers
            document.getElementById('product-list').addEventListener('click', e => {
                if (e.target.classList.contains('add-to-cart')) {
                    const id = parseInt(e.target.dataset.id);
                    addToCart(id);
                }
            });
        })();
    </script>
</body>

</html>