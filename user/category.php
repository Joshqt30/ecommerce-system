<?php
session_start();
include '../config/db.php';

// ── Inputs ────────────────────────────────────────────
$category  = isset($_GET['cat'])       ? trim($_GET['cat'])        : 'All';
$search    = isset($_GET['search'])    ? trim($_GET['search'])     : '';
$sort      = isset($_GET['sort'])      ? $_GET['sort']             : 'default';
$maxPrice  = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;
$brand     = isset($_GET['brand'])     ? trim($_GET['brand'])      : '';
$minRating = isset($_GET['rating'])    ? (int)$_GET['rating']      : 0;
$page      = isset($_GET['page'])      ? max(1, (int)$_GET['page']): 1;
$perPage   = 9;
$offset    = ($page - 1) * $perPage;

// ── ORDER BY ──────────────────────────────────────────
$orderBy = match($sort) {
    'price-asc'  => 'ORDER BY p.price ASC',
    'price-desc' => 'ORDER BY p.price DESC',
    'name'       => 'ORDER BY p.name ASC',
    'rating'     => 'ORDER BY avg_rating DESC',
    default      => 'ORDER BY p.created_at DESC',
};

// PostgreSQL placeholders ($1, $2, ...)$whereParts = [];
$params     = [];
$i          = 1;

if (!empty($search)) {
    $whereParts[] = "(p.name ILIKE \${$i} OR p.description ILIKE \${$i})";
    $params[] = '%' . $search . '%';
    $i++;
} elseif ($category !== 'All' && $category !== 'Others') {
    $whereParts[] = "p.category = \${$i}";
    $params[] = $category;
    $i++;
}

if ($maxPrice !== null) {
    $whereParts[] = "p.price <= \${$i}";
    $params[] = $maxPrice;
    $i++;
}

if (!empty($brand)) {
    $whereParts[] = "p.name ILIKE \${$i}";
    $params[] = $brand . '%';
    $i++;
}

$whereSQL = $whereParts ? 'WHERE ' . implode(' AND ', $whereParts) : '';

// Rating filter goes in HAVING because it uses AVG()
// But only if reviews table exists — we guard against that below
$havingSQL = $minRating > 0 
    ? "HAVING COALESCE(AVG(r.rating), 0) >= {$minRating}" 
    : '';
// ── Check if reviews table exists ────────────────────
$reviewsExist = false;

$checkReviews = pg_query($conn, "
    SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_name = 'reviews'
    )
");

if ($checkReviews) {
    $reviewsExist = pg_fetch_result($checkReviews, 0, 0) === 't';
}

// ── Build the SELECT depending on reviews table ───────
if ($reviewsExist) {
    $selectExtra = ", COALESCE(AVG(r.rating), 0) AS avg_rating, COUNT(r.id) AS review_count";
    $joinSQL     = "LEFT JOIN reviews r ON r.product_id = p.id";
$groupSQL = "GROUP BY p.id, p.name, p.description, p.price, p.stock, p.image, p.category";} else {
    $selectExtra = ", 0 AS avg_rating, 0 AS review_count";
    $joinSQL     = "";
    $groupSQL    = "";
    $havingSQL   = ""; // can't filter by rating without the table
}

// ── Count total for pagination ────────────────────────
$countSQL = "
    SELECT COUNT(*) AS total FROM (
        SELECT p.id {$selectExtra}
        FROM products p
        {$joinSQL}
        {$whereSQL}
        {$groupSQL}
        {$havingSQL}
    ) sub
";

$totalProducts = 0;
$countRes = pg_query_params($conn, $countSQL, $params);

$totalProducts = $countRes
    ? (int)pg_fetch_result($countRes, 0, 0)
    : 0;

$totalPages = max(1, ceil($totalProducts / $perPage)); // ✅ ADD THIS
// ── Main product query ────────────────────────────────
$mainSQL = "
    SELECT p.id, p.name, p.description, p.price, p.stock, p.image, p.category
           {$selectExtra}
    FROM products p
    {$joinSQL}
    {$whereSQL}
    {$groupSQL}
    {$havingSQL}
    {$orderBy}
LIMIT \${$i} OFFSET \$" . ($i+1) . "
";

$products = [];
$dbError  = null;

$queryParams = array_merge($params, [$perPage, $offset]);

$result = pg_query_params($conn, $mainSQL, $queryParams);

if (!$result) {
    $dbError = pg_last_error($conn);
} else {
    while ($row = pg_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// ── Dynamic brands for sidebar ────────────────────────
// Pull first word of product name as brand, scoped to category
$brands = [];
if ($category !== 'All' && $category !== 'Others' && empty($search)) {
    $bResult = pg_query_params($conn,
        "SELECT DISTINCT SPLIT_PART(name,' ',1) AS brand 
         FROM products WHERE category = $1 ORDER BY brand",
        [$category]
    );

    while ($b = pg_fetch_assoc($bResult)) {
        if (!empty($b['brand'])) $brands[] = $b['brand'];
    }

} else {
    $bResult = pg_query($conn,
        "SELECT DISTINCT SPLIT_PART(name,' ',1) AS brand 
         FROM products ORDER BY brand LIMIT 20"
    );

    while ($b = pg_fetch_assoc($bResult)) {
        if (!empty($b['brand'])) $brands[] = $b['brand'];
    }
}

// ── Slider max price ──────────────────────────────────
if ($category !== 'All' && $category !== 'Others' && empty($search)) {
    $mRes = pg_query_params($conn,
        "SELECT CEIL(MAX(price)) FROM products WHERE category = $1",
        [$category]
    );
} else {
    $mRes = pg_query($conn,
        "SELECT CEIL(MAX(price)) FROM products"
    );
}

$sliderMax = ($mRes ? (int)pg_fetch_result($mRes, 0, 0) : 0) ?: 50000;

// ── Output HTML (includes AFTER all logic) ────────────
include '../includes/cart-panel.php';
include '../includes/header.php';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?= htmlspecialchars($search ?: $category) ?> – E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/cart-panel.css">
    <link rel="stylesheet" href="../assets/css/category.css">
    <style>
        .suggest-name { flex: 1; font-size: 13px; color: #14181F; }
        .suggest-cat  { font-size: 11px; color: #9ca3af; white-space: nowrap; }
        .search-result-item { display: flex; align-items: center; gap: 10px; }
        .grid-empty   { grid-column: 1/-1; display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 60px 20px; color: #aaa; text-align: center; }
        .grid-empty p { font-size: 15px; }
        .grid-msg     { grid-column: 1/-1; color: #ef4444; padding: 20px; font-size: 13px; background: #fef2f2; border-radius: 8px; }
        .btn-filter   { width: 100%; padding: 10px; background: #14181F; color: #fff; border: none; border-radius: 10px; font-family: 'Inter', sans-serif; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 10px; transition: background .18s; }
        .btn-filter:hover { background: #2d3340; }
        .btn-clear-filters { display: block; text-align: center; margin-top: 8px; font-size: 12px; color: #6366f1; text-decoration: none; }
        .btn-clear-filters:hover { text-decoration: underline; }
        .card-stars .stars { color: #f59e0b; font-size: 12px; }
        .card-stars.no-rating { font-size: 11px; color: #c0c4cc; margin-top: 2px; }
        .card-badge.out { background: #fef2f2; color: #dc2626; font-size: 10px; }
        .card-badge.low { background: #fffbeb; color: #d97706; font-size: 10px; }
        .filter-check input[type="radio"] { accent-color: #6366f1; }
    </style>
</head>
<body>

<!-- Search bar -->
<div class="search-bar">
    <div class="search-field-wrap">
        <form method="GET" action="" id="searchForm" style="width:100%">
            <input type="hidden" name="cat" value="<?= htmlspecialchars($category) ?>">
            <div class="search-field" id="searchField">
                <input type="text" name="search" id="searchInput"
                       placeholder="Search for products" autocomplete="off"
                       value="<?= htmlspecialchars($search) ?>"/>
                <button class="search-btn" type="submit" aria-label="Search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                </button>
            </div>
        </form>
        <div class="search-results" id="searchResults"></div>
    </div>
</div>

<main class="category-page">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="../user/dashboard.php">Home</a>
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 18l6-6-6-6"/>
        </svg>
        <span><?= htmlspecialchars($search ? 'Search: ' . $search : $category) ?></span>
    </nav>

    <!-- Header row -->
    <div class="category-header">
        <div>
            <h1 class="category-heading">
                <?= htmlspecialchars($search ? 'Results for "' . $search . '"' : $category) ?>
            </h1>
            <p class="category-count">
                Showing <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?>
            </p>
        </div>
        <div class="sort-wrap">
            <label class="sort-label">Sort by</label>
            <form method="GET" id="sortForm">
                <input type="hidden" name="cat"       value="<?= htmlspecialchars($category) ?>">
                <input type="hidden" name="search"    value="<?= htmlspecialchars($search) ?>">
                <input type="hidden" name="max_price" value="<?= $maxPrice !== null ? $maxPrice : '' ?>">
                <input type="hidden" name="brand"     value="<?= htmlspecialchars($brand) ?>">
                <input type="hidden" name="rating"    value="<?= $minRating ?>">
                <select class="sort-select" name="sort"
                        onchange="document.getElementById('sortForm').submit()">
                    <option value="default"    <?= $sort==='default'    ?'selected':''?>>Featured</option>
                    <option value="price-asc"  <?= $sort==='price-asc'  ?'selected':''?>>Price: Low to High</option>
                    <option value="price-desc" <?= $sort==='price-desc' ?'selected':''?>>Price: High to Low</option>
                    <option value="name"       <?= $sort==='name'       ?'selected':''?>>Name A–Z</option>
                    <?php if ($reviewsExist): ?>
                    <option value="rating"     <?= $sort==='rating'     ?'selected':''?>>Top Rated</option>
                    <?php endif; ?>
                </select>
            </form>
        </div>
    </div>

    <div class="category-layout">

        <!-- Sidebar -->
        <aside class="filter-sidebar">
            <form method="GET" id="filterForm">
                <input type="hidden" name="cat"    value="<?= htmlspecialchars($category) ?>">
                <input type="hidden" name="sort"   value="<?= htmlspecialchars($sort) ?>">
                <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

                <!-- Price range -->
                <div class="filter-group">
                    <h3 class="filter-title">Price Range</h3>
                    <div class="price-range-labels">
                        <span>₱0</span>
                        <span id="priceMaxLabel">₱<?= number_format($maxPrice ?? $sliderMax) ?></span>
                    </div>
                    <input type="range" name="max_price" id="priceRange"
                           min="0" max="<?= $sliderMax ?>"
                           value="<?= $maxPrice ?? $sliderMax ?>"
                           class="price-slider"
                           oninput="document.getElementById('priceMaxLabel').textContent
                               = '₱' + parseInt(this.value).toLocaleString('en-PH')">
                </div>

                <!-- Brands (dynamic from DB) -->
                <?php if (!empty($brands)): ?>
                <div class="filter-group">
                    <h3 class="filter-title">Brand</h3>
                    <div class="filter-options">
                        <label class="filter-check">
                            <input type="radio" name="brand" value=""
                                   <?= empty($brand) ? 'checked' : '' ?>> All
                        </label>
                        <?php foreach ($brands as $b): ?>
                        <label class="filter-check">
                            <input type="radio" name="brand"
                                   value="<?= htmlspecialchars($b) ?>"
                                   <?= $brand === $b ? 'checked' : '' ?>>
                            <?= htmlspecialchars($b) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Rating (only shown if reviews table exists) -->
                <?php if ($reviewsExist): ?>
                <div class="filter-group">
                    <h3 class="filter-title">Min. Rating</h3>
                    <div class="filter-options">
                        <?php foreach ([0 => 'All', 4 => '★★★★☆ 4 &amp; up', 3 => '★★★☆☆ 3 &amp; up', 2 => '★★☆☆☆ 2 &amp; up'] as $val => $label): ?>
                        <label class="filter-check">
                            <input type="radio" name="rating" value="<?= $val ?>"
                                   <?= $minRating === $val ? 'checked' : '' ?>>
                            <?= $label ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn-filter">Apply Filters</button>

                <?php if (!empty($brand) || $minRating > 0 || $maxPrice !== null): ?>
                    <a href="?cat=<?= urlencode($category) ?>" class="btn-clear-filters">✕ Clear filters</a>
                <?php endif; ?>
            </form>
        </aside>

        <!-- Product grid -->
        <section class="product-grid" id="productGrid">

            <?php if ($dbError): ?>
                <div class="grid-msg">
                    <strong>Query error:</strong> <?= htmlspecialchars($dbError) ?>
                </div>

            <?php elseif (!empty($products)): ?>

                <?php foreach ($products as $row):
                    $avgRating   = round((float)$row['avg_rating'], 1);
                    $reviewCount = (int)$row['review_count'];
                    $stars       = '';
                    for ($s = 1; $s <= 5; $s++) $stars .= $s <= round($avgRating) ? '★' : '☆';
                    $imgSrc      = !empty($row['image'])
                        ? htmlspecialchars($row['image'])
                        : '../assets/img/placeholder.png';
                    $outOfStock  = (int)$row['stock'] === 0;
                    $lowStock    = !$outOfStock && (int)$row['stock'] <= 5;
                ?>
                <article class="product-card"
                    onclick="window.location.href='viewitems.php?id=<?= (int)$row['id'] ?>'"
                    style="cursor:pointer;"                         data-price="<?= (float)$row['price'] ?>"
                         data-name="<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>">

                    <div class="card-img-wrap">
                        <img src="<?= $imgSrc ?>"
                             alt="<?= htmlspecialchars($row['name']) ?>"
                             class="card-img"
                             onerror="this.src='../assets/img/placeholder.png'"/>

                        <button class="card-wishlist" aria-label="Wishlist">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
                            </svg>
                        </button>

                        <?php if ($outOfStock): ?>
                            <span class="card-badge out">Out of Stock</span>
                        <?php elseif ($lowStock): ?>
                            <span class="card-badge low">Only <?= (int)$row['stock'] ?> left!</span>
                        <?php endif; ?>
                    </div>

                    <div class="card-body">
                        <p class="card-name"><?= htmlspecialchars($row['name']) ?></p>
                        <p class="card-desc"><?= htmlspecialchars($row['description']) ?></p>

                        <?php if ($reviewCount > 0): ?>
                            <div class="card-stars">
                                <span class="stars"><?= $stars ?></span>
                                <span class="card-reviews">(<?= $reviewCount ?>)</span>
                            </div>
                        <?php else: ?>
                            <div class="card-stars no-rating">No reviews yet</div>
                        <?php endif; ?>

                        <div class="card-footer">
                            <span class="card-price">₱<?= number_format((float)$row['price'], 2) ?></span>
                            <div class="card-actions">
                                <?php if (!$outOfStock): ?>
                                    <button class="btn-cart"
                                        onclick="addToCart(this,
                                            <?= (int)$row['id'] ?>,
                                            '<?= addslashes(htmlspecialchars($row['name'])) ?>',
                                            <?= (float)$row['price'] ?>,
                                            '<?= addslashes($imgSrc) ?>')">
                                        Add to Cart
                                    </button>
                                    <button class="btn-buy"
                                        onclick="buyNow(<?= (int)$row['id'] ?>)">
                                        Buy now
                                    </button>
                                <?php else: ?>
                                    <button class="btn-cart" disabled
                                            style="opacity:.45; cursor:not-allowed;">
                                        Out of Stock
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>

            <?php else: ?>
                <div class="grid-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc"
                         stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <p>
                        <?= !empty($search)
                            ? 'No products match "<strong>' . htmlspecialchars($search) . '</strong>".'
                            : 'No products found in <strong>' . htmlspecialchars($category) . '</strong>.' ?>
                    </p>
                    <?php if (!empty($brand) || $minRating > 0 || $maxPrice !== null): ?>
                        <a href="?cat=<?= urlencode($category) ?>" class="btn-clear-filters">✕ Clear filters</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </section>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
               class="page-btn">← Prev</a>
        <?php endif; ?>

        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
               class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
        <?php endfor; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
               class="page-btn page-next">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>

<div class="toast" id="toast">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
    </svg>
    <span id="toastMsg">Done</span>
</div>

<script src="../scripts/cart-panel.js"></script>
<script>
// ── Toast ─────────────────────────────────────────────
function toast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    clearTimeout(el._t);
    el._t = setTimeout(() => el.classList.remove('show'), 2600);
}

// ── Add to cart ───────────────────────────────────────
function addToCart(btn, id, name, price, image) {
    if (typeof CartPanel !== 'undefined') {
        CartPanel.addItem({ id, name, price, image });
    }
    btn.textContent = 'Added ✓';
    btn.classList.add('added');
    setTimeout(() => {
        btn.textContent = 'Add to Cart';
        btn.classList.remove('added');
    }, 1800);
}

// ── Buy now ───────────────────────────────────────────
function buyNow(id) {
    window.location.href = '../user/checkout.php?product_id=' + id;
}

// ── Wishlist toggle ───────────────────────────────────
document.querySelectorAll('.card-wishlist').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const on = btn.classList.toggle('wished');
        btn.querySelector('svg').setAttribute('fill',   on ? '#ef4444' : 'none');
        btn.querySelector('svg').setAttribute('stroke', on ? '#ef4444' : 'currentColor');
        toast(on ? 'Added to wishlist ♥' : 'Removed from wishlist');
    });
});

// ── Smart search — inline PHP-powered AJAX ────────────
const searchInput   = document.getElementById('searchInput');
const searchResults = document.getElementById('searchResults');
let searchTimer;

searchInput.addEventListener('input', () => {
    clearTimeout(searchTimer);
    const q = searchInput.value.trim();

    if (q.length < 2) {
        searchResults.classList.remove('open');
        searchResults.innerHTML = '';
        return;
    }

    searchTimer = setTimeout(() => {
        const cat = '<?= addslashes($category) ?>';
        fetch(`../includes/search-suggestions.php?q=${encodeURIComponent(q)}&cat=${encodeURIComponent(cat)}`)
            .then(r => {
                if (!r.ok) throw new Error('Network error');
                return r.json();
            })
            .then(data => {
                if (!data || !data.length) {
                    searchResults.classList.remove('open');
                    return;
                }

                searchResults.innerHTML = data.map(item => `
                    <div class="search-result-item" data-name="${item.name.replace(/"/g,'&quot;')}">
                        <span class="suggest-name">${item.name}</span>
                        <span class="suggest-cat">${item.category}</span>
                    </div>
                `).join('');

                searchResults.classList.add('open');

                searchResults.querySelectorAll('.search-result-item').forEach(row => {
                    row.addEventListener('click', () => {
                        searchInput.value = row.dataset.name;
                        searchResults.classList.remove('open');
                        document.getElementById('searchForm').submit();
                    });
                });
            })
            .catch(err => {
                console.warn('Search suggestions failed:', err);
                searchResults.classList.remove('open');
            });
    }, 280);
});

searchInput.addEventListener('keydown', e => {
    if (e.key === 'Enter')  { searchResults.classList.remove('open'); }
    if (e.key === 'Escape') { searchResults.classList.remove('open'); }
});

document.addEventListener('click', e => {
    if (!e.target.closest('.search-field-wrap')) {
        searchResults.classList.remove('open');
    }
});

// ── Filter form: radio auto-submit ───────────────────
document.querySelectorAll('#filterForm input[type="radio"]').forEach(r => {
    r.addEventListener('change', () => document.getElementById('filterForm').submit());
});

// ── Price slider: submit on release ──────────────────
document.getElementById('priceRange').addEventListener('change', () => {
    document.getElementById('filterForm').submit();
});
</script>
</body>
</html>