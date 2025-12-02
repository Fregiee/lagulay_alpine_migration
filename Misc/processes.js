$(document).ready(function() {


// Logout for admin pages
$('#logoutBtn').on('click', async function() {
try {
const data = await $.ajax({
url: '../Misc/handleforms.php',
type: 'POST',
contentType: 'application/json',
data: JSON.stringify({ action: 'logout' }),
dataType: 'json'
});


  if (data.success) {
    Swal.fire('Logged out', data.message, 'success').then(() => window.location.href = '/login.php');
  } else {
    Swal.fire('Error', data.message, 'error');
  }
} catch (err) {
  console.error(err);
}


});


// Load Products for customer only
const $productList = $('#productList');
async function loadProducts() {
const userType = sessionStorage.getItem('userType');
if (!$productList.length) return;


try {
  const data = await $.ajax({
    url: '../Misc/handleforms.php',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ action: 'get_products' }),
    dataType: 'json'
  });

  if (data.success && data.products.length > 0) {
    $productList.empty();
    data.products.forEach(p => {
      const html = `
        <div class="border p-2 mb-2">
          <img src="../uploads/${p.image}" width="100"><br>
          <strong>${p.name}</strong><br>
          Price: ₱${p.price}<br><br>
          <small>Added by: ${p.added_by || 'Unknown'}</small><br><br>
          ${userType == 1 ? `
            <button class="add-to-cart-btn btn btn-sm btn-success" data-id="${p.id}" data-name="${p.name}" data-price="${p.price}">
              Add to Cart
            </button>` : userType == 2 || userType == 3 ? `
            <button class="edit-btn btn btn-sm btn-primary" data-id="${p.id}">Edit</button>
            <button class="delete-btn btn btn-sm btn-danger" data-id="${p.id}">Delete</button>` : ''}
        </div>`;
      $productList.append(html);
    });

    $productList.off('click', '.add-to-cart-btn').on('click', '.add-to-cart-btn', function() {
      const id = $(this).data('id');
      const name = $(this).data('name');
      const price = parseFloat($(this).data('price'));
      if (window.addToCart) {
        window.addToCart(id, name, price);
        Swal.fire('Added!', `${name} added to your cart.`, 'success');
      }
    });

  } else $productList.html('<p>No products available.</p>');

} catch (err) { console.error(err); $productList.html('<p>Error loading products.</p>'); }


}

loadProducts();

// Cart functionality
if (window.location.pathname.toLowerCase().includes('/customer/')) {


let cart = [];

window.addToCart = function(id, name, price) {
  const item = cart.find(x => x.id === id);
  if (item) item.quantity++;
  else cart.push({ id, name, price, quantity: 1 });
  updateCart();
};

function updateCart() {
  const $cartEl = $('#cart');
  $cartEl.empty();
  let total = 0;

  cart.forEach(item => {
    $cartEl.append(`<li class="list-group-item d-flex justify-content-between align-items-center">
      <div>${item.name} x${item.quantity}</div>
      <div>₱${item.price * item.quantity}</div>
    </li>`);
    total += item.price * item.quantity;
  });

  $('#total').text(total);
}

$('#order-btn').on('click', async function() {
  const money = parseFloat($('#money').val());
  const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);

  if (!cart.length) return Swal.fire('Empty', 'Your cart is empty.', 'warning');
  if (isNaN(money) || money <= 0) return Swal.fire('Invalid', 'Please enter a valid amount.', 'error');
  if (money < total) return Swal.fire('Insufficient', 'You don’t have enough money.', 'error');

  try {
    const data = await $.ajax({
      url: '../Misc/handleforms.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ action: 'place_order', money, product_list: cart.map(c => c.id).join(',') }),
      dataType: 'json'
    });

    Swal.fire(data.success ? 'Success' : 'Error', data.message, data.success ? 'success' : 'error');

    if (data.success) {
      cart = [];
      updateCart();
      loadTransactions();
      $('#money').val('');
    }
  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'Something went wrong placing your order.', 'error');
  }
});


//transactions
async function loadTransactions() {
  try {
    const data = await $.ajax({
      url: '../Misc/handleforms.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ action: 'get_orders' }),
      dataType: 'json'
    });

    const $container = $('#transactions');
    $container.empty();

    if (data.success && data.orders.length) {
      data.orders.forEach(o => {
        $container.append(`
          <div class="border rounded p-2 mb-2">
            <p class="mb-1"><strong>Order #${o.id}</strong></p>
            <p class="mb-1">Amount: ₱${o.money}</p>
            <p class="mb-0">Status: ${o.status}</p>
          </div>`);
      });
    } else $container.html('<p>No transactions found.</p>');

  } catch (err) { console.error(err); $('#transactions').html('<p>Error loading transactions.</p>'); }
}

loadTransactions();


}

// Admin Users
async function loadAdminUsers() {
const $container = $('#adminUsers');
if (!$container.length) return;


try {
  const data = await $.ajax({
    url: '../Misc/handleforms.php',
    type: 'POST',
    contentType: 'application/json',
    data: JSON.stringify({ action: 'get_admin_users' }),
    dataType: 'json'
  });

  if (!data.success || !data.users.length) { $container.html('<p>No admin users found.</p>'); return; }

  $container.empty();
  data.users.forEach(u => {
    const div = $(`
      <div style="border:1px solid #ccc; padding:10px; margin:5px">
        <p><strong>${u.username}</strong></p>
        <label>
          Suspension:
          <select data-id="${u.id}" class="suspend-select">
            <option value="0" ${u.suspension==0?'selected':''}>Active</option>
            <option value="1" ${u.suspension==1?'selected':''}>Suspended</option>
          </select>
        </label>
      </div>`);
    $container.append(div);
  });

  $container.off('change', '.suspend-select').on('change', '.suspend-select', async function() {
    const userId = $(this).data('id');
    const suspension = $(this).val();

    const confirmRes = await Swal.fire({
      title: 'Confirm',
      text: `Are you sure you want to ${suspension==1?'suspend':'activate'} this user?`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes'
    });

    if (!confirmRes.isConfirmed) {
      $(this).val(suspension==1?'0':'1');
      return;
    }

    const result = await $.ajax({
      url: '../Misc/handleforms.php',
      type: 'POST',
      contentType: 'application/json',
      data: JSON.stringify({ action: 'update_user_suspension', id: userId, suspension }),
      dataType: 'json'
    });

    Swal.fire(result.success?'Success':'Error', result.message, result.success?'success':'error');
  });

} catch (err) { console.error(err); $container.html('<p>Error loading admin users.</p>'); }


}

loadAdminUsers();

});
