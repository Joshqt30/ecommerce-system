<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout – Payment</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: 'Inter', system-ui, sans-serif; background: #f3f4f6; }

    /* Step connector */
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

    /* Payment option card */
    .payment-option {
      display: flex; align-items: flex-start; justify-content: space-between;
      padding: 16px 20px;
      border: 1.5px solid #e5e7eb;
      border-radius: 12px;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      margin-bottom: 10px;
    }
    .payment-option:hover { border-color: #a5b4fc; background: #fafafa; }
    .payment-option.selected { border-color: #6366f1; background: #f5f5ff; }
    .payment-option input[type="radio"] {
      accent-color: #6366f1; width: 17px; height: 17px;
      cursor: pointer; margin-top: 2px; flex-shrink: 0;
    }

    /* ── Sub-form (card details / other methods) ── */
    .sub-form {
      max-height: 0; overflow: hidden;
      transition: max-height .35s ease, opacity .25s ease, padding .25s ease;
      opacity: 0; padding-top: 0;
    }
    .sub-form.open {
      max-height: 600px; opacity: 1;
      padding-top: 14px; border-top: 1px solid #e5e7eb; margin-top: 14px;
    }

    /* Inputs inside sub-forms */
    .sub-input {
      width: 100%; padding: 10px 14px 10px 38px;
      border: 1.5px solid #e5e7eb; border-radius: 8px;
      font-family: 'Inter', sans-serif; font-size: 13px;
      outline: none; transition: border-color .18s; background: #fff;
      color: #374151;
    }
    .sub-input:focus { border-color: #6366f1; }
    .sub-input::placeholder { color: #9ca3af; }

    .input-wrap { position: relative; margin-bottom: 10px; }
    .input-wrap .input-icon {
      position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
      color: #9ca3af; pointer-events: none;
    }

    /* Other payment – provider selector tabs */
    .provider-tabs { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px; }
    .provider-tab {
      display: flex; align-items: center; gap: 7px;
      padding: 8px 14px; border-radius: 8px;
      border: 1.5px solid #e5e7eb; cursor: pointer;
      font-size: 12px; font-weight: 600; background: #fff;
      transition: border-color .18s, background .18s;
      color: #374151;
    }
    .provider-tab:hover { border-color: #a5b4fc; background: #f5f5ff; }
    .provider-tab.active { border-color: #6366f1; background: #eef2ff; color: #4f46e5; }
    .provider-tab img { width: 20px; height: 20px; object-fit: contain; }

    /* Provider sub-input panels */
    .provider-panel { display: none; }
    .provider-panel.open { display: block; }

    /* Toast */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: #14181F; color: #fff; padding: 13px 20px; border-radius: 10px;
      font-size: 14px; font-weight: 500; display: flex; align-items: center; gap: 10px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.16);
      transform: translateY(70px); opacity: 0;
      transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s;
      z-index: 999; pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }

    /* Confirm modal */
    .modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.35);
      z-index: 800; display: none; align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
      background: #fff; border-radius: 20px; padding: 40px 36px;
      max-width: 420px; width: 90%; text-align: center;
      animation: popIn .28s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes popIn { from { opacity:0; transform:scale(.88); } to { opacity:1; transform:none; } }
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

    <!-- ── RIGHT: Payment Methods ─────────────────── -->
    <div class="w-7/12 bg-white rounded-2xl shadow-sm p-8">

      <!-- Progress Steps -->
      <div class="flex items-center mb-10">
        <div class="flex items-center gap-2">
          <div class="step-dot done">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="text-sm font-medium text-indigo-500">Shipping</span>
        </div>
        <div class="step-line done"></div>
        <div class="flex items-center gap-2">
          <div class="step-dot done">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          </div>
          <span class="text-sm font-medium text-indigo-500">Delivery</span>
        </div>
        <div class="step-line done"></div>
        <div class="flex items-center gap-2">
          <div class="step-dot active">
            <svg width="10" height="10" viewBox="0 0 10 10"><circle cx="5" cy="5" r="3" fill="#6366f1"/></svg>
          </div>
          <span class="text-sm font-semibold text-indigo-600">Payment</span>
        </div>
      </div>

      <h3 class="text-lg font-semibold text-gray-800 mb-5">Payment Methods</h3>

      <!-- ── Option 1: Pay on Delivery ── -->
      <label class="payment-option selected" id="opt-pod" onclick="selectMethod('pod')">
        <div class="flex gap-3">
          <input type="radio" name="payment" value="pod" checked>
          <div>
            <p class="font-medium text-sm text-gray-800">Pay on Delivery</p>
            <p class="text-xs text-gray-400 mt-0.5">Pay with cash on delivery</p>
          </div>
        </div>
      </label>

      <!-- ── Option 2: Credit/Debit Cards ── -->
      <div class="payment-option" id="opt-card" onclick="selectMethod('card')">
        <div class="flex gap-3 flex-1 min-w-0">
          <input type="radio" name="payment" value="card" style="margin-top:2px; flex-shrink:0;">
          <div class="flex-1 min-w-0">
            <p class="font-medium text-sm text-gray-800">Credit/Debit Cards</p>
            <p class="text-xs text-gray-400 mt-0.5">Pay with your Credit / Debit Card</p>

            <!-- Card sub-form -->
            <div class="sub-form" id="cardForm">
              <!-- Card number -->
              <div class="input-wrap">
                <span class="input-icon">
                  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </span>
                <input class="sub-input" type="text" placeholder="Card number" maxlength="19" oninput="formatCard(this)">
              </div>
              <!-- MM/YY + CVV -->
              <div style="display:flex; gap:10px;">
                <div class="input-wrap" style="flex:1; margin-bottom:0;">
                  <span class="input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                  </span>
                  <input class="sub-input" type="text" placeholder="MM / YY" maxlength="7" oninput="formatExpiry(this)">
                </div>
                <div class="input-wrap" style="flex:1; margin-bottom:0;">
                  <span class="input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                  </span>
                  <input class="sub-input" type="text" placeholder="CVV" maxlength="4">
                </div>
              </div>
            </div>
          </div>
        </div>
        <!-- Card brand icons -->
        <div class="flex gap-1.5 flex-shrink-0 mt-1 ml-2">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/5/5e/Visa_Inc._logo.svg/200px-Visa_Inc._logo.svg.png" alt="Visa" class="h-5 w-auto object-contain">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Mastercard-logo.svg/200px-Mastercard-logo.svg.png" alt="Mastercard" class="h-5 w-auto object-contain">
          <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/f/fa/American_Express_logo_%282018%29.svg/200px-American_Express_logo_%282018%29.svg.png" alt="Amex" class="h-5 w-auto object-contain">
        </div>
      </div>

      <!-- ── Option 3: Direct Bank Transfer ── -->
      <div class="payment-option" id="opt-bank" onclick="selectMethod('bank')">
        <div class="flex gap-3 flex-1 min-w-0">
          <input type="radio" name="payment" value="bank" style="margin-top:2px; flex-shrink:0;">
          <div class="flex-1 min-w-0">
            <p class="font-medium text-sm text-gray-800">Direct Bank Transfer</p>
            <p class="text-xs text-gray-400 mt-0.5">Make payment directly through bank account.</p>

            <!-- Bank sub-form -->
            <div class="sub-form" id="bankForm">
              <div class="input-wrap">
                <span class="input-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="22" x2="21" y2="22"/><line x1="6" y1="18" x2="6" y2="11"/><line x1="10" y1="18" x2="10" y2="11"/><line x1="14" y1="18" x2="14" y2="11"/><line x1="18" y1="18" x2="18" y2="11"/><polygon points="12 2 20 7 4 7"/></svg>
                </span>
                <input class="sub-input" type="text" placeholder="Bank name">
              </div>
              <div class="input-wrap">
                <span class="input-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                </span>
                <input class="sub-input" type="text" placeholder="Account number" maxlength="20">
              </div>
              <div class="input-wrap" style="margin-bottom:0;">
                <span class="input-icon">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <input class="sub-input" type="text" placeholder="Account holder name">
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ── Option 4: Other Payment Methods ── -->
      <div class="payment-option" id="opt-other" onclick="selectMethod('other')">
        <div class="flex gap-3 flex-1 min-w-0">
          <input type="radio" name="payment" value="other" style="margin-top:2px; flex-shrink:0;">
          <div class="flex-1 min-w-0">
            <p class="font-medium text-sm text-gray-800">Other Payment Methods</p>
            <p class="text-xs text-gray-400 mt-0.5">Make payment through Gpay, Paypal, Paytm etc.</p>

            <!-- Other sub-form: provider tabs + dynamic input -->
            <div class="sub-form" id="otherForm">

              <!-- Provider selector -->
              <div class="provider-tabs" id="providerTabs">
                <button class="provider-tab active" data-provider="gcash" onclick="selectProvider(event,'gcash')">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#007aff"/><text x="3" y="17" font-size="8" font-weight="700" fill="#fff" font-family="Arial">GC</text></svg>
                  GCash
                </button>
                <button class="provider-tab" data-provider="paypal" onclick="selectProvider(event,'paypal')">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#003087"/><text x="3" y="17" font-size="7" font-weight="700" fill="#fff" font-family="Arial">PP</text></svg>
                  PayPal
                </button>
                <button class="provider-tab" data-provider="gpay" onclick="selectProvider(event,'gpay')">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#fff" stroke="#e5e7eb"/><text x="2" y="17" font-size="9" font-weight="700" font-family="Arial"><tspan fill="#4285f4">G</tspan><tspan fill="#34a853">P</tspan></text></svg>
                  GPay
                </button>
                <button class="provider-tab" data-provider="paytm" onclick="selectProvider(event,'paytm')">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#00baf2"/><text x="2" y="17" font-size="7" font-weight="700" fill="#fff" font-family="Arial">PT</text></svg>
                  Paytm
                </button>
              </div>

              <!-- GCash -->
              <div class="provider-panel open" id="panel-gcash">
                <div class="input-wrap">
                  <span class="input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.45 18a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 3.18 2 2 0 0 1 4.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  </span>
                  <input class="sub-input" type="tel" placeholder="GCash mobile number (e.g. 09xxxxxxxxx)" maxlength="11">
                </div>
              </div>

              <!-- PayPal -->
              <div class="provider-panel" id="panel-paypal">
                <div class="input-wrap">
                  <span class="input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                  </span>
                  <input class="sub-input" type="email" placeholder="PayPal email address">
                </div>
              </div>

              <!-- GPay -->
              <div class="provider-panel" id="panel-gpay">
                <div class="input-wrap">
                  <span class="input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.45 18a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 3.18 2 2 0 0 1 4.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  </span>
                  <input class="sub-input" type="tel" placeholder="GPay registered phone number">
                </div>
              </div>

              <!-- Paytm -->
              <div class="provider-panel" id="panel-paytm">
                <div class="input-wrap" style="margin-bottom:0;">
                  <span class="input-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2A19.79 19.79 0 0 1 11.45 18a19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 3.18 2 2 0 0 1 4.11 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 8.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  </span>
                  <input class="sub-input" type="tel" placeholder="Paytm registered phone number">
                </div>
              </div>

            </div><!-- /otherForm -->
          </div>
        </div>
        <!-- Other icons -->
        <div class="flex gap-1.5 flex-shrink-0 mt-1 ml-2 flex-wrap justify-end" style="max-width:110px;">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#007aff"/><text x="3" y="17" font-size="8" font-weight="700" fill="#fff" font-family="Arial">GC</text></svg>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#003087"/><text x="3" y="17" font-size="7" font-weight="700" fill="#fff" font-family="Arial">PP</text></svg>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#fff" stroke="#e5e7eb"/><text x="2" y="17" font-size="9" font-weight="700" font-family="Arial"><tspan fill="#4285f4">G</tspan><tspan fill="#34a853">P</tspan></text></svg>
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect width="24" height="24" rx="5" fill="#00baf2"/><text x="2" y="17" font-size="7" font-weight="700" fill="#fff" font-family="Arial">PT</text></svg>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-4 mt-8">
        <button onclick="history.back()"
                class="flex-none px-8 py-3 border border-gray-300 rounded-xl text-gray-700 font-medium text-sm hover:bg-gray-50 transition">
          Back
        </button>
        <button onclick="confirmPayment()"
                class="flex-1 bg-indigo-500 hover:bg-indigo-600 active:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition text-sm">
          Confirm Payment
        </button>
      </div>

    </div><!-- /right -->
  </div>
</div>

<!-- ── Confirm Modal ───────────────────────────────── -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal-box">
    <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-5">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#22c55e" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
    </div>
    <h2 class="text-xl font-bold text-gray-800 mb-2">Order Confirmed!</h2>
    <p class="text-sm text-gray-500 mb-6">Thank you for your purchase. Your order is being processed.</p>
    <div class="bg-gray-50 rounded-xl p-4 mb-6 text-sm text-left space-y-2">
      <div class="flex justify-between"><span class="text-gray-500">Order #</span><span class="font-semibold text-gray-800" id="orderNum">–</span></div>
      <div class="flex justify-between"><span class="text-gray-500">Payment</span><span class="font-semibold text-gray-800" id="paymentMethod">–</span></div>
      <div class="flex justify-between"><span class="text-gray-500">Total paid</span><span class="font-semibold text-indigo-600" id="modalTotal">–</span></div>
    </div>
    <button onclick="closeModal()"
            class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-semibold py-3 rounded-xl transition text-sm">
      Back to Shop
    </button>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg"></span>
</div>

<script>
  const BASE_PRICE = 298;
  let qty = 1, shippingCost = 0, discountAmount = 0;
  let selectedMethod = 'pod';
  let selectedProvider = 'gcash';

  const METHOD_LABELS = {
    pod: 'Pay on Delivery', card: 'Credit/Debit Card',
    bank: 'Direct Bank Transfer', other: 'Other Payment Methods'
  };

  // ── Quantity ───────────────────────────────────────────
  function changeQty(delta) { qty = Math.max(1, qty + delta); updateTotals(); }

  function removeItem() {
    if (confirm('Remove this item?')) {
      const c = document.getElementById('productCard');
      c.style.opacity = '0.3'; c.style.pointerEvents = 'none';
      qty = 0; updateTotals(); showToast('Item removed.');
    }
  }

  function updateTotals() {
    const sub = BASE_PRICE * qty;
    const grand = Math.max(0, sub + shippingCost - discountAmount);
    document.getElementById('qty').textContent        = qty;
    document.getElementById('itemPrice').textContent  = '$' + sub.toFixed(2);
    document.getElementById('subtotal').textContent   = '$' + sub.toFixed(2);
    document.getElementById('total').textContent      = '$' + grand.toFixed(2);
  }

  // ── Discount ──────────────────────────────────────────
  const CODES = { 'SAVE10': 10, 'WELCOME5': 5, 'FREESHIP': 0 };
  function applyDiscount() {
    const code = document.getElementById('discountInput').value.trim().toUpperCase();
    if (!code) { showToast('Please enter a code.'); return; }
    if (CODES.hasOwnProperty(code)) {
      discountAmount = CODES[code];
      showToast(`"${code}" applied — $${discountAmount} off!`);
    } else { discountAmount = 0; showToast('Invalid code.'); }
    updateTotals();
  }

  // ── Payment method ─────────────────────────────────────
  const FORMS = { card: 'cardForm', bank: 'bankForm', other: 'otherForm' };

  function selectMethod(key) {
    selectedMethod = key;

    // Toggle option card highlight + radio
    ['pod','card','bank','other'].forEach(k => {
      const el = document.getElementById('opt-' + k);
      el.classList.toggle('selected', k === key);
      el.querySelector('input[type="radio"]').checked = (k === key);
    });

    // Open the matching sub-form, close others
    Object.entries(FORMS).forEach(([k, formId]) => {
      const form = document.getElementById(formId);
      if (k === key) form.classList.add('open');
      else form.classList.remove('open');
    });
  }

  // ── Provider tabs (Other methods) ─────────────────────
  function selectProvider(e, provider) {
    e.stopPropagation(); // don't re-trigger selectMethod
    selectedProvider = provider;

    document.querySelectorAll('.provider-tab').forEach(t =>
      t.classList.toggle('active', t.dataset.provider === provider)
    );
    document.querySelectorAll('.provider-panel').forEach(p => p.classList.remove('open'));
    document.getElementById('panel-' + provider).classList.add('open');
  }

  // ── Card formatting ────────────────────────────────────
  function formatCard(input) {
    let v = input.value.replace(/\D/g,'').substring(0,16);
    input.value = v.replace(/(.{4})/g,'$1 ').trim();
  }
  function formatExpiry(input) {
    let v = input.value.replace(/\D/g,'').substring(0,4);
    if (v.length >= 3) v = v.substring(0,2) + ' / ' + v.substring(2);
    input.value = v;
  }

  // ── Confirm ───────────────────────────────────────────
  function confirmPayment() {
    // Validate card fields
    if (selectedMethod === 'card') {
      const inputs = document.querySelectorAll('#cardForm .sub-input');
      for (const inp of inputs) {
        if (!inp.value.trim()) { showToast('Please fill in all card details.'); inp.focus(); return; }
      }
    }
    // Validate bank fields
    if (selectedMethod === 'bank') {
      const inputs = document.querySelectorAll('#bankForm .sub-input');
      for (const inp of inputs) {
        if (!inp.value.trim()) { showToast('Please fill in all bank details.'); inp.focus(); return; }
      }
    }
    // Validate other (active provider)
    if (selectedMethod === 'other') {
      const activePanel = document.querySelector('#panel-' + selectedProvider + ' .sub-input');
      if (activePanel && !activePanel.value.trim()) {
        showToast('Please enter your ' + selectedProvider.charAt(0).toUpperCase() + selectedProvider.slice(1) + ' details.');
        activePanel.focus(); return;
      }
    }

    const sub = BASE_PRICE * qty;
    const grand = Math.max(0, sub + shippingCost - discountAmount);
    const methodLabel = selectedMethod === 'other'
      ? selectedProvider.charAt(0).toUpperCase() + selectedProvider.slice(1)
      : METHOD_LABELS[selectedMethod];

    document.getElementById('orderNum').textContent     = '#ORD-' + Math.floor(100000 + Math.random() * 900000);
    document.getElementById('paymentMethod').textContent = methodLabel;
    document.getElementById('modalTotal').textContent   = '$' + grand.toFixed(2);
    document.getElementById('confirmModal').classList.add('open');
  }

  function closeModal() {
    document.getElementById('confirmModal').classList.remove('open');
    showToast('Redirecting to shop… (demo)');
  }

  // ── Toast ─────────────────────────────────────────────
  function showToast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2600);
  }
</script>

</body>
</html>