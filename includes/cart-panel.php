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
      <span class="cart-total-price" id="cartTotalPrice">$0.00</span>
    </div>
    <button class="cart-checkout-btn" id="cartCheckoutBtn">Checkout</button>
  </div>
 
</aside>