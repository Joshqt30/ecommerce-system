<?php
include '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – Delivery</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="../assets/css/checkout.css">
    <link rel="stylesheet" href="../assets/css/delivery.css">
</head>
<body class="min-h-screen py-12 bg-gray-100">

<div class="max-w-6xl mx-auto px-6">
    <div class="flex gap-8">

        <!-- LEFT: Order Summary -->
        <div class="w-5/12 bg-white rounded-2xl shadow-sm p-8" style="height: fit-content;">

            <!-- Back + Title -->
            <div class="flex items-center gap-3 mb-8">
                <button onclick="history.back()"
                        class="group flex items-center justify-center w-11 h-11 bg-gray-100 hover:bg-gray-200 active:bg-gray-300 rounded-2xl transition-all duration-200 hover:scale-110 active:scale-95">
                    <span class="text-2xl text-gray-500 group-hover:text-gray-700 transition-all duration-200">←</span>
                </button>
                <h2 class="text-2xl font-semibold text-gray-800">Order Summary</h2>
            </div>

            <!-- Product -->
            <div class="flex gap-4 border border-gray-200 rounded-xl p-4 mb-6" id="productCard">
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

                <button onclick="removeItem()" 
                        class="text-gray-300 hover:text-red-400 transition self-start">
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

        <!-- RIGHT: Delivery Options -->
        <div class="w-7/12 bg-white rounded-2xl shadow-sm p-8">

            <!-- Progress Steps -->
            <div class="flex items-center mb-10">
                <div class="flex items-center gap-2">
                    <div class="step-dot done">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-indigo-500">Shipping</span>
                </div>
                <div class="step-line done"></div>

                <div class="flex items-center gap-2">
                    <div class="step-dot active">
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3" fill="#6366f1"/></svg>
                    </div>
                    <span class="text-sm font-semibold text-indigo-600">Delivery</span>
                </div>
                <div class="step-line"></div>

                <div class="flex items-center gap-2">
                    <div class="step-dot">
                        <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3" fill="#d1d5db"/></svg>
                    </div>
                    <span class="text-sm text-gray-400">Payment</span>
                </div>
            </div>

            <h3 class="text-lg font-semibold text-gray-800 mb-6">Delivery Options</h3>

            <!-- Delivery Options -->
            <label class="delivery-option selected" id="opt-standard">
                <div class="flex items-center gap-3">
                    <input type="radio" name="delivery" value="0" checked onchange="selectDelivery(this, 'standard', 0, 'FREE')">
                    <div>
                        <p class="font-medium text-sm text-gray-800">Standard 5-7 Business Days</p>
                    </div>
                </div>
                <span class="text-green-600 font-semibold text-sm">FREE</span>
            </label>

            <label class="delivery-option" id="opt-express">
                <div class="flex items-center gap-3">
                    <input type="radio" name="delivery" value="5" onchange="selectDelivery(this, 'express', 5, '+$5')">
                    <div>
                        <p class="font-medium text-sm text-gray-800">2-4 Business Days</p>
                    </div>
                </div>
                <span class="text-gray-600 font-semibold text-sm">+$5</span>
            </label>

            <label class="delivery-option" id="opt-sameday">
                <div class="flex items-center gap-3">
                    <input type="radio" name="delivery" value="15" onchange="selectDelivery(this, 'sameday', 15, '+$15')">
                    <div>
                        <p class="font-medium text-sm text-gray-800">Same day delivery</p>
                    </div>
                </div>
                <span class="text-gray-600 font-semibold text-sm">+$15</span>
            </label>

            <!-- Action Buttons -->
            <div class="flex gap-4 mt-10">
                <button onclick="history.back()"
                        class="flex-none px-8 py-3 border border-gray-300 rounded-xl text-gray-700 font-medium text-sm hover:bg-gray-50 active:bg-gray-100 transition">
                    Back
                </button>
                <button onclick="proceedToPayment()"
                        class="flex-1 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
                    Continue to Payment
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

<script src="../scripts/delivery.js"></script>

</body>
</html>