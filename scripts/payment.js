// checkout-payment.js

const BASE_PRICE = 298;
let qty = 1;
let shippingCost = 0;
let discountAmount = 0;
let selectedMethod = 'pod';
let selectedProvider = 'gcash';

const METHOD_LABELS = {
    pod: 'Pay on Delivery',
    card: 'Credit/Debit Card',
    bank: 'Direct Bank Transfer',
    other: 'Other Payment Methods'
};

const CODES = { 'SAVE10': 10, 'WELCOME5': 5, 'FREESHIP': 0 };
const FORMS = { card: 'cardForm', bank: 'bankForm', other: 'otherForm' };

// Quantity
function changeQty(delta) {
    qty = Math.max(1, qty + delta);
    updateTotals();
}

function removeItem() {
    if (confirm('Remove this item?')) {
        const card = document.getElementById('productCard');
        card.style.opacity = '0.3';
        card.style.pointerEvents = 'none';
        qty = 0;
        updateTotals();
        showToast('Item removed.');
    }
}

function updateTotals() {
    const sub = BASE_PRICE * qty;
    const grand = Math.max(0, sub + shippingCost - discountAmount);
    
    document.getElementById('qty').textContent = qty;
    document.getElementById('itemPrice').textContent = '$' + sub.toFixed(2);
    document.getElementById('subtotal').textContent = '$' + sub.toFixed(2);
    document.getElementById('total').textContent = '$' + grand.toFixed(2);
}

// Discount
function applyDiscount() {
    const code = document.getElementById('discountInput').value.trim().toUpperCase();
    if (!code) {
        showToast('Please enter a code.');
        return;
    }
    if (CODES.hasOwnProperty(code)) {
        discountAmount = CODES[code];
        showToast(`"${code}" applied — $${discountAmount} off!`);
    } else {
        discountAmount = 0;
        showToast('Invalid code.');
    }
    updateTotals();
}

// Payment Method Selection
function selectMethod(key) {
    selectedMethod = key;

    document.querySelectorAll('.payment-option').forEach(el => {
        el.classList.toggle('selected', el.id === 'opt-' + key);
    });

    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.checked = (radio.value === key);
    });

    Object.entries(FORMS).forEach(([k, formId]) => {
        const form = document.getElementById(formId);
        if (k === key) form.classList.add('open');
        else form.classList.remove('open');
    });
}

// Provider Selection
function selectProvider(e, provider) {
    e.stopPropagation();
    selectedProvider = provider;

    document.querySelectorAll('.provider-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.provider === provider);
    });

    document.querySelectorAll('.provider-panel').forEach(panel => {
        panel.classList.remove('open');
    });

    document.getElementById('panel-' + provider).classList.add('open');
}

// Card Formatting
function formatCard(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 16);
    input.value = v.replace(/(.{4})/g, '$1 ').trim();
}

function formatExpiry(input) {
    let v = input.value.replace(/\D/g, '').substring(0, 4);
    if (v.length >= 3) v = v.substring(0, 2) + ' / ' + v.substring(2);
    input.value = v;
}

// Confirm Payment
function confirmPayment() {
    // Basic validation
    if (selectedMethod === 'card') {
        const inputs = document.querySelectorAll('#cardForm .sub-input');
        for (let inp of inputs) {
            if (!inp.value.trim()) {
                showToast('Please fill in all card details.');
                inp.focus();
                return;
            }
        }
    }

    if (selectedMethod === 'bank') {
        const inputs = document.querySelectorAll('#bankForm .sub-input');
        for (let inp of inputs) {
            if (!inp.value.trim()) {
                showToast('Please fill in all bank details.');
                inp.focus();
                return;
            }
        }
    }

    if (selectedMethod === 'other') {
        const activePanel = document.querySelector('#panel-' + selectedProvider + ' .sub-input');
        if (activePanel && !activePanel.value.trim()) {
            showToast(`Please enter your ${selectedProvider.toUpperCase()} details.`);
            activePanel.focus();
            return;
        }
    }

    const grand = Math.max(0, BASE_PRICE * qty + shippingCost - discountAmount);
    const methodLabel = selectedMethod === 'other' 
        ? selectedProvider.charAt(0).toUpperCase() + selectedProvider.slice(1) 
        : METHOD_LABELS[selectedMethod];

    document.getElementById('orderNum').textContent = '#ORD-' + Math.floor(100000 + Math.random() * 900000);
    document.getElementById('paymentMethod').textContent = methodLabel;
    document.getElementById('modalTotal').textContent = '$' + grand.toFixed(2);

    document.getElementById('confirmModal').classList.add('open');
}

function closeModal() {
    document.getElementById('confirmModal').classList.remove('open');
    showToast('Redirecting to shop… (demo)');
}

// Toast
function showToast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2600);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // You can add more init code here if needed
});