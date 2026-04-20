<?php
session_start();

// ── Auth guard — uncomment when backend is ready ──────
// if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../auth/login.php');
//     exit;
// }

include '../config/db.php';

// ═══════════════════════════════════════════════════════
//  BACKEND DATA HOOKS — replace static values below
//  with real DB queries when ready
// ═══════════════════════════════════════════════════════

// -- Total Sales --
// $r = $conn->query("SELECT SUM(total) as total_sales FROM orders WHERE status != 'cancelled'");
// $totalSales = $r->fetch_assoc()['total_sales'] ?? 0;
$totalSales = 125000;

// -- Total Orders --
// $r = $conn->query("SELECT COUNT(*) as cnt FROM orders");
// $totalOrders = $r->fetch_assoc()['cnt'] ?? 0;
$totalOrders = 1240;

// -- Total Products --
// $r = $conn->query("SELECT COUNT(*) as cnt FROM products");
// $totalProducts = $r->fetch_assoc()['cnt'] ?? 0;
$totalProducts = 58;

// -- Low Stock Items (stock <= 5) --
// $r = $conn->query("SELECT COUNT(*) as cnt FROM products WHERE stock <= 5");
// $lowStock = $r->fetch_assoc()['cnt'] ?? 0;
$lowStock = 8;

// -- Chart data: daily sales this week & last week --
// Replace with real queries grouped by day of week
$thisWeekData  = [20000, 22000, 30000, 25409, 35000, 38000, 36000];
$lastWeekData  = [18000, 19000, 27000, 22000, 31000, 34000, 30000];

// -- Stats bar --
// $activeCustomers = $conn->query("SELECT COUNT(DISTINCT user_id) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_row()[0] ?? 0;
$activeCustomers  = '25k';
$repeatCustomers  = '5.6k';
$shopVisitors     = '250k';
$conversionRate   = '5.5%';

// -- Recent Orders --
// $recentOrders = [];
// $r = $conn->query("SELECT o.id, u.username, o.total, o.status, o.created_at FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 5");
// while ($row = $r->fetch_assoc()) $recentOrders[] = $row;
$recentOrders = [
    ['id' => 4829, 'customer' => 'Maria Santos',   'total' => 2990,  'status' => 'delivered',  'date' => '2025-04-18'],
    ['id' => 4830, 'customer' => 'Juan dela Cruz',  'total' => 5480,  'status' => 'processing', 'date' => '2025-04-18'],
    ['id' => 4831, 'customer' => 'Ana Reyes',       'total' => 1250,  'status' => 'delivered',  'date' => '2025-04-17'],
    ['id' => 4832, 'customer' => 'Carlo Mendoza',   'total' => 8900,  'status' => 'cancelled',  'date' => '2025-04-17'],
    ['id' => 4833, 'customer' => 'Liza Dizon',      'total' => 3400,  'status' => 'processing', 'date' => '2025-04-16'],
];

// -- Low stock products --
// $lowStockProducts = [];
// $r = $conn->query("SELECT name, stock, category FROM products WHERE stock <= 5 ORDER BY stock ASC LIMIT 5");
// while ($row = $r->fetch_assoc()) $lowStockProducts[] = $row;
$lowStockProducts = [
    ['name' => 'iPhone 15 Pro',        'stock' => 1, 'category' => 'Smartphones'],
    ['name' => 'Sony WH-1000XM5',      'stock' => 2, 'category' => 'Headsets'],
    ['name' => 'MacBook Air M3',        'stock' => 3, 'category' => 'Laptops'],
    ['name' => 'Apple Watch Series 9', 'stock' => 4, 'category' => 'Watches'],
    ['name' => 'Canon EOS R50',         'stock' => 5, 'category' => 'Cameras'],
];

$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard – E-Commerce</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <!-- Chart.js for the sales graph -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:          #e8e8e8;
      --sidebar-bg:  #d4d4d4;
      --white:       #ffffff;
      --text:        #1a1a1a;
      --text-muted:  #6b7280;
      --border:      #e0e0e0;
      --accent:      #4ade80;      /* green accent matching screenshot */
      --accent-dark: #16a34a;
      --danger:      #ef4444;
      --warning:     #f59e0b;
      --card-radius: 14px;
      --shadow:      0 2px 12px rgba(0,0,0,0.07);
      --font:        'Sora', system-ui, sans-serif;
    }

    html, body {
      height: 100%;
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
    }

    /* ── Layout ─────────────────────────────────────── */
    .admin-shell {
      display: grid;
      grid-template-rows: 1fr;
      grid-template-columns: 180px 1fr;
      height: 100vh;
      overflow: hidden;
    }

    /* ── Sidebar ────────────────────────────────────── */
    .sidebar {
      background: var(--sidebar-bg);
      display: flex;
      flex-direction: column;
      padding: 20px 12px;
      gap: 6px;
      border-right: 1px solid #c8c8c8;
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
      cursor: pointer;
      transition: background .15s, color .15s;
      border: none;
      background: transparent;
      width: 100%;
      text-align: left;
    }

    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; color: var(--text); }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active {
      background: var(--text);
      color: var(--white);
      font-weight: 600;
    }
    .nav-item.active svg { color: var(--white); }

    /* ── Main content ───────────────────────────────── */
    .main-content {
      overflow-y: auto;
      padding: 28px 28px 40px;
      display: flex;
      flex-direction: column;
      gap: 22px;
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

    .header-actions {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn-notif {
      width: 40px;
      height: 40px;
      border-radius: 12px;
      border: 1.5px solid var(--border);
      background: var(--white);
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      position: relative;
      transition: background .15s;
    }

    .btn-notif:hover { background: #f5f5f5; }
    .btn-notif svg { width: 18px; height: 18px; }

    .notif-badge {
      position: absolute;
      top: 7px; right: 8px;
      width: 8px; height: 8px;
      background: var(--danger);
      border-radius: 50%;
      border: 2px solid var(--white);
    }

    .btn-admin {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 9px 16px;
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

    /* ── Stat cards row ─────────────────────────────── */
    .stat-cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }

    .stat-card {
      background: var(--white);
      border-radius: var(--card-radius);
      padding: 20px 22px;
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      gap: 8px;
      position: relative;
      transition: box-shadow .2s, transform .15s;
    }

    .stat-card:hover { box-shadow: 0 6px 24px rgba(0,0,0,0.1); transform: translateY(-1px); }

    .stat-label {
      font-size: 12px;
      font-weight: 500;
      color: var(--text-muted);
      letter-spacing: .02em;
    }

    .stat-value {
      font-size: 28px;
      font-weight: 700;
      letter-spacing: -.6px;
      color: var(--text);
      line-height: 1;
    }

    .stat-menu {
      position: absolute;
      top: 16px; right: 16px;
      background: none;
      border: none;
      cursor: pointer;
      color: #bbb;
      font-size: 18px;
      line-height: 1;
      padding: 4px;
      border-radius: 6px;
      transition: color .15s, background .15s;
    }

    .stat-menu:hover { color: var(--text); background: #f5f5f5; }

    .stat-trend {
      font-size: 11px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
    }

    .trend-up   { color: var(--accent-dark); }
    .trend-down { color: var(--danger); }

    /* ── Chart section ──────────────────────────────── */
    .bottom-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 16px;
    }

    .chart-card {
      background: var(--white);
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 22px 24px;
    }

    .chart-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 18px;
    }

    .chart-title {
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
    }

    .chart-tabs {
      display: flex;
      gap: 6px;
      align-items: center;
    }

    .chart-tab {
      padding: 5px 12px;
      border-radius: 8px;
      font-family: var(--font);
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      border: 1.5px solid var(--border);
      background: transparent;
      color: var(--text-muted);
      transition: background .15s, color .15s, border-color .15s;
    }

    .chart-tab.active {
      background: var(--text);
      color: var(--white);
      border-color: var(--text);
    }

    .chart-tab:not(.active):hover { background: #f5f5f5; }

    .chart-menu {
      background: none;
      border: none;
      cursor: pointer;
      color: #bbb;
      font-size: 18px;
      padding: 2px 6px;
      border-radius: 6px;
    }

    .chart-menu:hover { background: #f5f5f5; color: var(--text); }

    /* Stats bar above chart */
    .chart-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0;
      border-bottom: 1px solid var(--border);
      margin-bottom: 16px;
    }

    .chart-stat-item {
      padding: 10px 0 14px;
      border-bottom: 3px solid transparent;
      cursor: pointer;
      transition: border-color .18s;
    }

    .chart-stat-item:first-child { border-bottom-color: var(--accent-dark); }
    .chart-stat-item:hover { border-bottom-color: var(--accent); }

    .csi-value {
      font-size: 18px;
      font-weight: 700;
      letter-spacing: -.4px;
      color: var(--text);
    }

    .csi-label {
      font-size: 11px;
      color: var(--text-muted);
      margin-top: 2px;
    }

    .chart-wrap { height: 200px; position: relative; }

    /* ── Side panels ────────────────────────────────── */
    .side-panels {
      display: flex;
      flex-direction: column;
      gap: 16px;
    }

    .panel-card {
      background: var(--white);
      border-radius: var(--card-radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 18px 20px;
      flex: 1;
      overflow: hidden;
    }

    .panel-title {
      font-size: 13px;
      font-weight: 600;
      color: var(--text);
      margin-bottom: 14px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .panel-link {
      font-size: 11px;
      color: var(--accent-dark);
      text-decoration: none;
      font-weight: 500;
    }

    .panel-link:hover { text-decoration: underline; }

    /* Recent orders mini table */
    .mini-table { width: 100%; border-collapse: collapse; font-size: 12px; }

    .mini-table th {
      text-align: left;
      padding: 6px 8px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .06em;
      color: var(--text-muted);
      border-bottom: 1px solid var(--border);
    }

    .mini-table td {
      padding: 9px 8px;
      border-bottom: 1px solid #f5f5f5;
      color: #374151;
      white-space: nowrap;
    }

    .mini-table tr:last-child td { border-bottom: none; }
    .mini-table tr:hover td { background: #fafafa; }

    /* Status badges */
    .badge {
      display: inline-flex;
      align-items: center;
      padding: 2px 8px;
      border-radius: 20px;
      font-size: 10px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
    }

    .badge-green    { background: #f0fdf4; color: #16a34a; }
    .badge-blue     { background: #eff6ff; color: #2563eb; }
    .badge-red      { background: #fef2f2; color: #dc2626; }
    .badge-yellow   { background: #fffbeb; color: #d97706; }

    /* Low stock list */
    .stock-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 8px 0;
      border-bottom: 1px solid #f5f5f5;
      font-size: 12px;
    }

    .stock-item:last-child { border-bottom: none; }

    .stock-name {
      font-weight: 500;
      color: var(--text);
      max-width: 150px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .stock-cat { font-size: 10px; color: var(--text-muted); margin-top: 1px; }

    .stock-count {
      font-size: 12px;
      font-weight: 700;
      padding: 3px 9px;
      border-radius: 20px;
      background: #fef2f2;
      color: #dc2626;
    }

    .stock-count.ok { background: #f0fdf4; color: #16a34a; }
  </style>
</head>
<body>

<div class="admin-shell">

  <!-- ── Sidebar ──────────────────────────────────── -->
  <aside class="sidebar">

    <a href="admin-dashboard.php" class="sidebar-logo">
      <div class="sidebar-logo-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round">
          <rect width="24" height="24" rx="6" fill="#14181F"/>
          <path d="M6 12h12M12 6l6 6-6 6" stroke="#fff" stroke-width="2"/>
        </svg>
      </div>
      <span class="sidebar-logo-text">E-Commerce</span>
    </a>

    <a href="admin-dashboard.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1"/>
        <rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/>
        <rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <a href="../admin/inventory.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M5 8h14M5 8a2 2 0 010-4h14a2 2 0 010 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/>
        <path d="M10 12h4"/>
      </svg>
      Inventory
    </a>

    <a href="admin-orders.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      Orders
    </a>

    <a href="admin-products.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="3" width="20" height="14" rx="2"/>
        <path d="M8 21h8M12 17v4"/>
      </svg>
      Product List
    </a>

    <a href="admin-customers.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
           stroke-linecap="round" stroke-linejoin="round">
        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
        <circle cx="9" cy="7" r="4"/>
        <path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
      </svg>
      Customers
    </a>

  </aside>

  <!-- ── Main content ─────────────────────────────── -->
  <main class="main-content">

    <!-- Page header -->
    <div class="page-header">
      <h1 class="page-title">Dashboard</h1>
      <div class="header-actions">
        <button class="btn-notif" title="Notifications">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.73 21a2 2 0 01-3.46 0"/>
          </svg>
          <span class="notif-badge"></span>
        </button>
        <button class="btn-admin">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
               stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="8" r="4"/>
            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
          </svg>
          <?= htmlspecialchars($adminName) ?>
        </button>
      </div>
    </div>

    <!-- Stat cards -->
    <div class="stat-cards">

      <div class="stat-card">
        <span class="stat-label">Total Sales</span>
        <span class="stat-value" id="valTotalSales">
          ₱ <?= number_format($totalSales) ?>
        </span>
        <span class="stat-trend trend-up">↑ 12.4% vs last month</span>
        <button class="stat-menu" title="Options">⋯</button>
      </div>

      <div class="stat-card">
        <span class="stat-label">Total Orders</span>
        <span class="stat-value" id="valTotalOrders">
          <?= number_format($totalOrders) ?>
        </span>
        <span class="stat-trend trend-up">↑ 8.1% vs last month</span>
        <button class="stat-menu" title="Options">⋯</button>
      </div>

      <div class="stat-card">
        <span class="stat-label">Total Products</span>
        <span class="stat-value" id="valTotalProducts">
          <?= number_format($totalProducts) ?>
        </span>
        <span class="stat-trend trend-down">↓ 2 removed this week</span>
        <button class="stat-menu" title="Options">⋯</button>
      </div>

      <div class="stat-card">
        <span class="stat-label">Low Stock Items</span>
        <span class="stat-value" id="valLowStock" style="color:<?= $lowStock > 5 ? '#ef4444' : 'inherit' ?>">
          <?= number_format($lowStock) ?>
        </span>
        <span class="stat-trend trend-down" style="color:#ef4444">⚠ Needs attention</span>
        <button class="stat-menu" title="Options">⋯</button>
      </div>

    </div>

    <!-- Chart + side panels -->
    <div class="bottom-grid">

      <!-- Sales chart -->
      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-title">Sales &amp; Orders Overview</span>
          <div class="chart-tabs">
            <button class="chart-tab active" id="tabThisWeek" onclick="switchChart('thisWeek')">This week</button>
            <button class="chart-tab" id="tabLastWeek" onclick="switchChart('lastWeek')">Last week</button>
            <button class="chart-menu">⋯</button>
          </div>
        </div>

        <!-- Mini stats bar -->
        <div class="chart-stats">
          <div class="chart-stat-item">
            <div class="csi-value"><?= $activeCustomers ?></div>
            <div class="csi-label">Active Customers</div>
          </div>
          <div class="chart-stat-item">
            <div class="csi-value"><?= $repeatCustomers ?></div>
            <div class="csi-label">Repeat Customers</div>
          </div>
          <div class="chart-stat-item">
            <div class="csi-value"><?= $shopVisitors ?></div>
            <div class="csi-label">Shop Visitors</div>
          </div>
          <div class="chart-stat-item">
            <div class="csi-value"><?= $conversionRate ?></div>
            <div class="csi-label">Conversion Rate</div>
          </div>
        </div>

        <div class="chart-wrap">
          <canvas id="salesChart"></canvas>
        </div>
      </div>

      <!-- Side panels -->
      <div class="side-panels">

        <!-- Recent orders -->
        <div class="panel-card">
          <div class="panel-title">
            Recent Orders
            <a href="admin-orders.php" class="panel-link">View all →</a>
          </div>
          <table class="mini-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $order):
                $badgeClass = match($order['status']) {
                  'delivered'  => 'badge-green',
                  'processing' => 'badge-blue',
                  'cancelled'  => 'badge-red',
                  default      => 'badge-yellow'
                };
              ?>
              <tr>
                <td style="color:#9ca3af">#<?= $order['id'] ?></td>
                <td><?= htmlspecialchars($order['customer']) ?></td>
                <td>₱<?= number_format($order['total']) ?></td>
                <td><span class="badge <?= $badgeClass ?>"><?= ucfirst($order['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Low stock -->
        <div class="panel-card">
          <div class="panel-title">
            Low Stock
            <a href="admin-inventory.php" class="panel-link">View all →</a>
          </div>
          <?php foreach ($lowStockProducts as $item): ?>
          <div class="stock-item">
            <div>
              <div class="stock-name"><?= htmlspecialchars($item['name']) ?></div>
              <div class="stock-cat"><?= htmlspecialchars($item['category']) ?></div>
            </div>
            <span class="stock-count <?= $item['stock'] >= 5 ? 'ok' : '' ?>">
              <?= $item['stock'] ?> left
            </span>
          </div>
          <?php endforeach; ?>
        </div>

      </div>
    </div>

  </main>
</div>

<script>
// ── Chart data from PHP ────────────────────────────────
const thisWeekData = <?= json_encode($thisWeekData) ?>;
const lastWeekData = <?= json_encode($lastWeekData) ?>;
const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// ── Build chart ───────────────────────────────────────
const ctx = document.getElementById('salesChart').getContext('2d');

const gradientGreen = ctx.createLinearGradient(0, 0, 0, 200);
gradientGreen.addColorStop(0, 'rgba(74, 222, 128, 0.35)');
gradientGreen.addColorStop(1, 'rgba(74, 222, 128, 0.0)');

const salesChart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: days,
    datasets: [{
      label: 'Sales (₱)',
      data: thisWeekData,
      borderColor: '#4ade80',
      backgroundColor: gradientGreen,
      borderWidth: 2.5,
      pointRadius: 3,
      pointHoverRadius: 6,
      pointBackgroundColor: '#4ade80',
      pointBorderColor: '#fff',
      pointBorderWidth: 2,
      fill: true,
      tension: 0.45,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    interaction: { intersect: false, mode: 'index' },
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#1a1a1a',
        titleColor: '#9ca3af',
        bodyColor: '#fff',
        bodyFont: { family: 'Sora', weight: '600', size: 13 },
        titleFont: { family: 'Sora', size: 11 },
        padding: 12,
        cornerRadius: 10,
        callbacks: {
          label: ctx => ' ₱' + ctx.parsed.y.toLocaleString('en-PH'),
        }
      }
    },
    scales: {
      x: {
        grid: { display: false },
        ticks: { font: { family: 'Sora', size: 11 }, color: '#9ca3af' },
        border: { display: false }
      },
      y: {
        grid: { color: '#f0f0f0' },
        ticks: {
          font: { family: 'Sora', size: 10 },
          color: '#9ca3af',
          callback: v => v >= 1000 ? (v/1000) + 'k' : v
        },
        border: { display: false }
      }
    }
  }
});

// ── Week toggle ───────────────────────────────────────
function switchChart(week) {
  const isThis = week === 'thisWeek';
  salesChart.data.datasets[0].data = isThis ? thisWeekData : lastWeekData;
  salesChart.update('active');

  document.getElementById('tabThisWeek').classList.toggle('active', isThis);
  document.getElementById('tabLastWeek').classList.toggle('active', !isThis);
}

// ── Active nav highlight on click ────────────────────
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', function() {
    // Only highlight — actual navigation handled by href
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
  });
});
</script>
</body>
</html>