<div id="cartOverlay" class="cart-overlay"></div>
<div id="cartPanel" class="cart-panel">
    <div class="cart-header">
        <h2>Shopping Cart <span id="panelCount">(0)</span></h2>
        <button id="cartPanelClose" class="close-cart">&times;</button>
    </div>
    <div id="cartItemsList" class="cart-items-list">
        <!-- items will appear here from JavaScript -->
    </div>
<div id="cartEmpty" class="cart-empty" style="display: none;">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <circle cx="9" cy="21" r="1"/>
        <circle cx="20" cy="21" r="1"/>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
    </svg>
    <p>Your cart is empty</p>
    <small>Add items to get started</small>
    <a href="../user/category.php?cat=All" class="continue-shop">Continue Shopping</a>
</div>
    <div id="cartPanelFooter" class="cart-footer" style="display: none;">
        <div class="cart-total">
            <span>Total</span>
            <span id="cartTotalPrice">₱0.00</span>
        </div>
        <button id="cartCheckoutBtn" class="btn-checkout">Checkout →</button>
    </div>
</div>