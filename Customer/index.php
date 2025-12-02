<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Snack Ordering System</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { background-color: #f8f9fa; }
.menu-section, .order-section {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    padding: 20px;
}
.card img { height: 150px; object-fit: cover; }
</style>
</head>
<body class="p-3">

<!-- ====================== -->
<!--   ALPINE GLOBAL STORE  -->
<!-- ====================== -->
<script>
document.addEventListener('alpine:init', () => {

    // Global cart store
    Alpine.store('cartStore', {
        cart: [],
        addItem(prod) {
            this.cart.push({
                id: prod.id,
                name: prod.name,
                price: parseFloat(prod.price)
            });
        },
        get total() {
            return this.cart.reduce((sum, i) => sum + i.price, 0);
        }
    });

});
</script>

<!-- ====================== -->
<!--     LOGOUT COMPONENT   -->
<!-- ====================== -->
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('logoutComponent', () => ({
        async logout() {
            try {
                const response = await fetch('../Misc/handleforms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'logout' })
                });

                const data = await response.json();
                if (data.success) {
                    Swal.fire('Logged out', data.message, 'success')
                        .then(() => window.location.href = '/login.php');
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                Swal.fire('Error', 'Something went wrong.', 'error');
            }
        }
    }));
});
</script>

<!-- ====================== -->
<!--   PRODUCT MANAGER      -->
<!-- ====================== -->
<script>
document.addEventListener('alpine:init', () => {

    Alpine.data('productManager', () => ({
        products: [],
        isLoading: false,

        async loadProducts() {
            this.isLoading = true;
            try {
                const res = await fetch('../Misc/handleforms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_products' })
                });
                const data = await res.json();
                this.products = data.success ? data.products : [];
            } catch (err) {
                console.error(err);
            }
            this.isLoading = false;
        },

        addToCart(prod) {
            Alpine.store('cartStore').addItem(prod);
            Swal.fire("Added!", `${prod.name} added to cart.`, "success");
        }
    }));

    Alpine.data('paymentHandler', () => ({
        money: '',
        transactions: [],

        // Load transactions from backend
        async loadTransactions() {
            try {
                const res = await fetch('../Misc/handleforms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_orders' })
                });
                const data = await res.json();
                if (data.success) {
                    this.transactions = data.orders;
                } else {
                    this.transactions = [];
                }
            } catch (err) {
                console.error(err);
                this.transactions = [];
            }
        },

        async pay() {
            const total = Alpine.store('cartStore').total;

            if (this.money === '' || isNaN(this.money)) {
                return Swal.fire('Error', 'Please enter an amount.', 'error');
            }

            const entered = parseFloat(this.money);

            if (entered < total) {
                return Swal.fire('Insufficient', 'Not enough money!', 'error');
            }

            const cart = Alpine.store('cartStore').cart;
            const productList = cart.map(i => i.id).join(',');

            try {
                const res = await fetch('../Misc/handleforms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'place_order',
                        money: entered,
                        product_list: productList
                    })
                });

                const data = await res.json();

                if (data.success) {
                    Swal.fire('Pending', `Your order has been submitted and is awaiting approval.`, 'info');

                    Alpine.store('cartStore').cart = [];
                    this.money = '';

                    this.loadTransactions(); // refresh transaction list
                } else {
                    Swal.fire('Error', data.message, 'error');
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Failed to place order.', 'error');
            }
        },

        init() {
            this.loadTransactions();
        }
    }));

});
</script>

<!-- ====================== -->
<!--       PAGE UI          -->
<!-- ====================== -->

<!-- Logout Bar -->
<div class="d-flex justify-content-between align-items-center mb-3" x-data="logoutComponent()">
  <p class="h5 mb-0">Welcome Customer</p>
  <button @click="logout" class="btn btn-outline-danger btn-sm">Logout</button>
</div>

<div class="container-fluid">
  <div class="row g-3">

    <!-- Menu Section -->
    <div class="col-md-8" x-data="productManager()" x-init="loadProducts()">
      <div class="menu-section p-3">
        <h4 class="mb-3">Menu</h4>

        <template x-if="isLoading">
          <p>Loading products...</p>
        </template>

        <template x-if="!isLoading && products.length === 0">
          <p>No products available.</p>
        </template>

        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-3">
          <template x-for="prod in products" :key="prod.id">
            <div class="border p-2 mb-2 rounded">
              <img :src="'../uploads/' + prod.image" class="img-fluid mb-2 rounded">
              <strong x-text="prod.name"></strong><br>
              Price: ₱<span x-text="prod.price"></span><br><br>
              <small>Added by: <span x-text="prod.added_by || 'Unknown'"></span></small>
              <br><br>
              <button class="btn btn-sm btn-success w-100" @click="addToCart(prod)">
                Add to Cart
              </button>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- Order & Transactions Section -->
    <div class="col-md-4" x-data="paymentHandler()" x-init="init()">
      <div class="order-section p-3">
        <h4 class="mb-3">Ordered Items</h4>

        <ul class="list-group mb-3">
          <template x-for="item in $store.cartStore.cart">
            <li class="list-group-item d-flex justify-content-between">
              <span x-text="item.name"></span>
              <span>₱<span x-text="item.price"></span></span>
            </li>
          </template>
        </ul>

        <h5>Total: ₱<span x-text="$store.cartStore.total"></span></h5>

        <input type="number" class="form-control mb-2" placeholder="Enter amount" x-model="money">
        <button class="btn btn-success w-100" @click="pay()">Pay!</button>
      </div>

      <div class="order-section p-3 mt-3">
        <h4>Your Transactions</h4>
        <ul class="list-group mt-2">
            <template x-for="t in transactions" :key="t.id">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Order #<span x-text="t.id"></span></strong><br>
                        Items: <span x-text="t.product_list"></span><br>
                        Amount: ₱<span x-text="t.money"></span>
                    </div>
                    <span class="badge" :class="t.status==='Pending'?'bg-warning text-dark':(t.status==='APPROVED'?'bg-success':'bg-danger')" x-text="t.status"></span>
                </li>
            </template>
            <template x-if="transactions.length === 0">
                <li class="list-group-item text-center">No transactions yet</li>
            </template>
        </ul>
      </div>
    </div>

  </div>
</div>

</body>
</html>
