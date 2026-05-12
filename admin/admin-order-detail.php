<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../config/db.php';
/** @var resource|\PgSql\Connection $conn */


define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

$orderId = (int)($_GET['id'] ?? 0);
$res = pg_query_params($conn,
    "SELECT o.*, u.username, u.email, u.first_name, u.last_name
     FROM orders o JOIN users u ON u.id = o.user_id
     WHERE o.id = $1",
    [$orderId]
);
$order = pg_fetch_assoc($res);
if (!$order) {
    echo "Order not found.";
    exit;
}

// Fetch order items
$itemsRes = pg_query_params($conn,
    "SELECT * FROM order_items WHERE order_id = $1",
    [$orderId]
);
$items = [];
while ($row = pg_fetch_assoc($itemsRes)) {
    $items[] = $row;
}

// Shipping details
$shipping = json_decode($order['address'] ?? '{}', true);
$adminName = $_SESSION['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <title>Order #<?= $order['id'] ?> – Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <style>
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
    .sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 12px 20px;
        border-bottom: 1px solid #bbb;
        margin-bottom: 8px;
        text-decoration: none;
    }
    .sidebar-logo-text { font-size: 14px; font-weight: 700; color: var(--text); letter-spacing: -.2px; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 12px; font-size: 13px; font-weight: 500; color: var(--text); text-decoration: none; transition: background .15s; }
    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: var(--text); color: #fff; font-weight: 600; }

    .main-content { display: flex; flex-direction: column; padding: 28px 28px 48px; gap: 24px; overflow-y: auto; background: var(--bg); }
      .page-header {
        display: flex;
        align-items: center;
        gap: 12px;           /* space between back link and title */
        margin-bottom: 0;
    }
    .back-btn {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    font-weight: 500;
    color: var(--muted);
    text-decoration: none;
    transition: color .15s;
    }
    .back-link:hover { text-decoration: underline; }
    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }
    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }
    .back-link { color: var(--blue); text-decoration: none; font-weight: 600; font-size: 13px; display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px; }
    .back-link:hover { text-decoration: underline; }

    .card {
      background: var(--white); border-radius: var(--radius); box-shadow: var(--shadow);
      border: 1px solid var(--border); padding: 24px;
    }
    .card-title { font-size: 16px; font-weight: 700; margin-bottom: 18px; padding-bottom: 12px; border-bottom: 1px solid var(--border); }

    .detail-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .detail-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 4px; }
    .detail-value { font-size: 14px; font-weight: 500; }

    .status-badge {
      display: inline-flex; align-items: center; gap: 5px; padding: 4px 11px; border-radius: 20px;
      font-size: 11px; font-weight: 600; white-space: nowrap;
    }
    .status-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; }
    .badge-pending   { background: #fef9ec; color: #b45309; }
    .badge-pending::before   { background: #d97706; }
    .badge-shipped   { background: #eff6ff; color: #2563eb; }
    .badge-shipped::before   { background: #2563eb; }
    .badge-delivered { background: #f0fdf4; color: #16a34a; }
    .badge-delivered::before { background: #16a34a; }
    .badge-cancelled { background: #fef2f2; color: #dc2626; }
    .badge-cancelled::before { background: #dc2626; }

    .items-list { display: flex; flex-direction: column; gap: 12px; }
    .item-card {
      display: flex; align-items: center; gap: 16px;
      padding: 16px; background: #f9fafb; border-radius: 12px; border: 1px solid var(--border);
    }
    .item-card img {
      width: 64px; height: 64px; object-fit: contain;
      background: var(--white); border-radius: 10px; border: 1px solid var(--border); padding: 4px;
    }
    .item-info { flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .item-name { font-weight: 600; font-size: 14px; }
    .item-meta { font-size: 13px; color: var(--muted); }
    .item-subtotal { font-weight: 700; font-size: 15px; color: var(--text); white-space: nowrap; }

    .shipping-address { font-size: 14px; line-height: 1.6; color: var(--text); }
  </style>
</head>
<body>
<div class="admin-shell">

  <!-- Sidebar -->
  <aside class="sidebar">
    <a href="../admin/admindashboard.php" class="sidebar-logo">
      <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
      <span class="sidebar-logo-text">E-Commerce</span>
    </a>
    <a href="../admin/admindashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admindashboard.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg> Dashboard
    </a>
    <a href="../admin/admin-orders.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-orders.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg> Orders
    </a>
    <a href="../admin/admin-products.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-products.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg> Products
    </a>
    <a href="../admin/admin-customers.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-customers.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg> Customers
    </a>
  </aside>

  <!-- Main -->
  <main class="main-content">
      <div class="page-header">
      <a href="admin-orders.php" class="back-link">
        ← Back
      </a>
      <h1 class="page-title">Order #<?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></h1>
    </div>

    <!-- Order overview card -->
    <div class="card">
      <div class="card-title">Order Details</div>
      <div class="detail-grid">
        <div class="detail-item">
          <div class="detail-label">Customer</div>
          <div class="detail-value">
            <?php
              $fullName = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
              if ($fullName === '') {
                  $fullName = $order['username'];
              }
              echo htmlspecialchars($fullName) . ' (' . htmlspecialchars($order['email']) . ')';
            ?>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Date</div>
          <div class="detail-value"><?= date('M d, Y', strtotime($order['created_at'])) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Status</div>
          <div class="detail-value">
            <span class="status-badge badge-<?= strtolower($order['status']) ?>">
              <?= ucfirst($order['status']) ?>
            </span>
          </div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Payment</div>
          <div class="detail-value" style="text-transform:capitalize"><?= $order['payment_status'] ?? 'unpaid' ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Total</div>
          <div class="detail-value" style="font-weight:700; font-size:16px;">₱<?= number_format($order['total'], 2) ?></div>
        </div>
        <div class="detail-item">
          <div class="detail-label">Payment Method</div>
          <div class="detail-value"><?= strtoupper($order['payment_method'] ?? 'cod') ?></div>
        </div>
      </div>
    </div>

    <!-- Shipping Address -->
    <div class="card">
      <div class="card-title">Shipping Address</div>
      <div class="shipping-address">
       <?= htmlspecialchars($shipping['first_name'] ?? '') ?> <?= htmlspecialchars($shipping['last_name'] ?? '') ?><br>
       <?= htmlspecialchars($shipping['address'] ?? '') ?><br>
       <?= htmlspecialchars($shipping['city'] ?? '') ?>, <?= htmlspecialchars($shipping['barangay'] ?? '') ?> <?= htmlspecialchars($shipping['postal_code'] ?? '') ?><br>
       <?= htmlspecialchars($shipping['phone'] ?? '') ?>
      </div>
    </div>

        <?php
            $lat = $shipping['latitude'] ?? '';
            $lng = $shipping['longitude'] ?? '';
            if ($lat && $lng):
            ?>
            <div class="card">
              <div class="card-title">Delivery Location</div>
              <div id="orderMap" style="height:250px; border-radius:12px; border:1px solid var(--border);"></div>
            </div>

            <!-- Load Leaflet CSS + JS (put these in <head> as well) -->
            <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
            <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
            <script>
            window.addEventListener('DOMContentLoaded', function() {
              var map = L.map('orderMap').setView([<?= $lat ?>, <?= $lng ?>], 17);
              L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
              }).addTo(map);
              L.marker([<?= $lat ?>, <?= $lng ?>]).addTo(map);
            });
            </script>
        <?php endif; ?>

    <!-- Items -->
    <div class="card">
      <div class="card-title">Items (<?= count($items) ?>)</div>
      <div class="items-list">
        <?php foreach ($items as $item): ?>
          <div class="item-card">
            <img src="<?= PRODUCT_IMGS_BASE . htmlspecialchars($item['image_url']) ?>" 
                 alt="<?= htmlspecialchars($item['product_name']) ?>"
                 onerror="this.src='data:image/svg+xml,...'">
            <div class="item-info">
              <div class="item-name"><?= htmlspecialchars($item['product_name']) ?></div>
              <div class="item-meta">Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></div>
            </div>
            <div class="item-subtotal">₱<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </main>
</div>
</body>
</html>