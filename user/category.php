<?php
include '../config/db.php'; // ✅ ADD THIS

$category = isset($_GET['cat']) ? $_GET['cat'] : 'All';
$search = isset($_GET['search']) ? $_GET['search'] : '';

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
                    <?php
                include '../config/db.php';

                $category = isset($_GET['cat']) ? $_GET['cat'] : 'All';
                $search = isset($_GET['search']) ? $_GET['search'] : '';

                // Build the SQL query
                if (!empty($search)) {
                    $search_escaped = $conn->real_escape_string($search);
                    $sql = "SELECT * FROM products WHERE name LIKE '%$search_escaped%' OR description LIKE '%$search_escaped%'";
                } elseif ($category == "All" || $category == "Others") {
                    $sql = "SELECT * FROM products";
                } else {
                    $sql = "SELECT * FROM products WHERE category = '$category'";
                }

                $result = $conn->query($sql);

                // Debug: kung walang result, ipakita ang error at SQL
                if (!$result) {
                    echo "<p>SQL Error: " . $conn->error . "</p>";
                    echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";
                } elseif ($result->num_rows > 0) {
                    while($row = $result->fetch_assoc()):
                ?>
                        <article class="product-card" data-price="<?= $row['price'] ?>" data-name="<?= $row['name'] ?>">
                            <div class="card-img-wrap">
                                <img src="<?= $row['image'] ?>" class="card-img"/>
                            </div>
                            <div class="card-body">
                                <p class="card-name"><?= $row['name'] ?></p>
                                <p class="card-desc"><?= $row['description'] ?></p>
                                <div class="card-footer">
                                    <span class="card-price">$<?= $row['price'] ?></span>
                                    <div class="card-actions">
                                        <button class="btn-cart" onclick="addToCart(this, <?= $row['id'] ?>, '<?= $row['name'] ?>', <?= $row['price'] ?>, '<?= $row['image'] ?>')">
                                            Add to Cart
                                        </button>
                                        <button class="btn-buy" onclick="buyNow(<?= $row['id'] ?>)">
                                            Buy now
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                <?php
                    endwhile;
               } else {
    if (!empty($search)) {
        echo "<p>No products match \"$search\".</p>";
    } else {
        echo "<p>No products found in <strong>" . htmlspecialchars($category) . "</strong>.</p>";
    }
}
                ?>
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