/**
 * cart-panel.js – Server‑backed cart (PostgreSQL)
 * Uses: get-cart.php, update-cart.php, remove-cart.php
 */

const CartPanel = (() => {

  let panel, overlay, closeBtn, itemsList, emptyState, footer, totalEl, countEl, navCountEl, checkoutBtn;

  // ── Init ─────────────────────────────────────────────
  function init() {
    panel      = document.getElementById('cartPanel');
    overlay    = document.getElementById('cartOverlay');
    closeBtn   = document.getElementById('cartPanelClose');
    itemsList  = document.getElementById('cartItemsList');
    emptyState = document.getElementById('cartEmpty');
    footer     = document.getElementById('cartPanelFooter');
    totalEl    = document.getElementById('cartTotalPrice');
    countEl    = document.getElementById('panelCount');
    checkoutBtn= document.getElementById('cartCheckoutBtn');
    navCountEl = document.getElementById('cartCount');

    if (!panel) return;

    // Open buttons
    document.querySelectorAll('#cartBtn, #navCart, .cart-btn').forEach(btn => {
      btn.addEventListener('click', (e) => { e.preventDefault(); open(); });
    });
    closeBtn?.addEventListener('click', close);
    overlay?.addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    checkoutBtn?.addEventListener('click', () => { window.location.href = '../user/checkout.php'; });

    render(); // initial load
  }

  // ── Open / Close ─────────────────────────────────────
  function open() {
    panel.classList.add('open');
    overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
    render(); // refresh when opened
  }

  function close() {
    panel.classList.remove('open');
    overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  // ── Fetch cart from server ──────────────────────────
  async function fetchCart() {
    try {
      const res = await fetch('../includes/get-cart.php');
      return await res.json();
    } catch (err) {
      console.error('Failed to load cart:', err);
      return [];
    }
  }

  // ── Update quantity ──────────────────────────────────
  async function updateQty(cartId, delta) {
    try {
      const res = await fetch('../includes/update-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId, delta })
      });
      const data = await res.json();
      if (data.success) await render();
      else toast('Error updating quantity');
    } catch (err) {
      console.error(err);
      toast('Something went wrong');
    }
  }

  // ── Remove item ──────────────────────────────────────
  async function removeItem(cartId) {
    try {
      const res = await fetch('../includes/remove-cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart_id: cartId })
      });
      const data = await res.json();
      if (data.success) await render();
      else toast('Error removing item');
    } catch (err) {
      console.error(err);
      toast('Something went wrong');
    }
  }

  // ── Render cart from server data ─────────────────────
  async function render() {
    const items = await fetchCart();
    const totalQty = items.reduce((s, i) => s + i.quantity, 0);
    const totalPrice = items.reduce((s, i) => s + i.price * i.quantity, 0);

    if (countEl) countEl.textContent = `(${totalQty})`;
    if (navCountEl) navCountEl.textContent = `(${totalQty})`;

    if (!items.length) {
      if (emptyState) emptyState.style.display = 'flex';
      if (itemsList) itemsList.innerHTML = '';
      if (footer) footer.style.display = 'none';
      return;
    }

    if (emptyState) emptyState.style.display = 'none';
    if (footer) footer.style.display = 'block';
    if (totalEl) totalEl.textContent = `₱${totalPrice.toFixed(2)}`;

    if (itemsList) {
      itemsList.innerHTML = items.map(item => `
        <div class="cart-item">
          <img class="cart-item-img"
               src="/ecommerce-system/imgs/products/${item.image}"
               onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'70\' height=\'70\' viewBox=\'0 0 70 70\'%3E%3Crect width=\'70\' height=\'70\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'12\'%3ENo img%3C/text%3E%3C/svg%3E'"
               alt="${item.name}">
          <div class="cart-item-info">
            <div class="cart-item-name">${escapeHtml(item.name)}</div>
            <div class="cart-item-price">₱${item.price}</div>
            <div class="cart-item-qty">
              <button onclick="CartPanel.updateQty(${item.variantId}, -1)">−</button>
              <span>${item.quantity}</span>
              <button onclick="CartPanel.updateQty(${item.variantId}, 1)">+</button>
            </div>
          </div>
          <button class="cart-item-delete" onclick="CartPanel.removeItem(${item.variantId})">✕</button>
        </div>
      `).join('');
    }
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

  function toast(msg) {
    let toast = document.getElementById('toast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'toast';
      toast.className = 'toast';
      toast.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg><span id="toastMsg"></span>`;
      document.body.appendChild(toast);
    }
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => toast.classList.remove('show'), 2600);
  }

  document.addEventListener('DOMContentLoaded', init);

  return { open, close, updateQty, removeItem, render };
})();