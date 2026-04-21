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

$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$page         = isset($_GET['page'])   ? max(1, (int)$_GET['page']) : 1;
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

// -- Real query when backend is ready --
// $where = "WHERE 1=1";
// $params = [];
// $types  = '';
// if ($filterStatus === 'instock')    { $where .= " AND stock > 5";  }
// if ($filterStatus === 'lowstock')   { $where .= " AND stock > 0 AND stock <= 5"; }
// if ($filterStatus === 'outofstock') { $where .= " AND stock = 0"; }
// if (!empty($search)) {
//     $where .= " AND (name LIKE ? OR id LIKE ?)";
//     $like = '%' . $search . '%';
//     $params = [$like, $like]; $types = 'ss';
// }
// $countRes = $conn->query("SELECT COUNT(*) FROM products $where");
// $total = $countRes->fetch_row()[0];
// $stmt = $conn->prepare("SELECT * FROM products $where ORDER BY id ASC LIMIT ? OFFSET ?");
// ... bind and execute

// -- Static placeholder data --
$allProducts = [
    ['id' => 1, 'product_id' => 'ORD0001', 'name' => 'Wireless Bluetooth Headphones', 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=60&q=75', 'stock' => 90,  'category' => 'Headsets'],
    ['id' => 2, 'product_id' => 'ORD0001', 'name' => "Men's T-Shirt",                 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=60&q=75', 'stock' => 20,  'category' => 'Clothing'],
    ['id' => 3, 'product_id' => 'ORD0001', 'name' => "Men's Leather Wallet",          'image' => 'https://images.unsplash.com/photo-1627123424574-724758594e93?w=60&q=75', 'stock' => 112, 'category' => 'Accessories'],
    ['id' => 4, 'product_id' => 'ORD0001', 'name' => 'Memory Foam Pillow',            'image' => 'https://images.unsplash.com/photo-1631049307264-da0ec9d70304?w=60&q=75', 'stock' => 12,  'category' => 'Home'],
    ['id' => 5, 'product_id' => 'ORD0001', 'name' => 'Adjustable Dumbbells',          'image' => 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=60&q=75', 'stock' => 9,   'category' => 'Sports'],
    ['id' => 6, 'product_id' => 'ORD0001', 'name' => 'Coffee Maker',                  'image' => 'https://images.unsplash.com/photo-1520970014086-2208d157c9e2?w=60&q=75', 'stock' => 0,   'category' => 'Kitchen'],
    ['id' => 7, 'product_id' => 'ORD0001', 'name' => 'Casual Baseball Cap',           'image' => 'https://images.unsplash.com/photo-1588850561407-ed78c282e89b?w=60&q=75', 'stock' => 86,  'category' => 'Clothing'],
    ['id' => 8, 'product_id' => 'ORD0001', 'name' => 'MacBook Air M3',                'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=60&q=75', 'stock' => 3,   'category' => 'Laptops'],
    ['id' => 9, 'product_id' => 'ORD0001', 'name' => 'Sony WH-1000XM5',              'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=60&q=75', 'stock' => 2,   'category' => 'Headsets'],
    ['id'=>10,  'product_id' => 'ORD0001', 'name' => 'Samsung 4K Monitor',            'image' => 'https://images.unsplash.com/photo-1593784991095-a205069470b6?w=60&q=75', 'stock' => 44,  'category' => 'Monitors'],
];

// Filter client-side for static demo
$filtered = array_filter($allProducts, function($p) use ($filterStatus, $search) {
    if ($filterStatus === 'instock'    && $p['stock'] <= 5)  return false;
    if ($filterStatus === 'lowstock'   && ($p['stock'] === 0 || $p['stock'] > 5)) return false;
    if ($filterStatus === 'outofstock' && $p['stock'] !== 0) return false;
    if ($search && stripos($p['name'], $search) === false)   return false;
    return true;
});
$filtered = array_values($filtered);

$totalAll      = count($allProducts);
$totalInStock  = count(array_filter($allProducts, fn($p) => $p['stock'] > 5));
$totalLow      = count(array_filter($allProducts, fn($p) => $p['stock'] > 0 && $p['stock'] <= 5));
$totalOut      = count(array_filter($allProducts, fn($p) => $p['stock'] === 0));

$total      = count($filtered);
$totalPages = max(1, ceil($total / $perPage));
$paged      = array_slice($filtered, $offset, $perPage);

$adminName = $_SESSION['admin_name'] ?? 'Admin';

function stockStatus(int $stock): array {
    if ($stock === 0)       return ['Out of Stock', 'status-out'];
    if ($stock <= 5)        return ['Low Stock',    'status-low'];
    return                         ['In Stock',     'status-in'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inventory – Admin</title>
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
      gap: 12px;
      flex-wrap: wrap;
    }

    /* Filter tabs */
    .filter-tabs {
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .filter-tab {
      padding: 6px 14px;
      border-radius: 8px;
      font-family: var(--font);
      font-size: 12px;
      font-weight: 500;
      border: none;
      background: transparent;
      color: var(--muted);
      cursor: pointer;
      transition: background .15s, color .15s;
      text-decoration: none;
      white-space: nowrap;
    }

    .filter-tab .tab-count {
      font-size: 11px;
      color: var(--muted);
      margin-left: 3px;
    }

    .filter-tab:hover { background: #f5f5f5; color: var(--text); }

    .filter-tab.active {
      background: #f0fdf4;
      color: #16a34a;
      font-weight: 600;
    }

    .filter-tab.active .tab-count { color: #16a34a; }

    /* Search + actions */
    .toolbar-right {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .search-wrap {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 7px 14px;
      border: 1.5px solid var(--border);
      border-radius: 10px;
      background: #fafafa;
      transition: border-color .18s;
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
      width: 160px;
    }

    .search-wrap input::placeholder { color: var(--muted); }

    .icon-btn {
      width: 34px;
      height: 34px;
      border-radius: 9px;
      border: 1.5px solid var(--border);
      background: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--muted);
      transition: background .15s, color .15s;
    }

    .icon-btn:hover { background: #f5f5f5; color: var(--text); }
    .icon-btn svg { width: 15px; height: 15px; }

    /* ── Table ──────────────────────────────────────── */
    .inv-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 13px;
    }

    .inv-table thead tr {
      background: #f9fafb;
      border-bottom: 1px solid var(--border);
    }

    .inv-table th {
      padding: 12px 18px;
      text-align: left;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--muted);
      white-space: nowrap;
    }

    .inv-table td {
      padding: 13px 18px;
      border-bottom: 1px solid #f5f5f5;
      color: var(--text);
      vertical-align: middle;
    }

    .inv-table tbody tr:last-child td { border-bottom: none; }

    .inv-table tbody tr { transition: background .12s; }
    .inv-table tbody tr:hover { background: #fafafa; }

    /* Row number */
    .col-no { color: var(--muted); font-size: 12px; width: 48px; }

    /* Product ID */
    .col-pid { color: var(--muted); font-family: monospace; font-size: 12px; }

    /* Product name with image */
    .product-cell {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .product-thumb {
      width: 40px;
      height: 40px;
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

    /* Stock number */
    .col-stock { font-weight: 600; font-size: 13px; }

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
      width: 6px;
      height: 6px;
      border-radius: 50%;
      flex-shrink: 0;
    }

    .status-in  { background: #f0fdf4; color: #16a34a; }
    .status-in::before  { background: #16a34a; }

    .status-low { background: #fffbeb; color: #d97706; }
    .status-low::before { background: #d97706; }

    .status-out { background: #fef2f2; color: #dc2626; }
    .status-out::before { background: #dc2626; }

    /* Action buttons in row */
    .row-actions {
      display: flex;
      align-items: center;
      gap: 6px;
      opacity: 0;
      transition: opacity .15s;
    }

    .inv-table tbody tr:hover .row-actions { opacity: 1; }

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
      min-width: 34px;
      height: 34px;
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
      display: flex;
      align-items: center;
      justify-content: center;
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

    <!-- Page header -->
    <div class="page-header">
      <h1 class="page-title">Inventory</h1>
      <button class="btn-admin">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
        <?= htmlspecialchars($adminName) ?>
      </button>
    </div>

    <!-- Table card -->
    <div class="table-card">

      <!-- Toolbar -->
      <div class="toolbar">

        <!-- Filter tabs -->
        <div class="filter-tabs">
          <?php
          $tabs = [
            'all'        => ['label' => 'All Products',  'count' => $totalAll],
            'instock'    => ['label' => 'In Stock',       'count' => $totalInStock],
            'lowstock'   => ['label' => 'Low Stock',      'count' => $totalLow],
            'outofstock' => ['label' => 'Out of Stock',   'count' => $totalOut],
          ];
          foreach ($tabs as $key => $tab):
            $active = ($filterStatus === $key) ? 'active' : '';
            $qs = http_build_query(['status' => $key, 'search' => $search, 'page' => 1]);
          ?>
            <a href="?<?= $qs ?>" class="filter-tab <?= $active ?>">
              <?= $tab['label'] ?>
              <span class="tab-count">(<?= $tab['count'] ?>)</span>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Search + icons -->
        <div class="toolbar-right">
          <form method="GET" style="display:contents">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
              </svg>
              <input type="text" name="search" placeholder="Search order report"
                     value="<?= htmlspecialchars($search) ?>"
                     onchange="this.form.submit()"/>
            </div>
          </form>

          <button class="icon-btn" title="Export" onclick="toast('Export coming soon.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
              <polyline points="7 10 12 15 17 10"/>
              <line x1="12" y1="15" x2="12" y2="3"/>
            </svg>
          </button>

          <button class="icon-btn" title="More options">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="12" cy="5"  r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Table -->
      <table class="inv-table">
        <thead>
          <tr>
            <th class="col-no">No.</th>
            <th>Product Id</th>
            <th>Product Name</th>
            <th>Stocks</th>
            <th>Status</th>
            <th></th>
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
            <?php foreach ($paged as $i => $p):
              [$statusLabel, $statusClass] = stockStatus((int)$p['stock']);
              $rowNo = $offset + $i + 1;
            ?>
            <tr>
              <td class="col-no"><?= $rowNo ?></td>
              <td class="col-pid">#<?= htmlspecialchars($p['product_id']) ?></td>
              <td>
                <div class="product-cell">
                  <img src="<?= htmlspecialchars($p['image']) ?>"
                       alt="<?= htmlspecialchars($p['name']) ?>"
                       class="product-thumb"
                       onerror="this.src='../assets/img/placeholder.png'"/>
                  <span class="product-name"><?= htmlspecialchars($p['name']) ?></span>
                </div>
              </td>
              <td class="col-stock"><?= number_format((int)$p['stock']) ?></td>
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
        // Show up to 5 page buttons + ellipsis + last
        for ($p = 1; $p <= $totalPages; $p++):
          $qs = http_build_query(array_merge($_GET, ['page' => $p]));
          $isActive = $p === $page ? 'active' : '';

          if ($totalPages > 7) {
            // Show first, last, current ±1, and ellipsis
            $show = ($p === 1 || $p === $totalPages || abs($p - $page) <= 1);
            $showEllipsisBefore = ($p === $page - 2 && $page > 3);
            $showEllipsisAfter  = ($p === $page + 2 && $page < $totalPages - 2);

            if ($showEllipsisBefore) { echo '<span class="page-ellipsis">…</span>'; }
            if (!$show) continue;
            if ($showEllipsisAfter)  { /* handled next iter */ }
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