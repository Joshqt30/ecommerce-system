<?php
session_start();

// ── Auth guard ─────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/db.php';
/** @var resource|\PgSql\Connection $conn */

define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

// ═══════════════════════════════════════════════════════
//  REAL DATA QUERIES (PostgreSQL)
// ═══════════════════════════════════════════════════════

$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$search       = isset($_GET['search']) ? trim($_GET['search']) : '';
$page         = isset($_GET['page'])   ? max(1, (int)$_GET['page']) : 1;
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

// ── Build WHERE conditions ─────────────────────────────
$whereClauses = [];
$params = [];
$paramIdx = 1;

if ($filterStatus !== 'all') {
    $whereClauses[] = "o.status = $" . $paramIdx++;
    $params[] = $filterStatus;
}

if (!empty($search)) {
    $whereClauses[] = "(o.id::text ILIKE $" . $paramIdx .
                     " OR EXISTS (SELECT 1 FROM order_items oi WHERE oi.order_id = o.id AND oi.product_name ILIKE $" . $paramIdx .
                     ") OR EXISTS (SELECT 1 FROM users u WHERE u.id = o.user_id AND (u.email ILIKE $" . $paramIdx . " OR u.username ILIKE $" . $paramIdx . ")))";
    $params[] = '%' . $search . '%';
    $paramIdx++;
}

$whereSQL = empty($whereClauses) ? '' : 'WHERE ' . implode(' AND ', $whereClauses);

// ── Count total orders for pagination ──────────────────
$countSQL = "SELECT COUNT(*) FROM orders o $whereSQL";
$countRes = pg_query_params($conn, $countSQL, $params);
$totalOrders = $countRes ? (int)pg_fetch_result($countRes, 0, 0) : 0;
$totalPages = max(1, ceil($totalOrders / $perPage));

// ── Fetch orders with user info and FIRST product only
$orderSQL = '
    SELECT 
        o.id,
        o.total,
        o.status,
        o.created_at,
        o.payment_method,
        o.payment_status,
        u.username,
        u.email,
        (SELECT product_name FROM order_items WHERE order_id = o.id LIMIT 1) AS first_product,
        (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) AS item_count,
        (SELECT image_url FROM order_items WHERE order_id = o.id LIMIT 1) AS image_url
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ' . $whereSQL . '
    ORDER BY o.created_at DESC, o.id DESC
    LIMIT $' . $paramIdx . ' OFFSET $' . ($paramIdx+1);

$params[] = $perPage;
$params[] = $offset;
$orderResult = pg_query_params($conn, $orderSQL, $params);

$orders = [];
if ($orderResult) {
    while ($row = pg_fetch_assoc($orderResult)) {
        $productDisplay = $row['first_product'] ?? '';
        if ($row['item_count'] > 1) {
            $productDisplay .= ' + ' . ($row['item_count'] - 1) . ' more';
        }
        if (empty($productDisplay)) {
            $productDisplay = '—';
        }

        // Use real payment_status (fallback to 'unpaid' for older rows)
        $payStatus = $row['payment_status'] ?? 'unpaid';
        $paymentLabel = $payStatus === 'paid' ? 'Paid' : 'Unpaid';
        $payClass = $payStatus === 'paid' ? 'pay-paid' : 'pay-unpaid';

        // Outside the $orders[] assignment, clean the image path
       // Robust image path cleaning
        $imageRaw = $row['image_url'] ?? '';
        if (!empty($imageRaw)) {
            // Remove full URLs (e.g. http://...)
            if (filter_var($imageRaw, FILTER_VALIDATE_URL)) {
                $imageRaw = parse_url($imageRaw, PHP_URL_PATH);
            }
            // Strip everything up to the last "products/" folder
            $imageRaw = preg_replace('#^.*/products/#', '', $imageRaw);
            // Remove any remaining leading slash
            $imageRaw = ltrim($imageRaw, '/');
        }

        $orders[] = [
            'id'             => $row['id'],
            'order_id'       => 'ORD' . str_pad($row['id'], 6, '0', STR_PAD_LEFT),
            'product'        => $productDisplay,
            'image'          => $imageRaw,          // now it's a clean relative path
            'date'           => date('d-m-Y', strtotime($row['created_at'])),
            'price'          => (float)$row['total'],
            'payment'        => $paymentLabel,
            'pay_class'      => $payClass,
            'status'         => ucfirst($row['status']),
            'customer'       => $row['username'] . ' (' . $row['email'] . ')'
        ];
    }
}

// ── Counts for filter tabs ─────────────────────────────
$totalAll       = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM orders"), 0, 0);
$totalCompleted = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'delivered'"), 0, 0);
$totalPending   = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'pending'"), 0, 0);
$totalCancelled = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'cancelled'"), 0, 0);
$totalShipped = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM orders WHERE status = 'shipped'"), 0, 0);

$paged = $orders;
$adminName = $_SESSION['username'] ?? 'Admin';
$adminEmail = $_SESSION['email'] ?? 'admin@example.com';

// ── Avatar ────────────────────────────────────────────
$avatarRes = pg_query_params($conn,
    "SELECT avatar FROM users WHERE id = $1",
    [$_SESSION['user_id']]
);
$avatarRow = pg_fetch_assoc($avatarRes);
$avatarFile = $avatarRow['avatar'] ?? '';
$avatarUrl = !empty($avatarFile) ? '/ecommerce-system/imgs/avatars/' . htmlspecialchars($avatarFile) : null;

function orderStatusStyle(string $status): array {
    return match(strtolower($status)) {
        'delivered' => ['status-delivered', 'Delivered'],
        'shipped'   => ['status-shipped',   'Shipped'],
        'pending'   => ['status-pending',   'Pending'],
        'cancelled' => ['status-cancelled', 'Cancelled'],
        default     => ['status-pending', $status],
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Orders – Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link rel="stylesheet" href="../assets/css/admin-header.css"/>
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

    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }

    .sidebar {
      background: var(--sidebar-bg); display: flex; flex-direction: column; padding: 20px 12px; gap: 6px;
      border-right: 1px solid #c8c8c8; position: sticky; top: 0; height: 100vh; overflow-y: auto;
    }
    .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 10px 12px 20px; border-bottom: 1px solid #bbb; margin-bottom: 8px; text-decoration: none; }
    .sidebar-logo-icon { width: 36px; height: 36px; background: var(--white); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1.5px solid #ddd; }
    .sidebar-logo-icon svg { width: 20px; height: 20px; }
    .sidebar-logo-text { font-size: 14px; font-weight: 700; color: var(--text); letter-spacing: -.2px; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 12px; font-size: 13px; font-weight: 500; color: var(--text); text-decoration: none; transition: background .15s; border: none; background: transparent; width: 100%; cursor: pointer; }
    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: var(--text); color: #fff; font-weight: 600; }

    .main-content { display: flex; flex-direction: column; padding: 28px 28px 48px; gap: 22px; overflow-y: auto; }
    .page-header { display: flex; align-items: center; justify-content: space-between; }
    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }
    .btn-admin { display: flex; align-items: center; gap: 9px; padding: 9px 18px; background: var(--white); border: 1.5px solid var(--border); border-radius: 12px; font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; }
    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }
    .table-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
    .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); gap: 12px; flex-wrap: wrap; }
    .filter-tabs { display: flex; align-items: center; gap: 4px; }
    .filter-tab { padding: 6px 14px; border-radius: 8px; font-family: var(--font); font-size: 12px; font-weight: 500; border: none; background: transparent; color: var(--muted); cursor: pointer; transition: background .15s, color .15s; text-decoration: none; white-space: nowrap; }
    .filter-tab .tab-count { font-size: 11px; color: var(--muted); margin-left: 3px; }
    .filter-tab:hover { background: #f5f5f5; color: var(--text); }
    .filter-tab.active { background: #f0fdf4; color: #16a34a; font-weight: 600; }
    .filter-tab.active .tab-count { color: #16a34a; }
    .toolbar-right { display: flex; align-items: center; gap: 8px; }
    .search-wrap { display: flex; align-items: center; gap: 8px; padding: 7px 14px; border: 1.5px solid var(--border); border-radius: 10px; background: #fafafa; transition: border-color .18s; }
    .search-wrap:focus-within { border-color: #aaa; background: #fff; }
    .search-wrap svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }
    .search-wrap input { border: none; background: transparent; font-family: var(--font); font-size: 12px; color: var(--text); outline: none; width: 170px; }
    .search-wrap input::placeholder { color: var(--muted); }
    .icon-btn { width: 34px; height: 34px; border-radius: 9px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); transition: background .15s, color .15s; }
    .icon-btn:hover { background: #f5f5f5; color: var(--text); }
    .icon-btn svg { width: 15px; height: 15px; }

    .ord-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .ord-table thead tr { background: #f9fafb; border-bottom: 1px solid var(--border); }
    .ord-table th { padding: 12px 18px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); white-space: nowrap; }
    .ord-table td { padding: 13px 18px; border-bottom: 1px solid #f5f5f5; color: var(--text); vertical-align: middle; }
    .ord-table tbody tr:last-child td { border-bottom: none; }
    .ord-table tbody tr { transition: background .12s; }
    .ord-table tbody tr:hover { background: #fafafa; }
    .col-no { color: var(--muted); font-size: 12px; width: 48px; }
    .col-oid { color: var(--muted); font-family: monospace; font-size: 12px; }
    .product-cell { display: flex; align-items: center; gap: 12px; }
    .product-thumb { width: 40px; height: 40px; border-radius: 8px; object-fit: cover; background: #f5f5f5; flex-shrink: 0; border: 1px solid var(--border); }
    .product-name { font-weight: 500; font-size: 13px; color: var(--text); line-height: 1.35; }
    .extra-items-badge {
    display: inline-block;
    background: #e0f2fe;
    color: #0369a1;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 12px;
    margin-left: 6px;
    vertical-align: middle;
}
    .col-price { font-weight: 600; font-size: 13px; }

    /* Payment toggle button */
    /* ── Interactive badge (payment + status) ─────────── */
    .interactive-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      padding: 6px 12px;
      padding-right: 28px;  /* room for the arrow */
      border-radius: 20px;
      font-family: var(--font);
      font-size: 11px;
      font-weight: 600;
      white-space: nowrap;
      cursor: pointer;
      border: none;
      background: #f9fafb;
      color: var(--text);
      position: relative;
      appearance: none;
      -webkit-appearance: none;
      transition: background .15s, box-shadow .15s;
    }

    .interactive-badge .badge-arrow {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      width: 10px;
      height: 10px;
      pointer-events: none;
      color: var(--muted);
    }

    /* Payment‑specific colours */
    .pay-badge.pay-paid   { background: #f0fdf4; color: #16a34a; }
    .pay-badge.pay-unpaid { background: #fef9ec; color: #b45309; }

    /* Status dropdown wrapper */
    .select-badge-wrap {
      position: relative;
      display: inline-block;
    }
    .select-badge-wrap .badge-arrow {
      position: absolute;
      right: 8px;
      top: 50%;
      transform: translateY(-50%);
      width: 10px;
      height: 10px;
      pointer-events: none;
      color: var(--muted);
    }

    .interactive-badge:hover {
      filter: brightness(0.93);
      box-shadow: 0 1px 4px rgba(0,0,0,0.05);
    }

    /* Status colours – applied by JS, but define for consistency */
    .status-select option {
      background: #fff;
      color: #1a1a1a;
    }

    .row-actions { display: flex; align-items: center; gap: 6px; opacity: 0; transition: opacity .15s; }
    .ord-table tbody tr:hover .row-actions { opacity: 1; }
    .action-btn { width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid var(--border); background: var(--white); display: flex; align-items: center; justify-content: center; cursor: pointer; color: var(--muted); transition: background .15s, color .15s, border-color .15s; }
    .action-btn:hover { background: #f5f5f5; color: var(--text); }
    .action-btn svg { width: 13px; height: 13px; }
    .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); font-size: 14px; }
    .pagination-wrap { display: flex; justify-content: center; align-items: center; gap: 6px; padding: 20px 0 4px; }
    .page-btn { min-width: 34px; height: 34px; padding: 0 10px; border-radius: 9px; border: 1.5px solid var(--border); background: var(--white); font-family: var(--font); font-size: 13px; font-weight: 500; color: var(--muted); cursor: pointer; transition: border-color .15s, background .15s, color .15s; display: flex; align-items: center; justify-content: center; text-decoration: none; }
    .page-btn:hover { border-color: #aaa; color: var(--text); }
    .page-btn.active { background: var(--text); border-color: var(--text); color: #fff; font-weight: 600; }
    .page-ellipsis { color: var(--muted); font-size: 13px; padding: 0 4px; }
    .toast { position: fixed; bottom: 28px; right: 28px; background: #1a1a1a; color: #fff; padding: 13px 20px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 28px rgba(0,0,0,0.16); transform: translateY(70px); opacity: 0; transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s; z-index: 999; pointer-events: none; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }

    /* ── Admin account group (greeting + avatar) ───────── */
.admin-account-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.admin-greeting {
    font-size: 13px;
    color: var(--muted);
    font-weight: 400;
    white-space: nowrap;
}

.admin-greeting strong {
    font-weight: 600;
    color: var(--text);
}

/* Avatar image inside the small button */
.account-avatar-img {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    object-fit: cover;
}

/* Avatar image inside the dropdown profile section */
.dd-avatar img {
    width: 62px;
    height: 62px;
    border-radius: 50%;
    object-fit: cover;
}
  </style>
</head>
<body>
<div class="admin-shell">

 <?php $current_file = basename($_SERVER['PHP_SELF']); ?>
 <aside class="sidebar">
  <a href="../admin/admindashboard.php" class="sidebar-logo">
    <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
    <span class="sidebar-logo-text">E-Commerce</span>
  </a>
  <a href="../admin/admindashboard.php" class="nav-item <?= $current_file == 'admindashboard.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Dashboard
  </a>
  <a href="../admin/admin-orders.php" class="nav-item <?= $current_file == 'admin-orders.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Orders
  </a>
  <a href="../admin/admin-products.php" class="nav-item <?= $current_file == 'admin-products.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg> Products
  </a>
  <a href="../admin/admin-customers.php" class="nav-item <?= $current_file == 'admin-customers.php' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Customers
  </a>
 </aside>

  <main class="main-content">

    <div class="page-header">
      <h1 class="page-title">Order Management</h1>
        <div class="admin-account-group">
            <span class="admin-greeting">Hello, <strong><?= htmlspecialchars($adminName) ?></strong></span>
            <div class="account-wrap" id="accountWrap">
                <button class="account-btn" id="accountBtn" aria-label="Account menu">
                    <?php if ($avatarUrl): ?>
                        <img src="<?= $avatarUrl ?>" alt="Avatar" class="account-avatar-img">
                    <?php else: ?>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                        </svg>
                    <?php endif; ?>
                </button>
                <div class="account-dropdown" id="accountDropdown">
                    <div class="dd-profile">
                        <div class="dd-avatar">
                            <?php if ($avatarUrl): ?>
                                <img src="<?= $avatarUrl ?>" alt="<?= htmlspecialchars($adminName) ?>">
                            <?php else: ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                    <circle cx="12" cy="8" r="4"/>
                                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
                                </svg>
                            <?php endif; ?>
                        </div>
                        <p class="dd-name"><?= htmlspecialchars($adminName) ?></p>
                        <p class="dd-email"><?= htmlspecialchars($adminEmail) ?></p>
                    </div>
                    <div class="dd-divider"></div>
                    <a href="../admin/admin-profile.php" class="dd-item">
                        <span class="dd-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
                            </svg>
                        </span>
                        Settings
                    </a>
                    <div class="dd-divider"></div>
                    <a href="../auth/logout.php" class="dd-item dd-logout">
                        <span class="dd-item-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" y1="12" x2="9" y2="12"/>
                            </svg>
                        </span>
                        Log out
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="table-card">

      <div class="toolbar">
        <div class="filter-tabs">
          <?php
          $tabs = [
            'all'       => ['All Orders',  $totalAll],
            'completed' => ['Completed',   $totalCompleted],
            'shipped'   => ['Shipped',     $totalShipped],
            'pending'   => ['Pending',     $totalPending],
            'cancelled' => ['Cancelled',   $totalCancelled],
          ];
          foreach ($tabs as $key => [$label, $count]):
            $active = ($filterStatus === $key) ? 'active' : '';
            $qs = http_build_query(['status' => $key, 'search' => $search, 'page' => 1]);
          ?>
            <a href="?<?= $qs ?>" class="filter-tab <?= $active ?>"><?= $label ?> <span class="tab-count">(<?= $count ?>)</span></a>
          <?php endforeach; ?>
        </div>

        <div class="toolbar-right">
          <form method="GET" style="display:contents">
            <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
            <div class="search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="search" placeholder="Search order report" value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()"/>
            </div>
          </form>
          <button class="icon-btn" title="Export" onclick="toast('Export coming soon.')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          </button>
        </div>
      </div>

      <table class="ord-table">
        <thead>
          <tr><th class="col-no">No.</th><th>Order Id</th><th>Product</th><th>Date</th><th>Price</th><th>Payment</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
          <?php if (empty($paged)): ?>
            <tr><td colspan="8"><div class="empty-state">No orders found.</div></td></tr>
          <?php else: ?>
            <?php foreach ($paged as $i => $o):
              $rowNo = $offset + $i + 1;
              $currentStatus = strtolower($o['status']);
            ?>
            <tr>
              <td class="col-no"><?= $rowNo ?></td>
              <td class="col-oid">#<?= htmlspecialchars($o['order_id']) ?></td>
                <td>
              <div class="product-cell">
                  <img src="<?= PRODUCT_IMGS_BASE . htmlspecialchars($o['image']) ?>"
                      alt="<?= htmlspecialchars($o['product']) ?>"
                      class="product-thumb"
                      onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22 viewBox=%220 0 40 40%22%3E%3Crect width=%2240%22 height=%2240%22 fill=%22%23f3f4f6%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%239ca3af%22 font-size=%2210%22%3ENo img%3C/text%3E%3C/svg%3E'">
                  <div>
                      <span class="product-name"><?= htmlspecialchars($o['product']) ?></span>
                      <?php if (($o['item_count'] ?? 1) > 1): ?>
                          <span class="extra-items-badge">+<?= $o['item_count'] - 1 ?> more</span>
                      <?php endif; ?>
                  </div>
              </div>
          </td>
              <td><?= htmlspecialchars($o['date']) ?></td>
              <td class="col-price">₱<?= number_format((float)$o['price'], 2) ?></td>
              <td>
              <button class="interactive-badge pay-badge <?= $o['pay_class'] ?>" 
                      onclick="togglePayment(<?= $o['id'] ?>, this)"
                      title="Click to toggle payment">
                  <?= htmlspecialchars($o['payment']) ?>
                  <svg class="badge-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
              </button>
          </td>
          <td>
                <div class="select-badge-wrap">
                    <select class="interactive-badge status-select" 
                            data-order-id="<?= $o['id'] ?>" 
                            onchange="updateOrderStatus(<?= $o['id'] ?>, this.value)">
                      <option value="pending"   <?= $currentStatus === 'pending'   ? 'selected' : '' ?>>Pending</option>
                      <option value="shipped"   <?= $currentStatus === 'shipped'   ? 'selected' : '' ?>>Shipped</option>
                      <option value="delivered" <?= $currentStatus === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                      <option value="cancelled" <?= $currentStatus === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                    <svg class="badge-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </td>
              <td>
                <div class="row-actions">
               <a href="admin-order-detail.php?id=<?= $o['id'] ?>" class="action-btn" title="View order">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            </a>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>

      <?php if ($totalPages > 1): ?>
      <div class="pagination-wrap">
        <?php
        for ($p = 1; $p <= $totalPages; $p++):
          $qs = http_build_query(array_merge($_GET, ['page' => $p]));
          $isActive = $p === $page ? 'active' : '';
          if ($totalPages > 7) {
            $show = ($p === 1 || $p === $totalPages || abs($p - $page) <= 1);
            if ($p === $page - 2 && $page > 3) echo '<span class="page-ellipsis">…</span>';
            if (!$show) continue;
          }
        ?>
          <a href="?<?= $qs ?>" class="page-btn <?= $isActive ?>"><?= $p ?></a>
        <?php endfor; ?>
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg">Done</span>
</div>

<script>
async function togglePayment(orderId, button) {
  const isPaid = button.classList.contains('pay-paid');
  const newStatus = isPaid ? 'unpaid' : 'paid';
  const res = await fetch('admin-update-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${orderId}&action=payment&payment_status=${newStatus}`
  });
  const data = await res.json();
  if (data.success) {
    button.classList.toggle('pay-paid', newStatus === 'paid');
    button.classList.toggle('pay-unpaid', newStatus === 'unpaid');
    button.textContent = newStatus === 'paid' ? 'Paid' : 'Unpaid';
    showToast(`Payment ${newStatus === 'paid' ? 'marked as Paid' : 'marked as Unpaid'}`);
  } else {
    showToast('Error updating payment', true);
  }
}

async function updateOrderStatus(orderId, newStatus) {
  const res = await fetch('admin-update-order.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `id=${orderId}&action=status&status=${newStatus}`
  });
  const data = await res.json();
  if (data.success) {
    showToast('Order status updated');
  } else {
    showToast('Error updating status', true);
  }
}

function showToast(msg, warn = false) {
  const el = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  el.className = 'toast' + (warn ? ' warn' : '');
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 2800);
}

function confirmDelete(id, orderId) {
  if (confirm(`Delete order "${orderId}"?`)) {
    showToast('Delete demo only');
  }
}

document.querySelectorAll('.status-select').forEach(select => {
  const colorMap = {
    'pending':   '#fef9ec',
    'shipped':   '#eff6ff',
    'delivered': '#f0fdf4',
    'cancelled': '#fef2f2'
  };
  select.addEventListener('change', function() {
    this.style.backgroundColor = colorMap[this.value] || '#f9fafb';
  });
  // set initial background
  select.style.backgroundColor = colorMap[select.value] || '#f9fafb';
});

(function() {
    const btn      = document.getElementById('accountBtn');
    const dropdown = document.getElementById('accountDropdown');
    const wrap     = document.getElementById('accountWrap');
    if (!btn) return;

    btn.addEventListener('click', e => {
        e.stopPropagation();
        const isOpen = dropdown.classList.toggle('open');
        btn.classList.toggle('active', isOpen);
    });

    document.addEventListener('click', e => {
        if (!wrap.contains(e.target)) {
            dropdown.classList.remove('open');
            btn.classList.remove('active');
        }
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            dropdown.classList.remove('open');
            btn.classList.remove('active');
        }
    });
})();
</script>
</body>
</html>