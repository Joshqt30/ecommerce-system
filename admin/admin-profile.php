<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

include '../config/db.php';

$admin_id = $_SESSION['user_id'];
$tab      = $_GET['tab'] ?? 'profile';

// ── Fetch admin ───────────────────────────────────────
$userRes = pg_query_params($conn,
    "SELECT username, email, phone, avatar FROM users WHERE id = $1",
    [$admin_id]
);
$user = pg_fetch_assoc($userRes);

$success    = '';
$error      = '';
$passSuccess = '';
$passError  = '';

// ═══════════════════════════════════════════════════════
//  UPDATE PROFILE (username, email, phone + avatar)
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $avatarPath = $user['avatar']; // keep existing unless new one uploaded

    // ── Handle avatar upload ──────────────────────────
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['avatar'];
        $maxSize  = 2 * 1024 * 1024; // 2MB
        $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed)) {
            $error = 'Avatar must be JPG, PNG, WebP, or GIF.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Avatar must be under 2MB.';
        } else {
            $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename  = 'admin_' . $admin_id . '_' . time() . '.' . strtolower($ext);
            $uploadDir = '../imgs/avatars/';

            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                // Delete old avatar file if it exists and isn't the default
                if (!empty($user['avatar']) && file_exists($uploadDir . $user['avatar'])) {
                    @unlink($uploadDir . $user['avatar']);
                }
                $avatarPath = $filename;
            } else {
                $error = 'Failed to upload avatar. Check folder permissions.';
            }
        }
    }

    if (empty($error)) {
        // Validate
        if (empty($username)) {
            $error = 'Username cannot be empty.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            // Check if email already taken by another user
            $emailCheck = pg_query_params($conn,
                "SELECT id FROM users WHERE email = $1 AND id != $2",
                [$email, $admin_id]
            );
            if (pg_num_rows($emailCheck) > 0) {
                $error = 'That email is already used by another account.';
            } else {
                $upd = pg_query_params($conn,
                    "UPDATE users SET username = $1, email = $2, phone = $3, avatar = $4 WHERE id = $5",
                    [$username, $email, $phone, $avatarPath, $admin_id]
                );
                if ($upd) {
                    $success = 'Profile updated successfully!';
                    $_SESSION['username'] = $username;
                    // Re-fetch
                    $userRes = pg_query_params($conn,
                        "SELECT username, email, phone, avatar FROM users WHERE id = $1",
                        [$admin_id]
                    );
                    $user = pg_fetch_assoc($userRes);
                } else {
                    $error = 'Update failed: ' . pg_last_error($conn);
                }
            }
        }
    }
}

// ═══════════════════════════════════════════════════════
//  CHANGE PASSWORD
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password']     ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $passRow = pg_fetch_assoc(pg_query_params($conn,
        "SELECT password FROM users WHERE id = $1", [$admin_id]
    ));

    if (!$passRow || !password_verify($current, $passRow['password'])) {
        $passError = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $passError = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $passError = 'New passwords do not match.';
    } elseif (password_verify($new, $passRow['password'])) {
        $passError = 'New password must be different from current password.';
    } else {
        $hashed = password_hash($new, PASSWORD_DEFAULT);
        $upd    = pg_query_params($conn,
            "UPDATE users SET password = $1 WHERE id = $2",
            [$hashed, $admin_id]
        );
        $passSuccess = $upd ? 'Password changed successfully.' : 'Failed: ' . pg_last_error($conn);
    }
}

// ═══════════════════════════════════════════════════════
//  DELETE ACCOUNT
// ═══════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    pg_query_params($conn,
        "DELETE FROM order_items WHERE order_id IN (SELECT id FROM orders WHERE user_id = $1)",
        [$admin_id]
    );
    pg_query_params($conn, "DELETE FROM orders WHERE user_id = $1", [$admin_id]);
    pg_query_params($conn, "DELETE FROM users  WHERE id = $1",      [$admin_id]);
    session_destroy();
    header('Location: ../auth/login.php');
    exit;
}

$adminName   = $user['username']  ?? 'Admin';
$adminEmail  = $user['email']     ?? '';
$adminPhone  = $user['phone']     ?? '';

// Avatar URL
$avatarFile = $user['avatar'] ?? '';
$avatarUrl  = !empty($avatarFile)
    ? '/ecommerce-system/imgs/avatars/' . htmlspecialchars($avatarFile)
    : null;

$current_file = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Profile – E-Commerce</title>
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
      --radius:     14px;
      --shadow:     0 2px 12px rgba(0,0,0,0.07);
      --font:       'Sora', system-ui, sans-serif;
    }
    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }

    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }

    /* ── Sidebar (untouched) ────────────────────────── */
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
    .main-content { display: flex; flex-direction: column; padding: 28px 28px 48px; gap: 22px; overflow-y: auto; }

    .page-header { display: flex; align-items: center; justify-content: space-between; }
    .page-title  { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }

    .btn-admin { display: flex; align-items: center; gap: 9px; padding: 9px 18px; background: var(--white); border: 1.5px solid var(--border); border-radius: 12px; font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s; text-decoration: none; color: var(--text); }
    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }

    /* ── Profile hero card ──────────────────────────── */
    .profile-hero {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      padding: 28px 32px;
      display: flex;
      align-items: center;
      gap: 24px;
    }

    /* Avatar upload zone */
    .avatar-zone {
      position: relative;
      flex-shrink: 0;
      cursor: pointer;
    }

    .avatar-circle {
      width: 88px;
      height: 88px;
      border-radius: 50%;
      background: #f0f0f0;
      border: 3px solid var(--border);
      overflow: hidden;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: border-color .18s;
    }

    .avatar-zone:hover .avatar-circle { border-color: #aaa; }

    .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
    .avatar-circle svg { width: 36px; height: 36px; color: #bbb; }

    .avatar-upload-badge {
      position: absolute;
      bottom: 2px; right: 2px;
      width: 26px; height: 26px;
      background: var(--text);
      border-radius: 50%;
      border: 2px solid var(--white);
      display: flex; align-items: center; justify-content: center;
      transition: background .15s, transform .15s;
    }

    .avatar-zone:hover .avatar-upload-badge { background: #333; transform: scale(1.1); }
    .avatar-upload-badge svg { width: 12px; height: 12px; color: #fff; }

    /* Hidden file input — entire zone is clickable */
    #avatarInput { display: none; }

    .avatar-hint { font-size: 11px; color: var(--muted); margin-top: 6px; text-align: center; }

    /* Hero right side */
    .hero-info { flex: 1; }
    .hero-name  { font-size: 20px; font-weight: 700; letter-spacing: -.3px; color: var(--text); }
    .hero-email { font-size: 13px; color: var(--muted); margin-top: 3px; }
    .hero-badge {
      display: inline-flex; align-items: center; gap: 5px;
      margin-top: 10px; padding: 4px 12px;
      background: #f0fdf4; color: #16a34a;
      border-radius: 20px; font-size: 11px; font-weight: 700;
    }
    .hero-badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: #16a34a; }

    .hero-stats { display: flex; gap: 24px; margin-top: 14px; }
    .hero-stat  { display: flex; flex-direction: column; gap: 2px; }
    .hero-stat-val { font-size: 18px; font-weight: 700; color: var(--text); }
    .hero-stat-lbl { font-size: 11px; color: var(--muted); }

    /* ── Tab bar ─────────────────────────────────────── */
    .tab-bar { display: flex; gap: 6px; background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 6px; box-shadow: var(--shadow); }
    .tab-btn { flex: 1; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 10px 14px; border-radius: 10px; border: none; background: transparent; font-family: var(--font); font-size: 13px; font-weight: 500; color: var(--muted); cursor: pointer; transition: background .15s, color .15s; text-decoration: none; }
    .tab-btn svg { width: 15px; height: 15px; flex-shrink: 0; }
    .tab-btn:hover { background: #f5f5f5; color: var(--text); }
    .tab-btn.active { background: var(--text); color: #fff; }

    /* ── Content card ───────────────────────────────── */
    .content-card { background: var(--white); border: 1px solid var(--border); border-radius: var(--radius); padding: 30px 32px; box-shadow: var(--shadow); display: none; }
    .content-card.active { display: block; }

    .card-title    { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: -.3px; margin-bottom: 4px; }
    .card-subtitle { font-size: 13px; color: var(--muted); margin-bottom: 24px; }

    /* ── Form ───────────────────────────────────────── */
    .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px; }
    .form-grid.single { grid-template-columns: 1fr; }
    .field { display: flex; flex-direction: column; gap: 6px; }
    .field-label { font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; }
    .field-input { padding: 11px 14px; border: 1.5px solid var(--border); border-radius: 10px; font-family: var(--font); font-size: 14px; color: var(--text); outline: none; transition: border-color .18s; background: #fff; }
    .field-input:focus { border-color: var(--text); }
    .field-input::placeholder { color: #c0c4cc; }

    .btn-save { margin-top: 6px; padding: 11px 28px; background: var(--text); color: #fff; border: none; border-radius: 10px; font-family: var(--font); font-size: 14px; font-weight: 600; cursor: pointer; transition: background .18s, transform .12s; }
    .btn-save:hover { background: #2d3340; }
    .btn-save:active { transform: scale(0.97); }

    /* ── Alerts ─────────────────────────────────────── */
    .alert { padding: 12px 16px; border-radius: 10px; font-size: 13px; font-weight: 500; margin-bottom: 18px; }
    .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
    .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }

    /* ── Section divider ────────────────────────────── */
    .section-sep   { margin-top: 28px; padding-top: 24px; border-top: 1px solid #f3f4f6; }
    .section-title { font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 16px; }

    /* ── Password strength bar ───────────────────────── */
    .strength-bar { height: 4px; border-radius: 4px; background: var(--border); margin-top: 6px; overflow: hidden; }
    .strength-fill { height: 100%; border-radius: 4px; width: 0; transition: width .3s, background .3s; }

    /* ── Toggle switches ─────────────────────────────── */
    .toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 13px 0; border-bottom: 1px solid #f3f4f6; }
    .toggle-row:last-of-type { border-bottom: none; }
    .toggle-info  { display: flex; flex-direction: column; gap: 3px; }
    .toggle-label { font-size: 14px; font-weight: 500; color: var(--text); }
    .toggle-desc  { font-size: 12px; color: var(--muted); }
    .toggle-switch { position: relative; width: 42px; height: 24px; cursor: pointer; flex-shrink: 0; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-track { position: absolute; inset: 0; background: var(--border); border-radius: 24px; transition: background .2s; }
    .toggle-track::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; top: 3px; background: #fff; border-radius: 50%; transition: transform .2s; box-shadow: 0 1px 4px rgba(0,0,0,0.15); }
    .toggle-switch input:checked + .toggle-track { background: var(--text); }
    .toggle-switch input:checked + .toggle-track::before { transform: translateX(18px); }

    /* ── Danger zone ─────────────────────────────────── */
    .danger-zone { border: 1.5px solid #fee2e2; border-radius: 12px; padding: 18px 22px; display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-top: 20px; }
    .danger-title { font-size: 14px; font-weight: 600; color: #ef4444; margin-bottom: 3px; }
    .danger-desc  { font-size: 12px; color: var(--muted); }
    .btn-danger   { padding: 10px 22px; background: transparent; color: #ef4444; border: 1.5px solid #ef4444; border-radius: 10px; font-family: var(--font); font-size: 13px; font-weight: 600; cursor: pointer; white-space: nowrap; transition: background .18s; }
    .btn-danger:hover { background: #fef2f2; }

    /* ── Toast ──────────────────────────────────────── */
    .toast { position: fixed; bottom: 28px; right: 28px; background: #1a1a1a; color: #fff; padding: 13px 20px; border-radius: 10px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; box-shadow: 0 8px 28px rgba(0,0,0,0.16); transform: translateY(70px); opacity: 0; transition: transform .3s cubic-bezier(.34,1.56,.64,1), opacity .28s; z-index: 999; pointer-events: none; }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast svg { color: #4ade80; }

    /* Avatar preview ring animation */
    @keyframes ring-pulse { 0%,100% { box-shadow: 0 0 0 0 rgba(0,0,0,0.15); } 50% { box-shadow: 0 0 0 6px rgba(0,0,0,0); } }
    .avatar-circle.uploading { animation: ring-pulse .8s infinite; }
  </style>
</head>
<body>
<div class="admin-shell">

  <!-- ── Sidebar (unchanged) ──────────────────────── -->
  <aside class="sidebar">
    <a href="../admin/admindashboard.php" class="sidebar-logo">
      <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
      <span class="sidebar-logo-text">E-Commerce</span>
    </a>

    <a href="../admin/admindashboard.php"
       class="nav-item <?= $current_file === 'admindashboard.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
        <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
      </svg>
      Dashboard
    </a>

    <a href="../admin/admin-orders.php"
       class="nav-item <?= $current_file === 'admin-orders.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/>
        <line x1="3" y1="6" x2="21" y2="6"/>
        <path d="M16 10a4 4 0 01-8 0"/>
      </svg>
      Orders
    </a>

    <a href="../admin/admin-products.php"
       class="nav-item <?= $current_file === 'admin-products.php' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <rect x="2" y="3" width="20" height="14" rx="2"/>
        <path d="M8 21h8M12 17v4"/>
      </svg>
      Products
    </a>

    <a href="../admin/admin-customers.php"
       class="nav-item <?= $current_file === 'admin-customers.php' ? 'active' : '' ?>">
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
      <h1 class="page-title">Admin Profile</h1>
      <a href="../admin/admindashboard.php" class="btn-admin">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Back to Dashboard
      </a>
    </div>

    <!-- Profile hero -->
    <div class="profile-hero">

      <!-- Avatar upload -->
      <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display:contents">
        <input type="hidden" name="update_profile" value="1">
        <label class="avatar-zone" for="avatarInput" title="Click to change photo">
          <div class="avatar-circle" id="avatarCircle">
            <?php if ($avatarUrl): ?>
              <img src="<?= $avatarUrl ?>" alt="Avatar" id="avatarPreview"/>
            <?php else: ?>
              <svg id="avatarPlaceholder" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/>
                <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
              </svg>
            <?php endif; ?>
          </div>
          <div class="avatar-upload-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z"/>
            </svg>
          </div>
        </label>
        <input type="file" id="avatarInput" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif"/>
      </form>
      <p class="avatar-hint" style="position:absolute;left:-9999px">JPG, PNG, WebP · Max 2MB</p>

      <!-- Admin info -->
      <div class="hero-info">
        <div class="hero-name"><?= htmlspecialchars($adminName) ?></div>
        <div class="hero-email"><?= htmlspecialchars($adminEmail) ?></div>
        <div class="hero-badge">Administrator</div>
        <?php
        // Quick stats
        $ordersCount = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM orders"), 0, 0) ?: 0;
        $prodsCount  = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM products"), 0, 0) ?: 0;
        $usersCount  = pg_fetch_result(pg_query($conn, "SELECT COUNT(*) FROM users WHERE role = 'user'"), 0, 0) ?: 0;
        ?>
        <div class="hero-stats">
          <div class="hero-stat">
            <span class="hero-stat-val"><?= number_format((int)$ordersCount) ?></span>
            <span class="hero-stat-lbl">Total Orders</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-val"><?= number_format((int)$prodsCount) ?></span>
            <span class="hero-stat-lbl">Products</span>
          </div>
          <div class="hero-stat">
            <span class="hero-stat-val"><?= number_format((int)$usersCount) ?></span>
            <span class="hero-stat-lbl">Customers</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab bar -->
    <div class="tab-bar">
      <a href="?tab=profile" class="tab-btn <?= $tab === 'profile' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/>
        </svg>
        Profile
      </a>
      <a href="?tab=security" class="tab-btn <?= $tab === 'security' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/>
          <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Security
      </a>
      <a href="?tab=preferences" class="tab-btn <?= $tab === 'preferences' ? 'active' : '' ?>">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="3"/>
          <path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/>
        </svg>
        Preferences
      </a>
    </div>

    <!-- ── Profile tab ──────────────────────────────── -->
    <div class="content-card <?= $tab === 'profile' ? 'active' : '' ?>">
      <h2 class="card-title">Personal Information</h2>
      <p class="card-subtitle">Update your admin account details.</p>

      <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>
      <?php if ($error && $tab === 'profile'): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="update_profile" value="1">
        <!-- Hidden avatar input linked to the hero zone -->
        <input type="file" name="avatar" id="avatarInputForm" accept="image/*" style="display:none"/>

        <div class="form-grid">
          <div class="field">
            <label class="field-label">Username</label>
            <input class="field-input" type="text" name="username"
                   value="<?= htmlspecialchars($adminName) ?>" required/>
          </div>
          <div class="field">
            <label class="field-label">Email Address</label>
            <input class="field-input" type="email" name="email"
                   value="<?= htmlspecialchars($adminEmail) ?>" required/>
          </div>
        </div>

        <div class="form-grid single">
          <div class="field">
            <label class="field-label">Phone Number</label>
            <input class="field-input" type="tel" name="phone"
                   value="<?= htmlspecialchars($adminPhone) ?>"
                   placeholder="+63 912 345 6789"/>
          </div>
        </div>

        <button class="btn-save" type="submit">Save Changes</button>
      </form>
    </div>

    <!-- ── Security tab ────────────────────────────── -->
    <div class="content-card <?= $tab === 'security' ? 'active' : '' ?>">
      <h2 class="card-title">Security</h2>
      <p class="card-subtitle">Keep your admin account safe.</p>

      <?php if ($passSuccess): ?>
        <div class="alert alert-success"><?= htmlspecialchars($passSuccess) ?></div>
      <?php endif; ?>
      <?php if ($passError): ?>
        <div class="alert alert-error"><?= htmlspecialchars($passError) ?></div>
      <?php endif; ?>

      <div class="section-title">Change Password</div>
      <form method="POST">
        <input type="hidden" name="change_password" value="1">

        <div class="form-grid single" style="margin-bottom:14px">
          <div class="field">
            <label class="field-label">Current Password</label>
            <input class="field-input" type="password" name="current_password"
                   placeholder="Enter current password" required/>
          </div>
        </div>

        <div class="form-grid" style="margin-bottom:6px">
          <div class="field">
            <label class="field-label">New Password</label>
            <input class="field-input" type="password" name="new_password"
                   id="newPassword" placeholder="At least 8 characters" required
                   oninput="checkStrength(this.value)"/>
            <div class="strength-bar"><div class="strength-fill" id="strengthFill"></div></div>
          </div>
          <div class="field">
            <label class="field-label">Confirm New Password</label>
            <input class="field-input" type="password" name="confirm_password"
                   placeholder="Repeat new password" required/>
          </div>
        </div>

        <p style="font-size:11px;color:var(--muted);margin-bottom:16px">
          Minimum 8 characters. Use a mix of letters, numbers, and symbols.
        </p>

        <button class="btn-save" type="submit">Update Password</button>
      </form>

      <!-- Danger zone -->
      <div class="section-sep">
        <div class="section-title" style="color:#ef4444">Danger Zone</div>
        <div class="danger-zone">
          <div>
            <p class="danger-title">Delete Admin Account</p>
            <p class="danger-desc">Permanently removes your admin account and all associated data. Cannot be undone.</p>
          </div>
          <form method="POST"
                onsubmit="return confirm('This will permanently delete your admin account.\nAre you absolutely sure?')">
            <input type="hidden" name="delete_account" value="1">
            <button type="submit" class="btn-danger">Delete Account</button>
          </form>
        </div>
      </div>
    </div>

    <!-- ── Preferences tab ──────────────────────────── -->
    <div class="content-card <?= $tab === 'preferences' ? 'active' : '' ?>">
      <h2 class="card-title">Preferences</h2>
      <p class="card-subtitle">Manage your notification settings.</p>

      <div class="section-title">Notifications</div>

      <div class="toggle-row">
        <div class="toggle-info">
          <span class="toggle-label">New Order Alerts</span>
          <span class="toggle-desc">Get notified whenever a new order is placed</span>
        </div>
        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-track"></span></label>
      </div>

      <div class="toggle-row">
        <div class="toggle-info">
          <span class="toggle-label">Low Stock Warnings</span>
          <span class="toggle-desc">Alert when a product stock drops to 5 or below</span>
        </div>
        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-track"></span></label>
      </div>

      <div class="toggle-row">
        <div class="toggle-info">
          <span class="toggle-label">New Customer Registrations</span>
          <span class="toggle-desc">Notify when a new customer signs up</span>
        </div>
        <label class="toggle-switch"><input type="checkbox"><span class="toggle-track"></span></label>
      </div>

      <div class="toggle-row">
        <div class="toggle-info">
          <span class="toggle-label">System Errors</span>
          <span class="toggle-desc">Receive alerts for critical system errors</span>
        </div>
        <label class="toggle-switch"><input type="checkbox" checked><span class="toggle-track"></span></label>
      </div>
    </div>

  </main>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <polyline points="20 6 9 17 4 12"/>
  </svg>
  <span id="toastMsg"></span>
</div>

<script>
// ── Auto-fire toast for PHP messages ──────────────────
<?php if ($success): ?>
  window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($success) ?>));
<?php elseif ($error): ?>
  window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($error) ?>, true));
<?php elseif ($passSuccess): ?>
  window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($passSuccess) ?>));
<?php elseif ($passError): ?>
  window.addEventListener('DOMContentLoaded', () => showToast(<?= json_encode($passError) ?>, true));
<?php endif; ?>

function showToast(msg, isError = false) {
  const el = document.getElementById('toast');
  el.querySelector('svg').style.color = isError ? '#f87171' : '#4ade80';
  document.getElementById('toastMsg').textContent = msg;
  el.classList.add('show');
  clearTimeout(el._t);
  el._t = setTimeout(() => el.classList.remove('show'), 3200);
}

// ── Avatar preview — clicking the hero zone ───────────
const avatarInput   = document.getElementById('avatarInput');
const avatarCircle  = document.getElementById('avatarCircle');

avatarInput.addEventListener('change', function() {
  const file = this.files[0];
  if (!file) return;
  if (file.size > 2 * 1024 * 1024) {
    showToast('File too large. Max 2MB.', true);
    return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    avatarCircle.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:50%"/>`;
    avatarCircle.classList.add('uploading');
    setTimeout(() => avatarCircle.classList.remove('uploading'), 800);
    showToast('Photo selected — save your profile to apply it.');

    // Copy the file to the actual form input and submit
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('avatarInputForm') &&
      (document.getElementById('avatarInputForm').files = dt.files);
    document.querySelector('form[enctype]').submit();
  };
  reader.readAsDataURL(file);
});

// ── Password strength indicator ───────────────────────
function checkStrength(val) {
  const fill = document.getElementById('strengthFill');
  if (!fill) return;
  let score = 0;
  if (val.length >= 8)                         score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/\d/.test(val))                          score++;
  if (/[^A-Za-z0-9]/.test(val))               score++;
  const colors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#16a34a'];
  const widths  = ['0%', '25%', '50%', '75%', '100%'];
  fill.style.width      = widths[score];
  fill.style.background = colors[score];
}
</script>
</body>
</html>