<?php
$category = isset($_GET['cat']) ? htmlspecialchars($_GET['cat']) : 'Computers & Laptops';
include '../includes/cart-panel.php';
include '../includes/header.php';
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $category ?> – E-Commerce</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/cart-panel.css">
    <link rel="stylesheet" href="../assets/css/category.css">
</head>
<body>

    <!-- Search Bar -->
    <div class="search-bar">
        <div class="search-field-wrap">
            <div class="search-field" id="searchField">
                <input type="text" placeholder="Search for products" id="searchInput" autocomplete="off" />
                <button class="search-btn" id="searchBtn" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </div>
            <div class="search-results" id="searchResults"></div>
        </div>
    </div>

    <!-- ── Page body ──────────────────────────────────── -->
    <main class="category-page">

        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="../user/dashboard.php">Home</a>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 18l6-6-6-6"/>
            </svg>
            <span><?= $category ?></span>
        </nav>

        <!-- Header row: title + sort -->
        <div class="category-header">
            <div>
                <h1 class="category-heading"><?= $category ?></h1>
                <p class="category-count" id="productCount">Showing 6 products</p>
            </div>
            <div class="sort-wrap">
                <label class="sort-label">Sort by</label>
                <select class="sort-select" id="sortSelect">
                    <option value="default">Featured</option>
                    <option value="price-asc">Price: Low to High</option>
                    <option value="price-desc">Price: High to Low</option>
                    <option value="name">Name A–Z</option>
                </select>
            </div>
        </div>

        <!-- Filter + Grid layout -->
        <div class="category-layout">

            <!-- ── Sidebar filters ── -->
            <aside class="filter-sidebar">
                <div class="filter-group">
                    <h3 class="filter-title">Price Range</h3>
                    <div class="price-range-labels">
                        <span id="priceMin">$0</span>
                        <span id="priceMax">$2000</span>
                    </div>
                    <input type="range" id="priceRange" min="0" max="2000" value="2000"
                           class="price-slider">
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">Brand</h3>
                    <div class="filter-options">
                        <label class="filter-check"><input type="checkbox" checked> All</label>
                        <label class="filter-check"><input type="checkbox"> Apple</label>
                        <label class="filter-check"><input type="checkbox"> Samsung</label>
                        <label class="filter-check"><input type="checkbox"> Dell</label>
                        <label class="filter-check"><input type="checkbox"> Lenovo</label>
                        <label class="filter-check"><input type="checkbox"> Sony</label>
                    </div>
                </div>

                <div class="filter-group">
                    <h3 class="filter-title">Rating</h3>
                    <div class="filter-options">
                        <label class="filter-check"><input type="checkbox"> ★★★★★ 5</label>
                        <label class="filter-check"><input type="checkbox"> ★★★★☆ 4 & up</label>
                        <label class="filter-check"><input type="checkbox"> ★★★☆☆ 3 & up</label>
                    </div>
                </div>
            </aside>

            <!-- ── Product grid ── -->
            <section class="product-grid" id="productGrid">

                <!-- Product card template (repeated) -->
                <!-- Card 1 -->
                <article class="product-card" data-price="298" data-name="MacBook Air M3">
                    <div class="card-img-wrap">
                        <img src="https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&q=80"
                             alt="MacBook Air M3" class="card-img"/>
                        <button class="card-wishlist" aria-label="Save to wishlist">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="card-name">MacBook Air M3</p>
                        <p class="card-desc">13-inch, 8GB RAM, 256GB SSD — ultra-thin and fast</p>
                        <div class="card-stars">★★★★★ <span class="card-reviews">(128)</span></div>
                        <div class="card-footer">
                            <span class="card-price">$298.00</span>
                            <div class="card-actions">
                                <button class="btn-cart" onclick="addToCart(this, 1, 'MacBook Air M3', 298, 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&q=80')">Add to Cart</button>
                                <button class="btn-buy" onclick="buyNow()">Buy now</button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Card 2 -->
                <article class="product-card" data-price="199" data-name="Dell XPS 15">
                    <div class="card-img-wrap">
                        <img src="https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400&q=80"
                             alt="Dell XPS 15" class="card-img"/>
                        <button class="card-wishlist" aria-label="Save to wishlist">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="card-name">Dell XPS 15</p>
                        <p class="card-desc">15.6-inch OLED, Intel i7, 512GB SSD performance laptop</p>
                        <div class="card-stars">★★★★☆ <span class="card-reviews">(94)</span></div>
                        <div class="card-footer">
                            <span class="card-price">$199.00</span>
                            <div class="card-actions">
                                <button class="btn-cart" onclick="addToCart(this, 2, 'Dell XPS 15', 199, 'https://images.unsplash.com/photo-1588872657578-7efd1f1555ed?w=400&q=80')">Add to Cart</button>
                                <button class="btn-buy" onclick="buyNow()">Buy now</button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Card 3 -->
                <article class="product-card" data-price="499" data-name="Lenovo ThinkPad X1">
                    <div class="card-img-wrap">
                        <img src="https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=400&q=80"
                             alt="Lenovo ThinkPad X1" class="card-img"/>
                        <button class="card-wishlist" aria-label="Save to wishlist">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                        <span class="card-badge">Sale</span>
                    </div>
                    <div class="card-body">
                        <p class="card-name">Lenovo ThinkPad X1 Carbon</p>
                        <p class="card-desc">14-inch, Intel i5, 16GB RAM, lightweight business laptop</p>
                        <div class="card-stars">★★★★★ <span class="card-reviews">(211)</span></div>
                        <div class="card-footer">
                            <span class="card-price">$499.00</span>
                            <div class="card-actions">
                                <button class="btn-cart" onclick="addToCart(this, 3, 'Lenovo ThinkPad X1 Carbon', 499, 'https://images.unsplash.com/photo-1541807084-5c52b6b3adef?w=400&q=80')">Add to Cart</button>
                                <button class="btn-buy" onclick="buyNow()">Buy now</button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Card 4 -->
                <article class="product-card" data-price="349" data-name="Samsung Galaxy Book">
                    <div class="card-img-wrap">
                        <img src="https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=400&q=80"
                             alt="Samsung Galaxy Book" class="card-img"/>
                        <button class="card-wishlist" aria-label="Save to wishlist">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="card-name">Samsung Galaxy Book Pro</p>
                        <p class="card-desc">15.6-inch AMOLED display, Intel Evo platform</p>
                        <div class="card-stars">★★★★☆ <span class="card-reviews">(76)</span></div>
                        <div class="card-footer">
                            <span class="card-price">$349.00</span>
                            <div class="card-actions">
                                <button class="btn-cart" onclick="addToCart(this, 4, 'Samsung Galaxy Book Pro', 349, 'https://images.unsplash.com/photo-1525547719571-a2d4ac8945e2?w=400&q=80')">Add to Cart</button>
                                <button class="btn-buy" onclick="buyNow()">Buy now</button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Card 5 -->
                <article class="product-card" data-price="129" data-name="Acer Chromebook">
                    <div class="card-img-wrap">
                        <img src="https://images.unsplash.com/photo-1484788984921-03950022c9ef?w=400&q=80"
                             alt="Acer Chromebook" class="card-img"/>
                        <button class="card-wishlist" aria-label="Save to wishlist">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                        <span class="card-badge new">New</span>
                    </div>
                    <div class="card-body">
                        <p class="card-name">Acer Chromebook Spin 714</p>
                        <p class="card-desc">14-inch convertible, Intel Core i3, 8GB RAM</p>
                        <div class="card-stars">★★★☆☆ <span class="card-reviews">(43)</span></div>
                        <div class="card-footer">
                            <span class="card-price">$129.00</span>
                            <div class="card-actions">
                                <button class="btn-cart" onclick="addToCart(this, 5, 'Acer Chromebook Spin 714', 129, 'https://images.unsplash.com/photo-1484788984921-03950022c9ef?w=400&q=80')">Add to Cart</button>
                                <button class="btn-buy" onclick="buyNow()">Buy now</button>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Card 6 -->
                <article class="product-card" data-price="899" data-name="Apple MacBook Pro">
                    <div class="card-img-wrap">
                        <img src="https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&q=80"
                             alt="Apple MacBook Pro" class="card-img"/>
                        <button class="card-wishlist" aria-label="Save to wishlist">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>
                    </div>
                    <div class="card-body">
                        <p class="card-name">Apple MacBook Pro 16"</p>
                        <p class="card-desc">M3 Pro chip, 18GB RAM, 512GB SSD — pro-level performance</p>
                        <div class="card-stars">★★★★★ <span class="card-reviews">(305)</span></div>
                        <div class="card-footer">
                            <span class="card-price">$899.00</span>
                            <div class="card-actions">
                                <button class="btn-cart" onclick="addToCart(this, 6, 'Apple MacBook Pro  16&quot;', 899, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=400&q=80')">Add to Cart</button>
                                <button class="btn-buy" onclick="buyNow()">Buy now</button>
                            </div>
                        </div>
                    </div>
                </article>

            </section>
        </div><!-- /category-layout -->

        <!-- Pagination -->
        <div class="pagination">
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
            <button class="page-btn page-next">
                Next
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </button>
        </div>

    </main>

    <!-- Toast -->
    <div class="toast" id="toast">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <span id="toastMsg">Done</span>
    </div>

    <script src="../scripts/cart-panel.js"></script>
    <script>
    // ── Toast ───────────────────────────────────────────
    function toast(msg) {
        const el = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2600);
    }

    // ── Add to cart ─────────────────────────────────────
    function addToCart(btn, id, name, price, image) {
        if (typeof CartPanel !== 'undefined') {
            CartPanel.addItem({ id, name, price, image });
        }
        // Button feedback
        btn.textContent = 'Added ✓';
        btn.classList.add('added');
        setTimeout(() => {
            btn.textContent = 'Add to Cart';
            btn.classList.remove('added');
        }, 1800);
    }

    // ── Buy now ─────────────────────────────────────────
    function buyNow() {
        toast('Redirecting to checkout… (demo)');
    }

    // ── Wishlist toggle ─────────────────────────────────
    document.querySelectorAll('.card-wishlist').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const active = btn.classList.toggle('wished');
            btn.querySelector('svg').setAttribute('fill', active ? '#ef4444' : 'none');
            btn.querySelector('svg').setAttribute('stroke', active ? '#ef4444' : 'currentColor');
            toast(active ? 'Added to wishlist ♥' : 'Removed from wishlist');
        });
    });

    // ── Sort ────────────────────────────────────────────
    document.getElementById('sortSelect').addEventListener('change', function () {
        const grid   = document.getElementById('productGrid');
        const cards  = [...grid.querySelectorAll('.product-card')];
        const sorted = cards.sort((a, b) => {
            const pa = parseFloat(a.dataset.price), pb = parseFloat(b.dataset.price);
            const na = a.dataset.name, nb = b.dataset.name;
            if (this.value === 'price-asc')  return pa - pb;
            if (this.value === 'price-desc') return pb - pa;
            if (this.value === 'name')       return na.localeCompare(nb);
            return 0;
        });
        sorted.forEach(c => grid.appendChild(c));
        toast('Sorted: ' + this.options[this.selectedIndex].text);
    });

    // ── Price filter ────────────────────────────────────
    document.getElementById('priceRange').addEventListener('input', function () {
        const max = parseInt(this.value);
        document.getElementById('priceMax').textContent = '$' + max.toLocaleString();
        document.querySelectorAll('.product-card').forEach(card => {
            const price = parseFloat(card.dataset.price);
            card.style.display = price <= max ? '' : 'none';
        });
        const visible = [...document.querySelectorAll('.product-card')].filter(c => c.style.display !== 'none').length;
        document.getElementById('productCount').textContent = `Showing ${visible} products`;
    });

    // ── Search ──────────────────────────────────────────
    const suggestions = [
        'Samsung Galaxy S24', 'iPhone 15 Pro', 'MacBook Air M3',
        'Sony WH-1000XM5', 'LG OLED TV 55"', 'Apple Watch Series 9',
        'Canon EOS R50', 'KitchenAid Stand Mixer', 'Dell XPS 15',
        'Bose QuietComfort 45', 'iPad Pro 12.9"', 'Logitech MX Master 3'
    ];
    const input   = document.getElementById('searchInput');
    const results = document.getElementById('searchResults');

    input.addEventListener('input', () => {
        const q = input.value.trim();
        if (!q) { results.classList.remove('open'); return; }
        const matches = suggestions.filter(s => s.toLowerCase().includes(q.toLowerCase()));
        if (!matches.length) { results.classList.remove('open'); return; }
        results.innerHTML = matches.map(m => `<div class="search-result-item">${m}</div>`).join('');
        results.classList.add('open');
        results.querySelectorAll('.search-result-item').forEach(row => {
            row.addEventListener('click', () => {
                input.value = row.textContent;
                results.classList.remove('open');
                toast(`Searching for "${row.textContent}"…`);
            });
        });
    });

    input.addEventListener('keydown', e => {
        if (e.key === 'Enter') { results.classList.remove('open'); if (input.value.trim()) toast(`Searching for "${input.value.trim()}"…`); }
        if (e.key === 'Escape') results.classList.remove('open');
    });
    document.getElementById('searchBtn').addEventListener('click', () => {
        results.classList.remove('open');
        if (input.value.trim()) toast(`Searching for "${input.value.trim()}"…`);
    });
    document.addEventListener('click', e => { if (!e.target.closest('.search-field-wrap')) results.classList.remove('open'); });

    // ── Pagination ──────────────────────────────────────
    document.querySelectorAll('.page-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            toast('Loading page ' + (btn.textContent.trim().includes('Next') ? 'next' : btn.textContent.trim()) + '…');
        });
    });
    </script>
</body>
</html>