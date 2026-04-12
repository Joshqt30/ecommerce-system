// delivery.js

const BASE_PRICE = 298;
let qty = 1;
let shippingCost = 0;
let discountAmount = 0;

const DISCOUNT_CODES = {
    'SAVE10': 10,
    'FREESHIP': 0,
    'WELCOME5': 5
};

// Quantity
function changeQty(delta) {
    qty = Math.max(1, qty + delta);
    updateTotals();
}

function removeItem() {
    if (confirm('Remove this item from your order?')) {
        toast('Item removed.');
        qty = 0;
        const productCard = document.getElementById('productCard');
        if (productCard) {
            productCard.style.opacity = '0.3';
            productCard.style.pointerEvents = 'none';
        }
        updateTotals();
    }
}

// Delivery Selection
function selectDelivery(radio, key, cost, label) {
    shippingCost = cost;

    document.querySelectorAll('.delivery-option').forEach(el => {
        el.classList.remove('selected');
    });

    document.getElementById('opt-' + key).classList.add('selected');

    const shippingLabel = document.getElementById('shippingLabel');
    shippingLabel.textContent = label;
    shippingLabel.className = cost === 0 
        ? 'font-medium text-green-600' 
        : 'font-medium text-gray-700';

    updateTotals();
}

// Discount
function applyDiscount() {
    const code = document.getElementById('discountInput').value.trim().toUpperCase();
    if (!code) {
        toast('Please enter a discount code.');
        return;
    }

    if (DISCOUNT_CODES.hasOwnProperty(code)) {
        discountAmount = DISCOUNT_CODES[code];
        toast(`Code "${code}" applied — $${discountAmount} off!`);
    } else {
        discountAmount = 0;
        toast('Invalid discount code.');
    }
    updateTotals();
}

// Update Totals
function updateTotals() {
    const subtotal = BASE_PRICE * qty;
    const grandTotal = Math.max(0, subtotal + shippingCost - discountAmount);

    document.getElementById('qty').textContent = qty;
    document.getElementById('itemPrice').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('total').textContent = '$' + grandTotal.toFixed(2);
}

// Proceed
function proceedToPayment() {
    toast('Proceeding to Payment… (demo)');
}

// Toast
function toast(msg) {
    const el = document.getElementById('toast');
    if (!el) return;
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2600);
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    // You can add more init logic here later if needed
});