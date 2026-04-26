<?php
session_start();

// ── Auth guard ────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/db.php';

// ═══════════════════════════════════════════════════════
//  ALL QUERIES — PostgreSQL (pg_* functions)
//  $conn is your pg_connect() resource from db.php
// ═══════════════════════════════════════════════════════

// ── Total Sales (excludes cancelled) ─────────────────
$r = pg_query($conn,
    "SELECT COALESCE(SUM(total), 0) AS total_sales
     FROM orders
     WHERE status != 'cancelled'"
);
$totalSales = $r ? (float)pg_fetch_result($r, 0, 'total_sales') : 0;

// ── Sales last month (for trend %) ───────────────────
$r = pg_query($conn,
    "SELECT COALESCE(SUM(total), 0) AS last_sales
     FROM orders
     WHERE status != 'cancelled'
       AND created_at >= DATE_TRUNC('month', NOW() - INTERVAL '1 month')
       AND created_at <  DATE_TRUNC('month', NOW())"
);
$lastMonthSales = $r ? (float)pg_fetch_result($r, 0, 'last_sales') : 0;
$salesTrend = $lastMonthSales > 0
    ? round((($totalSales - $lastMonthSales) / $lastMonthSales) * 100, 1)
    : 0;

// ── Total Orders ──────────────────────────────────────
$r = pg_query($conn, "SELECT COUNT(*) AS cnt FROM orders");
$totalOrders = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;

// Orders last month (for trend %)
$r = pg_query($conn,
    "SELECT COUNT(*) AS cnt FROM orders
     WHERE created_at >= DATE_TRUNC('month', NOW() - INTERVAL '1 month')
       AND created_at <  DATE_TRUNC('month', NOW())"
);
$lastMonthOrders = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;
$ordersTrend = $lastMonthOrders > 0
    ? round((($totalOrders - $lastMonthOrders) / $lastMonthOrders) * 100, 1)
    : 0;

// ── Total Products ────────────────────────────────────
$r = pg_query($conn, "SELECT COUNT(*) AS cnt FROM products");
$totalProducts = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;

// ── Low Stock (stock <= 5) ────────────────────────────
$r = pg_query($conn, "SELECT COUNT(*) AS cnt FROM products WHERE stock <= 5");
$lowStockCount = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;

// ── Chart: // This month daily sales
$r = pg_query($conn,
    "SELECT
         EXTRACT(DAY FROM created_at)::int AS day,
         COALESCE(SUM(total), 0)           AS daily_total
     FROM orders
     WHERE status != 'cancelled'
       AND created_at >= DATE_TRUNC('month', NOW())
       AND created_at <  DATE_TRUNC('month', NOW()) + INTERVAL '1 month'
     GROUP BY day
     ORDER BY day"
);
$thisMonthRaw = array_fill(1, 31, 0);
if ($r) {
    while ($row = pg_fetch_assoc($r)) {
        $thisMonthRaw[(int)$row['day']] = (float)$row['daily_total'];
    }
}

  // ── Chart: daily sales last month ──────────────────────
$r = pg_query($conn,
    "SELECT
         EXTRACT(DAY FROM created_at)::int AS day,
         COALESCE(SUM(total), 0)           AS daily_total
     FROM orders
     WHERE status != 'cancelled'
       AND created_at >= DATE_TRUNC('month', NOW()) - INTERVAL '1 month'
       AND created_at <  DATE_TRUNC('month', NOW())
     GROUP BY day
     ORDER BY day"
);
$lastMonthRaw = array_fill(1, 31, 0);
if ($r) {
    while ($row = pg_fetch_assoc($r)) {
        $lastMonthRaw[(int)$row['day']] = (float)$row['daily_total'];
    }
}


// ── Stats bar ─────────────────────────────────────────
// Active customers this week
$r = pg_query($conn,
    "SELECT COUNT(DISTINCT user_id) AS cnt FROM orders
     WHERE created_at >= DATE_TRUNC('week', NOW())"
);
$activeCustomers = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;

// Repeat customers (placed > 1 order ever)
$r = pg_query($conn,
    "SELECT COUNT(*) AS cnt FROM (
         SELECT user_id FROM orders GROUP BY user_id HAVING COUNT(*) > 1
     ) sub"
);
$repeatCustomers = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;

// Total distinct customers
$r = pg_query($conn,
    "SELECT COUNT(DISTINCT user_id) AS cnt FROM orders"
);
$totalCustomers = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;

// Conversion rate = orders this week / active customers this week
$r = pg_query($conn,
    "SELECT COUNT(*) AS cnt FROM orders
     WHERE created_at >= DATE_TRUNC('week', NOW())"
);
$ordersThisWeek = $r ? (int)pg_fetch_result($r, 0, 'cnt') : 0;
$convRate = $activeCustomers > 0
    ? round(($ordersThisWeek / $activeCustomers) * 100, 1) . '%'
    : '0%';

// ── Recent orders (last 5) ────────────────────────────
$r = pg_query($conn,
    "SELECT o.id, u.username AS customer, o.total, o.status,
            TO_CHAR(o.created_at, 'Mon DD, YYYY') AS date
     FROM orders o
     LEFT JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 5"
);
$recentOrders = [];
if ($r) {
    while ($row = pg_fetch_assoc($r)) $recentOrders[] = $row;
}

// ── Low stock products (5 most critical) ─────────────
$r = pg_query($conn,
    "SELECT name, stock, category
     FROM products
     WHERE stock <= 5
     ORDER BY stock ASC
     LIMIT 5"
);
$lowStockProducts = [];
if ($r) {
    while ($row = pg_fetch_assoc($r)) $lowStockProducts[] = $row;
}

$adminName = $_SESSION['username'] ?? 'Admin';

// ── Helper: format large numbers ─────────────────────
function shortNum(int|float $n): string {
    if ($n >= 1000000) return round($n / 1000000, 1) . 'M';
    if ($n >= 1000)    return round($n / 1000, 1) . 'k';
    return (string)$n;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:         #e8e8e8;
      --sidebar-bg: #d4d4d4;
      --white:      #ffffff;
      --text:       #1a1a1a;
      --muted:      #6b7280;
      --border:     #e0e0e0;
      --green:      #4ade80;
      --green-dark: #16a34a;
      --danger:     #ef4444;
      --radius:     14px;
      --shadow:     0 2px 12px rgba(0,0,0,0.07);
      --font:       'Sora', system-ui, sans-serif;
    }

    html, body {
      height: 100%;
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      -webkit-font-smoothing: antialiased;
    }

    .admin-shell {
      display: grid;
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

    /* ── Stat cards ─────────────────────────────────── */
    .stat-cards {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
    }

    .stat-card {
      background: var(--white);
      border-radius: var(--radius);
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
      color: var(--muted);
    }

    .stat-value {
      font-size: 26px;
      font-weight: 700;
      letter-spacing: -.5px;
      color: var(--text);
      line-height: 1;
    }

    .stat-menu {
      position: absolute;
      top: 14px; right: 14px;
      background: none; border: none;
      cursor: pointer; color: #bbb;
      font-size: 18px; padding: 4px;
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

    .trend-up   { color: var(--green-dark); }
    .trend-down { color: var(--danger); }
    .trend-warn { color: #d97706; }

    /* ── Bottom grid ────────────────────────────────── */
    .bottom-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 16px;
    }

    /* ── Chart card ─────────────────────────────────── */
    .chart-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 22px 24px;
    }

    .chart-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
    }

    .chart-title { font-size: 14px; font-weight: 600; }

    .chart-tabs { display: flex; gap: 6px; align-items: center; }

    .chart-tab {
      padding: 5px 12px;
      border-radius: 8px;
      font-family: var(--font);
      font-size: 11px;
      font-weight: 600;
      cursor: pointer;
      border: 1.5px solid var(--border);
      background: transparent;
      color: var(--muted);
      transition: background .15s, color .15s, border-color .15s;
    }

    .chart-tab.active { background: var(--text); color: #fff; border-color: var(--text); }
    .chart-tab:not(.active):hover { background: #f5f5f5; }

    .chart-menu {
      background: none; border: none;
      cursor: pointer; color: #bbb;
      font-size: 18px; padding: 2px 6px;
      border-radius: 6px;
    }

    .chart-menu:hover { background: #f5f5f5; color: var(--text); }

    /* Stats bar */
    .chart-stats {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      border-bottom: 1px solid var(--border);
      margin-bottom: 16px;
    }

  .chart-stat-item {
  padding: 10px 0 14px;
  border-bottom: 3px solid transparent;
  }

  .icon-btn {
  padding: 5px 10px;          /* same vertical padding as .chart-tab */
  display: flex;
  align-items: center;
  justify-content: center;
}
  
    .csi-value { font-size: 17px; font-weight: 700; letter-spacing: -.4px; }
    .csi-label { font-size: 11px; color: var(--muted); margin-top: 2px; }

    .chart-wrap { height: 200px; position: relative; }

    /* ── Side panels ────────────────────────────────── */
    .side-panels { display: flex; flex-direction: column; gap: 16px; }

    .panel-card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 18px 20px;
      flex: 1;
      overflow: hidden;
    }

    .panel-title {
      font-size: 13px; font-weight: 600;
      margin-bottom: 14px;
      display: flex; align-items: center; justify-content: space-between;
    }

    .panel-link { font-size: 11px; color: var(--green-dark); text-decoration: none; font-weight: 500; }
    .panel-link:hover { text-decoration: underline; }

    .mini-table { width: 100%; border-collapse: collapse; font-size: 12px; }

    .mini-table th {
      text-align: left; padding: 6px 8px;
      font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .06em;
      color: var(--muted); border-bottom: 1px solid var(--border);
    }

    .mini-table td {
      padding: 9px 8px;
      border-bottom: 1px solid #f5f5f5;
      color: #374151; white-space: nowrap;
    }

    .mini-table tr:last-child td { border-bottom: none; }
    .mini-table tr:hover td { background: #fafafa; }

    .badge {
      display: inline-flex; align-items: center;
      padding: 2px 8px; border-radius: 20px;
      font-size: 10px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .04em;
    }

    .badge-green  { background: #f0fdf4; color: #16a34a; }
    .badge-blue   { background: #eff6ff; color: #2563eb; }
    .badge-red    { background: #fef2f2; color: #dc2626; }
    .badge-yellow { background: #fffbeb; color: #d97706; }

    .stock-item {
      display: flex; align-items: center;
      justify-content: space-between;
      padding: 8px 0; border-bottom: 1px solid #f5f5f5;
      font-size: 12px;
    }

    .stock-item:last-child { border-bottom: none; }
    .stock-name { font-weight: 500; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .stock-cat  { font-size: 10px; color: var(--muted); margin-top: 1px; }

    .stock-pill {
      font-size: 11px; font-weight: 700;
      padding: 3px 9px; border-radius: 20px;
    }

    .pill-red  { background: #fef2f2; color: #dc2626; }
    .pill-warn { background: #fffbeb; color: #d97706; }
    .pill-ok   { background: #f0fdf4; color: #16a34a; }

    /* Empty state */
    .empty-row td {
      text-align: center;
      padding: 28px;
      color: var(--muted);
      font-size: 13px;
    }
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
      <h1 class="page-title">Dashboard</h1>
      <button class="btn-admin">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/>
          <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
        <?= htmlspecialchars($adminName) ?>
      </button>
    </div>

    <!-- Stat cards -->
    <div class="stat-cards">

      <div class="stat-card">
        <span class="stat-label">Total Sales</span>
        <span class="stat-value">₱ <?= number_format($totalSales) ?></span>
        <span class="stat-trend <?= $salesTrend >= 0 ? 'trend-up' : 'trend-down' ?>">
          <?= $salesTrend >= 0 ? '↑' : '↓' ?> <?= abs($salesTrend) ?>% vs last month
        </span>
      </div>

      <div class="stat-card">
        <span class="stat-label">Total Orders</span>
        <span class="stat-value"><?= number_format($totalOrders) ?></span>
        <span class="stat-trend <?= $ordersTrend >= 0 ? 'trend-up' : 'trend-down' ?>">
          <?= $ordersTrend >= 0 ? '↑' : '↓' ?> <?= abs($ordersTrend) ?>% vs last month
        </span>
      </div>

      <div class="stat-card">
        <span class="stat-label">Total Products</span>
        <span class="stat-value"><?= number_format($totalProducts) ?></span>
        <span class="stat-trend trend-up">↑ Active in store</span>
      </div>

      <div class="stat-card">
        <span class="stat-label">Low Stock Items</span>
        <span class="stat-value" style="color:<?= $lowStockCount > 0 ? '#ef4444' : 'inherit' ?>">
          <?= number_format($lowStockCount) ?>
        </span>
        <span class="stat-trend <?= $lowStockCount > 0 ? 'trend-down' : 'trend-up' ?>">
          <?= $lowStockCount > 0 ? '⚠ Needs attention' : '✓ Stock healthy' ?>
        </span>
      </div>

    </div>

    <!-- Chart + panels -->
    <div class="bottom-grid">

      <div class="chart-card">
        <div class="chart-header">
          <span class="chart-title">Sales &amp; Orders Overview</span>
          <div class="chart-tabs">
            <button class="chart-tab active" id="tabThis" onclick="switchChart('this')">This month</button>
            <button class="chart-tab"        id="tabLast" onclick="switchChart('last')">Last month</button>
        <button class="chart-tab icon-btn" onclick="exportCSV()" title="Export data as CSV">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
            <polyline points="7 10 12 15 17 10"/>
            <line x1="12" y1="15" x2="12" y2="3"/>
          </svg>
        </button>          
      </div>
        </div>

        <div class="chart-stats">
          <div class="chart-stat-item active-stat">
            <div class="csi-value"><?= shortNum($activeCustomers) ?></div>
            <div class="csi-label">Active Customers</div>
          </div>
          <div class="chart-stat-item">
            <div class="csi-value"><?= shortNum($repeatCustomers) ?></div>
            <div class="csi-label">Repeat Customers</div>
          </div>
          <div class="chart-stat-item">
            <div class="csi-value"><?= shortNum($totalCustomers) ?></div>
            <div class="csi-label">Total Customers</div>
          </div>
          <div class="chart-stat-item">
            <div class="csi-value"><?= $convRate ?></div>
            <div class="csi-label">Conversion Rate</div>
          </div>
        </div>

        <div class="chart-wrap">
          <canvas id="salesChart"></canvas>
        </div>
      </div>

      <div class="side-panels">

        <!-- Recent orders -->
        <div class="panel-card">
          <div class="panel-title">
            Recent Orders
            <a href="orders.php" class="panel-link">View all →</a>
          </div>
          <?php if (empty($recentOrders)): ?>
            <table class="mini-table"><tbody>
              <tr class="empty-row"><td colspan="4">No orders yet.</td></tr>
            </tbody></table>
          <?php else: ?>
          <table class="mini-table">
            <thead>
              <tr>
                <th>#</th><th>Customer</th><th>Total</th><th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o):
                $bc = match($o['status']) {
                  'delivered'  => 'badge-green',
                  'processing' => 'badge-blue',
                  'cancelled'  => 'badge-red',
                  default      => 'badge-yellow'
                };
              ?>
              <tr>
                <td style="color:#9ca3af">#<?= $o['id'] ?></td>
                <td><?= htmlspecialchars($o['customer'] ?? 'Guest') ?></td>
                <td>₱<?= number_format((float)$o['total']) ?></td>
                <td><span class="badge <?= $bc ?>"><?= ucfirst($o['status']) ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php endif; ?>
        </div>

        <!-- Low stock -->
        <div class="panel-card">
          <div class="panel-title">
            Low Stock
            <a href="inventory.php?status=lowstock" class="panel-link">View all →</a>
          </div>
          <?php if (empty($lowStockProducts)): ?>
            <p style="font-size:12px;color:var(--muted);padding:12px 0">All products well stocked ✓</p>
          <?php else: ?>
            <?php foreach ($lowStockProducts as $p):
              $pillClass = $p['stock'] == 0 ? 'pill-red' : ($p['stock'] <= 2 ? 'pill-warn' : 'pill-ok');
            ?>
            <div class="stock-item">
              <div>
                <div class="stock-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="stock-cat"><?= htmlspecialchars($p['category']) ?></div>
              </div>
              <span class="stock-pill <?= $pillClass ?>">
                <?= $p['stock'] ?> left
              </span>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>

  </main>
</div>

<script>
const labels = Array.from({length: 31}, (_, i) => i + 1);
const thisWeekData = <?= json_encode(array_values($thisMonthRaw)) ?>;
const lastWeekData = <?= json_encode(array_values($lastMonthRaw)) ?>;
// and use `labels` instead of `days`

const ctx = document.getElementById('salesChart').getContext('2d');
const grad = ctx.createLinearGradient(0, 0, 0, 200);
grad.addColorStop(0, 'rgba(74,222,128,0.35)');
grad.addColorStop(1, 'rgba(74,222,128,0.0)');

const chart = new Chart(ctx, {
  type: 'line',
  data: {
    labels: labels,
    datasets: [{
      data: thisWeekData,
      borderColor: '#4ade80',
      backgroundColor: grad,
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
        callbacks: { label: c => ' ₱' + c.parsed.y.toLocaleString('en-PH') }
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
          callback: v => v >= 1000 ? (v/1000)+'k' : v
        },
        border: { display: false }
      }
    }
  }
});

function switchChart(week) {
  chart.data.datasets[0].data = week === 'this' ? thisWeekData : lastWeekData;
  chart.update('active');
  document.getElementById('tabThis').classList.toggle('active', week === 'this');
  document.getElementById('tabLast').classList.toggle('active', week === 'last');
}

function exportCSV() {
  // Determine which dataset is currently shown
  const currentData = chart.data.datasets[0].data;
  const currentLabels = chart.data.labels;

  let csv = 'Day,Sales\n';
  currentLabels.forEach((day, idx) => {
    csv += `${day},${currentData[idx] || 0}\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `sales_${document.getElementById('tabThis').classList.contains('active') ? 'this' : 'last'}_month.csv`;
  a.click();
  URL.revokeObjectURL(url);
}

</script>
</body>
</html>