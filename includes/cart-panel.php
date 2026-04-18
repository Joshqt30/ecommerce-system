<?php
// cart-panel.php
// Updated to match the existing cart-panel.css structure
?>
<div class="cart-overlay" id="cartOverlay"></div>
 
<aside class="cart-panel" id="cartPanel">
 
  <!-- Header -->
  <div class="cart-panel-header">
    <div class="cart-panel-title">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <span>Shopping Cart</span>
      <span class="cart-panel-count" id="panelCount">(0)</span>
    </div>
    <button class="cart-panel-close" id="cartPanelClose" aria-label="Close cart">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>
 
  <!-- Items list -->
  <div class="cart-panel-body" id="cartPanelBody">
    <!-- Empty state -->
    <div class="cart-empty" id="cartEmpty">
      <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
      </svg>
      <p>Your cart is empty</p>
      <span>Add some products to get started</span>
    </div>
 
    <!-- Cart items will be injected here by JS -->
    <div id="cartItemsList"></div>
  </div>
 
  <!-- Footer -->
  <div class="cart-panel-footer" id="cartPanelFooter" style="display:none;">
    <div class="cart-total-row">
      <span class="cart-total-label">Total:</span>
      <span class="cart-total-price" id="cartTotalPrice">₱0.00</span>
    </div>
    <button class="cart-checkout-btn" id="cartCheckoutBtn">Checkout</button>
  </div>
 
</aside>

<script>
(function() {
    const PRODUCT_IMGS_BASE = '/ecommerce-system/imgs/products/';

    // Get cart data from localStorage
    function getCartItems() {
        const stored = localStorage.getItem('ecommerce_cart');
        return stored ? JSON.parse(stored) : [];
    }

    // Save cart data back to localStorage
    function saveCartItems(items) {
        localStorage.setItem('ecommerce_cart', JSON.stringify(items));
    }

    // Update all UI elements
    function renderCartPanel() {
        const items = getCartItems();
        const panelCount = document.getElementById('panelCount');
        const cartItemsList = document.getElementById('cartItemsList');
        const cartEmpty = document.getElementById('cartEmpty');
        const cartFooter = document.getElementById('cartPanelFooter');
        const cartTotalPrice = document.getElementById('cartTotalPrice');
        const headerCount = document.getElementById('cartCount'); // in header

        // Update counts
        const totalQty = items.reduce((sum, item) => sum + item.quantity, 0);
        if (panelCount) panelCount.textContent = `(${totalQty})`;
        if (headerCount) headerCount.textContent = `(${totalQty})`;

        if (items.length === 0) {
            if (cartItemsList) cartItemsList.innerHTML = '';
            if (cartEmpty) cartEmpty.style.display = 'flex';
            if (cartFooter) cartFooter.style.display = 'none';
            return;
        }

        // Hide empty state, show footer
        if (cartEmpty) cartEmpty.style.display = 'none';
        if (cartFooter) cartFooter.style.display = 'block';

        // Build items HTML using CSS class names from cart-panel.css
        let itemsHtml = '';
        let subtotal = 0;

        items.forEach(item => {
            const itemTotal = item.price * item.quantity;
            subtotal += itemTotal;

            itemsHtml += `
                <div class="cart-item" data-variant-id="${item.variantId}">
                    <img class="cart-item-img" src="${PRODUCT_IMGS_BASE}${item.image}" alt="${item.name}">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                        <div class="cart-item-qty">
                            <button class="qty-btn" data-action="dec" data-variant-id="${item.variantId}">−</button>
                            <span class="qty-val">${item.quantity}</span>
                            <button class="qty-btn" data-action="inc" data-variant-id="${item.variantId}">+</button>
                        </div>
                    </div>
                    <div class="cart-item-actions">
                        <button class="cart-item-delete" data-variant-id="${item.variantId}" title="Remove item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="3 6 5 6 21 6"></polyline>
                                <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                <line x1="10" y1="11" x2="10" y2="17"></line>
                                <line x1="14" y1="11" x2="14" y2="17"></line>
                            </svg>
                        </button>
                    </div>
                </div>
            `;
        });

        if (cartItemsList) cartItemsList.innerHTML = itemsHtml;
        if (cartTotalPrice) cartTotalPrice.textContent = `₱${subtotal.toFixed(2)}`;

        // Attach event listeners
        document.querySelectorAll('.qty-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const variantId = parseInt(btn.dataset.variantId);
                const action = btn.dataset.action;
                const delta = action === 'inc' ? 1 : -1;
                updateQuantity(variantId, delta);
            });
        });
        document.querySelectorAll('.cart-item-delete').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const variantId = parseInt(btn.dataset.variantId);
                removeItem(variantId);
            });
        });
    }

    function updateQuantity(variantId, delta) {
        const items = getCartItems();
        const item = items.find(i => i.variantId === variantId);
        if (!item) return;

        const newQty = item.quantity + delta;
        if (newQty <= 0) {
            const filtered = items.filter(i => i.variantId !== variantId);
            saveCartItems(filtered);
        } else {
            item.quantity = newQty;
            saveCartItems(items);
        }
        renderCartPanel();
    }

    function removeItem(variantId) {
        const items = getCartItems();
        const filtered = items.filter(i => i.variantId !== variantId);
        saveCartItems(filtered);
        renderCartPanel();
    }

    function setupPanelToggle() {
        const overlay = document.getElementById('cartOverlay');
        const panel = document.getElementById('cartPanel');
        const closeBtn = document.getElementById('cartPanelClose');
        const cartBtn = document.getElementById('cartBtn'); // header cart button

        function openPanel() {
            if (overlay) overlay.classList.add('open');
            if (panel) panel.classList.add('open');
            renderCartPanel(); // refresh when opened
        }

        function closePanel() {
            if (overlay) overlay.classList.remove('open');
            if (panel) panel.classList.remove('open');
        }

        if (cartBtn) {
            cartBtn.addEventListener('click', (e) => {
                e.preventDefault();
                openPanel();
            });
        }

        if (closeBtn) {
            closeBtn.addEventListener('click', closePanel);
        }

        if (overlay) {
            overlay.addEventListener('click', closePanel);
        }
    }

    // Checkout button
    document.getElementById('cartCheckoutBtn')?.addEventListener('click', () => {
        window.location.href = '../user/checkout.php';
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', () => {
        renderCartPanel();
        setupPanelToggle();
    });

    // If CartPanel class exists, sync with it
    if (typeof CartPanel !== 'undefined') {
        const originalSave = CartPanel.save;
        CartPanel.save = function() {
            originalSave.call(CartPanel);
            renderCartPanel();
        };
        CartPanel.init();
    }
})();
</script>