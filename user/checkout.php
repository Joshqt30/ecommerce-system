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

    <!-- LEFT: Order Summary -->
        <div class="w-5/12 bg-white rounded-2xl shadow-sm p-8">

        <div class="flex items-center gap-3 mb-10">
            <button class="text-gray-500 hover:text-gray-700 text-2xl leading-none transition">
            ←
            </button>
            <h2 class="text-2xl font-semibold text-gray-800">Order Summary</h2>
        </div>

      <!-- Product -->
      <div class="flex gap-5 bg-white border border-gray-200 rounded-xl p-5 mb-8">
        <img src="https://via.placeholder.com/120x80/1e3a8a/ffffff?text=Lumia" 
             alt="Microsoft Lumia 640 XL" 
             class="w-32 h-20 object-contain bg-gray-100 rounded-lg">

        <div class="flex-1">
          <p class="font-medium leading-tight">
            Microsoft Lumia 640 XL RM-1065<br>
            8GB Dual Sim
          </p>
          <div class="mt-4 flex items-center justify-between">
            <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
              <button onclick="changeQty(-1)" 
                      class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100">-</button>
              <span id="qty" class="w-10 text-center font-medium">1</span>
              <button onclick="changeQty(1)" 
                      class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100">+</button>
            </div>
            <span class="font-semibold text-lg">$298.00</span>
          </div>
        </div>

        <button class="text-gray-400 hover:text-red-500 text-2xl leading-none">🗑</button>
      </div>

    <!-- Discount -->
  <div class="flex gap-3 mb-8">
    <input type="text" 
           placeholder="Gift Card / Discount code"
           class="flex-1 border border-gray-300 rounded-2xl px-5 py-4 focus:outline-none focus:border-blue-500 text-base">
    <button class="bg-blue-600 hover:bg-blue-700 text-white px-9 rounded-2xl font-medium transition">
      Apply
    </button>
  </div>
  
      <!-- Totals -->
      <div class="space-y-4">
        <div class="flex justify-between text-gray-600">
          <span>Subtotal</span>
          <span class="font-medium">$298.00</span>
        </div>
        <div class="flex justify-between text-gray-600">
          <span>Shipping Fee</span>
          <span class="text-green-600 font-medium">FREE</span>
        </div>
        <div class="flex justify-between pt-4 border-t border-gray-200 text-lg">
          <span class="font-semibold">Total due</span>
          <span id="total" class="font-semibold text-blue-600">$298.00</span>
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