<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

include '../includes/header.php';
include '../includes/cart-panel.php';
include '../config/db.php';

$user_id = $_SESSION['user_id'];

// ✅ FIXED: match your actual DB structure
$stmt = $conn->prepare("SELECT username, email, phone, birth_date, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$tab = $_GET['tab'] ?? 'profile';

// ✅ UPDATE PROFILE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $email    = $_POST['email'];
    $phone    = $_POST['phone'];
    $birth    = $_POST['birth_date'];
    $address  = $_POST['address'];

    $update = $conn->prepare("UPDATE users SET username=?, email=?, phone=?, birth_date=?, address=? WHERE id=?");
    $update->bind_param("sssssi", $username, $email, $phone, $birth, $address, $user_id);

    if ($update->execute()) {
        $success = "Profile updated!";
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Update failed.";
    }
}

// ✅ FETCH ORDERS (FIXED QUERY)
$orders = [];
$orderQuery = $conn->prepare("
    SELECT o.id, o.total, o.status, o.created_at, p.name AS product_name
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$orderQuery->bind_param("i", $user_id);
$orderQuery->execute();
$res = $orderQuery->get_result();

while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
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
        <h2 class="card-title">Personal Information</h2>
        <p class="card-subtitle">Update your personal details here.</p>

        <div class="form-grid">
            <div class="field">
                <label class="field-label">First Name</label>
                <input class="field-input" type="text" value="John" placeholder="First name">
            </div>
            <div class="field">
                <label class="field-label">Last Name</label>
                <input class="field-input" type="text" value="Doe" placeholder="Last name">
            </div>
        </div>
        <div class="form-grid single">
            <div class="field">
                <label class="field-label">Email Address</label>
                <input class="field-input" type="email" value="johndoe@email.com">
            </div>
        </div>
        <div class="form-grid">
            <div class="field">
                <label class="field-label">Phone Number</label>
                <input class="field-input" type="tel" value="+63 912 345 6789">
            </div>
            <div class="field">
                <label class="field-label">Date of Birth</label>
                <input class="field-input" type="date" value="1995-06-15">
            </div>
        </div>
        <div class="form-grid single">
            <div class="field">
                <label class="field-label">Delivery Address</label>
                <input class="field-input" type="text" placeholder="Street, City, Province, ZIP">
            </div>
        </div>
        <button class="btn-save" onclick="toast('Profile updated!')">Save Changes</button>
    </div>

    <!-- ── Orders card ────────────────────────────────── -->
    <div class="account-card <?= $tab === 'orders' ? 'active' : '' ?>">
        <h2 class="card-title">My Orders</h2>
        <p class="card-subtitle">Your recent order history.</p>

        <table class="orders-table">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Product</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>#ORD-482910</td>
                    <td>MacBook Air M3</td>
                    <td>Apr 10, 2025</td>
                    <td>$298.00</td>
                    <td><span class="badge badge-green">Delivered</span></td>
                </tr>
                <tr>
                    <td>#ORD-391045</td>
                    <td>Sony WH-1000XM5</td>
                    <td>Mar 28, 2025</td>
                    <td>$349.00</td>
                    <td><span class="badge badge-blue">Processing</span></td>
                </tr>
                <tr>
                    <td>#ORD-274831</td>
                    <td>Dell XPS 15</td>
                    <td>Feb 14, 2025</td>
                    <td>$199.00</td>
                    <td><span class="badge badge-green">Delivered</span></td>
                </tr>
                <tr>
                    <td>#ORD-198204</td>
                    <td>Lenovo ThinkPad X1</td>
                    <td>Jan 5, 2025</td>
                    <td>$499.00</td>
                    <td><span class="badge badge-red">Cancelled</span></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- ── Settings card ──────────────────────────────── -->
    <div class="account-card <?= $tab === 'settings' ? 'active' : '' ?>">
        <h2 class="card-title">Account Settings</h2>
        <p class="card-subtitle">Manage your password and preferences.</p>

        <!-- Change password -->
        <div class="section-title">Change Password</div>
        <div class="form-grid single" style="margin-bottom:14px;">
            <div class="field">
                <label class="field-label">Current Password</label>
                <input class="field-input" type="password" placeholder="Enter current password">
            </div>
        </div>
        <div class="form-grid" style="margin-bottom:16px;">
            <div class="field">
                <label class="field-label">New Password</label>
                <input class="field-input" type="password" placeholder="New password">
            </div>
            <div class="field">
                <label class="field-label">Confirm Password</label>
                <input class="field-input" type="password" placeholder="Confirm new password">
            </div>
        </div>
        <button class="btn-save" onclick="toast('Password updated!')">Update Password</button>

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
                <button class="btn-danger"
                        onclick="if(confirm('Delete your account? This cannot be undone.')) toast('Account deletion requested. (demo)')">
                    Delete Account
                </button>
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
</body>
</html>