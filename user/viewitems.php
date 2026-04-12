<?php

include '../includes/cart-panel.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-Commerce – Product Page</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/cart-panel.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg: #f5f5f7;
      --white: #ffffff;
      --text: #1a1a1a;
      --text-muted: #6b7280;
      --border: #e5e7eb;
      --accent: #2563eb;
      --accent-hover: #1d4ed8;
      --star: #f59e0b;
      --tag-bg: #f0f4ff;
      --radius: 12px;
      --shadow: 0 2px 16px rgba(0,0,0,0.07);
    }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── Header ── */
    .header {
      background: #ffffff;
      position: sticky;
      top: 0;
      z-index: 100;
      border-bottom: 1px solid rgba(0,0,0,0.06);
    }

    .nav-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 30px 30px;
    }

    .logo-wrap {
      display: flex;
      align-items: center;
      gap: 20px;
      padding: 20px;
      border: 2px solid rgba(0,0,0,0.05);
      border-radius: 20px;
      text-decoration: none;
    }

    .logo-icon {
      width: 24px;
      height: 24px;
    }

    .logo-text {
      font-size: 20px;
      font-weight: 700;
      color: #000000;
      line-height: 24.2px;
    }

    .nav-links {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .nav-links a {
      font-size: 20px;
      font-weight: 400;
      color: #000000;
      text-decoration: none;
      line-height: 24.2px;
    }

    .cart-btn {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 20px;
      background: rgba(0,0,0,0.05);
      border-radius: 20px;
      text-decoration: none;
    }

    .cart-icon {
      width: 24px;
      height: 24px;
    }

    .cart-text {
      font-size: 20px;
      font-weight: 700;
      color: #000000;
      line-height: 24.2px;
    }

    .cart-count {
      color: #FF6C6C;
    }

    /* ── MAIN LAYOUT ── */
    main {
      max-width: 1100px;
      margin: 40px auto;
      padding: 0 24px;
    }

    .product-top {
      display: grid;
      grid-template-columns: 1fr 420px;
      gap: 40px;
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 32px;
    }

    /* ── GALLERY ── */
    .gallery {
      display: flex;
      gap: 16px;
    }
    .thumbnails {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .thumb {
      width: 68px;
      height: 68px;
      border-radius: 8px;
      border: 2px solid var(--border);
      overflow: hidden;
      cursor: pointer;
      transition: border-color .2s, transform .15s;
      background: var(--bg);
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .thumb:hover { border-color: var(--accent); transform: scale(1.04); }
    .thumb.active { border-color: var(--accent); }

    .main-image {
      flex: 1;
      background: var(--bg);
      border-radius: 10px;
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 340px;
      position: relative;
    }
    .main-image img {
      max-width: 100%;
      max-height: 360px;
      object-fit: contain;
      transition: opacity .25s, transform .3s;
    }
    .main-image img.switching {
      opacity: 0;
      transform: scale(0.97);
    }

    /* ── PRODUCT INFO ── */
    .product-info { display: flex; flex-direction: column; gap: 20px; }

    .product-title {
      font-family: 'DM Serif Display', serif;
      font-size: 22px;
      line-height: 1.3;
      color: var(--text);
    }

    .product-meta {
      display: flex;
      align-items: center;
      gap: 14px;
      flex-wrap: wrap;
    }
    .stars { display: flex; align-items: center; gap: 3px; }
    .star-icon { color: var(--star); font-size: 16px; }
    .rating-val { font-weight: 600; font-size: 14px; color: var(--text); }
    .sep { color: var(--border); }
    .orders-count {
      font-size: 13px;
      color: var(--text-muted);
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .product-specs {
      display: grid;
      grid-template-columns: 90px 1fr;
      gap: 8px 0;
      font-size: 14px;
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 16px 0;
    }
    .spec-label { color: var(--text-muted); font-weight: 400; }
    .spec-val { font-weight: 500; }

    /* ── OPTIONS ── */
    .option-group { display: flex; flex-direction: column; gap: 10px; }
    .option-label { font-size: 13px; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: .05em; }
    .option-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .opt-btn {
      padding: 7px 18px;
      border-radius: 8px;
      border: 1.5px solid var(--border);
      background: var(--white);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: border-color .18s, background .18s, color .18s, transform .12s;
      color: var(--text);
    }
    .opt-btn:hover { border-color: var(--accent); background: var(--tag-bg); }
    .opt-btn.active {
      border-color: var(--accent);
      background: var(--accent);
      color: #fff;
    }
    .opt-btn:active { transform: scale(0.97); }

    /* ── QUANTITY ── */
    .quantity-row { display: flex; align-items: center; gap: 0; }
    .qty-btn {
      width: 34px;
      height: 34px;
      border: 1.5px solid var(--border);
      background: var(--white);
      border-radius: 8px;
      font-size: 18px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text);
      transition: background .15s, border-color .15s;
      line-height: 1;
    }
    .qty-btn:hover { background: var(--bg); border-color: var(--accent); }
    .qty-btn:active { transform: scale(0.93); }
    .qty-value {
      width: 44px;
      text-align: center;
      font-size: 15px;
      font-weight: 600;
      border: none;
      background: none;
      font-family: 'DM Sans', sans-serif;
    }

    /* ── PRICE & CTA ── */
    .price-block { display: flex; flex-direction: column; gap: 4px; }
    .price-label { font-size: 12px; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; }
    .price { font-family: 'DM Serif Display', serif; font-size: 30px; color: var(--text); }

    .cta-row {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .btn-cart {
      flex: 1;
      padding: 13px 0;
      border-radius: 10px;
      background: var(--accent);
      color: #fff;
      border: none;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: background .18s, transform .12s, box-shadow .18s;
      box-shadow: 0 2px 12px rgba(37,99,235,0.18);
    }
    .btn-cart:hover { background: var(--accent-hover); box-shadow: 0 4px 20px rgba(37,99,235,0.28); transform: translateY(-1px); }
    .btn-cart:active { transform: scale(0.98); }
    .btn-buy {
      flex: 1;
      padding: 13px 0;
      border-radius: 10px;
      background: var(--white);
      color: var(--text);
      border: 1.5px solid var(--border);
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 600;
      cursor: pointer;
      transition: border-color .18s, background .18s, transform .12s;
    }
    .btn-buy:hover { border-color: var(--accent); background: var(--tag-bg); }
    .btn-buy:active { transform: scale(0.98); }
    .btn-wish {
      width: 44px;
      height: 44px;
      border-radius: 10px;
      border: 1.5px solid var(--border);
      background: var(--white);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text-muted);
      transition: border-color .18s, color .18s, transform .12s;
      flex-shrink: 0;
    }
    .btn-wish:hover { border-color: #ef4444; color: #ef4444; }
    .btn-wish.wished { border-color: #ef4444; color: #ef4444; }
    .btn-wish:active { transform: scale(0.93); }

    /* ── TABS ── */
    .product-tabs {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      margin-top: 24px;
      overflow: hidden;
    }
    .tab-nav {
      display: flex;
      border-bottom: 1px solid var(--border);
      padding: 0 32px;
    }
    .tab-btn {
      padding: 16px 0;
      margin-right: 32px;
      background: none;
      border: none;
      font-family: 'DM Sans', sans-serif;
      font-size: 14px;
      font-weight: 500;
      color: var(--text-muted);
      cursor: pointer;
      border-bottom: 2px solid transparent;
      transition: color .18s, border-color .18s;
      position: relative;
      bottom: -1px;
    }
    .tab-btn:hover { color: var(--text); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); font-weight: 600; }

    .tab-content { padding: 28px 32px; display: none; animation: fadeIn .25s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }

    .tab-content p {
      font-size: 14px;
      line-height: 1.75;
      color: #4b5563;
      margin-bottom: 16px;
    }
    .tab-content p:last-child { margin-bottom: 0; }

    /* ── TOAST ── */
    .toast {
      position: fixed;
      bottom: 32px;
      right: 32px;
      background: #1a1a1a;
      color: #fff;
      padding: 14px 22px;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 10px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      transform: translateY(80px);
      opacity: 0;
      transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .3s;
      z-index: 999;
      pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }

    /* ── RESPONSIVE ── */
    @media (max-width: 820px) {
      nav { padding: 0 20px; }
      main { margin: 20px auto; }
      .product-top { grid-template-columns: 1fr; padding: 20px; gap: 24px; }
      .gallery { flex-direction: column-reverse; }
      .thumbnails { flex-direction: row; }
      .thumb { width: 56px; height: 56px; }
    }
  </style>
</head>
<body>

<!-- ── Header ── -->
<header class="header">
  <div class="nav-bar">
    <a href="#" class="logo-wrap">
      <img class="logo-icon" src="https://cdn.codia.ai/figma/DNIGD5YlSaH0gJQnZ0iH7f/img-40e47e05667e0932.png" alt="E-Commerce logo" width="24" height="24" />
      <span class="logo-text">E-Commerce</span>
    </a>
    <nav class="nav-links">
      <a href="#">About</a>
      <a href="#">Shop</a>
      <a href="#">Help</a>
      <a href="#">Profile</a>
      <a href="#" class="cart-btn" id="navCart">
        <img class="cart-icon" src="https://cdn.codia.ai/figma/DNIGD5YlSaH0gJQnZ0iH7f/img-74e35220c0190233.svg" alt="Cart" width="24" height="24" />
        <span class="cart-text">Your cart <span class="cart-count" id="cartCount">(0)</span></span>
      </a>
    </nav>
  </div>
</header>

<!-- MAIN -->
<main>
  <div class="product-top">

    <!-- GALLERY -->
    <div class="gallery">
      <div class="thumbnails" id="thumbs">
        <div class="thumb active" data-idx="0">
          <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=200&q=80" alt="Laptop top view"/>
        </div>
        <div class="thumb" data-idx="1">
          <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=200&q=80" alt="Laptop open"/>
        </div>
        <div class="thumb" data-idx="2">
          <img src="https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=200&q=80" alt="Laptop side"/>
        </div>
        <div class="thumb" data-idx="3">
          <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=200&q=80" alt="Laptop keyboard"/>
        </div>
        <div class="thumb" data-idx="4">
          <img src="https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=200&q=80" alt="Laptop closed"/>
        </div>
        <div class="thumb" data-idx="5">
          <img src="https://images.unsplash.com/photo-1484788984921-03950022c9ef?w=200&q=80" alt="Laptop desk"/>
        </div>
      </div>
      <div class="main-image">
        <img id="mainImg"
          src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=700&q=85"
          alt="Microsoft Lumia 640 XL" />
      </div>
    </div>

    <!-- INFO -->
    <div class="product-info">
      <h1 class="product-title">Microsoft Lumia 640 XL RM-1065 8GB Dual SIM</h1>

      <div class="product-meta">
        <div class="stars">
          <span class="star-icon">★</span><span class="star-icon">★</span>
          <span class="star-icon">★</span><span class="star-icon">★</span>
          <span class="star-icon" style="color:#d1d5db">★</span>
        </div>
        <span class="rating-val">4.5</span>
        <span class="sep">·</span>
        <div class="orders-count">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          154 orders
        </div>
      </div>

      <div class="product-specs">
        <span class="spec-label">Made in:</span><span class="spec-val">Australia</span>
        <span class="spec-label">Design:</span><span class="spec-val">Modern</span>
        <span class="spec-label">Delivery:</span>
        <span class="spec-val" style="color:#16a34a; display:flex; align-items:center; gap:5px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          2 days delivery
        </span>
      </div>

      <!-- SCREEN SIZE -->
      <div class="option-group">
        <div class="option-label">Screen size</div>
        <div class="option-buttons" id="colorOpts">
          <button class="opt-btn" data-val="Orange">Orange</button>
          <button class="opt-btn active" data-val="Green">Green</button>
          <button class="opt-btn" data-val="Black">Black</button>
          <button class="opt-btn" data-val="White">White</button>
        </div>
      </div>

      <!-- QUANTITY -->
      <div class="option-group">
        <div class="option-label">Quantity</div>
        <div class="quantity-row">
          <button class="qty-btn" id="qtyMinus">−</button>
          <input class="qty-value" id="qtyVal" type="text" value="0" readonly/>
          <button class="qty-btn" id="qtyPlus">+</button>
        </div>
      </div>

      <!-- PRICE -->
      <div class="price-block">
        <div class="price-label">Price</div>
        <div class="price" id="priceDisplay">$298.00</div>
      </div>

      <!-- ACTIONS -->
      <div class="cta-row">
        <button class="btn-cart" id="addToCart">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          Add to cart
        </button>
        <button class="btn-buy" id="buyNow">Buy now</button>
        <button class="btn-wish" id="wishBtn" title="Wishlist">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
        </button>
      </div>
    </div>
  </div>

  <!-- TABS -->
  <div class="product-tabs">
    <div class="tab-nav">
      <button class="tab-btn active" data-tab="description">Description</button>
      <button class="tab-btn" data-tab="reviews">Reviews</button>
      <button class="tab-btn" data-tab="company">Company</button>
      <button class="tab-btn" data-tab="usage">Usage guide</button>
    </div>
    <div class="tab-content active" id="tab-description">
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
      <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
    </div>
    <div class="tab-content" id="tab-reviews">
      <p>⭐⭐⭐⭐⭐ — <strong>James D.</strong> – "Absolutely love this laptop. Fast, sleek, and the build quality is excellent. Highly recommend for anyone looking for a reliable machine."</p>
      <p>⭐⭐⭐⭐ — <strong>Sarah M.</strong> – "Great value for the price. Battery life is impressive and the screen is crisp. Setup was easy."</p>
      <p>⭐⭐⭐⭐ — <strong>Ali K.</strong> – "Good laptop overall. Arrived well-packaged and ahead of schedule. Performance is solid for everyday tasks."</p>
    </div>
    <div class="tab-content" id="tab-company">
      <p>We are a premium electronics retailer committed to delivering quality products directly to your door. Every item is sourced from verified manufacturers with a full warranty and dedicated customer support.</p>
      <p>Founded in 2015, our company has served over 500,000 customers worldwide, with a focus on transparency, fast delivery, and hassle-free returns.</p>
    </div>
    <div class="tab-content" id="tab-usage">
      <p><strong>Getting Started:</strong> Charge the device fully before first use. Press and hold the power button for 3 seconds to boot.</p>
      <p><strong>Care Instructions:</strong> Use a soft microfiber cloth for cleaning. Avoid exposing the device to extreme temperatures or moisture. Store in a cool, dry place when not in use.</p>
      <p><strong>Warranty:</strong> This product comes with a 12-month manufacturer warranty. Contact our support team for any hardware-related concerns.</p>
    </div>
  </div>
</main>

<!-- TOAST -->
<div class="toast" id="toast">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg">Item added to cart!</span>
</div>


<script src="../scripts/cart-panel.js"></script>
<script>
  const BASE_PRICE = 298;
  let qty = 0;
  let cartCount = 0;
  let wished = false;

  // Thumbnails
  const thumbImages = [
    "https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=700&q=85",
    "https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=700&q=85",
    "https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=700&q=85",
    "https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=700&q=85",
    "https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=700&q=85",
    "https://images.unsplash.com/photo-1484788984921-03950022c9ef?w=700&q=85"
  ];

  const mainImg = document.getElementById('mainImg');
  document.querySelectorAll('.thumb').forEach(t => {
    t.addEventListener('click', () => {
      document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      mainImg.classList.add('switching');
      setTimeout(() => {
        mainImg.src = thumbImages[+t.dataset.idx];
        mainImg.classList.remove('switching');
      }, 200);
    });
  });

  // Color options
  document.querySelectorAll('#colorOpts .opt-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('#colorOpts .opt-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
    });
  });

  // Quantity
  function updatePrice() {
    const total = qty > 0 ? (BASE_PRICE * qty).toFixed(2) : BASE_PRICE.toFixed(2);
    document.getElementById('priceDisplay').textContent = '$' + total;
    document.getElementById('qtyVal').value = qty;
  }
  document.getElementById('qtyPlus').addEventListener('click', () => { qty++; updatePrice(); });
  document.getElementById('qtyMinus').addEventListener('click', () => { if (qty > 0) { qty--; updatePrice(); } });

  // Cart
  function showToast(msg) {
    const toast = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 2800);
  }

  document.getElementById('addToCart').addEventListener('click', () => {
    const amount = qty > 0 ? qty : 1;
    if (qty === 0) { qty = 1; updatePrice(); }
    cartCount += amount;
    document.getElementById('cartCount').textContent = '(' + cartCount + ')';
    showToast(`${amount} item${amount > 1 ? 's' : ''} added to cart!`);
  });

  document.getElementById('buyNow').addEventListener('click', () => {
    const amount = qty > 0 ? qty : 1;
    showToast(`Proceeding to checkout for ${amount} item${amount > 1 ? 's' : ''}…`);
  });


  // Wishlist
  document.getElementById('wishBtn').addEventListener('click', () => {
    wished = !wished;
    const btn = document.getElementById('wishBtn');
    btn.classList.toggle('wished', wished);
    btn.innerHTML = wished
      ? `<svg width="17" height="17" viewBox="0 0 24 24" fill="#ef4444" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`
      : `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>`;
    showToast(wished ? 'Added to wishlist ♥' : 'Removed from wishlist');
  });

  // Tabs
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });

     if (typeof CartPanel !== 'undefined') {
        CartPanel.addItem({ id: 1, name: 'Product', price: 0, quantity: 1 });
        } else {
        console.error('CartPanel is not defined – check cart-panel.js');
        }

</script>
</body>
</html>