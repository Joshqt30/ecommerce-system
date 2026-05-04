<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include '../config/db.php';
include '../includes/header.php';
include '../includes/cart-panel.php';

$user_id = $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'profile';

$userQuery = "SELECT username, email, phone, birth_date, address FROM users WHERE id = $1";
$userResult = pg_query_params($conn, $userQuery, [$user_id]);

$user = pg_fetch_assoc($userResult);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $birth    = trim($_POST['birth_date'] ?? '');
    $address  = trim($_POST['address'] ?? '');

    $birth = ($birth === '') ? null : $birth;

    if (
        $username === $user['username'] &&
        $email === $user['email'] &&
        $phone === $user['phone'] &&
        $birth === $user['birth_date'] &&
        $address === $user['address']
    ) {
        $error = "No changes detected.";
    } else {

        $updateQuery = "
            UPDATE users 
            SET username = $1,
                email = $2,
                phone = $3,
                birth_date = $4,
                address = $5
            WHERE id = $6
        ";

        $updateResult = pg_query_params($conn, $updateQuery, [
            $username,
            $email,
            $phone,
            $birth,
            $address,
            $user_id
        ]);

        if ($updateResult) {
            $success = "Profile updated!";
            $userResult = pg_query_params($conn, $userQuery, [$user_id]);
            $user = pg_fetch_assoc($userResult);
        } else {
            $error = pg_last_error($conn);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {

    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $passQuery = "SELECT password FROM users WHERE id = $1";
    $passResult = pg_query_params($conn, $passQuery, [$user_id]);
    $row = pg_fetch_assoc($passResult);

    if (!$row || !password_verify($current, $row['password'])) {
        $passError = "Current password is incorrect!";
    } elseif ($new !== $confirm) {
        $passError = "New passwords do not match!";
    } else {

        $hashed = password_hash($new, PASSWORD_DEFAULT);

        $updatePass = "UPDATE users SET password = $1 WHERE id = $2";
        $passResult = pg_query_params($conn, $updatePass, [$hashed, $user_id]);

        if ($passResult) {
            $passSuccess = "Password updated successfully!";
        } else {
            $passError = "Failed to update password!";
        }
    }
}

$orders = [];

$orderQuery = "
    SELECT o.id, o.total, o.status, o.created_at,
           oi.variant_id, oi.product_name, oi.quantity, oi.price, oi.image_url
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.user_id = $1
    ORDER BY o.created_at DESC, o.id DESC
";

$orderResult = pg_query_params($conn, $orderQuery, [$user_id]);

if ($orderResult) {
    while ($row = pg_fetch_assoc($orderResult)) {
        $orderId = $row['id'];
        if (!isset($orders[$orderId])) {
            $orders[$orderId] = [
                'id'         => $row['id'],
                'total'      => $row['total'],
                'status'     => $row['status'],
                'created_at' => $row['created_at'],
                'items'      => []
            ];
        }

        if ($row['product_name']) {
            $orders[$orderId]['items'][] = [
                'variant_id' => $row['variant_id'],
                'name'       => $row['product_name'],
                'quantity'   => $row['quantity'],
                'price'      => $row['price'],
                'image'      => $row['image_url']
            ];
        }
    }
}

$orders = array_values($orders);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {

    $deleteItems = pg_query_params(
        $conn,
        "DELETE FROM order_items 
         WHERE order_id IN (
             SELECT id FROM orders WHERE user_id = $1
         )",
        [$user_id]
    );

    $deleteOrders = pg_query_params(
        $conn,
        "DELETE FROM orders WHERE user_id = $1",
        [$user_id]
    );

    $deleteUser = pg_query_params(
        $conn,
        "DELETE FROM users WHERE id = $1",
        [$user_id]
    );

    if ($deleteUser) {

    session_destroy();

    echo "<script>
            alert('Account deleted successfully.');
            window.location='../auth/login.php';
          </script>";
    exit();
}
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>My Account – E-Commerce</title>
    <link rel="preconnect" href="https://fonts.googleapis.com"/>
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="../assets/css/header.css">
    <link rel="stylesheet" href="../assets/css/cart-panel.css">
    <link rel="stylesheet" href="../assets/css/profile.css">
</head>
<body>

<div class="account-page">

    <!-- ── Tab bar (replaces sidebar) ────────────────── -->
    <div class="tab-bar">
        <a href="?tab=profile"
           class="tab-bar-btn <?= $tab === 'profile' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
            </svg>
            Profile
        </a>
        <a href="?tab=orders"
           class="tab-bar-btn <?= $tab === 'orders' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 01-8 0"/>
            </svg>
            My Orders
        </a>
        <a href="?tab=settings"
           class="tab-bar-btn <?= $tab === 'settings' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
            </svg>
            Settings
        </a>
    </div>

    <!-- ── Profile card ───────────────────────────────── -->
    <div class="account-card <?= $tab === 'profile' ? 'active' : '' ?>">

        <?php if (isset($success)): ?>
            <div class="success-message" style="color:green; margin-bottom:10px;"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message" style="color:red; margin-bottom:10px;"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
        <input type="hidden" name="update_profile" value="1">

        <h2 class="card-title">Personal Information</h2>
        <p class="card-subtitle">Update your personal details here.</p>

        <div class="form-grid">
            <div class="field">
                <label class="field-label">Username</label>
                <input class="field-input" type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" placeholder="First name">
            </div>
        </div>
        <div class="form-grid single">
            <div class="field">
                <label class="field-label">Email Address</label>
                <input class="field-input" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" placeholder="Email address">
            </div>
        </div>
        <div class="form-grid">
            <div class="field">
                <label class="field-label">Phone Number</label>
                <input class="field-input" type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Phone number" required>
            </div>
            <div class="field">
                <label class="field-label">Date of Birth</label>
                <input class="field-input" type="date" name="birth_date" value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>" placeholder="Date of birth" required>
            </div>
        </div>
        <div class="form-grid single">
            <div class="field">
                <label class="field-label">Delivery Address</label>
                <input class="field-input" type="text" name="address" value="<?= htmlspecialchars($user['address'] ?? '') ?>" placeholder="Street, City, Province, ZIP" required>
            </div>
        </div>
                <button class="btn-save" type="submit">Save Changes</button>
        </form>
        </div>

    <!-- ── Orders card ────────────────────────────────── -->
        <div class="account-card <?= $tab === 'orders' ? 'active' : '' ?>">
            <h2 class="card-title">My Orders</h2>
            <p class="card-subtitle">Your recent order history.</p>

            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5">
                        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
                        <line x1="3" y1="6" x2="21" y2="6"/>
                        <path d="M16 10a4 4 0 01-8 0"/>
                    </svg>
                    <p>You haven't placed any orders yet.</p>
                    <a href="dashboard.php" class="btn-shop">Start Shopping</a>
                </div>
            <?php else: ?>
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div class="order-info">
                                    <span class="order-id">Order #<?= $order['id'] ?></span>
                                    <span class="order-date"><?= date("M d, Y", strtotime($order['created_at'])) ?></span>
                                </div>
                                <div class="order-meta">
                                    <span class="badge <?= $order['status'] === 'delivered' ? 'badge-green' : ($order['status'] === 'pending' ? 'badge-yellow' : 'badge-blue') ?>">
                                        <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                    </span>
                                    <span class="order-total">₱<?= number_format($order['total'], 2) ?></span>
                                </div>
                            </div>
                            <div class="order-items">
                                <?php foreach ($order['items'] as $item): ?>
                                    <div class="order-item">
                                        <img src="/ecommerce-system/imgs/products/<?= htmlspecialchars($item['image']) ?>" 
                                            alt="<?= htmlspecialchars($item['name']) ?>" 
                                            class="order-item-img">
                                        <div class="order-item-details">
                                            <span class="order-item-name"><?= htmlspecialchars($item['name']) ?></span>
                                            <span class="order-item-meta">
                                                Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?>
                                            </span>
                                        </div>
                                        <span class="order-item-total">
                                            ₱<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <!-- ── Settings card ──────────────────────────────── -->
    <div class="account-card <?= $tab === 'settings' ? 'active' : '' ?>">
        <h2 class="card-title">Account Settings</h2>
        <p class="card-subtitle">Manage your password and preferences.</p>

    <!-- Change password -->
        <?php if (isset($passSuccess)): ?>
            <div class="success-message" style="color:green; margin-bottom:10px;"><?= htmlspecialchars($passSuccess) ?></div>
        <?php endif; ?>

        <?php if (isset($passError)): ?>
            <div class="error-message" style="color:red; margin-bottom:10px;"><?= htmlspecialchars($passError) ?></div>
        <?php endif; ?>
    <div class="section-title">Change Password</div>
    <form method="POST">
        <input type="hidden" name="change_password" value="1">

        <div class="form-grid single" style="margin-bottom:14px;">
            <div class="field">
                <label class="field-label">Current Password</label>
                <input class="field-input" type="password" name="current_password" placeholder="Enter current password" required>
            </div>
        </div>
        <div class="form-grid" style="margin-bottom:16px;">
            <div class="field">
                <label class="field-label">New Password</label>
                <input class="field-input" type="password" name="new_password" placeholder="New password" required>
            </div>
            <div class="field">
                <label class="field-label">Confirm Password</label>
                <input class="field-input" type="password" name="confirm_password" placeholder="Confirm new password" required>
            </div>
        </div>
        <button class="btn-save" type="submit">Update Password</button>
    </form>

        <!-- Notifications -->
        <div class="section-gap">
            <div class="section-title">Notifications</div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <span class="toggle-label">Order Updates</span>
                    <span class="toggle-desc">Get notified about your order status changes</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="toggle-track"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <span class="toggle-label">Promotions & Deals</span>
                    <span class="toggle-desc">Receive emails about sales and special offers</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" checked>
                    <span class="toggle-track"></span>
                </label>
            </div>

            <div class="toggle-row">
                <div class="toggle-info">
                    <span class="toggle-label">Newsletter</span>
                    <span class="toggle-desc">Weekly digest of new products and updates</span>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox">
                    <span class="toggle-track"></span>
                </label>
            </div>
        </div>

        <!-- Danger zone -->
        <div class="section-gap">
            <div class="section-title" style="color:#ef4444;">Danger Zone</div>
            <div class="danger-zone">
                <div>
                    <p class="danger-title">Delete Account</p>
                    <p class="danger-desc">Permanently delete your account. This cannot be undone.</p>
                </div>
                <form method="POST"
                    onsubmit="return confirm('Delete your account permanently? This cannot be undone.');">
                    <input type="hidden" name="delete_account" value="1">
                    <button type="submit" class="btn-danger">
                        Delete Account
                    </button>
                </form>
            </div>
        </div>
    </div>

</div>

<!-- Toast -->
<div class="toast" id="toast">
    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"
         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="20 6 9 17 4 12"/>
    </svg>
    <span id="toastMsg">Done</span>
</div>

<script src="../scripts/cart-panel.js"></script>
<script>
    function toast(msg) {
        const el = document.getElementById('toast');
        document.getElementById('toastMsg').textContent = msg;
        el.classList.add('show');
        clearTimeout(el._t);
        el._t = setTimeout(() => el.classList.remove('show'), 2600);
    }

    // Highlight the matching dropdown item based on current tab
    const tab = '<?= $tab ?>';
    const tabMap = { profile: 0, orders: 1, settings: 2 };
    const items = document.querySelectorAll('.dd-item:not(.dd-logout)');
    if (items[tabMap[tab]]) items[tabMap[tab]].classList.add('dd-active');
</script>

<script>
window.addEventListener("DOMContentLoaded", () => {
    const messages = document.querySelectorAll(".success-message, .error-message");

    messages.forEach(msg => {
        setTimeout(() => {
            msg.style.transition = "opacity 0.3s ease";
            msg.style.opacity = "0";
            setTimeout(() => msg.remove(), 300);
        }, 1500);
    });
});
</script>

</body>
</html>