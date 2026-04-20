<?php
session_start();
include '../config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Base image path
define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

// Shipping details from previous step (passed via session or GET)
// We'll store them in session when coming from checkout
$shipping = $_SESSION['shipping_details'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment – E‑Commerce</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    /* Copy all your existing payment.css styles here, then we'll add/modify */
    <?php echo file_get_contents('../assets/css/payment.css'); ?>
  </style>
</head>
<body class="min-h-screen py-12">

<div class="max-w-6xl mx-auto px-6">
  <div class="flex gap-8">

    <!-- LEFT: Order Summary (dynamic) -->
    <div class="w-5/12 bg-white rounded-2xl shadow-sm p-8" style="height:fit-content;">
      <div class="flex items-center gap-3 mb-8">
        <button onclick="history.back()"
                class="group flex items-center justify-center w-11 h-11 bg-gray-100 hover:bg-gray-200 rounded-2xl transition-all hover:scale-110 active:scale-95">
          <span class="text-2xl text-gray-500 group-hover:text-gray-700 transition">←</span>
        </button>
        <h2 class="text-2xl font-semibold text-gray-800">Order Summary</h2>
      </div>

      <div id="cartItemsContainer"></div>

      <!-- Totals -->
      <div class="space-y-3 text-sm mt-4">
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

    <!-- RIGHT: Payment Methods -->
    <div class="w-7/12 bg-white rounded-2xl shadow-sm p-8">

      <!-- Progress Steps -->
      <div class="flex items-center mb-10">
        <div class="flex items-center gap-2">
          <div class="step-dot done"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg></div>
          <span class="text-sm font-medium text-indigo-500">Shipping</span>
        </div>
        <div class="step-line done"></div>
       
        <div class="step-line done"></div>
        <div class="flex items-center gap-2">
          <div class="step-dot active"><svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3" fill="#6366f1"/></svg></div>
          <span class="text-sm font-semibold text-indigo-600">Payment</span>
        </div>
      </div>

      <!-- Secure Checkout Badge -->
      <div class="flex items-center justify-end mb-2 text-gray-500 text-xs">
          <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
          </svg>
          <span>SSL Secure • 256-bit Encryption</span>
      </div>

      <h3 class="text-lg font-semibold text-gray-800 mb-5">Payment Methods</h3>

      <form id="paymentForm" method="POST" action="../includes/process-payment.php">
        <input type="hidden" name="cart_data" id="cartDataInput">
        <input type="hidden" name="shipping_data" value="<?= htmlspecialchars(json_encode($shipping)) ?>">

        <!-- Option 1: Cash on Delivery (COD) -->
        <label class="payment-option selected" id="opt-cod" onclick="selectMethod('cod')">
          <div class="flex gap-3">
            <input type="radio" name="payment_method" value="cod" checked>
            <div>
              <p class="font-medium text-sm text-gray-800">Cash on Delivery (COD)</p>
              <p class="text-xs text-gray-400 mt-0.5">Pay with cash when your order arrives</p>
            </div>
          </div>
        </label>

        <!-- Option 2: GCash -->
        <label class="payment-option" id="opt-gcash" onclick="selectMethod('gcash')">
          <div class="flex gap-3 flex-1">
            <input type="radio" name="payment_method" value="gcash">
            <div class="flex-1">
              <p class="font-medium text-sm text-gray-800">GCash</p>
              <p class="text-xs text-gray-400 mt-0.5">Pay using your GCash wallet</p>
              <div class="sub-form" id="gcashForm">
                <div class="input-wrap">
                  <span class="input-icon">📱</span>
                  <input class="sub-input" type="tel" name="gcash_number" placeholder="GCash mobile number (09xxxxxxxxx)" maxlength="11">
                </div>
              </div>
            </div>
          </div>
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fc/GCash_logo.svg/200px-GCash_logo.svg.png" alt="GCash" class="h-6">
        </label>

        <!-- Option 3: Maya -->
        <label class="payment-option" id="opt-maya" onclick="selectMethod('maya')">
          <div class="flex gap-3 flex-1">
            <input type="radio" name="payment_method" value="maya">
            <div class="flex-1">
              <p class="font-medium text-sm text-gray-800">Maya</p>
              <p class="text-xs text-gray-400 mt-0.5">Pay with your Maya account</p>
              <div class="sub-form" id="mayaForm">
                <div class="input-wrap">
                  <span class="input-icon">📱</span>
                  <input class="sub-input" type="tel" name="maya_number" placeholder="Maya registered mobile number">
                </div>
              </div>
            </div>
          </div>
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4f/Maya_%28Philippines%29_logo.svg/200px-Maya_%28Philippines%29_logo.svg.png" alt="Maya" class="h-6">
        </label>

        <!-- Option 4: Bank Transfer (BDO/BPI) -->
        <label class="payment-option" id="opt-bank" onclick="selectMethod('bank')">
          <div class="flex gap-3 flex-1">
            <input type="radio" name="payment_method" value="bank">
            <div class="flex-1">
              <p class="font-medium text-sm text-gray-800">Bank Transfer</p>
              <p class="text-xs text-gray-400 mt-0.5">BDO, BPI, Metrobank, etc.</p>
              <div class="sub-form" id="bankForm">
                <select name="bank_name" class="sub-input mb-2">
                  <option value="">Select Bank</option>
                  <option>BDO</option>
                  <option>BPI</option>
                  <option>Metrobank</option>
                  <option>Landbank</option>
                  <option>UnionBank</option>
                </select>
                <div class="input-wrap">
                  <span class="input-icon">🔢</span>
                  <input class="sub-input" type="text" name="account_number" placeholder="Account number">
                </div>
                <div class="input-wrap">
                  <span class="input-icon">👤</span>
                  <input class="sub-input" type="text" name="account_name" placeholder="Account holder name">
                </div>
              </div>
            </div>
          </div>
        </label>

        <!-- Option 5: Credit/Debit Card -->
        <label class="payment-option" id="opt-card" onclick="selectMethod('card')">
          <div class="flex gap-3 flex-1">
            <input type="radio" name="payment_method" value="card">
            <div class="flex-1">
              <p class="font-medium text-sm text-gray-800">Credit/Debit Card</p>
              <p class="text-xs text-gray-400 mt-0.5">Visa, Mastercard, JCB</p>
              <div class="sub-form" id="cardForm">
                <div class="input-wrap">
                  <span class="input-icon">💳</span>
                  <input class="sub-input" type="text" name="card_number" placeholder="Card number" maxlength="19" oninput="formatCard(this)">
                </div>
                <div style="display:flex; gap:10px;">
                  <div class="input-wrap" style="flex:1;">
                    <input class="sub-input" type="text" name="expiry" placeholder="MM / YY" maxlength="7" oninput="formatExpiry(this)">
                  </div>
                  <div class="input-wrap" style="flex:1;">
                    <input class="sub-input" type="text" name="cvv" placeholder="CVV" maxlength="4">
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="flex gap-1">
            <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" class="h-5">
            <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" class="h-5">
          </div>
        </label>

        <!-- Actions -->
        <div class="flex gap-4 mt-8">
          <button type="button" onclick="history.back()"
                  class="flex-none px-8 py-3 border border-gray-300 rounded-xl text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
            Back
          </button>
         <button type="submit" id="confirmPaymentBtn"
                class="flex-1 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
          <span id="btnText">Confirm Payment</span>
          <svg id="btnSpinner" class="hidden animate-spin ml-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </button>
        </div>
      </form>

    </div><!-- /right -->
  </div>
</div>

<!-- Modal (same as before) -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal-box">
    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h2 class="text-xl font-bold text-gray-800 mb-2">Order Confirmed!</h2>
    <p class="text-sm text-gray-500 mb-6">Thank you for your purchase.</p>
    <button onclick="window.location.href='order_success.php?id=' + orderId"
            class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-3 rounded-xl transition text-sm">
      View Order
    </button>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
  const PRODUCT_IMGS_BASE = '/ecommerce-system/imgs/products/';
  let cart = JSON.parse(localStorage.getItem('ecommerce_cart') || '[]');
  
  // Redirect if cart empty
  if (cart.length === 0) {
    window.location.href = 'cart.php';
  }

  // Populate hidden input
  document.getElementById('cartDataInput').value = JSON.stringify(cart);

  // Render cart items
  function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    let subtotal = 0;
    let html = '';
    cart.forEach((item, idx) => {
      const itemTotal = item.price * item.quantity;
      subtotal += itemTotal;
      html += `
        <div class="flex gap-4 border border-gray-200 rounded-xl p-4 mb-4">
          <img src="${PRODUCT_IMGS_BASE}${item.image}" class="w-24 h-16 object-contain bg-gray-50 rounded-lg">
          <div class="flex-1">
            <p class="font-medium text-sm">${item.name}</p>
            <div class="mt-2 flex justify-between">
              <span>Qty: ${item.quantity}</span>
              <span class="font-semibold">₱${itemTotal.toFixed(2)}</span>
            </div>
          </div>
        </div>
      `;
    });
    container.innerHTML = html;
    document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('total').textContent = `₱${subtotal.toFixed(2)}`;
  }

  // Payment method selection (show/hide sub-forms)
  function selectMethod(method) {
    document.querySelectorAll('.payment-option').forEach(el => el.classList.remove('selected'));
    document.getElementById('opt-' + method).classList.add('selected');
    document.querySelectorAll('input[name="payment_method"]').forEach(r => r.checked = (r.value === method));
    
    // Hide all sub-forms
    document.querySelectorAll('.sub-form').forEach(f => f.classList.remove('open'));
    const form = document.getElementById(method + 'Form');
    if (form) form.classList.add('open');
  }

  // Card formatting
  function formatCard(input) {
    let v = input.value.replace(/\D/g, '').substring(0,16);
    input.value = v.replace(/(.{4})/g, '$1 ').trim();
  }
  function formatExpiry(input) {
    let v = input.value.replace(/\D/g, '').substring(0,4);
    if (v.length >= 3) v = v.substring(0,2) + ' / ' + v.substring(2);
    input.value = v;
  }

  // Toast
  function showToast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2600);
  }

  document.getElementById('paymentForm').addEventListener('submit', function(e) {
  const btn = document.getElementById('confirmPaymentBtn');
  const btnText = document.getElementById('btnText');
  const spinner = document.getElementById('btnSpinner');
  
  btn.disabled = true;
  btn.classList.add('opacity-70', 'cursor-not-allowed');
  btnText.textContent = 'Processing...';
  spinner.classList.remove('hidden');
});

  // Initialize
  renderCart();
  selectMethod('cod');
</script>
</body>
</html>