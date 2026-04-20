<?php
session_start();

// ── Auth guard — uncomment when ready ─────────────────
// if (!isset($_SESSION['admin_id']) || $_SESSION['role'] !== 'admin') {
//     header('Location: ../auth/login.php');
//     exit;
// }

// ═══════════════════════════════════════════════════════
//  PostgreSQL CONNECTION (PDO)
// ═══════════════════════════════════════════════════════
// Replace credentials with your actual DB config
// $dsn = "pgsql:host=localhost;port=5432;dbname=your_db";
// try {
//     $pdo = new PDO($dsn, 'your_user', 'your_password', [
//         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//     ]);
// } catch (PDOException $e) {
//     http_response_code(500);
//     die(json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]));
// }

// ═══════════════════════════════════════════════════════
//  HANDLE POST SUBMISSION
// ═══════════════════════════════════════════════════════
$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ── Sanitize inputs ────────────────────────────────
    $name        = trim($_POST['product_name']    ?? '');
    $description = trim($_POST['description']     ?? '');
    $price       = floatval($_POST['price']       ?? 0);
    $has_discount= isset($_POST['has_discount'])  ? true : false;
    $disc_price  = $has_discount ? floatval($_POST['discounted_price'] ?? 0) : null;
    $stock       = intval($_POST['stock_quantity']?? 0);
    $low_stock   = intval($_POST['low_stock_threshold'] ?? 5);
    $stock_status= trim($_POST['stock_status']    ?? 'in_stock');
    $category    = trim($_POST['category']        ?? '');
    $tags        = trim($_POST['tags']            ?? '');          // comma-separated string
    $colors      = trim($_POST['colors']          ?? '');          // JSON string of color objects

    // ── Validate ───────────────────────────────────────
    if (empty($name))                        $errors[] = 'Product name is required.';
    if ($price <= 0)                         $errors[] = 'Product price must be greater than 0.';
    if ($has_discount && $disc_price >= $price) $errors[] = 'Discounted price must be less than the original price.';
    if ($stock < 0)                          $errors[] = 'Stock quantity cannot be negative.';
    if (empty($category))                    $errors[] = 'Please select a category.';

    // ── Image upload ───────────────────────────────────
    $imagePaths = [];
    if (!empty($_FILES['product_images']['name'][0])) {
        $uploadDir = '../uploads/products/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        foreach ($_FILES['product_images']['tmp_name'] as $i => $tmpName) {
            if ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $mime = mime_content_type($tmpName);
            if (!in_array($mime, $allowedTypes)) {
                $errors[] = 'Only JPG, PNG, and WebP images are allowed.';
                continue;
            }
            if ($_FILES['product_images']['size'][$i] > 5 * 1024 * 1024) {
                $errors[] = 'Each image must be under 5 MB.';
                continue;
            }
            $ext      = pathinfo($_FILES['product_images']['name'][$i], PATHINFO_EXTENSION);
            $filename = uniqid('prod_', true) . '.' . strtolower($ext);
            if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
                $imagePaths[] = $filename;
            }
        }
    }

    // ── Insert into PostgreSQL ─────────────────────────
    if (empty($errors)) {
        // $tagsArr   = array_filter(array_map('trim', explode(',', $tags)));
        // $colorsArr = json_decode($colors, true) ?: [];

        // $sql = "
        //     INSERT INTO products
        //         (name, description, price, discounted_price, stock_quantity,
        //          low_stock_threshold, stock_status, category, tags, colors,
        //          images, created_at)
        //     VALUES
        //         (:name, :description, :price, :discounted_price, :stock_quantity,
        //          :low_stock_threshold, :stock_status, :category, :tags, :colors,
        //          :images, NOW())
        //     RETURNING id
        // ";
        // $stmt = $pdo->prepare($sql);
        // $stmt->execute([
        //     ':name'                => $name,
        //     ':description'         => $description,
        //     ':price'               => $price,
        //     ':discounted_price'    => $disc_price,
        //     ':stock_quantity'      => $stock,
        //     ':low_stock_threshold' => $low_stock,
        //     ':stock_status'        => $stock_status,
        //     ':category'            => $category,
        //     ':tags'                => implode(',', $tagsArr),
        //     ':colors'              => json_encode($colorsArr),
        //     ':images'              => json_encode($imagePaths),
        // ]);
        // $newId  = $stmt->fetchColumn();
        // $success = true;

        // Demo only:
        $success = true;
    }
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// ── Product categories (from your suggestionsMap, deduplicated) ────────────
$categories = [
    'Headsets',
    'Smartphones',
    'Cell phones',
    'Laptops',
    'Computers & Laptops',
    'Cameras',
    'Watches',
    'TV sets',
    'Sound',
    'Kitchen Equipment',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Add New Product – Admin</title>
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
      --blue:       #2563eb;
      --blue-dark:  #1d4ed8;
      --green:      #16a34a;
      --red:        #dc2626;
      --radius:     14px;
      --shadow:     0 2px 12px rgba(0,0,0,0.07);
      --font:       'Sora', system-ui, sans-serif;
    }

    html, body { height: 100%; font-family: var(--font); background: var(--bg); color: var(--text); -webkit-font-smoothing: antialiased; }

    /* ── Shell ──────────────────────────────────────── */
    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }

    /* ── Sidebar ────────────────────────────────────── */
    .sidebar {
      background: var(--sidebar-bg);
      display: flex; flex-direction: column;
      padding: 20px 12px; gap: 6px;
      border-right: 1px solid #c8c8c8;
      position: sticky; top: 0; height: 100vh; overflow-y: auto;
    }

    .sidebar-logo {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 12px 20px;
      border-bottom: 1px solid #bbb; margin-bottom: 8px;
      text-decoration: none;
    }
    .sidebar-logo img { width: 34px; height: 34px; object-fit: contain; border-radius: 8px; }
    .sidebar-logo-text { font-size: 14px; font-weight: 700; color: var(--text); letter-spacing: -.2px; }

    .nav-item {
      display: flex; align-items: center; gap: 12px;
      padding: 11px 14px; border-radius: 12px;
      font-size: 13px; font-weight: 500; color: var(--text);
      text-decoration: none; transition: background .15s;
      border: none; background: transparent; width: 100%; cursor: pointer;
    }
    .nav-item svg { width: 20px; height: 20px; flex-shrink: 0; }
    .nav-item:hover { background: rgba(0,0,0,0.07); }
    .nav-item.active { background: var(--text); color: #fff; font-weight: 600; }

    /* ── Main ───────────────────────────────────────── */
    .main-content {
      display: flex; flex-direction: column;
      padding: 28px 28px 48px; gap: 0;
      overflow-y: auto;
    }

    /* ── Top bar ────────────────────────────────────── */
    .top-bar {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 24px;
    }

    .top-bar-left { display: flex; align-items: center; gap: 14px; }

    .back-btn {
      display: flex; align-items: center; gap: 6px;
      font-size: 13px; font-weight: 500; color: var(--muted);
      text-decoration: none; background: none; border: none;
      cursor: pointer; transition: color .15s;
    }
    .back-btn:hover { color: var(--text); }
    .back-btn svg { width: 16px; height: 16px; }

    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }

    .top-bar-right { display: flex; align-items: center; gap: 10px; }

    .btn-admin {
      display: flex; align-items: center; gap: 9px;
      padding: 9px 18px;
      background: var(--white); border: 1.5px solid var(--border);
      border-radius: 12px; font-family: var(--font);
      font-size: 13px; font-weight: 600; cursor: pointer; transition: background .15s;
    }
    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }

    .btn-publish {
      display: flex; align-items: center; gap: 8px;
      padding: 9px 20px;
      background: var(--blue); color: #fff;
      border: none; border-radius: 12px;
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
    }
    .btn-publish:hover { background: var(--blue-dark); }
    .btn-publish svg { width: 15px; height: 15px; }

    /* ── Two-col form layout ─────────────────────────── */
    .form-grid {
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 20px;
      align-items: start;
    }

    /* ── Card ───────────────────────────────────────── */
    .card {
      background: var(--white);
      border-radius: var(--radius);
      box-shadow: var(--shadow);
      border: 1px solid var(--border);
      padding: 24px;
      margin-bottom: 16px;
    }

    .card-title {
      font-size: 15px; font-weight: 700;
      letter-spacing: -.2px;
      margin-bottom: 18px;
      padding-bottom: 14px;
      border-bottom: 1px solid var(--border);
    }

    .section-label {
      font-size: 13px; font-weight: 600;
      color: var(--text); margin-bottom: 6px;
    }

    .section-hint {
      font-size: 11px; color: var(--muted); margin-bottom: 8px;
    }

    /* ── Form controls ──────────────────────────────── */
    .form-group { margin-bottom: 18px; }
    .form-group:last-child { margin-bottom: 0; }

    .form-label {
      display: block; font-size: 12px; font-weight: 600;
      color: var(--text); margin-bottom: 7px;
    }
    .form-label span { color: var(--red); margin-left: 2px; }

    .form-input, .form-textarea, .form-select {
      width: 100%;
      font-family: var(--font); font-size: 13px; color: var(--text);
      background: #fafafa; border: 1.5px solid var(--border);
      border-radius: 10px; outline: none;
      transition: border-color .18s, background .18s;
    }

    .form-input    { padding: 10px 14px; }
    .form-textarea { padding: 10px 14px; resize: vertical; min-height: 110px; line-height: 1.6; }
    .form-select   { padding: 10px 14px; appearance: none; -webkit-appearance: none; cursor: pointer; }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus { border-color: #888; background: #fff; }

    .form-input::placeholder,
    .form-textarea::placeholder { color: var(--muted); }

    /* Select wrapper */
    .select-wrap { position: relative; }
    .select-wrap svg.chev {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      width: 13px; height: 13px; color: var(--muted); pointer-events: none;
    }

    /* Input row */
    .input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    /* Prefix input */
    .input-prefix {
      display: flex; align-items: stretch;
      border: 1.5px solid var(--border); border-radius: 10px;
      overflow: hidden; background: #fafafa;
      transition: border-color .18s;
    }
    .input-prefix:focus-within { border-color: #888; background: #fff; }
    .input-prefix-label {
      display: flex; align-items: center;
      padding: 0 12px; background: #f0f0f0;
      border-right: 1.5px solid var(--border);
      font-size: 13px; font-weight: 600; color: var(--muted);
    }
    .input-prefix input {
      flex: 1; border: none; background: transparent;
      font-family: var(--font); font-size: 13px; color: var(--text);
      padding: 10px 12px; outline: none;
    }
    .input-prefix input::placeholder { color: var(--muted); }

    /* Discount toggle */
    .discount-toggle-row {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 12px;
    }
    .toggle-switch {
      position: relative; display: inline-block;
      width: 40px; height: 22px;
    }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider {
      position: absolute; inset: 0;
      background: #d1d5db; border-radius: 22px;
      cursor: pointer; transition: background .2s;
    }
    .toggle-slider::before {
      content: '';
      position: absolute; width: 16px; height: 16px;
      border-radius: 50%; background: #fff;
      left: 3px; top: 3px; transition: transform .2s;
      box-shadow: 0 1px 3px rgba(0,0,0,.2);
    }
    .toggle-switch input:checked + .toggle-slider { background: var(--blue); }
    .toggle-switch input:checked + .toggle-slider::before { transform: translateX(18px); }

    .discount-fields { display: none; }
    .discount-fields.visible { display: block; }

    /* ── Image upload ───────────────────────────────── */
    .image-drop-zone {
      border: 2px dashed var(--border);
      border-radius: 12px;
      padding: 32px 20px;
      text-align: center;
      cursor: pointer;
      transition: border-color .18s, background .18s;
      background: #fafafa;
      position: relative;
    }
    .image-drop-zone:hover { border-color: #aaa; background: #f5f5f5; }
    .image-drop-zone.dragover { border-color: var(--blue); background: #eff6ff; }

    .image-drop-zone input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer;
    }

    .drop-icon {
      width: 44px; height: 44px; margin: 0 auto 12px;
      background: #f0f0f0; border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      color: var(--muted);
    }
    .drop-icon svg { width: 22px; height: 22px; }

    .drop-title { font-size: 13px; font-weight: 600; margin-bottom: 4px; }
    .drop-hint  { font-size: 11px; color: var(--muted); }

    /* Image previews */
    .image-previews {
      display: flex; gap: 10px; flex-wrap: wrap; margin-top: 14px;
    }

    .preview-thumb {
      position: relative; width: 72px; height: 72px;
    }
    .preview-thumb img {
      width: 100%; height: 100%;
      object-fit: cover; border-radius: 8px;
      border: 1.5px solid var(--border);
    }
    .preview-thumb .remove-img {
      position: absolute; top: -6px; right: -6px;
      width: 20px; height: 20px; border-radius: 50%;
      background: var(--red); color: #fff;
      border: 2px solid #fff;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; font-size: 11px; font-weight: 700;
      line-height: 1;
    }

    /* ── Tags ───────────────────────────────────────── */
    .tags-wrap {
      display: flex; flex-wrap: wrap; gap: 6px;
      min-height: 42px;
      padding: 8px 10px;
      border: 1.5px solid var(--border); border-radius: 10px;
      background: #fafafa; cursor: text;
      transition: border-color .18s;
    }
    .tags-wrap:focus-within { border-color: #888; background: #fff; }

    .tag-chip {
      display: flex; align-items: center; gap: 5px;
      padding: 3px 10px;
      background: #e0f2fe; color: #0369a1;
      border-radius: 20px; font-size: 11px; font-weight: 600;
    }
    .tag-chip button {
      background: none; border: none; cursor: pointer;
      color: #0369a1; font-size: 13px; line-height: 1;
      padding: 0; display: flex; align-items: center;
    }

    .tag-input {
      border: none; background: transparent;
      font-family: var(--font); font-size: 12px; color: var(--text);
      outline: none; flex: 1; min-width: 100px;
    }
    .tag-input::placeholder { color: var(--muted); }
    #tagsHidden { display: none; }

    /* ── Color picker ───────────────────────────────── */
    .color-list {
      display: flex; flex-wrap: wrap; gap: 10px;
      margin-bottom: 14px;
    }

    .color-item {
      display: flex; flex-direction: column; align-items: center; gap: 5px;
      position: relative;
    }

    .color-swatch {
      width: 40px; height: 40px; border-radius: 50%;
      border: 2.5px solid var(--border);
      cursor: pointer; position: relative;
      transition: transform .15s;
      box-shadow: 0 1px 4px rgba(0,0,0,.15);
    }
    .color-swatch:hover { transform: scale(1.1); }

    .color-swatch .remove-color {
      position: absolute; top: -5px; right: -5px;
      width: 16px; height: 16px; border-radius: 50%;
      background: var(--red); color: #fff;
      border: 2px solid #fff;
      display: none; align-items: center; justify-content: center;
      cursor: pointer; font-size: 9px; font-weight: 700;
    }
    .color-swatch:hover .remove-color { display: flex; }

    .color-name {
      font-size: 9px; font-weight: 600; color: var(--muted);
      text-align: center; max-width: 44px;
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }

    /* Add color btn */
    .add-color-btn {
      width: 40px; height: 40px; border-radius: 50%;
      border: 2px dashed var(--border);
      background: #fafafa; cursor: pointer;
      display: flex; align-items: center; justify-content: center;
      color: var(--muted); transition: border-color .15s, color .15s;
    }
    .add-color-btn:hover { border-color: var(--blue); color: var(--blue); }
    .add-color-btn svg { width: 16px; height: 16px; }

    /* Color picker modal */
    .color-modal-overlay {
      position: fixed; inset: 0; background: rgba(0,0,0,0.35);
      z-index: 500; display: none; align-items: center; justify-content: center;
    }
    .color-modal-overlay.open { display: flex; }

    .color-modal {
      background: var(--white); border-radius: 16px;
      box-shadow: 0 12px 40px rgba(0,0,0,0.18);
      padding: 24px; width: 320px;
    }

    .color-modal h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; }

    .color-preview-box {
      width: 100%; height: 80px; border-radius: 10px;
      border: 1.5px solid var(--border); margin-bottom: 16px;
      transition: background .1s;
    }

    .color-row { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }

    .native-color-input {
      width: 44px; height: 44px; padding: 2px;
      border: 1.5px solid var(--border); border-radius: 8px;
      cursor: pointer; background: none;
    }

    .modal-btn-row { display: flex; gap: 8px; margin-top: 16px; }

    .btn-cancel-modal {
      flex: 1; padding: 9px; border-radius: 10px;
      border: 1.5px solid var(--border); background: var(--white);
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
    }
    .btn-cancel-modal:hover { background: #f5f5f5; }

    .btn-add-color {
      flex: 1; padding: 9px; border-radius: 10px;
      border: none; background: var(--blue); color: #fff;
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
    }
    .btn-add-color:hover { background: var(--blue-dark); }

    /* ── Stock controls ─────────────────────────────── */
    .stock-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

    /* ── Alerts ─────────────────────────────────────── */
    .alert {
      padding: 12px 16px; border-radius: 10px;
      font-size: 13px; font-weight: 500;
      margin-bottom: 18px;
    }
    .alert-error   { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

    /* ── Form footer ─────────────────────────────────── */
    .form-footer {
      display: flex; align-items: center; justify-content: flex-end;
      gap: 10px; padding-top: 4px;
    }

    .btn-secondary {
      padding: 10px 20px; border-radius: 10px;
      border: 1.5px solid var(--border); background: var(--white);
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; color: var(--text); transition: background .15s;
    }
    .btn-secondary:hover { background: #f5f5f5; }

    .btn-primary {
      padding: 10px 24px; border-radius: 10px;
      border: none; background: var(--blue); color: #fff;
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
      display: flex; align-items: center; gap: 8px;
    }
    .btn-primary:hover { background: var(--blue-dark); }
    .btn-primary svg { width: 15px; height: 15px; }

    /* ── Required notice ────────────────────────────── */
    .req-note { font-size: 11px; color: var(--muted); margin-bottom: 20px; }
    .req-note span { color: var(--red); }
  </style>
</head>
<body>

<div class="admin-shell">

  <!-- ── Sidebar ──────────────────────────────────── -->
  <aside class="sidebar">
    <a href="admin-dashboard.php" class="sidebar-logo">
      <img src="../imgs/icons/ecommercelogo.png" alt="E-Commerce logo"/>
      <span class="sidebar-logo-text">E-Commerce</span>
    </a>

    <a href="../admin/admindashboard.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
      Dashboard
    </a>
    <a href="admin-inventory.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14M5 8a2 2 0 010-4h14a2 2 0 010 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8"/><path d="M10 12h4"/></svg>
      Inventory
    </a>
    <a href="admin-orders.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>
      Orders
    </a>
    <a href="admin-products.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Product List
    </a>
    <a href="admin-customers.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Customers
    </a>
  </aside>

  <!-- ── Main ─────────────────────────────────────── -->
  <main class="main-content">

    <!-- Top bar -->
    <div class="top-bar">
      <div class="top-bar-left">
        <a href="admin-products.php" class="back-btn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
          Back
        </a>
        <h1 class="page-title">Add New Product</h1>
      </div>
      <div class="top-bar-right">
        <button class="btn-admin" type="button">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          <?= htmlspecialchars($adminName) ?>
        </button>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success">✓ Product published successfully! <a href="admin-products.php">Back to Product List</a></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e): ?><div>• <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <p class="req-note">Fields marked <span>*</span> are required.</p>

    <form method="POST" enctype="multipart/form-data" id="productForm">

      <div class="form-grid">

        <!-- ── LEFT COLUMN ────────────────────────── -->
        <div>

          <!-- Basic Details -->
          <div class="card">
            <div class="card-title">Basic Details</div>

            <div class="form-group">
              <label class="form-label" for="product_name">Product Name <span>*</span></label>
              <input class="form-input" type="text" id="product_name" name="product_name"
                     placeholder="e.g. iPhone 15 Pro"
                     value="<?= htmlspecialchars($_POST['product_name'] ?? '') ?>" required/>
            </div>

            <div class="form-group">
              <label class="form-label" for="description">Product Description</label>
              <textarea class="form-textarea" id="description" name="description"
                        placeholder="Describe the product — features, specs, highlights..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
            </div>
          </div>

          <!-- Pricing -->
          <div class="card">
            <div class="card-title">Pricing</div>

            <div class="form-group">
              <label class="form-label" for="price">Product Price <span>*</span></label>
              <div class="input-prefix">
                <span class="input-prefix-label">₱</span>
                <input type="number" id="price" name="price" step="0.01" min="0"
                       placeholder="0.00"
                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required/>
              </div>
            </div>

            <div class="form-group">
              <div class="discount-toggle-row">
                <label class="form-label" style="margin:0">Enable Discounted Price</label>
                <label class="toggle-switch">
                  <input type="checkbox" id="discountToggle" name="has_discount"
                         <?= isset($_POST['has_discount']) ? 'checked' : '' ?>
                         onchange="toggleDiscount(this)"/>
                  <span class="toggle-slider"></span>
                </label>
              </div>
              <div class="discount-fields <?= isset($_POST['has_discount']) ? 'visible' : '' ?>" id="discountFields">
                <label class="form-label" for="discounted_price">Discounted Price</label>
                <div class="input-prefix">
                  <span class="input-prefix-label">₱</span>
                  <input type="number" id="discounted_price" name="discounted_price"
                         step="0.01" min="0" placeholder="0.00"
                         value="<?= htmlspecialchars($_POST['discounted_price'] ?? '') ?>"/>
                </div>
                <p class="section-hint" style="margin-top:6px">Must be lower than the original price.</p>
              </div>
            </div>
          </div>

          <!-- Inventory -->
          <div class="card">
            <div class="card-title">Inventory</div>

            <div class="stock-grid">
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="stock_quantity">Stock Quantity <span>*</span></label>
                <input class="form-input" type="number" id="stock_quantity" name="stock_quantity"
                       min="0" placeholder="0"
                       value="<?= htmlspecialchars($_POST['stock_quantity'] ?? '') ?>" required/>
              </div>
              <div class="form-group" style="margin-bottom:0">
                <label class="form-label" for="low_stock_threshold">Low Stock Alert At</label>
                <input class="form-input" type="number" id="low_stock_threshold" name="low_stock_threshold"
                       min="1" placeholder="5"
                       value="<?= htmlspecialchars($_POST['low_stock_threshold'] ?? '5') ?>"/>
              </div>
            </div>

            <div class="form-group" style="margin-top:14px">
              <label class="form-label" for="stock_status">Stock Status <span>*</span></label>
              <div class="select-wrap">
                <select class="form-select" id="stock_status" name="stock_status">
                  <option value="in_stock"     <?= (($_POST['stock_status'] ?? '') === 'in_stock')     ? 'selected' : '' ?>>In Stock</option>
                  <option value="out_of_stock" <?= (($_POST['stock_status'] ?? '') === 'out_of_stock') ? 'selected' : '' ?>>Out of Stock</option>
                  <option value="pre_order"    <?= (($_POST['stock_status'] ?? '') === 'pre_order')    ? 'selected' : '' ?>>Pre-Order</option>
                </select>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </div>
            </div>
          </div>

          <!-- Form footer -->
          <div class="form-footer">
            <a href="admin-products.php" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
              Publish Product
            </button>
          </div>

        </div>

        <!-- ── RIGHT COLUMN ───────────────────────── -->
        <div>

          <!-- Product Images -->
          <div class="card">
            <div class="card-title">Product Images</div>
            <div class="image-drop-zone" id="dropZone"
                 ondragover="handleDragOver(event)"
                 ondragleave="handleDragLeave(event)"
                 ondrop="handleDrop(event)">
              <input type="file" name="product_images[]" id="imageInput"
                     accept="image/jpeg,image/png,image/webp"
                     multiple onchange="previewImages(this)"/>
              <div class="drop-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              </div>
              <div class="drop-title">Click or drag images here</div>
              <div class="drop-hint">JPG, PNG, WebP — max 5 MB each · Up to 5 images</div>
            </div>
            <div class="image-previews" id="imagePreviews"></div>
          </div>

          <!-- Categories -->
          <div class="card">
            <div class="card-title">Categories</div>

            <div class="form-group">
              <label class="form-label" for="category">Product Category <span>*</span></label>
              <div class="select-wrap">
                <select class="form-select" id="category" name="category" required>
                  <option value="">Select a category</option>
                  <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"
                      <?= (($_POST['category'] ?? '') === $cat) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($cat) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <svg class="chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Product Tags</label>
              <p class="section-hint">Type a tag and press Enter or comma to add it.</p>
              <div class="tags-wrap" id="tagsWrap" onclick="document.getElementById('tagInput').focus()">
                <input type="text" id="tagInput" class="tag-input" placeholder="e.g. new, sale, trending"/>
              </div>
              <input type="hidden" name="tags" id="tagsHidden"/>
            </div>
          </div>

          <!-- Available Colors -->
          <div class="card">
            <div class="card-title">Available Colors</div>
            <p class="section-hint" style="margin-bottom:14px">Click + to add a color. Click any swatch to edit it.</p>

            <div class="color-list" id="colorList"></div>
            <button type="button" class="add-color-btn" onclick="openColorModal(null)" title="Add color">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            </button>

            <input type="hidden" name="colors" id="colorsHidden"/>
          </div>

        </div>

      </div><!-- /form-grid -->
    </form>

  </main>
</div>

<!-- ── Color Picker Modal ──────────────────────────── -->
<div class="color-modal-overlay" id="colorModalOverlay">
  <div class="color-modal">
    <h3 id="colorModalTitle">Add Color</h3>
    <div class="color-preview-box" id="colorPreviewBox"></div>
    <div class="color-row">
      <input type="color" class="native-color-input" id="nativeColorPicker" value="#000000" oninput="syncColorFromNative(this.value)"/>
      <div class="input-prefix" style="flex:1">
        <span class="input-prefix-label">#</span>
        <input type="text" id="hexInput" placeholder="000000" maxlength="6" oninput="syncColorFromHex(this.value)"/>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label" for="colorNameInput">Color Name</label>
      <input class="form-input" type="text" id="colorNameInput" placeholder="e.g. Midnight Black"/>
    </div>
    <div class="modal-btn-row">
      <button class="btn-cancel-modal" onclick="closeColorModal()">Cancel</button>
      <button class="btn-add-color" onclick="confirmColor()">Save Color</button>
    </div>
  </div>
</div>

<script>
// ═══════════════════════════════════════════════════════
//  DISCOUNT TOGGLE
// ═══════════════════════════════════════════════════════
function toggleDiscount(cb) {
  document.getElementById('discountFields').classList.toggle('visible', cb.checked);
  const di = document.getElementById('discounted_price');
  if (!cb.checked) di.value = '';
}

// ═══════════════════════════════════════════════════════
//  IMAGE UPLOAD & PREVIEW
// ═══════════════════════════════════════════════════════
let selectedFiles = [];

function previewImages(input) {
  handleFiles(Array.from(input.files));
}

function handleFiles(files) {
  const max = 5;
  files.forEach(file => {
    if (selectedFiles.length >= max) return;
    if (!file.type.match(/image\/(jpeg|png|webp)/)) return;
    selectedFiles.push(file);
  });
  renderPreviews();
}

function renderPreviews() {
  const wrap = document.getElementById('imagePreviews');
  wrap.innerHTML = '';
  selectedFiles.forEach((file, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const div  = document.createElement('div');
      div.className = 'preview-thumb';
      div.innerHTML = `
        <img src="${e.target.result}" alt="preview"/>
        <span class="remove-img" onclick="removeImage(${i})">✕</span>`;
      wrap.appendChild(div);
    };
    reader.readAsDataURL(file);
  });
}

function removeImage(i) {
  selectedFiles.splice(i, 1);
  renderPreviews();
}

// Drag & drop
function handleDragOver(e) {
  e.preventDefault();
  document.getElementById('dropZone').classList.add('dragover');
}
function handleDragLeave(e) {
  document.getElementById('dropZone').classList.remove('dragover');
}
function handleDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').classList.remove('dragover');
  handleFiles(Array.from(e.dataTransfer.files));
}

// ═══════════════════════════════════════════════════════
//  TAGS
// ═══════════════════════════════════════════════════════
let tags = [];

document.getElementById('tagInput').addEventListener('keydown', function(e) {
  if (e.key === 'Enter' || e.key === ',') {
    e.preventDefault();
    addTag(this.value.trim().replace(/,/g,''));
    this.value = '';
  }
  if (e.key === 'Backspace' && this.value === '' && tags.length) {
    tags.pop();
    renderTags();
  }
});

function addTag(val) {
  if (!val || tags.includes(val) || tags.length >= 10) return;
  tags.push(val);
  renderTags();
}

function removeTag(i) {
  tags.splice(i, 1);
  renderTags();
}

function renderTags() {
  const wrap = document.getElementById('tagsWrap');
  // Remove existing chips
  wrap.querySelectorAll('.tag-chip').forEach(el => el.remove());
  const input = document.getElementById('tagInput');
  tags.forEach((t, i) => {
    const chip = document.createElement('span');
    chip.className = 'tag-chip';
    chip.innerHTML = `${t}<button type="button" onclick="removeTag(${i})">×</button>`;
    wrap.insertBefore(chip, input);
  });
  document.getElementById('tagsHidden').value = tags.join(',');
}

// ═══════════════════════════════════════════════════════
//  COLOR PICKER
// ═══════════════════════════════════════════════════════
let colors        = [];   // [{ hex: '#ff0000', name: 'Red' }, ...]
let editingIndex  = null;
let currentHex    = '#000000';

function openColorModal(index) {
  editingIndex = index;
  const modal  = document.getElementById('colorModalOverlay');
  const title  = document.getElementById('colorModalTitle');

  if (index !== null && colors[index]) {
    currentHex = colors[index].hex;
    document.getElementById('colorNameInput').value = colors[index].name;
    title.textContent = 'Edit Color';
  } else {
    currentHex = '#3b82f6';
    document.getElementById('colorNameInput').value = '';
    title.textContent = 'Add Color';
  }

  syncUI(currentHex);
  modal.classList.add('open');
}

function closeColorModal() {
  document.getElementById('colorModalOverlay').classList.remove('open');
  editingIndex = null;
}

function syncColorFromNative(hex) {
  currentHex = hex;
  document.getElementById('hexInput').value = hex.replace('#','');
  document.getElementById('colorPreviewBox').style.background = hex;
}

function syncColorFromHex(val) {
  const full = val.length === 3
    ? '#' + val.split('').map(c=>c+c).join('')
    : '#' + val;
  if (/^#[0-9a-fA-F]{6}$/.test(full)) {
    currentHex = full;
    document.getElementById('nativeColorPicker').value = full;
    document.getElementById('colorPreviewBox').style.background = full;
  }
}

function syncUI(hex) {
  document.getElementById('nativeColorPicker').value = hex;
  document.getElementById('hexInput').value = hex.replace('#','');
  document.getElementById('colorPreviewBox').style.background = hex;
}

function confirmColor() {
  const name = document.getElementById('colorNameInput').value.trim() || currentHex;
  const entry = { hex: currentHex, name };

  if (editingIndex !== null) {
    colors[editingIndex] = entry;
  } else {
    colors.push(entry);
  }

  renderColors();
  closeColorModal();
}

function removeColor(i) {
  colors.splice(i, 1);
  renderColors();
}

function renderColors() {
  const list = document.getElementById('colorList');
  list.innerHTML = '';
  colors.forEach((c, i) => {
    const div = document.createElement('div');
    div.className = 'color-item';
    div.innerHTML = `
      <div class="color-swatch" style="background:${c.hex}" onclick="openColorModal(${i})" title="${c.name}">
        <span class="remove-color" onclick="event.stopPropagation();removeColor(${i})">✕</span>
      </div>
      <span class="color-name">${c.name}</span>`;
    list.appendChild(div);
  });
  document.getElementById('colorsHidden').value = JSON.stringify(colors);
}

// Close modal on overlay click
document.getElementById('colorModalOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeColorModal();
});

// ═══════════════════════════════════════════════════════
//  FORM SUBMIT — attach dynamically selected files
// ═══════════════════════════════════════════════════════
document.getElementById('productForm').addEventListener('submit', function(e) {
  // Sync hidden fields before submit
  document.getElementById('tagsHidden').value   = tags.join(',');
  document.getElementById('colorsHidden').value  = JSON.stringify(colors);

  // Attach selected files to the file input via DataTransfer
  if (selectedFiles.length) {
    const dt = new DataTransfer();
    selectedFiles.forEach(f => dt.items.add(f));
    document.getElementById('imageInput').files = dt.files;
  }
});
</script>
</body>
</html>