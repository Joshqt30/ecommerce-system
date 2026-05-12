<?php
session_start();
include '../config/db.php';
/** @var resource|\PgSql\Connection $conn */

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch user info for auto‑fill
$userQuery = "SELECT email, phone, address, first_name, last_name FROM users WHERE id = $1";
$userResult = pg_query_params($conn, $userQuery, [$userId]);
$userData = pg_fetch_assoc($userResult);

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Collect shipping details
    $shipping = [
        'first_name' => trim($_POST['first_name'] ?? ''),
        'last_name'  => trim($_POST['last_name'] ?? ''),
        'email'      => trim($_POST['email'] ?? ''),
        'phone'      => trim($_POST['phone'] ?? ''),
        'address'    => trim($_POST['address'] ?? ''),
        'city'       => trim($_POST['city'] ?? ''),
        'barangay'      => trim($_POST['barangay'] ?? ''),
        'postal_code'=> trim($_POST['postal_code'] ?? ''),
        'latitude'   => trim($_POST['latitude'] ?? ''),
        'longitude'  => trim($_POST['longitude'] ?? '')
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

// Base image path
define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
    <input type="hidden" name="cart_data" id="cartDataInput" value="">
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

        <!-- Cart items will be injected here from server -->
        <div id="cartItemsContainer">
          <div class="text-center py-8 text-gray-500">Loading cart...</div>
        </div>

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
                value="<?= htmlspecialchars($userData['first_name'] ?? '') ?>"
                class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
          <input type="text" name="last_name" placeholder="Last Name" required
                value="<?= htmlspecialchars($userData['last_name'] ?? '') ?>"
                class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
        </div>

        <input type="email" name="email" placeholder="Email" required value="<?= htmlspecialchars($userData['email'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-6 focus:outline-none focus:border-blue-500">

        <input type="tel" name="phone" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>" placeholder="Phone Number" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-8 focus:outline-none focus:border-blue-500">

        <h3 class="text-blue-600 font-semibold mb-6">Shipping Details</h3>

       <button type="button" id="detectLocationBtn"
        class="mb-4 px-4 py-2 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded-xl font-medium text-sm transition flex items-center gap-2">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <circle cx="12" cy="12" r="3"/>
          </svg>
          Use my current location
        </button>

        <!-- Map container (hidden by default) -->
        <div id="mapContainer" style="display:none; margin-bottom:16px;">
          <div id="map" style="height:250px; border-radius:12px; border:1px solid #d1d5db;" class="mb-2"></div>
          <p class="text-xs text-gray-500">Drag the pin to your preferred delivery point</p>
        </div>

        <input type="text" name="flat_no" placeholder="Flat/House no."
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-4 focus:outline-none focus:border-blue-500">

        <input type="text" name="address" value="<?= htmlspecialchars($userData['address'] ?? '') ?>" placeholder="Address" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-4 focus:outline-none focus:border-blue-500">

        <div class="grid grid-cols-2 gap-4 mb-6">
        <input type="text" name="city" placeholder="City" required
              class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
        <input type="text" name="barangay" placeholder="Barangay" required
              class="border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:border-blue-500">
      </div>

        <input type="text" name="postal_code" placeholder="Postal Code" required
               class="w-full border border-gray-300 rounded-lg px-4 py-3 mb-8 focus:outline-none focus:border-blue-500">

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

    <input type="hidden" name="latitude" id="latitudeInput" value="">
    <input type="hidden" name="longitude" id="longitudeInput" value="">

  </form>
</div>

<script>
  const PRODUCT_IMGS_BASE = '/ecommerce-system/imgs/products/';
  let cartItems = []; // will hold server cart data

  // Fetch cart from server
  async function loadCartFromServer() {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('direct_buy') === '1') {
      const productId = urlParams.get('product_id');
      const qty = parseInt(urlParams.get('qty')) || 1;
      if (!productId) {
        document.getElementById('cartItemsContainer').innerHTML = '<div class="text-center py-8 text-red-500">Missing product</div>';
        return;
      }
      // Fetch product details from server
      try {
        const res = await fetch(`../includes/get-product.php?id=${productId}`);
        if (!res.ok) throw new Error('Product not found');
        const product = await res.json();
        cartItems = [{
          variantId: null,
          productId: product.id,
          name: product.name,
          price: product.price,
          quantity: qty,
          image: product.image || ''
        }];
      } catch (err) {
        console.error(err);
        document.getElementById('cartItemsContainer').innerHTML = '<div class="text-center py-8 text-red-500">Error loading product</div>';
        return;
      }
    } else {
      // Normal cart loading from get-cart.php
      try {
        const response = await fetch('../includes/get-cart.php');
        const data = await response.json();
        cartItems = data;
      } catch (err) {
        console.error('Failed to load cart:', err);
        document.getElementById('cartItemsContainer').innerHTML = '<div class="text-center py-8 text-red-500">Error loading cart</div>';
        return;
      }
    }
    renderCart();
  }

  function renderCart() {
    const container = document.getElementById('cartItemsContainer');
    if (!cartItems.length) {
      container.innerHTML = '<div class="text-center py-8 text-gray-500">Your cart is empty</div>';
      updateTotals(0);
      return;
    }

    let subtotal = 0;
    let html = '';

    cartItems.forEach((item, idx) => {
      const itemTotal = item.price * item.quantity;
      subtotal += itemTotal;
      html += `
        <div class="flex gap-4 border border-gray-200 rounded-xl p-4 mb-4">
          <img src="${PRODUCT_IMGS_BASE}${item.image}" alt="${item.name}"
               class="w-24 h-16 object-contain bg-gray-50 rounded-lg flex-shrink-0"
               onerror="this.src='https://via.placeholder.com/100'">
          <div class="flex-1">
            <p class="font-medium text-sm leading-snug text-gray-800">${escapeHtml(item.name)}</p>
            <div class="mt-3 flex items-center justify-between">
              <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden">
                <button type="button" onclick="updateQty(${item.variantId}, -1)" class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">−</button>
                <span class="w-10 text-center text-sm font-semibold">${item.quantity}</span>
                <button type="button" onclick="updateQty(${item.variantId}, 1)" class="w-8 h-8 flex items-center justify-center text-lg hover:bg-gray-100 text-gray-600 transition">+</button>
              </div>
              <span class="font-semibold text-gray-800">₱${itemTotal.toFixed(2)}</span>
            </div>
          </div>
          <button type="button" onclick="removeItem(${item.variantId})" class="text-gray-300 hover:text-red-400 transition self-start">
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6M14 11v6M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </div>
      `;
    });

    container.innerHTML = html;
    updateTotals(subtotal);
    // Store cart data in hidden input for form submission
    document.getElementById('cartDataInput').value = JSON.stringify(cartItems);
  }

  function updateTotals(subtotal) {
    document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('total').textContent = `₱${subtotal.toFixed(2)}`;
  }

  window.updateQty = async (cartId, delta) => {
    try {
      const res = await fetch('../includes/update-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId, delta: delta })
      });
      const data = await res.json();
      if (data.success) {
        await loadCartFromServer(); // refresh cart
      } else {
        alert('Error updating quantity');
      }
    } catch (err) {
      console.error(err);
      alert('Something went wrong');
    }
  };

  window.removeItem = async (cartId) => {
    try {
      const res = await fetch('../includes/remove-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId })
      });
      const data = await res.json();
      if (data.success) {
        await loadCartFromServer();
        if (cartItems.length === 0) location.reload();
      } else {
        alert('Error removing item');
      }
    } catch (err) {
      console.error(err);
      alert('Something went wrong');
    }
  };

  function applyDiscount() {
    alert('Discount codes not implemented in demo.');
  }

  function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>]/g, function(m) {
      if (m === '&') return '&amp;';
      if (m === '<') return '&lt;';
      if (m === '>') return '&gt;';
      return m;
    });
  }

  // Initial load
  loadCartFromServer();

 // ─── Map & Geolocation ──────────────────────────
let map;        // Leaflet map instance
let marker;     // draggable marker

document.getElementById('detectLocationBtn')?.addEventListener('click', function () {
  if (!navigator.geolocation) {
    alert("Geolocation is not supported by your browser.");
    return;
  }

  const btn = this;
  btn.disabled = true;
  btn.textContent = "Detecting location...";

  navigator.geolocation.getCurrentPosition(
    position => {
      const { latitude, longitude } = position.coords;
      initMap(latitude, longitude);
      btn.disabled = false;
      btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/></svg> Use my current location`;
    },
    error => {
      alert("Location access denied or unavailable.");
      btn.disabled = false;
      btn.innerHTML = `<svg ...>Use my current location</svg>`;
    }
  );
});

function initMap(lat, lng) {
  // Show map container
  const container = document.getElementById('mapContainer');
  container.style.display = 'block';

  // If map already exists, remove it (re-init)
  if (map) {
    map.remove();
    map = null;
  }

  // Create Leaflet map
  map = L.map('map').setView([lat, lng], 17);  // zoom level 17 good for streets

  // OpenStreetMap tiles
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Add draggable marker
  marker = L.marker([lat, lng], { draggable: true }).addTo(map);

  // Reverse geocode initial position
  reverseGeocode(lat, lng);

  // Update address when marker is dragged
  marker.on('dragend', function () {
    const pos = marker.getLatLng();
    reverseGeocode(pos.lat, pos.lng);
  });

  // Also allow clicking on map to move pin
  map.on('click', function (e) {
    marker.setLatLng(e.latlng);
    reverseGeocode(e.latlng.lat, e.latlng.lng);
  });
}

// Reverse geocoding using Nominatim (with delay to respect usage policy)
let geocodeTimer;
function reverseGeocode(lat, lng) {
  clearTimeout(geocodeTimer);
  geocodeTimer = setTimeout(async () => {
    try {
      const res = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}&addressdetails=1`
      );
      const data = await res.json();
      if (data && data.address) {
        const addr = data.address;
        console.log('Nominatim address:', addr);
        document.querySelector('input[name="city"]').value = addr.city || addr.town || addr.village || addr.county || '';
        document.querySelector('input[name="barangay"]').value = 
          addr.suburb ||
          addr.village ||
          addr.neighbourhood ||
          addr.quarter ||
          addr.city_district ||
          '';
        document.querySelector('input[name="postal_code"]').value = addr.postcode || '';
        // Keep hidden lat/lng in sync
        document.getElementById('latitudeInput').value = lat.toFixed(6);
        document.getElementById('longitudeInput').value = lng.toFixed(6);

        // Build street string
        const street = [addr.road, addr.house_number].filter(Boolean).join(' ');
        if (street) document.querySelector('input[name="address"]').value = street;
        // Also optionally set flat_no if you want building number separately
        if (addr.house_number) {
          document.querySelector('input[name="flat_no"]').value = addr.house_number;
        }
      }
    } catch (err) {
      console.error('Reverse geocode error:', err);
    }
  }, 500);  // small delay to avoid rapid API calls while dragging
}

</script>

</body>
</html>