<?php
session_start();

// ── Auth guard ────────────────────────────────────────
if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/db.php';

define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

// ═══════════════════════════════════════════════════════
//  HANDLE ACTIONS (delete)
// ═══════════════════════════════════════════════════════

if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delId = (int)$_POST['product_id'];
    if ($delId > 0) {
        pg_query_params($conn, "DELETE FROM product_variants WHERE product_id = $1", [$delId]);
        $del = pg_query_params($conn, "DELETE FROM products WHERE id = $1", [$delId]);
        $deleteMsg = $del ? 'Product deleted successfully.' : 'Delete failed: ' . pg_last_error($conn);
    }
    $qs = http_build_query(array_filter([
        'search'   => $_GET['search']   ?? '',
        'stock'    => $_GET['stock']    ?? 'all',
        'price'    => $_GET['price']    ?? 'all',
        'category' => $_GET['category'] ?? 'all',
        'sort'     => $_GET['sort']     ?? 'default',
        'page'     => $_GET['page']     ?? 1,
        'msg'      => $deleteMsg ?? '',
    ]));
    header("Location: admin-products.php?{$qs}");
    exit;
}

// ═══════════════════════════════════════════════════════
//  INPUTS
// ═══════════════════════════════════════════════════════

$search      = isset($_GET['search'])   ? trim($_GET['search'])      : '';
$filterStock = isset($_GET['stock'])    ? $_GET['stock']             : 'all';
$filterPrice = isset($_GET['price'])    ? $_GET['price']             : 'all';
$filterCat   = isset($_GET['category']) ? $_GET['category']          : 'all';
$sortBy      = isset($_GET['sort'])     ? $_GET['sort']              : 'default';
$page        = isset($_GET['page'])     ? max(1,(int)$_GET['page'])  : 1;
$perPage     = 10;
$offset      = ($page - 1) * $perPage;
$flashMsg    = isset($_GET['msg'])      ? trim($_GET['msg'])         : '';

// ═══════════════════════════════════════════════════════
//  BUILD WHERE CLAUSE
// ═══════════════════════════════════════════════════════

$conditions = [];
$params     = [];
$pi         = 1;

if (!empty($search)) {
    $conditions[] = "(p.name ILIKE \${$pi} OR p.category ILIKE \${$pi})";
    $params[] = '%' . $search . '%';
    $pi++;
}

if ($filterStock === 'active')     { $conditions[] = "p.stock > 0"; }
if ($filterStock === 'outofstock') { $conditions[] = "p.stock = 0"; }
if ($filterStock === 'lowstock')   { $conditions[] = "p.stock > 0 AND p.stock <= 5"; }

if ($filterPrice === '0-1000')      { $conditions[] = "p.price::numeric BETWEEN 0 AND 1000"; }
if ($filterPrice === '1000-10000')  { $conditions[] = "p.price::numeric BETWEEN 1001 AND 10000"; }
if ($filterPrice === '10000-50000') { $conditions[] = "p.price::numeric BETWEEN 10001 AND 50000"; }
if ($filterPrice === '50000+')      { $conditions[] = "p.price::numeric > 50000"; }

if ($filterCat !== 'all' && !empty($filterCat)) {
    $conditions[] = "p.category = \${$pi}";
    $params[] = $filterCat;
    $pi++;
}

$whereSQL = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$orderSQL = match($sortBy) {
    'name_asc'    => 'ORDER BY p.name ASC',
    'name_desc'   => 'ORDER BY p.name DESC',
    'price_asc'   => 'ORDER BY p.price::numeric ASC',
    'price_desc'  => 'ORDER BY p.price::numeric DESC',
    'stock_asc'   => 'ORDER BY p.stock ASC',
    'stock_desc'  => 'ORDER BY p.stock DESC',
    'newest'      => 'ORDER BY p.created_at DESC',
    default       => 'ORDER BY p.id ASC',
};

// ═══════════════════════════════════════════════════════
//  SUMMARY STATS
// ═══════════════════════════════════════════════════════

$r = pg_query($conn, "SELECT COUNT(*) FROM products");
$totalProducts = $r ? (int)pg_fetch_result($r, 0, 0) : 0;

$r = pg_query($conn, "SELECT COUNT(*) FROM products WHERE stock > 0");
$totalActive = $r ? (int)pg_fetch_result($r, 0, 0) : 0;

$r = pg_query($conn, "SELECT COUNT(*) FROM products WHERE stock = 0");
$totalOut = $r ? (int)pg_fetch_result($r, 0, 0) : 0;

$r = pg_query($conn, "SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5");
$totalLow = $r ? (int)pg_fetch_result($r, 0, 0) : 0;

// ═══════════════════════════════════════════════════════
//  TOTAL FILTERED COUNT
// ═══════════════════════════════════════════════════════

$countSQL    = "SELECT COUNT(*) FROM products p {$whereSQL}";
$countResult = pg_query_params($conn, $countSQL, $params);
$totalFiltered = $countResult ? (int)pg_fetch_result($countResult, 0, 0) : 0;
$totalPages    = max(1, ceil($totalFiltered / $perPage));

// ═══════════════════════════════════════════════════════
//  MAIN PRODUCT QUERY
// ═══════════════════════════════════════════════════════

$mainSQL = "
    SELECT
        p.id,
        p.name,
        p.description,
        p.price::numeric        AS price,
        p.stock,
        p.image,
        p.category,
        p.created_at,
        COUNT(pv.id)            AS variant_count,
        COALESCE(MIN(pv.price::numeric), p.price::numeric) AS min_price,
        COALESCE(MAX(pv.price::numeric), p.price::numeric) AS max_price
    FROM products p
    LEFT JOIN product_variants pv ON pv.product_id = p.id
    {$whereSQL}
    GROUP BY p.id
    {$orderSQL}
    LIMIT \${$pi} OFFSET \$" . ($pi + 1) . "
";

$queryParams   = array_merge($params, [$perPage, $offset]);
$result        = pg_query_params($conn, $mainSQL, $queryParams);
$dbError       = $result ? null : pg_last_error($conn);

$products = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $products[] = $row;
    }
}

// ═══════════════════════════════════════════════════════
//  DISTINCT CATEGORIES
// ═══════════════════════════════════════════════════════

$catResult  = pg_query($conn,
    "SELECT DISTINCT category FROM products
     WHERE category IS NOT NULL AND category != ''
     ORDER BY category"
);
$allCategories = [];
if ($catResult) {
    while ($row = pg_fetch_assoc($catResult)) {
        $allCategories[] = $row['category'];
    }
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

function qs(array $overrides = []): string {
    global $search, $filterStock, $filterPrice, $filterCat, $sortBy, $page;
    return http_build_query(array_merge([
        'search'   => $search,
        'stock'    => $filterStock,
        'price'    => $filterPrice,
        'category' => $filterCat,
        'sort'     => $sortBy,
        'page'     => $page,
    ], $overrides));
}

function productStatus(int $stock): array {
    if ($stock === 0)      return ['Out of Stock', 'status-out'];
    if ($stock <= 5)       return ['Low Stock',    'status-low'];
    return                        ['Active',        'status-active'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Product List – Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    /* (Keep all your existing CSS – unchanged) */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:         #e8e8e8;
      --sidebar-bg: #d4d4d4;
      --white:      #ffffff;
      --text:       #1a1a1a;
      --muted:      #6b7280;
      --border:     #e0e0e0;
      --green:      #16a34a;
      --blue:       #2563eb;
      --danger:     #ef4444;
      --radius:     14px;
      --shadow:     0 2px 12px rgba(0,0,0,0.07);
      --font:       'Sora', system-ui, sans-serif;
    }
    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }
    .sidebar {
      background: var(--sidebar-bg);
      display: flex;
      flex-direction: column;
      padding: 20px 12px;
      gap: 6px;
      border-right: 1px solid #c8c8c8;
      position: sticky;
      top: 0;
      height: 100vh;
      overflow-y: auto;
    }

    .sidebar-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 12px 20px;
      border-bottom: 1px solid #bbb;
      margin-bottom: 8px;
      text-decoration: none;
    }

    .sidebar-logo-icon {
      width: 36px;
      height: 36px;
      background: var(--white);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
      border: 1.5px solid #ddd;
    }

  .sidebar-logo-icon svg { width: 20px; height: 20px; }

    .sidebar-logo-text {
      font-size: 14px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -.2px;
    }


    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px; border-radius: 12px;
      font-size: 13px; font-weight: 500; color: var(--text);
      text-decoration: none; transition: background .15s;
      border: none; background: transparent; width: 100%; cursor: pointer;
    }
    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: var(--text); color: #fff; font-weight: 600; }
    .nav-item.active svg { color: #fff; }
    .main-content { display: flex; flex-direction: column; padding: 28px 28px 48px; gap: 22px; overflow-y: auto; }
    .page-header { display: flex; align-items: center; justify-content: space-between; }
    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }
    .btn-admin {
      display: flex; align-items: center; gap: 9px;
      padding: 9px 18px; background: var(--white);
      border: 1.5px solid var(--border); border-radius: 12px;
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
    }
    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }
    .stats-row {
      display: flex; gap: 1px;
      background: var(--border); border: 1px solid var(--border);
      border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow);
    }
    .stat-card { background: var(--white); padding: 18px 24px; flex: 1; }
    .stat-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 6px; }
    .stat-value { font-size: 28px; font-weight: 700; letter-spacing: -.5px; color: var(--text); }
    .stat-value.red  { color: var(--danger); }
    .stat-value.warn { color: #d97706; }
    .flash { padding: 12px 18px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
    .flash-success { background: #f0fdf4; color: var(--green); border: 1px solid #bbf7d0; }
    .flash-error   { background: #fef2f2; color: var(--danger); border: 1px solid #fecaca; }
    .table-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
    .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); gap: 10px; flex-wrap: wrap; }
    .search-wrap { display: flex; align-items: center; gap: 8px; padding: 7px 14px; border: 1.5px solid var(--border); border-radius: 10px; background: #fafafa; transition: border-color .18s; min-width: 200px; }
    .search-wrap:focus-within { border-color: #aaa; background: #fff; }
    .search-wrap svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }
    .search-wrap input { border: none; background: transparent; font-family: var(--font); font-size: 12px; color: var(--text); outline: none; width: 160px; }
    .search-wrap input::placeholder { color: var(--muted); }
    .toolbar-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .filter-wrap { position: relative; }
    .filter-wrap svg.chev { position: absolute; right: 9px; top: 50%; transform: translateY(-50%); width: 11px; height: 11px; color: var(--muted); pointer-events: none; }
    .filter-select { appearance: none; font-family: var(--font); font-size: 12px; font-weight: 500; color: var(--text); background: var(--white); border: 1.5px solid var(--border); border-radius: 10px; padding: 7px 28px 7px 12px; cursor: pointer; outline: none; transition: border-color .15s; }
    .filter-select:hover, .filter-select:focus { border-color: #aaa; }
    .btn-add { display: flex; align-items: center; gap: 7px; padding: 8px 16px; background: var(--blue); color: #fff; border: none; border-radius: 10px; font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; text-decoration: none; white-space: nowrap; }
    .btn-add:hover { background: #1d4ed8; }
    .btn-add svg { width: 14px; height: 14px; }
    .prod-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .prod-table thead tr { background: #f9fafb; border-bottom: 1px solid var(--border); }
    .prod-table th { padding: 12px 18px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); white-space: nowrap; }
    .prod-table td { padding: 13px 18px; border-bottom: 1px solid #f5f5f5; color: var(--text); vertical-align: middle; }
    .prod-table tbody tr:last-child td { border-bottom: none; }
    .prod-table tbody tr { transition: background .12s; }
    .prod-table tbody tr:hover { background: #fafafa; }
    .product-cell { display: flex; align-items: center; gap: 12px; }
    .product-thumb { width: 42px; height: 42px; border-radius: 8px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; border: 1px solid var(--border); }
    .product-name { font-weight: 500; font-size: 13px; line-height: 1.35; }
    .product-sku { font-size: 10px; color: var(--muted); margin-top: 2px; font-family: monospace; }
    .col-muted { color: var(--muted); font-size: 12px; }
    .col-price { font-weight: 600; }
    .col-stock { font-weight: 600; }
    .price-range { font-size: 11px; color: var(--muted); display: block; margin-top: 2px; }
    .variant-chip { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; background: #f0f4ff; color: #3b82f6; border-radius: 20px; font-size: 10px; font-weight: 600; }
    .status-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .status-active { background: #f0fdf4; color: #16a34a; }
    .status-active::before { background: #16a34a; }
    .status-low { background: #fffbeb; color: #d97706; }
    .status-low::before { background: #d97706; }
    .status-out { background: #fef2f2; color: #dc2626; }
    .status-out::before { background: #dc2626; }
    .row-actions { display: flex; align-items: center; gap: 6px; opacity: 0; transition: opacity .15s; }
    .prod-table tbody tr:hover .row-actions { opacity: 1; }
    .action-btn { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); transition: background .15s, color .15s, border-color .15s; text-decoration: none; }
    .action-btn:hover { background: #f5f5f5; color: var(--text); }
    .action-btn.danger:hover { background: #fef2f2; color: var(--danger); border-color: #fecaca; }
    .action-btn svg { width: 13px; height: 13px; }
    .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); font-size: 14px; }
    .pagination-wrap { display: flex; justify-content: center; align-items: center; gap: 6px; padding: 20px 0 4px; }
    .page-btn { min-width: 34px; height: 34px; padding: 0 10px; border-radius: 9px; border: 1.5px solid var(--border); background: var(--white); font-family: var(--font); font-size: 13px; font-weight: 500; color: var(--muted); cursor: pointer; transition: border-color .15s, background .15s, color .15s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
    .page-btn:hover { border-color: #aaa; color: var(--text); }
    .page-btn.active { background: var(--text); border-color: var(--text); color: #fff; font-weight: 600; }
    .page-ellipsis { color: var(--muted); font-size: 13px; padding: 0 4px; }
    .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.35); z-index: 800; display: none; align-items: center; justify-content: center; }
    .modal-overlay.open { display: flex; }
    .modal-box { background: #fff; border-radius: 16px; padding: 32px 28px; max-width: 380px; width: 90%; text-align: center; animation: popIn .22s cubic-bezier(.34,1.56,.64,1); }
    @keyframes popIn { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:none; } }
    .modal-icon { font-size: 36px; margin-bottom: 12px; }
    .modal-title { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
    .modal-body { font-size: 13px; color: var(--muted); margin-bottom: 22px; }
    .modal-name { font-weight: 600; color: var(--text); }
    .modal-actions { display: flex; gap: 10px; justify-content: center; }
    .btn-cancel { padding: 10px 22px; background: #f5f5f5; border: none; border-radius: 10px; font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; }
    .btn-cancel:hover { background: #ebebeb; }
    .btn-delete { padding: 10px 22px; background: var(--danger); color: #fff; border: none; border-radius: 10px; font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; }
    .btn-delete:hover { background: #dc2626; }
    .toast { position: fixed; bottom: 28px; right: 28px; background: #1a1a1a; color: #fff; padding: 13px 20px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 28px rgba(0,0,0,0.16); transform: translateY(70px); opacity: 0; transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s; z-index: 999; pointer-events: none; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }
  </style>
</head>
<body>

<div class="admin-shell">

<?php
// Get current filename
$current_file = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <a href="../admin/admindashboard.php" class="sidebar-logo">
    <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
    <span class="sidebar-logo-text">E-Commerce</span>
  </a>

  <a href="../admin/admindashboard.php" class="nav-item <?= $current_file == 'admindashboard.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
    Dashboard
  </a>

  <a href="../admin/inventory.php" class="nav-item <?= $current_file == 'inventory.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 8h14M5 8a2 2 0 010-4h14a2 2 0 010 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/><path d="M10 12h4"/></svg>
    Inventory
  </a>

  <a href="../admin/admin-orders.php" class="nav-item <?= $current_file == 'admin-orders.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
    Orders
  </a>

  <a href="../admin/admin-products.php" class="nav-item <?= $current_file == 'admin-products.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
    Product List
  </a>

  <a href="../admin/admin-customers.php" class="nav-item <?= $current_file == 'admin-customers.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
    Customers
  </a>
</aside>


  <!-- ── Main ─────────────────────────────────────── -->
  <main class="main-content">

    <div class="page-header">
      <h1 class="page-title">Product List</h1>
      <button class="btn-admin">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
        <?= htmlspecialchars($adminName) ?>
      </button>
    </div>

    <?php if ($flashMsg): ?>
      <div class="flash <?= str_contains($flashMsg, 'failed') ? 'flash-error' : 'flash-success' ?>">
        <?= str_contains($flashMsg, 'failed') ? '✕' : '✓' ?>
        <?= htmlspecialchars($flashMsg) ?>
      </div>
    <?php endif; ?>

    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= number_format($totalProducts) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Products</div>
        <div class="stat-value"><?= number_format($totalActive) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Low Stock</div>
        <div class="stat-value warn"><?= number_format($totalLow) ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value <?= $totalOut > 0 ? 'red' : '' ?>"><?= number_format($totalOut) ?></div>
      </div>
    </div>

    <div class="table-card">

      <form method="GET" id="filterForm">
        <div class="toolbar">
          <div class="search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="search" placeholder="Search products..."
                   value="<?= htmlspecialchars($search) ?>"
                   onchange="this.form.submit()"/>
          </div>

          <div class="toolbar-right">
            <div class="filter-wrap">
              <select name="stock" class="filter-select" onchange="this.form.submit()">
                <option value="all"        <?= $filterStock === 'all'        ? 'selected' : '' ?>>Stock Status</option>
                <option value="active"     <?= $filterStock === 'active'     ? 'selected' : '' ?>>Active</option>
                <option value="lowstock"   <?= $filterStock === 'lowstock'   ? 'selected' : '' ?>>Low Stock</option>
                <option value="outofstock" <?= $filterStock === 'outofstock' ? 'selected' : '' ?>>Out of Stock</option>
              </select>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            <div class="filter-wrap">
              <select name="price" class="filter-select" onchange="this.form.submit()">
                <option value="all"        <?= $filterPrice === 'all'        ? 'selected' : '' ?>>Price Range</option>
                <option value="0-1000"     <?= $filterPrice === '0-1000'     ? 'selected' : '' ?>>₱0 – ₱1,000</option>
                <option value="1000-10000" <?= $filterPrice === '1000-10000' ? 'selected' : '' ?>>₱1,000 – ₱10,000</option>
                <option value="10000-50000"<?= $filterPrice === '10000-50000'? 'selected' : '' ?>>₱10,000 – ₱50,000</option>
                <option value="50000+"     <?= $filterPrice === '50000+'     ? 'selected' : '' ?>>₱50,000+</option>
              </select>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            <div class="filter-wrap">
              <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="all">Category</option>
                <?php foreach ($allCategories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCat === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            <div class="filter-wrap">
              <select name="sort" class="filter-select" onchange="this.form.submit()">
                <option value="default"    <?= $sortBy === 'default'    ? 'selected' : '' ?>>Sort by</option>
                <option value="name_asc"   <?= $sortBy === 'name_asc'   ? 'selected' : '' ?>>Name A–Z</option>
                <option value="name_desc"  <?= $sortBy === 'name_desc'  ? 'selected' : '' ?>>Name Z–A</option>
                <option value="price_asc"  <?= $sortBy === 'price_asc'  ? 'selected' : '' ?>>Price Low–High</option>
                <option value="price_desc" <?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price High–Low</option>
                <option value="stock_asc"  <?= $sortBy === 'stock_asc'  ? 'selected' : '' ?>>Stock Low–High</option>
                <option value="newest"     <?= $sortBy === 'newest'     ? 'selected' : '' ?>>Newest First</option>
              </select>
              <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </div>

            <input type="hidden" name="page" value="1"/>

            <a href="../admin/admin-add-product.php" class="btn-add">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              Add Product
            </a>
          </div>
        </div>
      </form>

      <?php if ($dbError): ?>
        <div style="padding:20px; color:var(--danger); font-size:13px; background:#fef2f2;">
          <strong>Query error:</strong> <?= htmlspecialchars($dbError) ?>
        </div>
      <?php endif; ?>

      <table class="prod-table">
        <thead>
          <tr><th>Product</th><th>Category</th><th>Variants</th><th>Stock</th><th>Price</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr><td colspan="7"><div class="empty-state"><?= !empty($search) ? 'No products match "' . htmlspecialchars($search) . '".' : 'No products found.' ?></div></td></tr>
          <?php else: ?>
            <?php foreach ($products as $p):
              [$statusLabel, $statusClass] = productStatus((int)$p['stock']);
              $imgSrc = !empty($p['image']) ? PRODUCT_IMGS_BASE . $p['image'] : '../assets/img/placeholder.png';
              $variantCount = (int)$p['variant_count'];
              $minP = (float)$p['min_price'];
              $maxP = (float)$p['max_price'];
              $priceDisplay = $variantCount > 0 && $minP !== $maxP
                  ? '₱' . number_format($minP) . ' – ₱' . number_format($maxP)
                  : '₱' . number_format((float)$p['price'], 2);
            ?>
            <tr>
              <td>
                <div class="product-cell">
                  <img src="<?= htmlspecialchars($imgSrc) ?>"
                  alt="<?= htmlspecialchars($p['name']) ?>"
                  class="product-thumb"
                  onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'42\' height=\'42\' viewBox=\'0 0 42 42\'%3E%3Crect width=\'42\' height=\'42\' fill=\'%23f3f4f6\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\' fill=\'%239ca3af\' font-size=\'10\'%3ENo img%3C/text%3E%3C/svg%3E'">
                  <div>
                    <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="product-sku">ID #<?= $p['id'] ?></div>
                  </div>
                </div>
               </td>
              <td class="col-muted"><?= htmlspecialchars($p['category'] ?? '—') ?></td>
              <td>
                <?php if ($variantCount > 0): ?>
                  <span class="variant-chip"><?= $variantCount ?> variant<?= $variantCount > 1 ? 's' : '' ?></span>
                <?php else: ?>
                  <span class="col-muted">—</span>
                <?php endif; ?>
               </td>
              <td class="col-stock"><?= number_format((int)$p['stock']) ?></td>
              <td class="col-price"><?= $priceDisplay ?></td>
              <td><span class="status-badge <?= $statusClass ?>"><?= $statusLabel ?></span></td>
              <td>
                <div class="row-actions">
                  <a href="../user/viewitems.php?id=<?= $p['id'] ?>" class="action-btn" title="View" target="_blank">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                  </a>
                  <a href="edit-product.php?id=<?= $p['id'] ?>" class="action-btn" title="Edit">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                  </a>
                  <button class="action-btn danger" title="Delete" onclick="openDeleteModal(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/><path d="M10 11v6M14 11v6M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/></svg>
                  </button>
                </div>
               </td>
             </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
      <div class="pagination-wrap">
        <?php for ($pg = 1; $pg <= $totalPages; $pg++):
          $pgqs = qs(['page' => $pg]);
          $isActive = $pg === $page ? 'active' : '';
          if ($totalPages > 7) {
              $show = ($pg === 1 || $pg === $totalPages || abs($pg - $page) <= 1);
              if ($pg === $page - 2 && $page > 3) echo '<span class="page-ellipsis">…</span>';
              if (!$show) continue;
          }
        ?>
          <a href="?<?= $pgqs ?>" class="page-btn <?= $isActive ?>"><?= $pg ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php if ($totalFiltered > 0): ?>
    <p style="text-align:center; font-size:12px; color:var(--muted);">
      Showing <?= count($products) ?> of <?= number_format($totalFiltered) ?> products
    </p>
    <?php endif; ?>

  </main>
</div>

<div class="modal-overlay" id="deleteModal">
  <div class="modal-box">
    <div class="modal-icon">🗑</div>
    <div class="modal-title">Delete Product?</div>
    <div class="modal-body">
      You're about to delete <span class="modal-name" id="modalProductName"></span>.
      This will also remove all its variants. This cannot be undone.
    </div>
    <div class="modal-actions">
      <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
      <form method="POST" style="display:contents" id="deleteForm">
        <input type="hidden" name="action" value="delete"/>
        <input type="hidden" name="product_id" id="modalProductId"/>
        <button type="submit" class="btn-delete">Delete</button>
      </form>
    </div>
  </div>
</div>

<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg">Done</span>
</div>

<script>
function openDeleteModal(id, name) {
  document.getElementById('modalProductName').textContent = name;
  document.getElementById('modalProductId').value = id;
  document.getElementById('deleteModal').classList.add('open');
}
function closeDeleteModal() {
  document.getElementById('deleteModal').classList.remove('open');
}
document.getElementById('deleteModal').addEventListener('click', function(e) {
  if (e.target === this) closeDeleteModal();
});
function toast(msg) {
  const el = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 2800);
}
<?php if ($flashMsg): ?>
  window.addEventListener('DOMContentLoaded', () => toast(<?= json_encode($flashMsg) ?>));
<?php endif; ?>
document.querySelector('.search-wrap input').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('filterForm').submit();
});
</script>
</body>
</html>