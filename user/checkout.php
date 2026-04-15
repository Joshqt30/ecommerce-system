<?php
include '../config/db.php';
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
    .step-dot {
      width: 8px; height: 8px; background: #9ca3af; border-radius: 50%;
    }
    .step-active .step-dot { background: #3b82f6; }
  </style>
</head>
<body class="min-h-screen py-12">

<div class="max-w-6xl mx-auto px-6">
  <div class="flex gap-8">

  <!-- ── LEFT: Order Summary ─────────────────────── -->
    <div class="w-5/12 bg-white rounded-2xl shadow-sm p-8" style="height:fit-content;">
      <div class="flex items-center gap-3 mb-8">
        <button onclick="history.back()"
                class="group flex items-center justify-center w-11 h-11 bg-gray-100 hover:bg-gray-200 rounded-2xl transition-all hover:scale-110 active:scale-95">
          <span class="text-2xl text-gray-500 group-hover:text-gray-700 transition">←</span>
        </button>
        <h2 class="text-2xl font-semibold text-gray-800">Order Summary</h2>
      </div>

      <!-- Product -->
      <div class="flex gap-4 border border-gray-200 rounded-xl p-4 mb-6" id="productCard">
        <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=200&q=80"
             alt="Laptop" class="w-24 h-16 object-contain bg-gray-50 rounded-lg flex-shrink-0">
        <div class="flex-1">
          <p class="font-medium text-sm leading-snug text-gray-800">
            Microsoft Lumia 640 XL RM-1065<br>8GB Dual Sim
          </p>
          <div class="mt-3 flex items-center justify-between">
            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
              <button onclick="changeQty(-1)" class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">−</button>
              <span id="qty" class="w-10 text-center text-sm font-semibold">1</span>
              <button onclick="changeQty(1)"  class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">+</button>
            </div>
            <span id="itemPrice" class="font-semibold text-gray-800">$298.00</span>
          </div>
        </div>
        <button onclick="removeItem()" class="text-gray-300 hover:text-red-400 transition self-start">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
          </svg>
        </button>
      </div>

      <!-- Discount -->
      <div class="flex gap-3 mb-6">
        <input type="text" id="discountInput" placeholder="Gift Card / Discount code"
               class="flex-1 border border-gray-300 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-indigo-400 transition">
        <button onclick="applyDiscount()"
                class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 rounded-xl font-medium text-sm transition">Apply</button>
      </div>

      <!-- Totals -->
      <div class="space-y-3 text-sm">
        <div class="flex justify-between text-gray-500">
          <span>Subtotal</span><span id="subtotal" class="font-medium text-gray-700">$298.00</span>
        </div>
        <div class="flex justify-between text-gray-500">
          <span>Shipping Fee</span><span id="shippingLabel" class="font-medium text-green-600">FREE</span>
        </div>
        <div class="flex justify-between pt-4 border-t border-gray-200 text-base">
          <span class="font-semibold text-gray-800">Total due</span>
          <span id="total" class="font-semibold text-indigo-600">$298.00</span>
        </div>
      </div>
    </div>
    

    <!-- RIGHT: Shipping Form -->
    <div class="w-7/12 bg-white rounded-xl shadow-sm p-8">

      <!-- Progress Steps -->
      <div class="flex items-center gap-4 mb-10">
        <div class="flex items-center gap-2 step-active">
          <div class="step-dot"></div>
          <span class="font-medium text-blue-600">Shipping</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>
        <div class="flex items-center gap-2 text-gray-400">
          <div class="step-dot"></div>
          <span>Delivery</span>
        </div>
        <div class="flex-1 h-px bg-gray-300"></div>
        <div class="flex items-center gap-2 text-gray-400">
          <div class="step-dot"></div>
          <span>Payment</span>
        </div>
      </div>

      <h3 class="text-blue-600 font-semibold mb-6">Contact Details</h3>

      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" placeholder="First Name" 
               class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
        <input type="text" placeholder="Last Name" 
               class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
      </div>

      <input type="email" placeholder="Email" 
             class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-6 focus:outline-none focus:border-blue-500">

      <input type="tel" value="+63" placeholder="Phone Number" 
             class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-8 focus:outline-none focus:border-blue-500">

      <h3 class="text-blue-600 font-semibold mb-6">Shipping Details</h3>

      <input type="text" placeholder="Flat/House no." 
             class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-4 focus:outline-none focus:border-blue-500">

      <input type="text" placeholder="Address" 
             class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-4 focus:outline-none focus:border-blue-500">

      <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" placeholder="City" 
               class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
        <input type="text" placeholder="State" 
               class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
      </div>

      <input type="text" placeholder="Postal Code" 
             class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-8 focus:outline-none focus:border-blue-500">

      <label class="flex items-center gap-3 mb-8 cursor-pointer">
        <input type="checkbox" checked class="w-5 h-5 accent-blue-600">
        <span class="text-gray-700">My shipping and Billing address are the same</span>
      </label>

      <button onclick="alert('Proceeding to next step... (demo)')"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-4 rounded-xl transition text-lg">
        Continue
      </button>

    </div>

  </div>
</div>

<script>
  let qty = 1;
  const price = 298;

  function changeQty(change) {
    qty = Math.max(1, qty + change);
    document.getElementById('qty').textContent = qty;
    
    const total = (price * qty).toFixed(2);
    document.getElementById('total').textContent = '$' + total;
  }
</script>

</body>
</html>