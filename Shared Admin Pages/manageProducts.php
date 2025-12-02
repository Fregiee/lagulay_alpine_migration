<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script type="module" src="../Misc/processes.js" defer></script>
</head>
<body>
    <h2>Add products, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

     <h3>Available Products</h3>
    <div x-data="addProduct" x-init="loadProducts()">

    <form @submit.prevent="addProduct($event)">
        <input type="text" name="name" placeholder="Product Name" required>
        <input type="file" name="image" accept="image/*" required>
        <input type="text" name="price" placeholder="Price" required>
        <button type="submit">Add Product</button>
    </form>

    <template x-for="prod in products" :key="prod.id">
        <div class="border p-2 mb-2">
            <img :src="'../uploads/' + prod.image" width="100">
            <strong x-text="prod.name"></strong>
            Price: ₱<span x-text="prod.price"></span>
            <button @click="editProduct(prod.id)">Edit</button>
            <button @click="deleteProduct(prod.id)">Delete</button>
        </div>
    </template>

</div>

</body>


<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('addProduct', () => ({
        products: [],
        isLoading: false,

        async addProduct(e) {
            const form = e.target;
            const formData = new FormData(form);
            formData.append('action', 'addProduct');

            try {
                const res = await fetch('../Misc/handleforms.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (data.success) {
                    Swal.fire('Success', data.message, 'success');
                    form.reset();

                    
                    this.loadProducts();
                } else {
                    Swal.fire('Error', data.message, 'error');
                }

            } catch (err) {
                console.error(err);
            }
        },
         async loadProducts() {
        this.isLoading = true;

        try {
            const res = await fetch('../Misc/handleforms.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'get_products' })
            });

            const data = await res.json();

            if (data.success) {
                this.products = data.products;
            } else {
                this.products = [];
            }

        } catch (err) {
            console.error(err);
            this.products = [];
        }

        this.isLoading = false;
    },

    
    async deleteProduct(id) {
        const confirmDelete = await Swal.fire({
            title: 'Are you sure?',
            text: 'This product will be deleted permanently.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!'
        });

        if (!confirmDelete.isConfirmed) return;

        const res = await fetch('../Misc/handleforms.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'deleteProduct', id })
        });

        const data = await res.json();

        Swal.fire(data.success ? 'Deleted!' : 'Error', data.message, data.success ? 'success' : 'error');

        if (data.success) this.loadProducts();
    },

    
    async editProduct(id) {
        const name = prompt('Enter new product name:');
        const price = prompt('Enter new price:');

        if (!name || !price) return;

        const res = await fetch('../Misc/handleforms.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'editProduct', id, name, price })
        });

        const data = await res.json();

        Swal.fire(data.success ? 'Updated!' : 'Error', data.message, data.success ? 'success' : 'error');

        if (data.success) this.loadProducts();
    }
    }));
    
});
</script>
</html>
