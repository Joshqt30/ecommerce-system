<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout – Delivery</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; background: #f3f4f6; }

    /* Step connector line */
    .step-line { flex: 1; height: 1px; background: #d1d5db; margin: 0 6px; }
    .step-line.done { background: #6366f1; }

    /* Step dot */
    .step-dot {
      width: 22px; height: 22px; border-radius: 50%;
      border: 2px solid #d1d5db;
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; color: #d1d5db; flex-shrink: 0;
    }
    .step-dot.done { background: #6366f1; border-color: #6366f1; color: #fff; }
    .step-dot.active { border-color: #6366f1; color: #6366f1; }

    /* Radio delivery option */
    .delivery-option {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px;
      border: 1.5px solid #e5e7eb;
      border-radius: 10px;
      cursor: pointer;
      transition: border-color .18s, background .18s;
      margin-bottom: 12px;
    }
    .delivery-option:hover { border-color: #6366f1; background: #fafafa; }
    .delivery-option.selected { border-color: #6366f1; background: #f5f5ff; }

    .delivery-option input[type="radio"] { accent-color: #6366f1; width: 18px; height: 18px; cursor: pointer; }

    /* Toast */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: #14181F; color: #fff;
      padding: 13px 20px; border-radius: 10px;
      font-size: 14px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.16);
      transform: translateY(70px); opacity: 0;
      transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s;
      z-index: 999; pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
  </style>
</head>
<body class="min-h-screen py-12">

<div class="max-w-6xl mx-auto px-6">
  <div class="flex gap-8">

    <!-- ── LEFT: Order Summary ─────────────────────── -->
    <div class="w-5/12 bg-white rounded-2xl shadow-sm p-8" style="height: fit-content;">

      <!-- Back + Title -->
      <div class="flex items-center gap-3 mb-8">
        <button onclick="history.back()"
                class="group flex items-center justify-center w-11 h-11 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-2xl transition-all duration-200 hover:scale-110 active:scale-95">
          <span class="text-2xl text-gray-500 group-hover:text-gray-700 transition-all duration-200">←</span>
        </button>
        <h2 class="text-2xl font-semibold text-gray-800">Order Summary</h2>
      </div>

      <!-- Product card -->
      <div class="flex gap-4 border border-gray-200 rounded-xl p-4 mb-6">
        <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=200&q=80"
             alt="Microsoft Lumia 640 XL"
             class="w-24 h-16 object-contain bg-gray-50 rounded-lg flex-shrink-0">
        <div class="flex-1">
          <p class="font-medium text-sm leading-snug text-gray-800">
            Microsoft Lumia 640 XL RM-1065<br>8GB Dual Sim
          </p>
          <div class="mt-3 flex items-center justify-between">
            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
              <button onclick="changeQty(-1)"
                      class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">−</button>
              <span id="qty" class="w-10 text-center text-sm font-semibold">1</span>
              <button onclick="changeQty(1)"
                      class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">+</button>
            </div>
            <span id="itemPrice" class="font-semibold text-gray-800">$298.00</span>
          </div>
        </div>
        <button onclick="removeItem()" class="text-gray-300 hover:text-red-400 transition self-start text-lg leading-none">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/>
            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </button>
      </div>

      <!-- Discount -->
      <div class="flex gap-3 mb-6">
        <input type="text" id="discountInput" placeholder="Gift Card / Discount code"
               class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-400 transition">
        <button onclick="applyDiscount()"
                class="bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-700 text-white px-6 rounded-xl font-medium text-sm transition">
          Apply
        </button>
      </div>

      <!-- Totals -->
      <div class="space-y-3 text-sm">
        <div class="flex justify-between text-gray-500">
          <span>Subtotal</span>
          <span id="subtotal" class="font-medium text-gray-700">$298.00</span>
        </div>
        <div class="flex justify-between text-gray-500">
          <span>Shipping Fee</span>
          <span id="shippingLabel" class="font-medium text-green-600">FREE</span>
        </div>
        <div class="flex justify-between pt-4 border-t border-gray-200 text-base">
          <span class="font-semibold text-gray-800">Total due</span>
          <span id="total" class="font-semibold text-indigo-600">$298.00</span>
        </div>
      </div>
    </div>

    <!-- ── RIGHT: Delivery Options ─────────────────── -->
    <div class="w-7/12 bg-white rounded-2xl shadow-sm p-8">

      <!-- Progress Steps -->
      <div class="flex items-center mb-10">
        <!-- Shipping (done) -->
        <div class="flex items-center gap-2">
          <div class="step-dot done">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </div>
          <span class="text-sm font-medium text-indigo-500">Shipping</span>
        </div>

        <div class="step-line done"></div>

        <!-- Delivery (active) -->
        <div class="flex items-center gap-2">
          <div class="step-dot active">
            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3" fill="#6366f1"/></svg>
          </div>
          <span class="text-sm font-semibold text-indigo-600">Delivery</span>
        </div>

        <div class="step-line"></div>

        <!-- Payment (inactive) -->
        <div class="flex items-center gap-2">
          <div class="step-dot">
            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3" fill="#d1d5db"/></svg>
          </div>
          <span class="text-sm text-gray-400">Payment</span>
        </div>
      </div>

      <!-- Section title -->
      <h3 class="text-lg font-semibold text-gray-800 mb-6">Delivery Options</h3>

      <!-- Option 1: Standard -->
      <label class="delivery-option selected" id="opt-standard">
        <div class="flex items-center gap-3">
          <input type="radio" name="delivery" value="0" checked onchange="selectDelivery(this, 'standard', 0, 'FREE')">
          <div>
            <p class="font-medium text-sm text-gray-800">Standard 5-7 Business Days</p>
          </div>
        </div>
        <span class="text-green-600 font-semibold text-sm">FREE</span>
      </label>

      <!-- Option 2: 2-4 days -->
      <label class="delivery-option" id="opt-express">
        <div class="flex items-center gap-3">
          <input type="radio" name="delivery" value="5" onchange="selectDelivery(this, 'express', 5, '+$5')">
          <div>
            <p class="font-medium text-sm text-gray-800">2-4 Business Days</p>
          </div>
        </div>
        <span class="text-gray-600 font-semibold text-sm">+$5</span>
      </label>

      <!-- Option 3: Same day -->
      <label class="delivery-option" id="opt-sameday">
        <div class="flex items-center gap-3">
          <input type="radio" name="delivery" value="15" onchange="selectDelivery(this, 'sameday', 15, '+$15')">
          <div>
            <p class="font-medium text-sm text-gray-800">Same day delivery</p>
          </div>
        </div>
        <span class="text-gray-600 font-semibold text-sm">+$15</span>
      </label>

      <!-- Action buttons -->
      <div class="flex gap-4 mt-10">
        <button onclick="history.back()"
                class="flex-none px-8 py-3 border border-gray-300 rounded-xl text-gray-700 font-medium text-sm hover:bg-gray-50 active:bg-gray-100 transition">
          Back
        </button>
        <button onclick="proceedToPayment()"
                class="flex-1 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
          Continue
        </button>
      </div>

    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg"></span>
</div>

<script>
  const BASE_PRICE = 298;
  let qty = 1;
  let shippingCost = 0;
  let discountAmount = 0;
  const DISCOUNT_CODES = { 'SAVE10': 10, 'FREESHIP': 0, 'WELCOME5': 5 };

  // ── Quantity ──────────────────────────────────────────
  function changeQty(delta) {
    qty = Math.max(1, qty + delta);
    updateTotals();
  }

  function removeItem() {
    if (confirm('Remove this item from your order?')) {
      toast('Item removed.');
      qty = 0;
      document.querySelector('.flex.gap-4.border').style.opacity = '0.3';
      document.querySelector('.flex.gap-4.border').style.pointerEvents = 'none';
      updateTotals();
    }
  }

  // ── Delivery selection ────────────────────────────────
  function selectDelivery(radio, key, cost, label) {
    shippingCost = cost;
    document.querySelectorAll('.delivery-option').forEach(el => el.classList.remove('selected'));
    document.getElementById('opt-' + key).classList.add('selected');
    document.getElementById('shippingLabel').textContent = label;
    document.getElementById('shippingLabel').className =
      cost === 0
        ? 'font-medium text-green-600'
        : 'font-medium text-gray-700';
    updateTotals();
  }

  // ── Discount ──────────────────────────────────────────
  function applyDiscount() {
    const code = document.getElementById('discountInput').value.trim().toUpperCase();
    if (!code) { toast('Please enter a discount code.'); return; }
    if (DISCOUNT_CODES.hasOwnProperty(code)) {
      discountAmount = DISCOUNT_CODES[code];
      toast(`Code "${code}" applied — $${discountAmount} off!`);
    } else {
      discountAmount = 0;
      toast('Invalid discount code.');
    }
    updateTotals();
  }

  // ── Totals ────────────────────────────────────────────
  function updateTotals() {
    const subtotal = BASE_PRICE * qty;
    const grandTotal = Math.max(0, subtotal + shippingCost - discountAmount);

    document.getElementById('qty').textContent = qty;
    document.getElementById('itemPrice').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('total').textContent = '$' + grandTotal.toFixed(2);
  }

  // ── Continue ──────────────────────────────────────────
  function proceedToPayment() {
    toast('Proceeding to Payment… (demo)');
  }

  // ── Toast ─────────────────────────────────────────────
  function toast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2600);
  }
</script>

</body>
</html>