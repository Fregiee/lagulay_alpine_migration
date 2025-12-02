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
    <script type="module" src="../Misc/processes.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>

  
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Admin Panel</h2>
            <button id="logoutBtn" class="btn btn-outline-danger btn-sm">Logout</button>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <p class="mb-1">Welcome, 
                    <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>!
                </p>
                <div class="d-flex gap-3 mt-3">
                    <a href="manageProducts.php" class="btn btn-primary btn-sm">Manage Products</a>
                    <a href="manageTransactions.php" class="btn btn-secondary btn-sm">Manage Transactions</a>
                </div>
            </div>
        </div>

        <?php if ($_SESSION['type'] == 3): ?>
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Admin Users</h5>
                    <button id="addUserBtn" class="btn btn-success btn-sm">+ Add User</button>
                </div>
                <div class="card-body" id="adminUsers">Loading...</div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Pending Orders</h5>
            </div>
            <div class="card-body" id="pendingOrders">Loading...</div>
        </div>
    </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', async () => {
            const addUserBtn = document.getElementById('addUserBtn');
            const pendingOrders = document.getElementById('pendingOrders');

            // Load pending orders
            async function loadPendingOrders() {
                const res = await fetch('../Misc/handleforms.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'get_pending_orders' })
                });
                const data = await res.json();

                if (!data.success || data.orders.length === 0) {
                    pendingOrders.innerHTML = '<p class="text-muted">No pending orders.</p>';
                    return;
                }

                pendingOrders.innerHTML = '';
                data.orders.forEach(o => {
                    const div = document.createElement('div');
                    div.className = 'border rounded p-3 mb-3 bg-white shadow-sm';
                    div.innerHTML = `
                        <p class="fw-bold mb-1">Order #${o.id} — ₱${o.money}</p>
                        <p class="mb-1"><strong>Products:</strong> ${o.product_names}</p>
                        <p class="mb-2"><span class="badge bg-warning text-dark">${o.status}</span></p>
                        <div class="d-flex gap-2">
                            <button class="approve-btn btn btn-success btn-sm" data-id="${o.id}">
                                <i class="bi bi-check-circle"></i> Approve
                            </button>
                            <button class="decline-btn btn btn-danger btn-sm" data-id="${o.id}">
                                <i class="bi bi-x-circle"></i> Decline
                            </button>
                        </div>
                    `;
                    pendingOrders.appendChild(div);
                });

                // Approve/Decline listeners
                document.querySelectorAll('.approve-btn, .decline-btn').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        const id = e.target.closest('button').dataset.id;
                        const actionType = e.target.closest('button').classList.contains('approve-btn') ? 'approve' : 'decline';
                        const confirm = await Swal.fire({
                            title: `Are you sure?`,
                            text: `You are about to ${actionType} this order.`,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: `Yes, ${actionType} it!`
                        });
                        if (!confirm.isConfirmed) return;

                        const res = await fetch('../Misc/handleforms.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({
                                action: 'update_order_status',
                                id,
                                status: actionType === 'approve' ? 'APPROVED' : 'DECLINED'
                            })
                        });
                        const result = await res.json();
                        Swal.fire(result.success ? 'Updated!' : 'Error', result.message, result.success ? 'success' : 'error');
                        if (result.success) loadPendingOrders();
                    });
                });
            }

            loadPendingOrders();

            // Add user (existing)
            if (addUserBtn) {
                addUserBtn.addEventListener('click', async () => {
                    const { value: formValues } = await Swal.fire({
                        title: 'Add New User',
                        html: `
                            <input id="swal-username" class="swal2-input" placeholder="Username">
                            <input id="swal-password" type="password" class="swal2-input" placeholder="Password">
                            <input id="swal-type" class="swal2-input" placeholder="User type (1, 2, or 3)">
                        `,
                        focusConfirm: false,
                        showCancelButton: true,
                        confirmButtonText: 'Add User',
                        preConfirm: () => {
                            return {
                                username: document.getElementById('swal-username').value,
                                password: document.getElementById('swal-password').value,
                                type: document.getElementById('swal-type').value
                            };
                        }
                    });

                    if (!formValues) return;

                    const res = await fetch('../Misc/handleforms.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'addUser',
                            username: formValues.username,
                            password: formValues.password,
                            type: formValues.type
                        })
                    });

                    const data = await res.json();
                    Swal.fire(data.success ? 'Success' : 'Error', data.message, data.success ? 'success' : 'error');
                });
            }
        });
    </script>
</body>
</html>
