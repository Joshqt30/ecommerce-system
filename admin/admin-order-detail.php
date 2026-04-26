<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
include '../config/db.php';
define('PRODUCT_IMGS_BASE', '/ecommerce-system/imgs/products/');

$orderId = (int)($_GET['id'] ?? 0);
$res = pg_query_params($conn,
    "SELECT o.*, u.username, u.email
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
  <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <style>
    /* same admin-shell, sidebar styles as your other pages */
    /* For brevity, just reuse the sidebar HTML structure */
    body { font-family: 'Sora', sans-serif; background: #e8e8e8; margin:0; }
    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }
    .main-content { padding: 28px; background:#fff; }
    .back-link { color: #2563eb; text-decoration:none; font-weight:600; display:inline-block; margin-bottom:20px; }
    .order-detail-card { background:#f9fafb; border-radius:14px; padding:24px; margin-bottom:16px; }
    .item-row { display:flex; align-items:center; gap:16px; padding:12px 0; border-bottom:1px solid #eee; }
    .item-row img { width:60px; height:60px; object-fit:contain; background:#fff; border-radius:8px; border:1px solid #eee; }
    .sidebar {
    background: #d4d4d4; display: flex; flex-direction: column; padding: 20px 12px; gap: 6px;
    border-right: 1px solid #c8c8c8; height: 100vh; overflow-y: auto;
    }
    .sidebar-logo { display: flex; align-items: center; gap: 10px; padding: 10px 12px 20px; border-bottom: 1px solid #bbb; margin-bottom: 8px; text-decoration: none; }
    .sidebar-logo img { width: 36px; height: 36px; }
    .sidebar-logo-text { font-size: 14px; font-weight: 700; color: #1a1a1a; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 11px 14px; border-radius: 12px; font-size: 13px; font-weight: 500; color: #1a1a1a; text-decoration: none; transition: background .15s; }
    .nav-item svg { width: 20px; height: 20px; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: #1a1a1a; color: #fff; }
    
  </style>
</head>
<body>
<div class="admin-shell">
        <aside class="sidebar">
        <a href="../admin/admindashboard.php" class="sidebar-logo">
            <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
            <span class="sidebar-logo-text">E-Commerce</span>
        </a>

        <a href="../admin/admindashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admindashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>

        <a href="../admin/admin-orders.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-orders.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
            Orders
        </a>

        <a href="../admin/admin-products.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-products.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            Products
        </a>

        <a href="../admin/admin-customers.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'admin-customers.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
            Customers
        </a>
        </aside>
  <div class="main-content">
    <a href="admin-orders.php" class="back-link">← Back to Orders</a>
    <h1>Order #<?= $order['id'] ?></h1>
    <div class="order-detail-card">
      <p><strong>Customer:</strong> <?= htmlspecialchars($order['username']) ?> (<?= htmlspecialchars($order['email']) ?>)</p>
      <p><strong>Date:</strong> <?= date('M d, Y', strtotime($order['created_at'])) ?></p>
      <p><strong>Status:</strong> <?= $order['status'] ?></p>
      <p><strong>Payment:</strong> <?= $order['payment_status'] ?? 'unpaid' ?></p>
      <p><strong>Total:</strong> ₱<?= number_format($order['total'], 2) ?></p>

      <h3>Shipping Address</h3>
      <p>
        <?= htmlspecialchars($shipping['first_name'] ?? '') ?> <?= htmlspecialchars($shipping['last_name'] ?? '') ?><br>
        <?= htmlspecialchars($shipping['address'] ?? '') ?><br>
        <?= htmlspecialchars($shipping['city'] ?? '') ?>, <?= htmlspecialchars($shipping['state'] ?? '') ?> <?= htmlspecialchars($shipping['postal_code'] ?? '') ?><br>
        <?= htmlspecialchars($shipping['phone'] ?? '') ?>
      </p>
    </div>

    <h2>Items</h2>
    <?php foreach ($items as $item): ?>
      <div class="item-row">
        <img src="<?= PRODUCT_IMGS_BASE . htmlspecialchars($item['image_url']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
        <div style="flex:1">
          <p><?= htmlspecialchars($item['product_name']) ?></p>
          <p>Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></p>
        </div>
        <p><strong>₱<?= number_format($item['price'] * $item['quantity'], 2) ?></strong></p>
      </div>
    <?php endforeach; ?>
  </div>
</div>
</body>
</html>