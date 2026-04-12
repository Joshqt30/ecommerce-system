<?php

include '../includes/cart-panel.php';
include '../includes/header.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>E-Commerce – Product Page</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/cart-panel.css">
  <link rel="stylesheet" href="../assets/css/product.css">
  <link rel="stylesheet" href="../assets/css/header.css">
</head>
<body>


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
          <input class="qty-value" id="qtyVal" type="text" value="1" readonly/>
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
<script src="../scripts/viewitems.js"></script>

</body>
</html>