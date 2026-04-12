<?php

include '../includes/cart-panel.php';

?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>E-Commerce Store</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/cart-panel.css">
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

      <!-- Search bar -->
      <div class="search-bar">
        <div class="search-field-wrap">
          <div class="search-field" id="searchField">
            <input type="text" placeholder="Search for products" id="searchInput" autocomplete="off" />
            <button class="search-btn" id="searchBtn" aria-label="Search">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
            </button>
          </div>
          <div class="search-results" id="searchResults"></div>
        </div>
      </div>
    </header>

    <!-- ── Category Boxes ─────────────────────────────── -->
    <section class="categories-section">

      <!-- Cell phones -->
      <div class="box-category">
        <div class="category-info">
          <h2 class="category-title">Cell phones</h2>
          <div class="category-links">
            <a href="#" data-category="Samsung phones">Samsung phone</a>
            <a href="#" data-category="Phone accessories">Phone accessories</a>
            <a href="#" data-category="Cases & covers">Cases &amp; covers</a>
          </div>
          <a href="#" class="view-all-btn" data-category="Cell phones">
            <span>View all</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
        <img class="category-image cat-img-phones"
          src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300&q=80"
          alt="Cell phones" />
      </div>

      <!-- Computers & Laptops -->
      <div class="box-category">
        <div class="category-info">
          <h2 class="category-title">Computers &amp; Laptops</h2>
          <div class="category-links">
            <a href="#" data-category="Monitors">Monitors</a>
            <a href="#" data-category="Apple laptops">Apple laptops</a>
            <a href="#" data-category="Web cameras">Web cameras</a>
          </div>
          <a href="#" class="view-all-btn" data-category="Computers & Laptops">
            <span>View all</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
        <img class="category-image cat-img-computers"
          src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=300&q=80"
          alt="Computers and Laptops" />
      </div>

      <!-- Kitchen equipment -->
      <div class="box-category">
        <div class="category-info">
          <h2 class="category-title">Kitchen Equipment</h2>
          <div class="category-links">
            <a href="#" data-category="Blenders & Mixers">Blenders, Mixers</a>
            <a href="#" data-category="Kitchen knives">Kitchen knife</a>
            <a href="#" data-category="Kitchen accessories">Other accessories</a>
          </div>
          <a href="#" class="view-all-btn" data-category="Kitchen Equipment">
            <span>View all</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M5 12h14M12 5l7 7-7 7"/>
            </svg>
          </a>
        </div>
        <img class="category-image cat-img-kitchen"
          src="https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=300&q=80"
          alt="Kitchen equipment" />
      </div>

    </section>

    <!-- ── Sub-category Icons ─────────────────────────── -->
    <section class="subcategories-section">

      <div class="item-subcategory" data-category="Smartphones">
        <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=160&q=75" alt="Smartphones" width="120" height="120" />
        <span class="subcategory-title">Smartphones</span>
      </div>

      <div class="item-subcategory" data-category="Headsets">
        <img src="https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=160&q=75" alt="Headsets" width="120" height="120" />
        <span class="subcategory-title">Headsets</span>
      </div>

      <div class="item-subcategory" data-category="Laptops">
        <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=160&q=75" alt="Laptops" width="120" height="120" />
        <span class="subcategory-title">Laptops</span>
      </div>

      <div class="item-subcategory" data-category="TV sets">
        <img src="https://images.unsplash.com/photo-1593784991095-a205069470b6?w=160&q=75" alt="TV sets" width="120" height="120" />
        <span class="subcategory-title">TV sets</span>
      </div>

      <div class="item-subcategory" data-category="Sound">
        <img src="https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=160&q=75" alt="Sound" width="120" height="120" />
        <span class="subcategory-title">Sound</span>
      </div>

      <div class="item-subcategory" data-category="Watches">
        <img src="https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=160&q=75" alt="Watches" width="120" height="120" />
        <span class="subcategory-title">Watches</span>
      </div>

      <div class="item-subcategory" data-category="Cameras">
        <img src="https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=160&q=75" alt="Cameras" width="120" height="120" />
        <span class="subcategory-title">Cameras</span>
      </div>

      <div class="item-subcategory" data-category="Others">
        <img src="https://images.unsplash.com/photo-1491553895911-0055eca6402d?w=160&q=75" alt="Others" width="120" height="120" />
        <span class="subcategory-title">Others</span>
      </div>

    </section>

    <!-- Toast notification -->
    <div class="toast" id="toast">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
      </svg>
      <span id="toastMsg">Done</span>
    </div>

    <script src="../scripts/cart-panel.js"></script>
    <script>
      // ── Toast helper ───────────────────────────────────────
      let cartCount = 0;

      function toast(msg) {
        const el = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2600);
      }

      // ── Cart button ────────────────────────────────────────
      document.getElementById('cartBtn').addEventListener('click', e => {
        e.preventDefault();
        toast(cartCount > 0
          ? `You have ${cartCount} item${cartCount !== 1 ? 's' : ''} in your cart.`
          : 'Your cart is empty.');
      });

      // ── Nav links ──────────────────────────────────────────
      document.querySelectorAll('.nav-links a:not(.cart-btn)').forEach(a => {
        a.addEventListener('click', e => {
          e.preventDefault();
          toast(`Navigating to ${a.textContent.trim()}…`);
        });
      });

      // ── Category sub-links ─────────────────────────────────
      document.querySelectorAll('.category-links a').forEach(a => {
        a.addEventListener('click', e => {
          e.preventDefault();
          toast(`Browsing: ${a.dataset.category || a.textContent.trim()}`);
        });
      });

      // ── View all buttons ───────────────────────────────────
      document.querySelectorAll('.view-all-btn').forEach(btn => {
        btn.addEventListener('click', e => {
          e.preventDefault();
          toast(`Viewing all in: ${btn.dataset.category}`);
        });
      });

      // ── Sub-category icons ─────────────────────────────────
      document.querySelectorAll('.item-subcategory').forEach(item => {
        item.addEventListener('click', () => {
          toast(`Browsing: ${item.dataset.category}`);
        });
      });

      // ── Search with autocomplete ───────────────────────────
      const suggestions = [
        'Samsung Galaxy S24', 'iPhone 15 Pro', 'MacBook Air M3',
        'Sony WH-1000XM5', 'LG OLED TV 55"', 'Apple Watch Series 9',
        'Canon EOS R50', 'KitchenAid Stand Mixer', 'Dell XPS 15',
        'Bose QuietComfort 45', 'iPad Pro 12.9"', 'Logitech MX Master 3',
        'Samsung 4K Monitor', 'Dyson V15 Vacuum', 'Nintendo Switch OLED'
      ];

      const input   = document.getElementById('searchInput');
      const results = document.getElementById('searchResults');
      const btn     = document.getElementById('searchBtn');

      function renderSuggestions(q) {
        if (!q.trim()) { results.classList.remove('open'); return; }
        const matches = suggestions.filter(s => s.toLowerCase().includes(q.toLowerCase()));
        if (!matches.length) { results.classList.remove('open'); return; }
        results.innerHTML = matches.map(m =>
          `<div class="search-result-item">${m}</div>`
        ).join('');
        results.classList.add('open');
        results.querySelectorAll('.search-result-item').forEach(row => {
          row.addEventListener('click', () => {
            input.value = row.textContent;
            results.classList.remove('open');
            toast(`Searching for "${row.textContent}"…`);
          });
        });
      }

      input.addEventListener('input', () => renderSuggestions(input.value));

      input.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
          e.preventDefault();
          results.classList.remove('open');
          if (input.value.trim()) toast(`Searching for "${input.value.trim()}"…`);
        }
        if (e.key === 'Escape') results.classList.remove('open');
      });

      btn.addEventListener('click', () => {
        results.classList.remove('open');
        if (input.value.trim()) toast(`Searching for "${input.value.trim()}"…`);
        else toast('Please enter a search term.');
      });

      document.addEventListener('click', e => {
        if (!e.target.closest('.search-field-wrap')) results.classList.remove('open');
      });

            if (typeof CartPanel !== 'undefined') {
        CartPanel.addItem({ id: 1, name: 'Product', price: 0, quantity: 1 });
        } else {
        console.error('CartPanel is not defined – check cart-panel.js');
        }
    </script>

  </body>
</html>