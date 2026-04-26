<?php
session_start();
include '../config/db.php';          // ← your PostgreSQL $conn

// ── Auth guard ────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// ═══════════════════════════════════════════════════════
//  HANDLE AJAX ACTIONS (update / get details)
// ═══════════════════════════════════════════════════════
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // -- Update customer (also saves admin_notes) --
    if ($action === 'update') {
        $id      = intval($_POST['id']);
        $name    = trim($_POST['name']    ?? '');
        $email   = trim($_POST['email']   ?? '');
        $phone   = trim($_POST['phone']   ?? '');
        $address = trim($_POST['address'] ?? '');
        $notes   = trim($_POST['notes']   ?? '');

        $res = pg_query_params($conn,
            "UPDATE users SET
                username   = $1,
                email      = $2,
                phone      = $3,
                address    = $4,
                admin_notes = $5
             WHERE id = $6",
            [$name, $email, $phone, $address, $notes, $id]
        );
        echo json_encode(['success' => (bool)$res]);
        exit;
    }

    // -- Get single customer details (for the side panel) --
    if ($action === 'get' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $res = pg_query_params($conn,
            "SELECT u.id, u.username AS name, u.email, u.phone, u.address,
                    u.created_at, u.admin_notes,
                    COUNT(o.id) AS order_count
             FROM users u
             LEFT JOIN orders o ON o.user_id = u.id
             WHERE u.id = $1
             GROUP BY u.id",
            [$id]
        );
        $customer = pg_fetch_assoc($res);
        echo json_encode($customer ?: ['error' => 'Not found']);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// ═══════════════════════════════════════════════════════
//  LIST QUERY (real data)
// ═══════════════════════════════════════════════════════
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Build WHERE
$where = "WHERE (u.role IS NULL OR u.role != 'admin')";
$params = [];
$pi = 1;

if (!empty($search)) {
    $where .= " AND (u.username ILIKE \${$pi} OR u.email ILIKE \${$pi} OR CAST(u.id AS TEXT) ILIKE \${$pi})";
    $params[] = '%' . $search . '%';
    $pi++;
}

// Total count
$countSQL = "SELECT COUNT(*) FROM users u $where";
$countRes = pg_query_params($conn, $countSQL, $params);
$total = $countRes ? (int)pg_fetch_result($countRes, 0, 0) : 0;
$totalPages = max(1, ceil($total / $perPage));

// Fetch customers with order count
$mainSQL = "
    SELECT u.id, u.username AS name, u.email, u.phone,
           u.address, u.created_at, u.admin_notes,
           COUNT(o.id) AS order_count
    FROM users u
    LEFT JOIN orders o ON o.user_id = u.id
    $where
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT \${$pi} OFFSET \$" . ($pi + 1) . "
";
$queryParams = array_merge($params, [$perPage, $offset]);
$result = pg_query_params($conn, $mainSQL, $queryParams);
$customers = [];
if ($result) {
    while ($row = pg_fetch_assoc($result)) {
        $customers[] = $row;
    }
}

$adminName = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Customers – Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    /* ── Same CSS as original, no changes needed ──── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:         #e8e8e8;
      --sidebar-bg: #d4d4d4;
      --white:      #ffffff;
      --text:       #1a1a1a;
      --muted:      #6b7280;
      --border:     #e0e0e0;
      --blue:       #2563eb;
      --radius:     14px;
      --shadow:     0 2px 12px rgba(0,0,0,0.07);
      --font:       'Sora', system-ui, sans-serif;
    }
    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }
    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }
    .sidebar {
      background: var(--sidebar-bg); display: flex; flex-direction: column;
      padding: 20px 12px; gap: 6px; border-right: 1px solid #c8c8c8;
      position: sticky; top: 0; height: 100vh; overflow-y: auto;
    }
    .sidebar-logo {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px 20px; border-bottom: 1px solid #bbb;
      margin-bottom: 8px; text-decoration: none;
    }
    .sidebar-logo-icon {
      width: 36px; height: 36px; background: var(--white); border-radius: 10px;
      display: flex; align-items: center; justify-content: center; flex-shrink: 0;
      border: 1.5px solid #ddd;
    }
    .sidebar-logo-icon svg { width: 20px; height: 20px; }
    .sidebar-logo-text { font-size: 14px; font-weight: 700; color: var(--text); letter-spacing: -.2px; }
    .nav-item {
      display: flex; align-items: center; gap: 12px; padding: 11px 14px;
      border-radius: 12px; font-size: 13px; font-weight: 500; color: var(--text);
      text-decoration: none; transition: background .15s; border: none;
      background: transparent; width: 100%; cursor: pointer;
    }
    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: var(--text); color: #fff; font-weight: 600; }
    .content-area { display: grid; grid-template-columns: 1fr 0px; transition: .3s; overflow: hidden; min-height: 100vh; }
    .content-area.panel-open { grid-template-columns: 1fr 320px; }
    .main-content { display: flex; flex-direction: column; padding: 28px 24px 48px; gap: 22px; overflow-y: auto; min-width: 0; }
    .page-header { display: flex; align-items: center; justify-content: space-between; }
    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }
    .btn-admin {
      display: flex; align-items: center; gap: 9px; padding: 9px 18px;
      background: var(--white); border: 1.5px solid var(--border); border-radius: 12px;
      font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }
    .table-card { background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden; }
    .toolbar { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--border); gap: 12px; flex-wrap: wrap; }
    .toolbar-right { display: flex; align-items: center; gap: 8px; }
    .search-wrap {
      display: flex; align-items: center; gap: 8px; padding: 7px 14px;
      border: 1.5px solid var(--border); border-radius: 10px; background: #fafafa; transition: border-color .18s;
    }
    .search-wrap:focus-within { border-color: #aaa; background: #fff; }
    .search-wrap svg { width: 14px; height: 14px; color: var(--muted); flex-shrink: 0; }
    .search-wrap input {
      border: none; background: transparent; font-family: var(--font);
      font-size: 12px; color: var(--text); outline: none; width: 170px;
    }
    .search-wrap input::placeholder { color: var(--muted); }
    .icon-btn {
      width: 34px; height: 34px; border-radius: 9px; border: 1.5px solid var(--border);
      background: var(--white); display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted); transition: background .15s, color .15s;
    }
    .icon-btn:hover { background: #f5f5f5; color: var(--text); }
    .icon-btn svg { width: 15px; height: 15px; }
    .cust-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .cust-table thead tr { background: #f9fafb; border-bottom: 1px solid var(--border); }
    .cust-table th { padding: 12px 18px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); white-space: nowrap; }
    .cust-table td { padding: 13px 18px; border-bottom: 1px solid #f5f5f5; color: var(--text); vertical-align: middle; }
    .cust-table tbody tr:last-child td { border-bottom: none; }
    .cust-table tbody tr { transition: background .12s; cursor: pointer; }
    .cust-table tbody tr:hover { background: #f5fffe; }
    .cust-table tbody tr.selected { background: #f0fdf4; }
    .col-id { color: var(--muted); font-family: monospace; font-size: 12px; }
    .col-email { color: var(--muted); font-size: 12px; }
    .col-count { font-weight: 600; }
    .name-cell { display: flex; align-items: center; gap: 10px; }
    .avatar {
      width: 34px; height: 34px; border-radius: 50%; background: #e0f2fe; color: #0369a1;
      display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; text-transform: uppercase;
    }
    .row-actions { display: flex; align-items: center; gap: 6px; }
    .action-btn {
      width: 28px; height: 28px; border-radius: 7px; border: 1.5px solid var(--border);
      background: var(--white); display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted); transition: background .15s, color .15s, border-color .15s;
    }
    .action-btn:hover { background: #f5f5f5; color: var(--text); }
    .action-btn svg { width: 13px; height: 13px; }
    .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); font-size: 14px; }
    .pagination-wrap { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-top: 1px solid var(--border); }
    .pagination-info { font-size: 12px; color: var(--muted); }
    .pagination-btns { display: flex; align-items: center; gap: 5px; }
    .page-btn {
      min-width: 32px; height: 32px; padding: 0 9px; border-radius: 8px;
      border: 1.5px solid var(--border); background: var(--white);
      font-family: var(--font); font-size: 12px; font-weight: 500; color: var(--muted);
      cursor: pointer; display: flex; align-items: center; justify-content: center;
      text-decoration: none; transition: border-color .15s, background .15s, color .15s;
    }
    .page-btn:hover { border-color: #aaa; color: var(--text); }
    .page-btn.active { background: var(--text); border-color: var(--text); color: #fff; font-weight: 600; }
    .page-btn:disabled { opacity: .4; cursor: default; pointer-events: none; }
    .page-ellipsis { color: var(--muted); font-size: 12px; padding: 0 3px; }

    /* Detail panel (unchanged, but status select removed from HTML) */
    .detail-panel {
      background: var(--white); border-left: 1px solid var(--border);
      overflow-y: auto; overflow-x: hidden; width: 320px;
      opacity: 0; pointer-events: none; transition: opacity .25s;
    }
    .content-area.panel-open .detail-panel { opacity: 1; pointer-events: auto; }
    .panel-header { display: flex; align-items: center; justify-content: space-between; padding: 20px 20px 0; }
    .panel-close {
      width: 28px; height: 28px; border-radius: 8px; border: 1.5px solid var(--border);
      background: var(--white); display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted); transition: background .15s; flex-shrink: 0;
    }
    .panel-close:hover { background: #f5f5f5; color: var(--text); }
    .panel-close svg { width: 13px; height: 13px; }
    .panel-avatar-section { display: flex; align-items: center; gap: 14px; padding: 20px 20px 0; }
    .panel-avatar {
      width: 52px; height: 52px; border-radius: 50%; background: #e0f2fe; color: #0369a1;
      display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700; flex-shrink: 0; text-transform: uppercase;
    }
    .panel-cust-name { font-size: 15px; font-weight: 700; line-height: 1.3; }
    .panel-cust-id { font-size: 11px; color: var(--muted); font-family: monospace; margin-top: 2px; }
    .panel-section { padding: 18px 20px; border-bottom: 1px solid var(--border); }
    .panel-section:last-child { border-bottom: none; }
    .panel-section-title { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); margin-bottom: 12px; }
    .panel-field { margin-bottom: 12px; }
    .panel-field:last-child { margin-bottom: 0; }
    .panel-field-label { font-size: 10px; font-weight: 600; color: var(--muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing:.05em; }
    .panel-input {
      width: 100%; font-family: var(--font); font-size: 12px; color: var(--text);
      background: #fafafa; border: 1.5px solid var(--border); border-radius: 8px;
      padding: 8px 11px; outline: none; transition: border-color .18s, background .18s;
    }
    .panel-input:focus { border-color: #888; background: #fff; }
    .panel-textarea {
      width: 100%; font-family: var(--font); font-size: 12px; color: var(--text);
      background: #fafafa; border: 1.5px solid var(--border); border-radius: 8px;
      padding: 8px 11px; outline: none; resize: vertical; min-height: 72px; line-height: 1.5;
      transition: border-color .18s;
    }
    .panel-textarea:focus { border-color: #888; background: #fff; }
    .order-overview { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; }
    .order-stat { background: #f9fafb; border-radius: 10px; padding: 12px 10px; text-align: center; border: 1px solid var(--border); }
    .order-stat-val { font-size: 18px; font-weight: 700; }
    .order-stat-lbl { font-size: 9px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing:.05em; margin-top: 3px; }
    .activity-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
    .activity-row:last-child { margin-bottom: 0; }
    .activity-row .alabel { color: var(--muted); }
    .activity-row .aval { font-weight: 500; }
    .panel-save-btn {
      width: 100%; padding: 10px; border-radius: 10px; border: none;
      background: var(--blue); color: #fff; font-family: var(--font);
      font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
      display: flex; align-items: center; justify-content: center; gap: 7px;
    }
    .panel-save-btn:hover { background: #1d4ed8; }
    .panel-save-btn svg { width: 14px; height: 14px; }
    .toast {
      position: fixed; bottom: 28px; right: 28px; background: #1a1a1a; color: #fff;
      padding: 13px 20px; border-radius: 10px; font-size: 13px; font-weight: 500;
      display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 28px rgba(0,0,0,0.16);
      transform: translateY(70px); opacity: 0;
      transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s;
      z-index: 999; pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }
    .toast.warn svg { color: #fbbf24; }
  </style>
</head>
<body>
<div class="admin-shell">

  <!-- ── Sidebar ──────────────────────────────────── -->
  <?php $current_file = basename($_SERVER['PHP_SELF']); ?>
  <aside class="sidebar">
    <a href="../admin/admindashboard.php" class="sidebar-logo">
      <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
      <span class="sidebar-logo-text">E-Commerce</span>
    </a>
    <a href="../admin/admindashboard.php" class="nav-item <?= $current_file == 'admindashboard.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a href="../admin/admin-orders.php" class="nav-item <?= $current_file == 'admin-orders.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Orders
    </a>
    <a href="../admin/admin-products.php" class="nav-item <?= $current_file == 'admin-products.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Products
    </a>
    <a href="../admin/admin-customers.php" class="nav-item <?= $current_file == 'admin-customers.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Customers
    </a>
  </aside>

  <div class="content-area" id="contentArea">
    <main class="main-content">
      <div class="page-header">
        <h1 class="page-title">Customers</h1>
        <button class="btn-admin">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          <?= htmlspecialchars($adminName) ?>
        </button>
      </div>

      <div class="table-card">
        <div class="toolbar">
          <!-- Removed status filter tabs -->
          <form method="GET" style="display:contents">
            <div class="search-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
              <input type="text" name="search" placeholder="Search by name, email, ID…"
                     value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()"/>
            </div>
          </form>
          <div class="toolbar-right">
            <button class="icon-btn" title="Export" onclick="showToast('Export coming soon.')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>
          </div>
        </div>

        <table class="cust-table">
          <thead>
            <tr>
              <th>Customer ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Orders</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customers)): ?>
              <tr><td colspan="6"><div class="empty-state">No customers found.</div></td></tr>
            <?php else: ?>
              <?php foreach ($customers as $c):
                $initial = strtoupper(substr($c['name'], 0, 1));
              ?>
              <tr onclick="openPanel(<?= $c['id'] ?>)" id="row-<?= $c['id'] ?>">
                <td class="col-id">#<?= htmlspecialchars($c['id']) ?></td>
                <td>
                  <div class="name-cell">
                    <div class="avatar"><?= $initial ?></div>
                    <span><?= htmlspecialchars($c['name']) ?></span>
                  </div>
                </td>
                <td class="col-email"><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['phone']) ?></td>
                <td class="col-count"><?= $c['order_count'] ?></td>
                <td>
                  <div class="row-actions" onclick="event.stopPropagation()">
                    <button class="action-btn" title="View details" onclick="openPanel(<?= $c['id'] ?>)">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <div class="pagination-wrap">
          <span class="pagination-info">Showing <?= count($customers) ?> of <?= $total ?> customers</span>
          <div class="pagination-btns">
            <?php if ($totalPages > 1): ?>
              <?php 
                $prevQs = http_build_query(array_merge($_GET, ['page' => $page - 1]));
                $nextQs = http_build_query(array_merge($_GET, ['page' => $page + 1]));
              ?>
              <a href="<?= $page > 1 ? '?'.$prevQs : '#' ?>" class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">← Prev</a>
              <?php for ($p = 1; $p <= $totalPages; $p++):
                $pQs = http_build_query(array_merge($_GET, ['page' => $p]));
                if ($totalPages > 7 && abs($p - $page) > 1 && $p !== 1 && $p !== $totalPages):
                  if ($p === $page - 2 || $p === $page + 2) echo '<span class="page-ellipsis">…</span>';
                  continue;
                endif;
              ?>
                <a href="?<?= $pQs ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
              <?php endfor; ?>
              <a href="<?= $page < $totalPages ? '?'.$nextQs : '#' ?>" class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next →</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>

    <!-- Detail panel (status dropdown removed, admin notes kept) -->
    <aside class="detail-panel" id="detailPanel">
      <div class="panel-header">
        <span style="font-size:13px;font-weight:700;">Customer Details</span>
        <button class="panel-close" onclick="closePanel()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <div class="panel-avatar-section">
        <div class="panel-avatar" id="pAvatar"></div>
        <div>
          <div class="panel-cust-name" id="pName"></div>
          <div class="panel-cust-id" id="pId"></div>
        </div>
      </div>

      <div class="panel-section">
        <div class="panel-section-title">Customer Info</div>
        <div class="panel-field">
          <div class="panel-field-label">Full Name</div>
          <input class="panel-input" type="text" id="editName" placeholder="Full name"/>
        </div>
        <div class="panel-field">
          <div class="panel-field-label">Email</div>
          <input class="panel-input" type="email" id="editEmail" placeholder="email@example.com"/>
        </div>
        <div class="panel-field">
          <div class="panel-field-label">Phone</div>
          <input class="panel-input" type="text" id="editPhone" placeholder="+1234567890"/>
        </div>
        <div class="panel-field">
          <div class="panel-field-label">Address</div>
          <input class="panel-input" type="text" id="editAddress" placeholder="123 Main St, City"/>
        </div>
      </div>

      <div class="panel-section">
        <div class="panel-section-title">Activity</div>
        <div class="activity-row">
          <span class="alabel">Registered</span>
          <span class="aval" id="pRegistered">—</span>
        </div>
      </div>

      <div class="panel-section">
        <div class="panel-section-title">Order Overview</div>
        <div class="order-overview">
          <div class="order-stat">
            <div class="order-stat-val" id="pOrderCount">—</div>
            <div class="order-stat-lbl">Total Orders</div>
          </div>
          <div class="order-stat">
            <div class="order-stat-val" id="pCompleted" style="color:#16a34a">—</div>
            <div class="order-stat-lbl">Completed</div>
          </div>
        </div>
      </div>

      <div class="panel-section">
        <div class="panel-section-title">Admin Notes</div>
        <textarea class="panel-textarea" id="editNotes" placeholder="Add internal notes about this customer…"></textarea>
      </div>

      <div class="panel-section">
        <button class="panel-save-btn" onclick="saveCustomer()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
      </div>
    </aside>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg">Done</span>
</div>

<script>
let activeCustId = null;
let activeRowEl = null;

async function openPanel(id) {
  const res = await fetch(`?action=get&id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
  const cust = await res.json();
  if (cust.error) return;

  activeCustId = id;
  document.querySelectorAll('.cust-table tbody tr').forEach(r => r.classList.remove('selected'));
  const row = document.getElementById('row-' + id);
  if (row) { row.classList.add('selected'); activeRowEl = row; }

  const avEl = document.getElementById('pAvatar');
  avEl.textContent = cust.name.charAt(0).toUpperCase();
  document.getElementById('pName').textContent = cust.name;
  document.getElementById('pId').textContent = '#' + cust.id;
  document.getElementById('editName').value = cust.name || '';
  document.getElementById('editEmail').value = cust.email || '';
  document.getElementById('editPhone').value = cust.phone || '';
  document.getElementById('editAddress').value = cust.address || '';
  document.getElementById('editNotes').value = cust.admin_notes || '';
  document.getElementById('pRegistered').textContent = formatDate(cust.created_at);
  document.getElementById('pOrderCount').textContent = cust.order_count ?? '—';
  document.getElementById('pCompleted').textContent = Math.floor(cust.order_count * 0.7) ?? '—'; // approximate

  document.getElementById('contentArea').classList.add('panel-open');
}

function closePanel() {
  document.getElementById('contentArea').classList.remove('panel-open');
  if (activeRowEl) activeRowEl.classList.remove('selected');
  activeCustId = null; activeRowEl = null;
}

async function saveCustomer() {
  if (!activeCustId) return;
  const payload = new URLSearchParams({
    action: 'update',
    id: activeCustId,
    name: document.getElementById('editName').value.trim(),
    email: document.getElementById('editEmail').value.trim(),
    phone: document.getElementById('editPhone').value.trim(),
    address: document.getElementById('editAddress').value.trim(),
    notes: document.getElementById('editNotes').value.trim()
  });

  const res = await fetch('admin-customers.php', {
    method: 'POST',
    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
    body: payload
  });
  const json = await res.json();
  if (json.success) {
    showToast('Customer updated.');
  } else {
    showToast('Error: ' + (json.error || 'Unknown'), true);
  }
}

function formatDate(str) {
  if (!str) return '—';
  const d = new Date(str);
  return isNaN(d) ? str : d.toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
}

function showToast(msg, warn = false) {
  const el = document.getElementById('toast');
  document.getElementById('toastMsg').textContent = msg;
  el.className = 'toast' + (warn ? ' warn' : '');
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 2800);
}
</script>
</body>
</html>