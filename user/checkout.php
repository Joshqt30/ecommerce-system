<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

  // Handle order placement (Step 1: collect shipping, go to payment)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
      // Collect shipping details
      $shipping = [
          'first_name' => trim($_POST['first_name'] ?? ''),
          'last_name'  => trim($_POST['last_name'] ?? ''),
          'email'      => trim($_POST['email'] ?? ''),
          'phone'      => trim($_POST['phone'] ?? ''),
          'address'    => trim($_POST['address'] ?? ''),
          'city'       => trim($_POST['city'] ?? ''),
          'state'      => trim($_POST['state'] ?? ''),
          'postal_code'=> trim($_POST['postal_code'] ?? '')
      ];

      // Basic validation
      if (empty($shipping['first_name']) || empty($shipping['last_name']) || empty($shipping['email']) || empty($shipping['address']) || empty($shipping['city'])) {
          die("Please fill all required fields.");
      }

      // Store shipping details in session
      $_SESSION['shipping_details'] = $shipping;

      // Redirect to payment page
      header("Location: payment.php");
      exit;
  }


// Base image path (same as viewitems)
define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title> 
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; }
    .step-dot { width: 8px; height: 8px; background: #9ca3af; border-radius: 50%; }
    .step-active .step-dot { background: #3b82f6; }
  </style>
</head>
<body class="min-h-screen py-12">

<div class="max-w-6xl mx-auto px-6">
  <form method="POST" id="checkoutForm">
    <input type="hidden" name="cart_data" id="cartDataInput">
    <input type="hidden" name="place_order" value="1">

    <div class="flex gap-8">

      <!-- LEFT: Order Summary -->
      <div class="w-5/12 bg-white rounded-2xl shadow-sm p-8" style="height:fit-content;">
        <div class="flex items-center gap-3 mb-8">
          <button type="button" onclick="history.back()"
                  class="group flex items-center justify-center w-11 h-11 bg-gray-100 hover:bg-gray-200 rounded-2xl transition-all hover:scale-110 active:scale-95">
            <span class="text-2xl text-gray-500 group-hover:text-gray-700 transition">←</span>
          </button>
          <h2 class="text-2xl font-semibold text-gray-800">Order Summary</h2>
        </div>

        <!-- Cart items injected here -->
        <div id="cartItemsContainer"></div>

        <!-- Discount -->
        <div class="flex gap-3 mb-6">
          <input type="text" id="discountInput" placeholder="Gift Card / Discount code"
                 class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-400 transition">
          <button type="button" onclick="applyDiscount()"
                  class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 rounded-xl font-medium text-sm transition">Apply</button>
        </div>

        <!-- Totals -->
        <div class="space-y-3 text-sm">
          <div class="flex justify-between text-gray-500">
            <span>Subtotal</span><span id="subtotal" class="font-medium text-gray-700">₱0.00</span>
          </div>
          <div class="flex justify-between text-gray-500">
            <span>Shipping Fee</span><span id="shippingLabel" class="font-medium text-green-600">FREE</span>
          </div>
          <div class="flex justify-between pt-4 border-t border-gray-200 text-base">
            <span class="font-semibold text-gray-800">Total due</span>
            <span id="total" class="font-semibold text-indigo-600">₱0.00</span>
          </div>
        </div>
      </div>
      
      <!-- RIGHT: Shipping Form -->
      <div class="w-7/12 bg-white rounded-xl shadow-sm p-8">
        <!-- Simplified Progress Step -->
        <div class="flex items-center gap-4 mb-8">
          <div class="flex items-center gap-2 step-active">
            <div class="step-dot"></div>
            <span class="font-medium text-blue-600">Shipping & Delivery</span>
          </div>
          <div class="flex-1 h-px bg-gray-300"></div>
          <div class="flex items-center gap-2 text-gray-400">
            <div class="step-dot"></div>
            <span>Payment</span>
          </div>
        </div>

        <h3 class="text-blue-600 font-semibold mb-6">Contact Details</h3>

        <div class="grid grid-cols-2 gap-4 mb-6">
          <input type="text" name="first_name" placeholder="First Name" required
                 class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
          <input type="text" name="last_name" placeholder="Last Name" required
                 class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
        </div>

        <input type="email" name="email" placeholder="Email" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-6 focus:outline-none focus:border-blue-500">

        <input type="tel" name="phone" value="+63" placeholder="Phone Number" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-8 focus:outline-none focus:border-blue-500">

        <h3 class="text-blue-600 font-semibold mb-6">Shipping Details</h3>

        <input type="text" name="flat_no" placeholder="Flat/House no."
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-4 focus:outline-none focus:border-blue-500">

        <input type="text" name="address" placeholder="Address" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-4 focus:outline-none focus:border-blue-500">

        <div class="grid grid-cols-2 gap-4 mb-6">
          <input type="text" name="city" placeholder="City" required
                 class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
          <input type="text" name="state" placeholder="State" required
                 class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
        </div>

        <input type="text" name="postal_code" placeholder="Postal Code" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-8 focus:outline-none focus:border-blue-500">

        <!-- Delivery Estimate (static, clean) -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
          <div class="flex items-center gap-3 mb-2">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2">
              <rect x="1" y="3" width="22" height="16" rx="2" ry="2"/>
              <line x1="1" y1="10" x2="23" y2="10"/>
            </svg>
            <span class="font-semibold text-gray-800">Estimated Delivery</span>
          </div>
          <p class="text-sm text-gray-700">
            <strong>3–5 business days</strong> after order confirmation.<br>
            <span class="text-green-600 font-medium">Free standard shipping</span>
          </p>
        </div>

        <label class="flex items-center gap-3 mb-8 cursor-pointer">
          <input type="checkbox" checked class="w-5 h-5 accent-blue-600">
          <span class="text-gray-700">My shipping and Billing address are the same</span>
        </label>

        <button type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-4 rounded-xl transition text-lg">
          Place Order
        </button>
      </div>
    </div>
  </form>
</div>

<script>
  // Base path for images (must match PHP constant)
  const PRODUCT_IMGS_BASE = '/ecommerce-system/imgs/products/';

  // Load cart from localStorage
  let cart = JSON.parse(localStorage.getItem('ecommerce_cart') || '[]');

  // If cart is empty, show message
  if (cart.length === 0) {
    document.body.innerHTML = '<div class="text-center py-20"><h2 class="text-xl font-semibold">Your cart is empty</h2><a href="dashboard.php" class="text-blue-600 mt-4 inline-block">Continue Shopping</a></div>';
  }

  // Store cart data in hidden input for PHP
  document.getElementById('cartDataInput').value = JSON.stringify(cart);

  // Render cart items
  function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    let subtotal = 0;
    let html = '';

    cart.forEach((item, index) => {
      const itemTotal = item.price * item.quantity;
      subtotal += itemTotal;

      html += `
        <div class="flex gap-4 border border-gray-200 rounded-xl p-4 mb-4" data-index="${index}">
          <img src="${PRODUCT_IMGS_BASE}${item.image}" alt="${item.name}"
               class="w-24 h-16 object-contain bg-gray-50 rounded-lg flex-shrink-0">
          <div class="flex-1">
            <p class="font-medium text-sm leading-snug text-gray-800">${item.name}</p>
            <div class="mt-3 flex items-center justify-between">
              <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                <button type="button" onclick="updateQty(${index}, -1)" class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">−</button>
                <span class="w-10 text-center text-sm font-semibold">${item.quantity}</span>
                <button type="button" onclick="updateQty(${index}, 1)" class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">+</button>
              </div>
              <span class="font-semibold text-gray-800">₱${itemTotal.toFixed(2)}</span>
            </div>
          </div>
          <button type="button" onclick="removeItem(${index})" class="text-gray-300 hover:text-red-400 transition self-start">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </div>
      `;
    });

    container.innerHTML = html;
    updateTotals(subtotal);
  }

  function updateTotals(subtotal) {
    document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('total').textContent = `₱${subtotal.toFixed(2)}`;
  }

  window.updateQty = (index, delta) => {
    const item = cart[index];
    if (!item) return;
    const newQty = item.quantity + delta;
    if (newQty < 1) return;
    item.quantity = newQty;
    saveCart();
  };

  window.removeItem = (index) => {
    cart.splice(index, 1);
    saveCart();
  };

  function saveCart() {
    localStorage.setItem('ecommerce_cart', JSON.stringify(cart));
    document.getElementById('cartDataInput').value = JSON.stringify(cart);
    renderCart();
    if (cart.length === 0) {
      location.reload();
    }
  }

  function applyDiscount() {
    alert('Discount codes not implemented in demo.');
  }

  // Initial render
  renderCart();
</script>

</body>
</html>