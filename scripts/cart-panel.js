/**
 * cart-panel.js
 * ─────────────────────────────────────────────────────
 * Drop this script into any page that includes cart-panel.php.
 * It manages:
 *   - Opening / closing the side panel
 *   - A shared in-memory cart (ready to swap for localStorage / API later)
 *   - Adding, removing, and updating item quantities
 *   - Keeping the cart count badge in the nav in sync
 *
 * To add an item from any page, call:
 *   CartPanel.addItem({ id, name, price, image })
 * ─────────────────────────────────────────────────────
 */

const CartPanel = (() => {

  // ── State ────────────────────────────────────────────
  let items = []; // [{ id, name, price, image, qty }]

  // ── DOM refs (resolved after DOMContentLoaded) ───────
  let panel, overlay, closeBtn, body, itemsList,
      emptyState, footer, totalEl, countEl, navCountEl, checkoutBtn;

  // ── Init ─────────────────────────────────────────────
  function init() {
    panel      = document.getElementById('cartPanel');
    overlay    = document.getElementById('cartOverlay');
    closeBtn   = document.getElementById('cartPanelClose');
    body       = document.getElementById('cartPanelBody');
    itemsList  = document.getElementById('cartItemsList');
    emptyState = document.getElementById('cartEmpty');
    footer     = document.getElementById('cartPanelFooter');
    totalEl    = document.getElementById('cartTotalPrice');
    countEl    = document.getElementById('panelCount');
    checkoutBtn= document.getElementById('cartCheckoutBtn');

    // Nav cart badge — works with both the dashboard and product page markup
    navCountEl = document.getElementById('cartCount');

    if (!panel) return; // panel not present on this page

       // Open panel when cart button is clicked (fixed selector)
    document.querySelectorAll('.cart-btn, #navCart').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        open();
      });
    });
    // Close
    closeBtn.addEventListener('click', close);
    overlay.addEventListener('click', close);

    // Keyboard ESC
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') close();
    });

    // Checkout
    checkoutBtn.addEventListener('click', () => {
      showToast('Proceeding to checkout…');
      close();
    });
  }

  // ── Open / Close ─────────────────────────────────────
  function open() {
    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    panel.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

   // ── Add item (public API) ─────────────────────────────
  function addItem(product) {
    // Safety: make sure init has run
    if (!panel) init();

    const existing = items.find(i => i.id === product.id);
    if (existing) {
      existing.qty++;
    } else {
      items.push({ ...product, qty: 1 });
    }
    render();
    syncNavBadge();
    open();           // This forces the panel to open when item is added
  }

  // ── Remove item ───────────────────────────────────────
  function removeItem(id) {
    items = items.filter(i => i.id !== id);
    render();
    syncNavBadge();
  }

  // ── Update quantity ───────────────────────────────────
  function updateQty(id, delta) {
    const item = items.find(i => i.id === id);
    if (!item) return;
    item.qty = Math.max(1, item.qty + delta);
    render();
    syncNavBadge();
  }

  // ── Render ────────────────────────────────────────────
  function render() {
    if (!itemsList) return;

    const totalQty   = items.reduce((s, i) => s + i.qty, 0);
    const totalPrice = items.reduce((s, i) => s + i.price * i.qty, 0);

    // Count badge in panel header
    countEl.textContent = `(${totalQty})`;

    // Empty / filled state
    if (items.length === 0) {
      emptyState.style.display = 'flex';
      itemsList.innerHTML = '';
      footer.style.display = 'none';
      return;
    }

    emptyState.style.display = 'none';
    footer.style.display = 'block';
    totalEl.textContent = '$' + totalPrice.toFixed(2);

    // Build item cards
    itemsList.innerHTML = items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <img class="cart-item-img"
          src="${item.image}"
          alt="${item.name}"
          onerror="this.src='https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=160&q=75'" />
        <div class="cart-item-info">
          <div class="cart-item-name">${item.name}</div>
          <div class="cart-item-price">$${(item.price * item.qty).toFixed(2)}</div>
          <div class="cart-item-qty">
            <button class="qty-btn" data-action="dec" data-id="${item.id}">−</button>
            <span class="qty-val">${item.qty}</span>
            <button class="qty-btn" data-action="inc" data-id="${item.id}">+</button>
          </div>
        </div>
        <div class="cart-item-actions">
          <button class="cart-item-confirm" data-id="${item.id}" title="Save">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="20 6 9 17 4 12"/>
            </svg>
          </button>
          <button class="cart-item-delete" data-id="${item.id}" title="Remove">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="3 6 5 6 21 6"/>
              <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
              <path d="M10 11v6M14 11v6"/>
              <path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/>
            </svg>
          </button>
        </div>
      </div>
    `).join('');

    // Bind qty and delete buttons
    itemsList.querySelectorAll('.qty-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const delta = btn.dataset.action === 'inc' ? 1 : -1;
        updateQty(btn.dataset.id, delta);
      });
    });

    itemsList.querySelectorAll('.cart-item-confirm').forEach(btn => {
      btn.addEventListener('click', () => showToast('Item saved ✓'));
    });

    itemsList.querySelectorAll('.cart-item-delete').forEach(btn => {
      btn.addEventListener('click', () => {
        removeItem(btn.dataset.id);
        showToast('Item removed from cart.');
      });
    });
  }

  // ── Sync the nav "Your cart (n)" badge ───────────────
  function syncNavBadge() {
    if (!navCountEl) return;
    const total = items.reduce((s, i) => s + i.qty, 0);
    navCountEl.textContent = `(${total})`;
  }

  // ── Toast (shared, works on any page) ────────────────
  function showToast(msg) {
    let toast = document.getElementById('toast');
    if (!toast) {
      // Create one if the page doesn't already have it
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.className = 'toast';
      toast.innerHTML = `
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span id="toastMsg"></span>`;
      document.body.appendChild(toast);
    }
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 2600);
  }

  // ── Run on DOM ready ──────────────────────────────────
  document.addEventListener('DOMContentLoaded', init);

  // ── Public API ────────────────────────────────────────
  return { addItem, removeItem, updateQty, open, close };

})();