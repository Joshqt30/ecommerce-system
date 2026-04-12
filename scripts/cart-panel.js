// CartPanel JavaScript
const CartPanel = (() => {
  let items = [];
  let panel, overlay, closeBtn, itemsList, emptyState, footer, totalEl, countEl, navCountEl, checkoutBtn;

  function init() {
    panel = document.getElementById('cartPanel');
    overlay = document.getElementById('cartOverlay');
    closeBtn = document.getElementById('cartPanelClose');
    itemsList = document.getElementById('cartItemsList');
    emptyState = document.getElementById('cartEmpty');
    footer = document.getElementById('cartPanelFooter');
    totalEl = document.getElementById('cartTotalPrice');
    countEl = document.getElementById('panelCount');
    checkoutBtn = document.getElementById('cartCheckoutBtn');
    navCountEl = document.getElementById('cartCount');

    if (!panel) return;

    document.querySelectorAll('.cart-btn, #cartBtn').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        open();
      });
    });

    if (closeBtn) closeBtn.addEventListener('click', close);
    if (overlay) overlay.addEventListener('click', close);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
    if (checkoutBtn) checkoutBtn.addEventListener('click', () => { showToast('Proceeding to checkout…'); close(); });
  }

  function open() {
    if (panel) panel.classList.add('open');
    if (overlay) overlay.classList.add('open');
    document.body.style.overflow = 'hidden';
  }

  function close() {
    if (panel) panel.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
  }

  function addItem(product) {
    const existing = items.find(i => i.id === product.id);
    if (existing) existing.qty++;
    else items.push({ ...product, qty: 1 });
    render();
    syncNavBadge();
    open();
  }

  function removeItem(id) {
    items = items.filter(i => i.id !== id);
    render();
    syncNavBadge();
  }

  function updateQty(id, delta) {
    const item = items.find(i => i.id === id);
    if (!item) return;
    item.qty = Math.max(1, item.qty + delta);
    render();
    syncNavBadge();
  }

  function render() {
    if (!itemsList) return;
    const totalQty = items.reduce((s, i) => s + i.qty, 0);
    const totalPrice = items.reduce((s, i) => s + i.price * i.qty, 0);
    if (countEl) countEl.textContent = `(${totalQty})`;

    if (items.length === 0) {
      if (emptyState) emptyState.style.display = 'flex';
      itemsList.innerHTML = '';
      if (footer) footer.style.display = 'none';
      return;
    }
    if (emptyState) emptyState.style.display = 'none';
    if (footer) footer.style.display = 'block';
    if (totalEl) totalEl.textContent = '$' + totalPrice.toFixed(2);

    itemsList.innerHTML = items.map(item => `
      <div class="cart-item" data-id="${item.id}">
        <img class="cart-item-img" src="${item.image}" onerror="this.src='https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=160&q=75'">
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
          <button class="cart-item-delete" data-id="${item.id}">🗑️</button>
        </div>
      </div>
    `).join('');

    itemsList.querySelectorAll('.qty-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const delta = btn.dataset.action === 'inc' ? 1 : -1;
        updateQty(btn.dataset.id, delta);
      });
    });
    itemsList.querySelectorAll('.cart-item-delete').forEach(btn => {
      btn.addEventListener('click', () => removeItem(btn.dataset.id));
    });
  }

  function syncNavBadge() {
    if (!navCountEl) return;
    const total = items.reduce((s, i) => s + i.qty, 0);
    navCountEl.textContent = `(${total})`;
  }

  function showToast(msg) {
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
    setTimeout(() => toast.classList.remove('show'), 2600);
  }

  document.addEventListener('DOMContentLoaded', init);
  return { addItem, removeItem, updateQty, open, close };
})();