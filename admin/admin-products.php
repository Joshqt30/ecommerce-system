<?php
session_start();

// ── Auth guard — uncomment when ready ─────────────────
// if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../auth/login.php');
//     exit;
// }

include '../config/db.php';

// ═══════════════════════════════════════════════════════
//  BACKEND DATA HOOKS
// ═══════════════════════════════════════════════════════

$search      = isset($_GET['search'])     ? trim($_GET['search'])       : '';
$filterStock = isset($_GET['stock'])      ? $_GET['stock']              : 'all';
$filterPrice = isset($_GET['price'])      ? $_GET['price']              : 'all';
$filterCat   = isset($_GET['category'])   ? $_GET['category']           : 'all';
$sortBy      = isset($_GET['sort'])       ? $_GET['sort']               : 'default';
$page        = isset($_GET['page'])       ? max(1, (int)$_GET['page'])  : 1;
$perPage     = 10;
$offset      = ($page - 1) * $perPage;

// -- Real query when backend is ready --
// $where  = "WHERE 1=1";
// $params = [];
// $types  = '';
//
// if ($filterStock === 'active')      { $where .= " AND stock > 0"; }
// if ($filterStock === 'outofstock')  { $where .= " AND stock = 0"; }
//
// if ($filterPrice === '0-100')       { $where .= " AND price BETWEEN 0 AND 100"; }
// if ($filterPrice === '100-500')     { $where .= " AND price BETWEEN 100 AND 500"; }
// if ($filterPrice === '500+')        { $where .= " AND price > 500"; }
//
// if ($filterCat !== 'all') {
//     $where  .= " AND category = ?";
//     $params[]= $filterCat; $types .= 's';
// }
//
// if (!empty($search)) {
//     $where  .= " AND (name LIKE ? OR id LIKE ?)";
//     $like    = '%' . $search . '%';
//     $params  = array_merge($params, [$like, $like]); $types .= 'ss';
// }
//
// $orderClause = match($sortBy) {
//     'name_asc'   => 'ORDER BY name ASC',
//     'name_desc'  => 'ORDER BY name DESC',
//     'price_asc'  => 'ORDER BY price ASC',
//     'price_desc' => 'ORDER BY price DESC',
//     'stock_asc'  => 'ORDER BY stock ASC',
//     default      => 'ORDER BY id ASC',
// };
//
// $countRes = $conn->query("SELECT COUNT(*) FROM products $where");
// $total    = $countRes->fetch_row()[0];
// $stmt     = $conn->prepare("SELECT * FROM products $where $orderClause LIMIT ? OFFSET ?");
// ... bind and execute

// -- Static placeholder data --
$allProducts = [
    ['id' => 1, 'product_id' => 'PRD0001', 'name' => 'Wireless Earbuds',     'image' => 'https://images.unsplash.com/photo-1572635196237-14b3f281503f?w=60&q=75', 'category' => 'Electronics',    'stock' => 320, 'price' => 250.00],
    ['id' => 2, 'product_id' => 'PRD0002', 'name' => 'Yoga Mat',             'image' => 'https://images.unsplash.com/photo-1601925228036-35e06b5ee4d1?w=60&q=75', 'category' => 'Fitness',         'stock' => 0,   'price' => 99.00],
    ['id' => 3, 'product_id' => 'PRD0003', 'name' => 'Ceramic Coffee Mug',   'image' => 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=60&q=75', 'category' => 'Home & Kitchen',  'stock' => 120, 'price' => 158.00],
    ['id' => 4, 'product_id' => 'PRD0004', 'name' => 'Smart Watch',          'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=60&q=75', 'category' => 'Electronics',    'stock' => 210, 'price' => 1250.00],
    ['id' => 5, 'product_id' => 'PRD0005', 'name' => 'Running Shoes',        'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=60&q=75', 'category' => 'Apparel',         'stock' => 80,  'price' => 279.00],
    ['id' => 6, 'product_id' => 'PRD0006', 'name' => 'Leather Backpack',     'image' => 'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?w=60&q=75', 'category' => 'Accessories',     'stock' => 55,  'price' => 480.00],
    ['id' => 7, 'product_id' => 'PRD0007', 'name' => 'Bluetooth Speaker',    'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=60&q=75', 'category' => 'Electronics',    'stock' => 0,   'price' => 350.00],
    ['id' => 8, 'product_id' => 'PRD0008', 'name' => 'Stainless Water Bottle','image'=> 'https://images.unsplash.com/photo-1602143407151-7111542de6e8?w=60&q=75', 'category' => 'Fitness',         'stock' => 200, 'price' => 75.00],
    ['id' => 9, 'product_id' => 'PRD0009', 'name' => 'Desk Lamp',            'image' => 'https://images.unsplash.com/photo-1513506003901-1e6a35093a92?w=60&q=75', 'category' => 'Home & Kitchen',  'stock' => 44,  'price' => 120.00],
    ['id'=>10,  'product_id' => 'PRD0010', 'name' => 'Classic Snapback Cap',  'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=60&q=75', 'category' => 'Apparel',         'stock' => 90,  'price' => 55.00],
];

// Collect all unique categories for the dropdown
$allCategories = array_values(array_unique(array_column($allProducts, 'category')));
sort($allCategories);

// Client-side filtering for static demo
$filtered = array_filter($allProducts, function($p) use ($filterStock, $filterPrice, $filterCat, $search) {
    if ($filterStock === 'active'     && $p['stock'] === 0)  return false;
    if ($filterStock === 'outofstock' && $p['stock'] !== 0)  return false;

    if ($filterPrice === '0-100'   && !($p['price'] >= 0    && $p['price'] <= 100))  return false;
    if ($filterPrice === '100-500' && !($p['price'] > 100   && $p['price'] <= 500))  return false;
    if ($filterPrice === '500+'    && !($p['price'] > 500))                           return false;

    if ($filterCat !== 'all' && $p['category'] !== $filterCat) return false;

    if ($search && stripos($p['name'], $search) === false
               && stripos($p['product_id'], $search) === false) return false;
    return true;
});
$filtered = array_values($filtered);

// Client-side sorting
usort($filtered, function($a, $b) use ($sortBy) {
    return match($sortBy) {
        'name_asc'   => strcmp($a['name'],  $b['name']),
        'name_desc'  => strcmp($b['name'],  $a['name']),
        'price_asc'  => $a['price'] <=> $b['price'],
        'price_desc' => $b['price'] <=> $a['price'],
        'stock_asc'  => $a['stock'] <=> $b['stock'],
        default      => $a['id'] <=> $b['id'],
    };
});

$totalProducts = count($allProducts);
$totalActive   = count(array_filter($allProducts, fn($p) => $p['stock'] > 0));
$totalOut      = count(array_filter($allProducts, fn($p) => $p['stock'] === 0));

$total      = count($filtered);
$totalPages = max(1, ceil($total / $perPage));
$paged      = array_slice($filtered, $offset, $perPage);

$adminName = $_SESSION['admin_name'] ?? 'Admin';

function productStatus(int $stock): array {
    return $stock === 0
        ? ['Out of Stock', 'status-out']
        : ['Active',       'status-active'];
}

// Helper: build query string preserving all current filters
function qs(array $overrides = []): string {
    global $search, $filterStock, $filterPrice, $filterCat, $sortBy, $page;
    $base = [
        'search'   => $search,
        'stock'    => $filterStock,
        'price'    => $filterPrice,
        'category' => $filterCat,
        'sort'     => $sortBy,
        'page'     => $page,
    ];
    return http_build_query(array_merge($base, $overrides));
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
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:         #e8e8e8;
      --sidebar-bg: #d4d4d4;
      --white:      #ffffff;
      --text:       #1a1a1a;
      --muted:      #6b7280;
      --border:     #e0e0e0;
      --accent:     #4ade80;
      --blue:       #2563eb;
      --radius:     14px;
      --shadow:     0 2px 12px rgba(0,0,0,0.07);
      --font:       'Sora', system-ui, sans-serif;
    }

    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }

    /* ── Shell ──────────────────────────────────────── */
    .admin-shell {
      display: grid;
      grid-template-columns: 180px 1fr;
      min-height: 100vh;
    }

    /* ── Sidebar ────────────────────────────────────── */
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

    .sidebar-logo img {
      width: 34px; height: 34px;
      object-fit: contain;
      border-radius: 8px;
    }

    .sidebar-logo-text {
      font-size: 14px;
      font-weight: 700;
      color: var(--text);
      letter-spacing: -.2px;
    }

    .nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 11px 14px;
      border-radius: 12px;
      font-size: 13px;
      font-weight: 500;
      color: var(--text);
      text-decoration: none;
      transition: background .15s;
      border: none;
      background: transparent;
      width: 100%;
      cursor: pointer;
    }

    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: var(--text); color: #fff; font-weight: 600; }
    .nav-item.active svg { color: #fff; }

    /* ── Main ───────────────────────────────────────── */
    .main-content {
      display: flex;
      flex-direction: column;
      padding: 28px 28px 48px;
      gap: 22px;
      overflow-y: auto;
    }

    /* ── Page header ────────────────────────────────── */
    .page-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .page-title {
      font-size: 22px;
      font-weight: 700;
      letter-spacing: -.4px;
    }

    .btn-admin {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 9px 18px;
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 12px;
      font-family: var(--font);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s;
    }

    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }

    /* ── Summary stats ──────────────────────────────── */
    .stats-row {
      display: flex;
      gap: 1px;
      background: var(--border);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      overflow: hidden;
      box-shadow: var(--shadow);
    }

    .stat-card {
      background: var(--white);
      padding: 18px 24px;
      flex: 1;
    }

    .stat-label {
      font-size: 11px;
      font-weight: 600;
      color: var(--muted);
      text-transform: uppercase;
      letter-spacing: .06em;
      margin-bottom: 6px;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -.5px;
      color: var(--text);
    }

    /* ── Table card ─────────────────────────────────── */
    .table-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      overflow: hidden;
    }

    /* ── Toolbar ────────────────────────────────────── */
    .toolbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
      gap: 10px;
      flex-wrap: wrap;
    }

    /* Search */
    .search-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 7px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: #fafafa;
      transition: border-color .18s;
      min-width: 190px;
    }

    .search-wrap:focus-within { border-color: #aaa; background: #fff; }
    .search-wrap svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }

    .search-wrap input {
      border: none;
      background: transparent;
      font-family: var(--font);
      font-size: 12px;
      color: var(--text);
      outline: none;
      width: 150px;
    }

    .search-wrap input::placeholder { color: var(--muted); }

    /* Filter dropdowns */
    .toolbar-filters {
      display: flex;
      align-items: center;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-select-wrap {
      position: relative;
      display: flex;
      align-items: center;
    }

    .filter-select-wrap svg.chevron {
      position: absolute;
      right: 10px;
      width: 12px;
      height: 12px;
      color: var(--muted);
      pointer-events: none;
    }

    .filter-select {
      appearance: none;
      -webkit-appearance: none;
      font-family: var(--font);
      font-size: 12px;
      font-weight: 500;
      color: var(--text);
      background: var(--white);
      border: 1.5px solid var(--border);
      border-radius: 10px;
      padding: 7px 30px 7px 13px;
      cursor: pointer;
      outline: none;
      transition: border-color .15s;
    }

    .filter-select:hover { border-color: #aaa; }
    .filter-select:focus { border-color: #888; }

    /* Add product button */
    .btn-add {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      background: var(--blue);
      color: #fff;
      border: none;
      border-radius: 10px;
      font-family: var(--font);
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: background .15s;
      white-space: nowrap;
      text-decoration: none;
    }

    .btn-add:hover { background: #1d4ed8; }
    .btn-add svg { width: 15px; height: 15px; }

    /* ── Table ──────────────────────────────────────── */
    .prod-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .prod-table thead tr {
      background: #f9fafb;
      border-bottom: 1px solid var(--border);
    }

    .prod-table th {
      padding: 12px 18px;
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--muted);
      white-space: nowrap;
    }

    .prod-table td {
      padding: 13px 18px;
      border-bottom: 1px solid #f5f5f5;
      color: var(--text);
      vertical-align: middle;
    }

    .prod-table tbody tr:last-child td { border-bottom: none; }
    .prod-table tbody tr { transition: background .12s; }
    .prod-table tbody tr:hover { background: #fafafa; }

    /* Product cell */
    .product-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .product-thumb {
      width: 40px; height: 40px;
      border-radius: 8px;
      object-fit: cover;
      background: #f5f5f5;
      flex-shrink: 0;
      border: 1px solid var(--border);
    }

    .product-name {
      font-weight: 500;
      font-size: 13px;
      color: var(--text);
      line-height: 1.35;
    }

    .col-category { color: var(--muted); font-size: 13px; }
    .col-stock    { font-weight: 600; font-size: 13px; }
    .col-price    { font-weight: 600; font-size: 13px; }

    /* Status badges */
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 4px 11px;
      border-radius: 20px;
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
    }

    .status-badge::before {
      content: '';
      width: 6px; height: 6px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .status-active { background: #f0fdf4; color: #16a34a; }
    .status-active::before { background: #16a34a; }

    .status-out { background: #fef2f2; color: #dc2626; }
    .status-out::before { background: #dc2626; }

    /* Row actions */
    .row-actions {
      display: flex;
      align-items: center;
      gap: 6px;
      opacity: 0;
      transition: opacity .15s;
    }

    .prod-table tbody tr:hover .row-actions { opacity: 1; }

    .action-btn {
      width: 28px; height: 28px;
      border-radius: 7px;
      border: 1.5px solid var(--border);
      background: var(--white);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer;
      color: var(--muted);
      transition: background .15s, color .15s, border-color .15s;
    }

    .action-btn:hover { background: #f5f5f5; color: var(--text); }
    .action-btn.delete:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .action-btn svg { width: 13px; height: 13px; }

    /* Empty state */
    .empty-state {
      padding: 60px 20px;
      text-align: center;
      color: var(--muted);
      font-size: 14px;
    }

    /* ── Pagination ─────────────────────────────────── */
    .pagination-wrap {
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
      padding: 20px 0 4px;
    }

    .page-btn {
      min-width: 34px; height: 34px;
      padding: 0 10px;
      border-radius: 9px;
      border: 1.5px solid var(--border);
      background: var(--white);
      font-family: var(--font);
      font-size: 13px;
      font-weight: 500;
      color: var(--muted);
      cursor: pointer;
      transition: border-color .15s, background .15s, color .15s;
      display: flex; align-items: center; justify-content: center;
      text-decoration: none;
    }

    .page-btn:hover { border-color: #aaa; color: var(--text); }
    .page-btn.active { background: var(--text); border-color: var(--text); color: #fff; font-weight: 600; }
    .page-ellipsis { color: var(--muted); font-size: 13px; padding: 0 4px; }

    /* ── Toast ──────────────────────────────────────── */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: #1a1a1a; color: #fff;
      padding: 13px 20px; border-radius: 10px;
      font-size: 13px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.16);
      transform: translateY(70px); opacity: 0;
      transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s;
      z-index: 999; pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }
  </style>
</head>
<body>

<div class="admin-shell">

  <!-- ── Sidebar ──────────────────────────────────── -->
  <aside class="sidebar">

    <a href="admin-dashboard.php" class="sidebar-logo">
      <img src="../imgs/icons/ecommercelogo.png" alt="E-Commerce logo"/>
      <span class="sidebar-logo-text">E-Commerce</span>
    </a>

    <a href="../admin/admindashboard.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <a href="admin-inventory.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 8h14M5 8a2 2 0 010-4h14a2 2 0 010 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
        <path d="M10 12h4"/>
      </svg>
      Inventory
    </a>

    <a href="admin-orders.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      Orders
    </a>

    <a href="admin-products.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="3" width="20" height="14" rx="2"/>
        <path d="M8 21h8M12 17v4"/>
      </svg>
      Product List
    </a>

    <a href="admin-customers.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Customers
    </a>

  </aside>

  <!-- ── Main ─────────────────────────────────────── -->
  <main class="main-content">

    <!-- Page header -->
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

    <!-- Stats -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?= $totalProducts ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Active Products</div>
        <div class="stat-value"><?= $totalActive ?></div>
      </div>
      <div class="stat-card">
        <div class="stat-label">Out of Stock</div>
        <div class="stat-value"><?= $totalOut ?></div>
      </div>
    </div>

    <!-- Table card -->
    <div class="table-card">

      <!-- Toolbar -->
      <form method="GET">
        <div class="toolbar">

          <!-- Search -->
          <div class="search-wrap">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/>
              <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="text" name="search" placeholder="Search products..."
                   value="<?= htmlspecialchars($search) ?>"/>
          </div>

          <!-- Filters + Add button -->
          <div class="toolbar-filters">

            <!-- Stock Status -->
            <div class="filter-select-wrap">
              <select name="stock" class="filter-select" onchange="this.form.submit()">
                <option value="all"        <?= $filterStock === 'all'        ? 'selected' : '' ?>>Stock Status</option>
                <option value="active"     <?= $filterStock === 'active'     ? 'selected' : '' ?>>Active</option>
                <option value="outofstock" <?= $filterStock === 'outofstock' ? 'selected' : '' ?>>Out of Stock</option>
              </select>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </div>

            <!-- Price Range -->
            <div class="filter-select-wrap">
              <select name="price" class="filter-select" onchange="this.form.submit()">
                <option value="all"    <?= $filterPrice === 'all'    ? 'selected' : '' ?>>Price Range</option>
                <option value="0-100"  <?= $filterPrice === '0-100'  ? 'selected' : '' ?>>₱0 – ₱100</option>
                <option value="100-500"<?= $filterPrice === '100-500'? 'selected' : '' ?>>₱100 – ₱500</option>
                <option value="500+"   <?= $filterPrice === '500+'   ? 'selected' : '' ?>>₱500+</option>
              </select>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </div>

            <!-- Category -->
            <div class="filter-select-wrap">
              <select name="category" class="filter-select" onchange="this.form.submit()">
                <option value="all">Category</option>
                <?php foreach ($allCategories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat) ?>" <?= $filterCat === $cat ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </div>

            <!-- Sort By -->
            <div class="filter-select-wrap">
              <select name="sort" class="filter-select" onchange="this.form.submit()">
                <option value="default"   <?= $sortBy === 'default'    ? 'selected' : '' ?>>Sort by</option>
                <option value="name_asc"  <?= $sortBy === 'name_asc'   ? 'selected' : '' ?>>Name A–Z</option>
                <option value="name_desc" <?= $sortBy === 'name_desc'  ? 'selected' : '' ?>>Name Z–A</option>
                <option value="price_asc" <?= $sortBy === 'price_asc'  ? 'selected' : '' ?>>Price Low–High</option>
                <option value="price_desc"<?= $sortBy === 'price_desc' ? 'selected' : '' ?>>Price High–Low</option>
                <option value="stock_asc" <?= $sortBy === 'stock_asc'  ? 'selected' : '' ?>>Stock Low–High</option>
              </select>
              <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
              </svg>
            </div>

            <!-- Hidden page reset -->
            <input type="hidden" name="page" value="1"/>

            <!-- Add Product -->
            <a href="admin-add-product.php" class="btn-add">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"/>
                <line x1="5" y1="12" x2="19" y2="12"/>
              </svg>
              Add Product
            </a>

          </div>
        </div>
      </form>

      <!-- Table -->
      <table class="prod-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Category</th>
            <th>Stock</th>
            <th>Price</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($paged)): ?>
            <tr>
              <td colspan="6">
                <div class="empty-state">No products found.</div>
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($paged as $p):
              [$statusLabel, $statusClass] = productStatus((int)$p['stock']);
            ?>
            <tr>
              <td>
                <div class="product-cell">
                  <img src="<?= htmlspecialchars($p['image']) ?>"
                       alt="<?= htmlspecialchars($p['name']) ?>"
                       class="product-thumb"
                       onerror="this.src='../assets/img/placeholder.png'"/>
                  <span class="product-name"><?= htmlspecialchars($p['name']) ?></span>
                </div>
              </td>
              <td class="col-category"><?= htmlspecialchars($p['category']) ?></td>
              <td class="col-stock"><?= number_format((int)$p['stock']) ?></td>
              <td class="col-price">₱ <?= number_format((float)$p['price'], 2) ?></td>
              <td>
                <span class="status-badge <?= $statusClass ?>">
                  <?= $statusLabel ?>
                </span>
              </td>
              <td>
                <div class="row-actions">
                  <button class="action-btn" title="Edit"
                          onclick="toast('Edit product <?= $p['id'] ?> (coming soon)')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/>
                      <path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                    </svg>
                  </button>
                  <button class="action-btn delete" title="Delete"
                          onclick="confirmDelete(<?= $p['id'] ?>, '<?= addslashes(htmlspecialchars($p['name'])) ?>')">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                      <polyline points="3 6 5 6 21 6"/>
                      <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                      <path d="M10 11v6M14 11v6"/>
                      <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"/>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
      <div class="pagination-wrap">
        <?php
        for ($p = 1; $p <= $totalPages; $p++):
          $qs = qs(['page' => $p]);
          $isActive = $p === $page ? 'active' : '';

          if ($totalPages > 7) {
            $show = ($p === 1 || $p === $totalPages || abs($p - $page) <= 1);
            $showEllipsisBefore = ($p === $page - 2 && $page > 3);
            if ($showEllipsisBefore) echo '<span class="page-ellipsis">…</span>';
            if (!$show) continue;
          }
        ?>
          <a href="?<?= $qs ?>" class="page-btn <?= $isActive ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div><!-- /table-card -->
  </main>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg">Done</span>
</div>

<script>
function toast(msg) {
  const el = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 2600);
}

function confirmDelete(id, name) {
  if (confirm(`Delete "${name}"? This cannot be undone.`)) {
    // Wire to backend:
    // window.location.href = `admin-delete-product.php?id=${id}`;
    toast(`"${name}" deleted. (demo)`);
  }
}
</script>
</body>
</html>