<?php
session_start();
include '../config/db.php';

define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

// ── Product ID ────────────────────────────────────────
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($productId <= 0) { header('Location: dashboard.php'); exit; }

// ── Fetch product ─────────────────────────────────────
$res     = pg_query_params($conn, "SELECT * FROM products WHERE id = $1", [$productId]);
$product = $res ? pg_fetch_assoc($res) : null;
if (!$product) { header('Location: dashboard.php'); exit; }

// ── Fetch product images ──────────────────────────────
$imgRes = pg_query_params($conn,
    "SELECT image_url FROM product_images WHERE product_id = $1 ORDER BY sort_order ASC",
    [$productId]
);
if (!$imgRes) {
    error_log("Failed to fetch product images: " . pg_last_error($conn));
    // You could also echo a hidden comment in HTML for debugging:
    // echo "<!-- Image query error: " . htmlspecialchars(pg_last_error($conn)) . " -->";
}
$images = [];
if ($imgRes) while ($img = pg_fetch_assoc($imgRes)) $images[] = $img['image_url'];

// Add main product image only if it isn't already included
if (!empty($product['image']) && !in_array($product['image'], $images)) {
    $images[] = $product['image'];
}
if (empty($images)) $images[] = ''; // last resort placeholder

// ── Fetch variants ────────────────────────────────────
$varRes  = pg_query_params($conn,
    "SELECT * FROM product_variants WHERE product_id = $1 ORDER BY id",
    [$productId]
);
$variants = [];
if ($varRes) {
    while ($v = pg_fetch_assoc($varRes)) {
        $v['attributes'] = json_decode($v['attributes'], true) ?? [];
        $variants[] = $v;
    }
}

// ── Build attribute map (e.g. Color => [Midnight, Blue], Storage => [128GB, 256GB]) ──
$attributeMap = [];
foreach ($variants as $v) {
    foreach ($v['attributes'] as $attrName => $attrValue) {
        if (!isset($attributeMap[$attrName])) $attributeMap[$attrName] = [];
        if (!in_array($attrValue, $attributeMap[$attrName])) {
            $attributeMap[$attrName][] = $attrValue;
        }
    }
}

// ── Real ratings from DB ──────────────────────────────
$ratingRes = pg_query_params($conn,
    "SELECT COALESCE(AVG(rating),0) AS avg_rating, COUNT(*) AS total_reviews
     FROM reviews WHERE product_id = $1",
    [$productId]
);
$ratingData   = $ratingRes ? pg_fetch_assoc($ratingRes) : ['avg_rating' => 0, 'total_reviews' => 0];
$avgRating    = round((float)$ratingData['avg_rating'], 1);
$totalReviews = (int)$ratingData['total_reviews'];

// ── Real order count ──────────────────────────────────
$orderRes = pg_query_params($conn,
    "SELECT COALESCE(SUM(oi.quantity), 0) AS total_sold
     FROM order_items oi
     JOIN product_variants pv ON pv.id = oi.variant_id
     JOIN orders o ON o.id = oi.order_id
     WHERE pv.product_id = $1 AND o.status != 'cancelled'",
    [$productId]
);
$totalSold = $orderRes ? (int)pg_fetch_result($orderRes, 0, 'total_sold') : 0;

// ── Fetch reviews ─────────────────────────────────────
$reviewRes = pg_query_params($conn,
    "SELECT r.*, COALESCE(u.username, r.username, 'Anonymous') AS display_name
     FROM reviews r
     LEFT JOIN users u ON u.id = r.user_id
     WHERE r.product_id = $1
     ORDER BY r.created_at DESC",
    [$productId]
);
$reviews = [];
if ($reviewRes) while ($r = pg_fetch_assoc($reviewRes)) $reviews[] = $r;
$totalReviewCount = count($reviews);
$initialLimit     = 3;

// ── Handle review POST (PRG) ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $rating  = max(1, min(5, (int)($_POST['rating'] ?? 3)));
    $comment = trim($_POST['comment'] ?? '');
    $userId  = $_SESSION['user_id'] ?? null;

    if (!empty($comment)) {
        if ($userId) {
            pg_query_params($conn,
                "INSERT INTO reviews (product_id, user_id, rating, comment, created_at)
                 VALUES ($1, $2, $3, $4, NOW())",
                [$productId, $userId, $rating, $comment]
            );
        } else {
            $anonName = 'user_' . substr(hash('sha256', session_id() . uniqid()), 0, 6);
            pg_query_params($conn,
                "INSERT INTO reviews (product_id, username, rating, comment, created_at)
                 VALUES ($1, $2, $3, $4, NOW())",
                [$productId, $anonName, $rating, $comment]
            );
        }
    }
    header("Location: viewitems.php?id={$productId}&tab=reviews");
    exit;
}

$activeTab = $_GET['tab'] ?? 'description';

include '../includes/header.php';
include '../includes/cart-panel.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($product['name']) ?> – E-Commerce</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Serif+Display&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="../assets/css/cart-panel.css">
  <link rel="stylesheet" href="../assets/css/header.css">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #f5f5f7; --white: #fff; --text: #1a1a1a; --muted: #6b7280;
      --border: #e5e7eb; --accent: #2563eb; --accent-hover: #1d4ed8;
      --star: #f59e0b; --tag-bg: #f0f4ff; --radius: 12px;
      --shadow: 0 2px 16px rgba(0,0,0,0.07);
      --font: 'DM Sans', system-ui, sans-serif;
    }
    body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; -webkit-font-smoothing: antialiased; }

    main { max-width: 1100px; margin: 36px auto; padding: 0 24px; }

    /* ── Product top ────────────────────────────────── */
    .product-top {
      display: grid; grid-template-columns: 1fr 420px; gap: 40px;
      background: var(--white); border-radius: var(--radius);
      box-shadow: var(--shadow); padding: 32px;
    }

    /* ── Gallery ─────────────────────────────────────── */
    .gallery { display: flex; gap: 16px; }
    .thumbnails { display: flex; flex-direction: column; gap: 10px; }
    .thumb {
      width: 68px; height: 68px; border-radius: 8px;
      border: 2px solid var(--border); overflow: hidden; cursor: pointer;
      transition: border-color .2s, transform .15s; background: var(--bg);
    }
    .thumb img { width: 100%; height: 100%; object-fit: cover; }
    .thumb:hover { border-color: var(--accent); transform: scale(1.04); }
    .thumb.active { border-color: var(--accent); }
    .main-image {
      flex: 1; background: var(--bg); border-radius: 10px;
      overflow: hidden; display: flex; align-items: center; justify-content: center;
      min-height: 340px;
    }
    .main-image img {
      max-width: 100%; max-height: 360px; object-fit: contain;
      transition: opacity .25s, transform .3s;
    }
    .main-image img.switching { opacity: 0; transform: scale(0.97); }

    /* ── Product info ─────────────────────────────────── */
    .product-info { display: flex; flex-direction: column; gap: 18px; }
    .product-title { font-family: 'DM Serif Display', serif; font-size: 22px; line-height: 1.3; }

    .product-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
    .stars { display: flex; align-items: center; gap: 2px; }
    .star { font-size: 15px; }
    .star.on { color: var(--star); }
    .star.off { color: #d1d5db; }
    .rating-val { font-weight: 600; font-size: 14px; }
    .meta-sep { color: var(--border); }
    .orders-count { font-size: 13px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
    .orders-count svg { width: 14px; height: 14px; }

    .product-specs {
      display: grid; grid-template-columns: 90px 1fr; gap: 8px 0;
      font-size: 14px; border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border); padding: 14px 0;
    }
    .spec-label { color: var(--muted); }
    .spec-val { font-weight: 500; }

    /* ── Attribute options ───────────────────────────── */
    .option-group { display: flex; flex-direction: column; gap: 8px; }
    .option-label { font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; }
    .option-buttons { display: flex; gap: 8px; flex-wrap: wrap; }

    /* Color swatches */
    .color-swatch-btn {
      width: 32px; height: 32px; border-radius: 50%;
      border: 3px solid transparent; cursor: pointer;
      transition: transform .15s, border-color .15s;
      box-shadow: 0 1px 4px rgba(0,0,0,.2);
    }
    .color-swatch-btn:hover { transform: scale(1.15); }
    .color-swatch-btn.active { border-color: var(--accent); transform: scale(1.1); }

    /* Text option buttons (Size, Storage etc.) */
    .opt-btn {
      padding: 7px 16px; border-radius: 8px; border: 1.5px solid var(--border);
      background: var(--white); font-family: var(--font); font-size: 13px;
      font-weight: 500; cursor: pointer; transition: border-color .15s, background .15s, color .15s;
      color: var(--text);
    }
    .opt-btn:hover { border-color: var(--accent); background: var(--tag-bg); }
    .opt-btn.active { border-color: var(--accent); background: var(--accent); color: #fff; }
    .opt-btn:disabled { opacity: .4; cursor: not-allowed; text-decoration: line-through; }

    /* ── Stock status ─────────────────────────────────── */
    .stock-badge {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 600; padding: 5px 12px;
      border-radius: 20px;
    }
    .stock-badge::before { content: ''; width: 7px; height: 7px; border-radius: 50%; }
    .stock-in  { background: #f0fdf4; color: #16a34a; }
    .stock-in::before  { background: #16a34a; }
    .stock-low { background: #fffbeb; color: #d97706; }
    .stock-low::before { background: #d97706; }
    .stock-out { background: #fef2f2; color: #dc2626; }
    .stock-out::before { background: #dc2626; }

    /* ── Quantity ─────────────────────────────────────── */
    .quantity-row { display: flex; align-items: center; }
    .qty-btn {
      width: 34px; height: 34px; border: 1.5px solid var(--border);
      background: var(--white); border-radius: 8px; font-size: 18px;
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      transition: background .15s, border-color .15s; line-height: 1;
    }
    .qty-btn:hover { background: var(--bg); border-color: var(--accent); }
    .qty-btn:disabled { opacity: .4; cursor: not-allowed; }
    .qty-value {
      width: 44px; text-align: center; font-size: 15px; font-weight: 600;
      border: none; background: none; font-family: var(--font);
    }

    /* ── Price ───────────────────────────────────────── */
    .price-block { display: flex; flex-direction: column; gap: 4px; }
    .price-label { font-size: 11px; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
    .price { font-family: 'DM Serif Display', serif; font-size: 30px; color: var(--text); }

    /* ── CTA ─────────────────────────────────────────── */
    .cta-row { display: flex; gap: 10px; align-items: center; }
    .btn-cart {
      flex: 1; padding: 13px 0; border-radius: 10px; background: var(--accent);
      color: #fff; border: none; font-family: var(--font); font-size: 14px; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
      transition: background .18s, transform .12s;
      box-shadow: 0 2px 12px rgba(37,99,235,0.18);
    }
    .btn-cart:hover { background: var(--accent-hover); transform: translateY(-1px); }
    .btn-cart:disabled { opacity: .45; cursor: not-allowed; transform: none; }
    .btn-buy {
      flex: 1; padding: 13px 0; border-radius: 10px; background: var(--white);
      color: var(--text); border: 1.5px solid var(--border); font-family: var(--font);
      font-size: 14px; font-weight: 600; cursor: pointer;
      transition: border-color .18s, background .18s;
    }
    .btn-buy:hover { border-color: var(--accent); background: var(--tag-bg); }
    .btn-buy:disabled { opacity: .45; cursor: not-allowed; }

    /* ── Tabs ────────────────────────────────────────── */
    .product-tabs { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); margin-top: 24px; overflow: hidden; }
    .tab-nav { display: flex; border-bottom: 1px solid var(--border); padding: 0 32px; }
    .tab-btn {
      padding: 16px 0; margin-right: 32px; background: none; border: none;
      font-family: var(--font); font-size: 14px; font-weight: 500; color: var(--muted);
      cursor: pointer; border-bottom: 2px solid transparent;
      transition: color .18s, border-color .18s; position: relative; bottom: -1px;
    }
    .tab-btn:hover { color: var(--text); }
    .tab-btn.active { color: var(--accent); border-bottom-color: var(--accent); font-weight: 600; }
    .tab-content { padding: 28px 32px; display: none; animation: fadeIn .25s ease; }
    .tab-content.active { display: block; }
    @keyframes fadeIn { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:none; } }
    .tab-content p { font-size: 14px; line-height: 1.75; color: #4b5563; margin-bottom: 16px; }

    /* ── Reviews ─────────────────────────────────────── */
    .review-summary {
      display: flex; align-items: center; gap: 24px;
      padding: 20px 24px; background: #fafafa; border-radius: 12px;
      border: 1px solid var(--border); margin-bottom: 20px;
    }
    .review-avg-score { font-size: 48px; font-weight: 700; line-height: 1; color: var(--text); }
    .review-avg-stars { display: flex; gap: 3px; margin: 4px 0; }
    .review-avg-stars .star { font-size: 18px; }
    .review-total { font-size: 13px; color: var(--muted); }

    .review-card {
      padding: 18px 0; border-bottom: 1px solid #f3f4f6;
      animation: fadeIn .2s ease;
    }
    .review-card:last-child { border-bottom: none; }
    .review-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .review-user { font-weight: 600; font-size: 14px; }
    .review-date { font-size: 12px; color: var(--muted); }
    .review-stars { display: flex; gap: 2px; margin-bottom: 8px; }
    .review-stars .star { font-size: 14px; }
    .review-comment { font-size: 14px; color: #374151; line-height: 1.6; }

    .more-reviews { display: none; }
    .more-reviews.open { display: block; }

    .see-more-btn {
      display: flex; align-items: center; gap: 6px; margin-top: 12px;
      background: none; border: 1.5px solid var(--border); padding: 8px 16px;
      border-radius: 8px; font-family: var(--font); font-size: 13px; font-weight: 500;
      color: var(--muted); cursor: pointer; transition: border-color .15s, color .15s;
    }
    .see-more-btn:hover { border-color: var(--accent); color: var(--accent); }
    .no-reviews { font-size: 14px; color: var(--muted); padding: 16px 0; }

    /* Review form */
    .review-form { margin-top: 24px; padding-top: 20px; border-top: 1px solid var(--border); }
    .review-form-title { font-size: 15px; font-weight: 600; margin-bottom: 14px; }

    .star-rating-input { display: flex; gap: 4px; margin-bottom: 14px; }
    .star-rating-input label {
      font-size: 28px; cursor: pointer; color: #d1d5db;
      transition: color .1s; line-height: 1;
    }
    .star-rating-input input[type="radio"] { display: none; }
    .star-rating-input input[type="radio"]:checked ~ label,
    .star-rating-input label:hover,
    .star-rating-input label:hover ~ label { color: var(--star); }
    /* Reverse trick for RTL star fill */
    .star-rating-input { flex-direction: row-reverse; }
    .star-rating-input label:hover,
    .star-rating-input label:hover ~ label { color: var(--star); }

    .review-input-row { display: flex; gap: 10px; align-items: flex-start; }
    .review-input-row textarea {
      flex: 1; border: 1.5px solid var(--border); border-radius: 10px;
      padding: 11px 14px; font-family: var(--font); font-size: 14px;
      resize: vertical; min-height: 80px; outline: none;
      transition: border-color .18s;
    }
    .review-input-row textarea:focus { border-color: var(--accent); }
    .submit-review-btn {
      padding: 11px 22px; background: var(--accent); color: #fff;
      border: none; border-radius: 10px; font-family: var(--font);
      font-size: 14px; font-weight: 600; cursor: pointer;
      transition: background .18s; white-space: nowrap;
    }
    .submit-review-btn:hover { background: var(--accent-hover); }

    /* ── Toast ───────────────────────────────────────── */
    .toast {
      position: fixed; bottom: 32px; right: 32px; background: #1a1a1a; color: #fff;
      padding: 14px 22px; border-radius: 10px; font-size: 14px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      transform: translateY(80px); opacity: 0;
      transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .3s;
      z-index: 999; pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }

    @media (max-width: 820px) {
      .product-top { grid-template-columns: 1fr; }
      .gallery { flex-direction: column-reverse; }
      .thumbnails { flex-direction: row; }
    }
  </style>
</head>
<body>

<main>
  <div class="product-top">

    <!-- Gallery -->
    <div class="gallery">
      <div class="thumbnails" id="thumbs">
        <?php foreach ($images as $i => $img): ?>
        <div class="thumb <?= $i === 0 ? 'active' : '' ?>" data-idx="<?= $i ?>">
          <img src="<?= $img ? PRODUCT_IMGS_BASE . htmlspecialchars($img) : '../assets/img/placeholder.png' ?>"
               alt="" onerror="this.src='../assets/img/placeholder.png'"/>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="main-image">
        <img id="mainImg"
             src="<?= $images[0] ? PRODUCT_IMGS_BASE . htmlspecialchars($images[0]) : '../assets/img/placeholder.png' ?>"
             alt="<?= htmlspecialchars($product['name']) ?>"
             onerror="this.src='../assets/img/placeholder.png'"/>
      </div>
    </div>

    <!-- Info -->
    <div class="product-info">

      <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

      <!-- Real rating from DB -->
      <div class="product-meta">
        <div class="stars">
          <?php for ($s = 1; $s <= 5; $s++): ?>
            <span class="star <?= $s <= round($avgRating) ? 'on' : 'off' ?>">★</span>
          <?php endfor; ?>
        </div>
        <span class="rating-val"><?= $avgRating > 0 ? $avgRating : 'No rating' ?></span>
        <?php if ($totalReviews > 0): ?>
          <span class="meta-sep">·</span>
          <span style="font-size:13px;color:var(--muted)"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></span>
        <?php endif; ?>
        <?php if ($totalSold > 0): ?>
          <span class="meta-sep">·</span>
          <div class="orders-count">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
            </svg>
            <?= number_format($totalSold) ?> sold
          </div>
        <?php endif; ?>
      </div>

      <!-- Specs from DB (category as design, no hardcoded "Australia") -->
      <div class="product-specs">
        <span class="spec-label">Category:</span>
        <span class="spec-val"><?= htmlspecialchars($product['category'] ?? '—') ?></span>
        <span class="spec-label">Stock:</span>
        <span class="spec-val"><?= number_format((int)$product['stock']) ?> units</span>
        <span class="spec-label">Delivery:</span>
        <span class="spec-val" style="color:#16a34a;display:flex;align-items:center;gap:5px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          2–5 business days
        </span>
      </div>

      <!-- Dynamic attribute selectors (Color, Storage, Size, etc.) -->
      <?php foreach ($attributeMap as $attrName => $values): ?>
        <div class="option-group">
          <div class="option-label" id="label-<?= htmlspecialchars($attrName) ?>">
            <?= htmlspecialchars($attrName) ?>
          </div>
          <div class="option-buttons" data-attribute="<?= htmlspecialchars($attrName) ?>">
            <?php
            $isColor = (strtolower($attrName) === 'color');
            $first = true;
            foreach ($values as $val):

                // Detect if value is a hex color or use an extended map
                $cssColor = '';
                if (preg_match('/^#?[0-9a-fA-F]{6}$/', $val)) {
                    $cssColor = (strpos($val, '#') === 0) ? $val : '#' . $val;
                } else {
                    $colorMap = [
                        'midnight' => '#1c1c1e', 'starlight' => '#f5f0e8',
                        'blue' => '#3b82f6',     'red' => '#ef4444',
                        'green' => '#22c55e',    'white' => '#f5f5f5',
                        'black' => '#1c1c1e',    'silver' => '#c0c0c0',
                        'gold' => '#d4af37',     'graphite' => '#374151',
                        'pink' => '#ec4899',     'purple' => '#a855f7',
                        'yellow' => '#eab308',   'orange' => '#f97316',
                        'alpine' => '#e8f4f8',
                    ];
                    $cssColor = $colorMap[strtolower($val)] ?? '';
                }
            ?>
                    <button class="opt-btn <?= $first ? 'active' : '' ?>"
                data-val="<?= htmlspecialchars($val) ?>">
          <?= htmlspecialchars($val) ?>
        </button>
            <?php $first = false; endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Stock status badge (dynamic) -->
      <div id="stockStatusWrap">
        <?php
        $baseStock = (int)$product['stock'];
        if ($baseStock === 0) echo '<span class="stock-badge stock-out">Out of Stock</span>';
        elseif ($baseStock <= 5) echo '<span class="stock-badge stock-low">Only ' . $baseStock . ' left</span>';
        else echo '<span class="stock-badge stock-in">In Stock</span>';
        ?>
      </div>

      <!-- Quantity -->
      <div class="option-group">
        <div class="option-label">Quantity</div>
        <div class="quantity-row">
          <button class="qty-btn" id="qtyMinus" <?= $baseStock === 0 ? 'disabled' : '' ?>>−</button>
          <input class="qty-value" id="qtyVal" type="text" value="1" readonly/>
          <button class="qty-btn" id="qtyPlus"  <?= $baseStock === 0 ? 'disabled' : '' ?>>+</button>
        </div>
      </div>

      <!-- Price -->
      <div class="price-block">
        <div class="price-label">Price</div>
        <div class="price" id="priceDisplay"
             data-base-price="<?= (float)$product['price'] ?>">
          ₱<?= number_format((float)$product['price'], 2) ?>
        </div>
      </div>

      <!-- Actions -->
      <div class="cta-row">
        <button class="btn-cart" id="addToCart" <?= $baseStock === 0 ? 'disabled' : '' ?>>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
          </svg>
          <?= $baseStock === 0 ? 'Out of Stock' : 'Add to cart' ?>
        </button>
        <button class="btn-buy" id="buyNow" <?= $baseStock === 0 ? 'disabled' : '' ?>>Buy now</button>
      </div>

    </div>
  </div>

  <!-- Tabs -->
  <div class="product-tabs">
    <div class="tab-nav">
      <button class="tab-btn <?= $activeTab === 'description' ? 'active' : '' ?>" data-tab="description">Description</button>
      <button class="tab-btn <?= $activeTab === 'reviews'     ? 'active' : '' ?>" data-tab="reviews">
        Reviews <?= $totalReviews > 0 ? "({$totalReviews})" : '' ?>
      </button>
    </div>

    <!-- Description -->
    <div class="tab-content <?= $activeTab === 'description' ? 'active' : '' ?>" id="tab-description">
      <?php if (!empty($product['description'])): ?>
        <?php foreach (explode("\n", htmlspecialchars($product['description'])) as $line): ?>
          <?php if (trim($line) && !str_starts_with(trim($line), '[')): // skip meta notes ?>
            <p><?= $line ?></p>
          <?php endif; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <p style="color:var(--muted)">No description available for this product.</p>
      <?php endif; ?>
    </div>

    <!-- Reviews -->
    <div class="tab-content <?= $activeTab === 'reviews' ? 'active' : '' ?>" id="tab-reviews">

      <!-- Summary bar -->
      <?php if ($totalReviews > 0): ?>
      <div class="review-summary">
        <div>
          <div class="review-avg-score"><?= $avgRating ?></div>
          <div class="review-avg-stars">
            <?php for ($s = 1; $s <= 5; $s++): ?>
              <span class="star <?= $s <= round($avgRating) ? 'on' : 'off' ?>">★</span>
            <?php endfor; ?>
          </div>
          <div class="review-total"><?= $totalReviews ?> review<?= $totalReviews !== 1 ? 's' : '' ?></div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Review list -->
      <?php if ($totalReviews > 0): ?>
        <?php for ($i = 0; $i < min($initialLimit, $totalReviews); $i++):
          $r = $reviews[$i]; ?>
          <div class="review-card">
            <div class="review-top">
              <span class="review-user"><?= htmlspecialchars($r['display_name']) ?></span>
              <span class="review-date"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
            </div>
            <div class="review-stars">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <span class="star <?= $s <= $r['rating'] ? 'on' : 'off' ?>">★</span>
              <?php endfor; ?>
            </div>
            <div class="review-comment"><?= htmlspecialchars($r['comment']) ?></div>
          </div>
        <?php endfor; ?>

        <?php if ($totalReviews > $initialLimit): ?>
          <div class="more-reviews" id="moreReviews">
            <?php for ($i = $initialLimit; $i < $totalReviews; $i++):
              $r = $reviews[$i]; ?>
              <div class="review-card">
                <div class="review-top">
                  <span class="review-user"><?= htmlspecialchars($r['display_name']) ?></span>
                  <span class="review-date"><?= date('M d, Y', strtotime($r['created_at'])) ?></span>
                </div>
                <div class="review-stars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <span class="star <?= $s <= $r['rating'] ? 'on' : 'off' ?>">★</span>
                  <?php endfor; ?>
                </div>
                <div class="review-comment"><?= htmlspecialchars($r['comment']) ?></div>
              </div>
            <?php endfor; ?>
          </div>
          <button class="see-more-btn" id="seeMoreBtn" onclick="toggleMoreReviews(this)">
            <span>See all <?= $totalReviews ?> reviews</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <polyline points="6 9 12 15 18 9"/>
            </svg>
          </button>
        <?php endif; ?>

      <?php else: ?>
        <p class="no-reviews">No reviews yet. Be the first to review this product.</p>
      <?php endif; ?>

      <!-- Review form -->
      <?php if (isset($_GET['review']) && $_GET['review'] === 'ok'): ?>
        <div style="padding:12px 16px;background:#f0fdf4;border-radius:10px;color:#16a34a;font-weight:500;font-size:13px;margin-bottom:16px;border:1px solid #bbf7d0;">
          ✓ Your review was submitted. Thank you!
        </div>
      <?php endif; ?>

      <form method="POST" class="review-form">
        <input type="hidden" name="submit_review" value="1"/>
        <div class="review-form-title">Write a Review</div>

        <!-- Star click selector (CSS-only trick, RTL layout) -->
        <div class="star-rating-input" id="starInput">
          <input type="radio" name="rating" id="r5" value="5"/><label for="r5" title="5 stars">★</label>
          <input type="radio" name="rating" id="r4" value="4"/><label for="r4" title="4 stars">★</label>
          <input type="radio" name="rating" id="r3" value="3" checked/><label for="r3" title="3 stars">★</label>
          <input type="radio" name="rating" id="r2" value="2"/><label for="r2" title="2 stars">★</label>
          <input type="radio" name="rating" id="r1" value="1"/><label for="r1" title="1 star">★</label>
        </div>

        <div class="review-input-row">
          <textarea name="comment" placeholder="Share your experience with this product..." required></textarea>
          <button type="submit" class="submit-review-btn">Submit</button>
        </div>
      </form>
    </div>

    <!-- Company -->
    <div class="tab-content <?= $activeTab === 'company' ? 'active' : '' ?>" id="tab-company">
      <p>We are a premium electronics retailer committed to delivering quality products directly to your door. Every item is sourced from verified manufacturers with a full warranty and dedicated customer support.</p>
      <p>Founded in 2015, our company has served over 500,000 customers worldwide, with a focus on transparency, fast delivery, and hassle-free returns.</p>
    </div>

    <!-- Usage guide -->
    <div class="tab-content <?= $activeTab === 'usage' ? 'active' : '' ?>" id="tab-usage">
      <p><strong>Getting Started:</strong> Charge the device fully before first use. Press and hold the power button for 3 seconds to boot.</p>
      <p><strong>Care Instructions:</strong> Use a soft microfiber cloth for cleaning. Avoid exposing the device to extreme temperatures or moisture.</p>
      <p><strong>Warranty:</strong> This product comes with a 12-month manufacturer warranty. Contact our support team for any hardware-related concerns.</p>
    </div>
  </div>
</main>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg">Done</span>
</div>

<script src="../scripts/cart-panel.js"></script>
<script>
// ── Data from PHP ──────────────────────────────────────
const PRODUCT_ID    = <?= json_encode($productId) ?>;
const PRODUCT_NAME  = <?= json_encode($product['name']) ?>;
const PRODUCT_IMG   = <?= json_encode($images[0] ? (PRODUCT_IMGS_BASE . $images[0]) : '') ?>;
const IMGS_BASE     = <?= json_encode(PRODUCT_IMGS_BASE) ?>;
const ALL_IMAGES    = <?= json_encode($images) ?>;
const ALL_VARIANTS  = <?= json_encode($variants) ?>;
const BASE_PRICE    = <?= (float)$product['price'] ?>;
const BASE_STOCK    = <?= (int)$product['stock'] ?>;

// ── State ──────────────────────────────────────────────
let qty             = 1;
let selectedAttrs   = {};   // e.g. { Color: 'Midnight', Storage: '128GB' }
let activeVariant   = null; // matched variant object
let currentMaxStock = BASE_STOCK;

// ── Toast ──────────────────────────────────────────────
function showToast(msg) {
  const el = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 2800);
}

// ── Thumbnails ─────────────────────────────────────────
const mainImg = document.getElementById('mainImg');
document.querySelectorAll('.thumb').forEach(t => {
  t.addEventListener('click', () => {
    document.querySelectorAll('.thumb').forEach(x => x.classList.remove('active'));
    t.classList.add('active');
    const idx    = +t.dataset.idx;
    const newSrc = ALL_IMAGES[idx]
      ? IMGS_BASE + ALL_IMAGES[idx]
      : '../assets/img/placeholder.png';
    mainImg.classList.add('switching');
    setTimeout(() => {
      mainImg.src = newSrc;
      mainImg.classList.remove('switching');
    }, 200);
  });
});

// ── Option selection ───────────────────────────────────
document.querySelectorAll('.option-buttons').forEach(group => {
  const attrName = group.dataset.attribute;
  group.querySelectorAll('[data-val]').forEach(btn => {
    btn.addEventListener('click', () => {
      group.querySelectorAll('[data-val]').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      selectedAttrs[attrName] = btn.dataset.val;
      matchVariant();
    });
    // Init from first active
    if (btn.classList.contains('active')) {
      selectedAttrs[attrName] = btn.dataset.val;
    }
  });
});

// ── Match variant based on selected attributes ─────────
function matchVariant() {
  if (ALL_VARIANTS.length === 0) {
    // No variants — use product base price/stock
    updateUI(BASE_PRICE, BASE_STOCK, null);
    return;
  }

  // Find variant where ALL selected attrs match
  const match = ALL_VARIANTS.find(v => {
    return Object.entries(selectedAttrs).every(
      ([k, val]) => v.attributes[k] === val
    );
  });

  if (match) {
    activeVariant = match;
    const price = parseFloat(match.price);
    const stock = parseInt(match.stock_quantity ?? 0);
    // If variant has its own image, swap the main image
    if (match.image_url) {
      mainImg.classList.add('switching');
      setTimeout(() => {
        mainImg.src = IMGS_BASE + match.image_url;
        mainImg.classList.remove('switching');
      }, 200);
    }
    updateUI(price, stock, match);
  } else {
    // Combination not available
    activeVariant = null;
    updateUI(BASE_PRICE, 0, null);
  }
}

function updateUI(price, stock, variant) {
  currentMaxStock = stock;

  // Price
  document.getElementById('priceDisplay').textContent =
    '₱' + price.toLocaleString('en-PH', { minimumFractionDigits: 2 });

  // Stock badge
  const wrap = document.getElementById('stockStatusWrap');
  if (stock === 0)      wrap.innerHTML = '<span class="stock-badge stock-out">Out of Stock</span>';
  else if (stock <= 5)  wrap.innerHTML = `<span class="stock-badge stock-low">Only ${stock} left</span>`;
  else                  wrap.innerHTML = '<span class="stock-badge stock-in">In Stock</span>';

  // Qty reset if exceeded
  if (qty > stock) qty = Math.max(1, stock);
  if (stock === 0) qty = 0;
  document.getElementById('qtyVal').value = stock > 0 ? qty : 0;

  // Button states
  const outOfStock = stock === 0;
  document.getElementById('qtyMinus').disabled  = outOfStock || qty <= 1;
  document.getElementById('qtyPlus').disabled   = outOfStock || qty >= stock;
  document.getElementById('addToCart').disabled = outOfStock;
  document.getElementById('buyNow').disabled    = outOfStock;
  document.getElementById('addToCart').textContent = outOfStock ? 'Out of Stock' : 'Add to cart';
  if (!outOfStock) {
    document.getElementById('addToCart').insertAdjacentHTML('afterbegin',
      `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg> `
    );
  }
}

// ── Quantity controls ──────────────────────────────────
document.getElementById('qtyMinus').addEventListener('click', () => {
  if (qty > 1) { qty--; syncQty(); }
});
document.getElementById('qtyPlus').addEventListener('click', () => {
  if (qty < currentMaxStock) { qty++; syncQty(); }
});
function syncQty() {
  document.getElementById('qtyVal').value   = qty;
  document.getElementById('qtyMinus').disabled = qty <= 1;
  document.getElementById('qtyPlus').disabled  = qty >= currentMaxStock;
}

// ── Add to Cart ────────────────────────────────────────
document.getElementById('addToCart').addEventListener('click', async () => {
  if (currentMaxStock === 0) return;
  const price = activeVariant ? parseFloat(activeVariant.price) : BASE_PRICE;
  try {
    const res = await fetch('../includes/add.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ product_id: PRODUCT_ID, quantity: qty })
    });
    const data = await res.json();
    if (data.success) {
      showToast(`${qty} × ${PRODUCT_NAME} added to cart!`);
      // Refresh the cart panel if it's visible
      if (typeof CartPanel !== 'undefined') CartPanel.render();
    } else {
      showToast(data.message || 'Failed to add to cart');
    }
  } catch (err) {
    console.error(err);
    showToast('Something went wrong');
  }
});

// ── Buy Now ────────────────────────────────────────────
document.getElementById('buyNow').addEventListener('click', () => {
  if (currentMaxStock === 0) return;
  window.location.href = `../user/checkout.php?direct_buy=1&product_id=${PRODUCT_ID}&qty=${qty}`;
});

// ── Tabs ───────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
  });
});

// ── See more reviews ───────────────────────────────────
function toggleMoreReviews(btn) {
  const more = document.getElementById('moreReviews');
  const open = more.classList.toggle('open');
  btn.querySelector('span').textContent = open ? 'Show fewer reviews' : 'See all <?= $totalReviews ?> reviews';
  btn.querySelector('svg').style.transform = open ? 'rotate(180deg)' : '';
}

// ── Init — match default variant on load ───────────────
matchVariant();

// ── Auto-open reviews tab if ?tab=reviews ─────────────
<?php if ($activeTab === 'reviews'): ?>
document.querySelector('[data-tab="reviews"]').click();
<?php endif; ?>
</script>
</body>
</html>