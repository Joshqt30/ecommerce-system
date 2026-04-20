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
// $dsn = "pgsql:host=localhost;port=5432;dbname=your_db";
// try {
//     $pdo = new PDO($dsn, 'your_user', 'your_password', [
//         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//     ]);
// } catch (PDOException $e) {
//     http_response_code(500);
//     die('DB connection failed.');
// }

// ═══════════════════════════════════════════════════════
//  HANDLE AJAX ACTIONS (archive / edit)
// ═══════════════════════════════════════════════════════
if (isset($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    // -- Archive customer --
    // if ($action === 'archive') {
    //     $id   = intval($_POST['id']);
    //     $stmt = $pdo->prepare("UPDATE users SET status = 'archived', archived_at = NOW() WHERE id = :id");
    //     $stmt->execute([':id' => $id]);
    //     echo json_encode(['success' => true]);
    //     exit;
    // }

    // -- Update customer --
    // if ($action === 'update') {
    //     $id     = intval($_POST['id']);
    //     $name   = trim($_POST['name']   ?? '');
    //     $email  = trim($_POST['email']  ?? '');
    //     $phone  = trim($_POST['phone']  ?? '');
    //     $status = trim($_POST['status'] ?? '');
    //     $notes  = trim($_POST['notes']  ?? '');
    //     $allowed_statuses = ['active', 'inactive', 'vip'];
    //     if (!in_array($status, $allowed_statuses)) { echo json_encode(['success'=>false,'error'=>'Invalid status']); exit; }
    //     $stmt = $pdo->prepare("
    //         UPDATE users SET name=:name, email=:email, phone=:phone, status=:status, admin_notes=:notes, updated_at=NOW()
    //         WHERE id=:id
    //     ");
    //     $stmt->execute([':name'=>$name,':email'=>$email,':phone'=>$phone,':status'=>$status,':notes'=>$notes,':id'=>$id]);
    //     echo json_encode(['success' => true]);
    //     exit;
    // }

    // -- Get single customer details --
    // if ($action === 'get' && isset($_GET['id'])) {
    //     $id   = intval($_GET['id']);
    //     $stmt = $pdo->prepare("
    //         SELECT u.*, COUNT(o.id) as order_count,
    //             SUM(CASE WHEN o.status='completed' THEN 1 ELSE 0 END) as completed_orders,
    //             SUM(CASE WHEN o.status='cancelled' THEN 1 ELSE 0 END) as cancelled_orders
    //         FROM users u
    //         LEFT JOIN orders o ON o.user_id = u.id
    //         WHERE u.id = :id AND u.status != 'archived'
    //         GROUP BY u.id
    //     ");
    //     $stmt->execute([':id' => $id]);
    //     $customer = $stmt->fetch();
    //     echo json_encode($customer ?: ['error' => 'Not found']);
    //     exit;
    // }

    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

// ═══════════════════════════════════════════════════════
//  LIST QUERY
// ═══════════════════════════════════════════════════════
$search      = trim($_GET['search'] ?? '');
$filterStatus= $_GET['status'] ?? 'all';
$page        = max(1, intval($_GET['page'] ?? 1));
$perPage     = 10;
$offset      = ($page - 1) * $perPage;

// -- Real query --
// $where  = "WHERE u.status != 'archived'";
// $params = [];
// if ($filterStatus !== 'all') {
//     $where .= " AND u.status = :status";
//     $params[':status'] = $filterStatus;
// }
// if (!empty($search)) {
//     $where .= " AND (u.name ILIKE :search OR u.email ILIKE :search OR u.customer_id ILIKE :search)";
//     $params[':search'] = '%' . $search . '%';
// }
// $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
// $countStmt->execute($params);
// $total = $countStmt->fetchColumn();
//
// $params[':limit']  = $perPage;
// $params[':offset'] = $offset;
// $stmt = $pdo->prepare("
//     SELECT u.id, u.customer_id, u.name, u.email, u.phone, u.status,
//            u.created_at, u.last_login,
//            COUNT(o.id) as order_count,
//            u.admin_notes
//     FROM users u
//     LEFT JOIN orders o ON o.user_id = u.id
//     $where
//     GROUP BY u.id
//     ORDER BY u.created_at DESC
//     LIMIT :limit OFFSET :offset
// ");
// $stmt->execute($params);
// $customers = $stmt->fetchAll();

// -- Static placeholder data --
$allCustomers = [
    ['id'=>1,'customer_id'=>'CUST001','name'=>'John Doe',      'email'=>'john.doe@example.com',    'phone'=>'+1234567890','order_count'=>25,'status'=>'active',  'created_at'=>'2025-01-15','last_login'=>'2025-01-10','admin_notes'=>'','address'=>'123 Main St, NY'],
    ['id'=>2,'customer_id'=>'CUST002','name'=>'Jane Smith',    'email'=>'jane.smith@example.com',  'phone'=>'+1234567891','order_count'=>5, 'status'=>'inactive','created_at'=>'2025-01-16','last_login'=>'2024-12-01','admin_notes'=>'','address'=>'456 Oak Ave, LA'],
    ['id'=>3,'customer_id'=>'CUST003','name'=>'Emily Davis',   'email'=>'emily.d@example.com',     'phone'=>'+1234567892','order_count'=>30,'status'=>'vip',    'created_at'=>'2025-01-17','last_login'=>'2025-01-14','admin_notes'=>'High-value customer.','address'=>'789 Pine Rd, SF'],
    ['id'=>4,'customer_id'=>'CUST004','name'=>'Carlos Reyes',  'email'=>'c.reyes@example.com',     'phone'=>'+1234567893','order_count'=>12,'status'=>'active',  'created_at'=>'2025-01-18','last_login'=>'2025-01-09','admin_notes'=>'','address'=>'321 Elm St, TX'],
    ['id'=>5,'customer_id'=>'CUST005','name'=>'Maria Santos',  'email'=>'m.santos@example.com',    'phone'=>'+1234567894','order_count'=>8, 'status'=>'active',  'created_at'=>'2025-01-19','last_login'=>'2025-01-11','admin_notes'=>'','address'=>'654 Maple Dr, FL'],
    ['id'=>6,'customer_id'=>'CUST006','name'=>'David Kim',     'email'=>'d.kim@example.com',       'phone'=>'+1234567895','order_count'=>41,'status'=>'vip',    'created_at'=>'2025-01-20','last_login'=>'2025-01-13','admin_notes'=>'Prefers express shipping.','address'=>'987 Cedar Ln, WA'],
    ['id'=>7,'customer_id'=>'CUST007','name'=>'Sophie Turner', 'email'=>'s.turner@example.com',    'phone'=>'+1234567896','order_count'=>3, 'status'=>'inactive','created_at'=>'2025-01-21','last_login'=>'2024-11-20','admin_notes'=>'','address'=>'111 Birch Blvd, OR'],
    ['id'=>8,'customer_id'=>'CUST008','name'=>'Liam Johnson',  'email'=>'l.johnson@example.com',   'phone'=>'+1234567897','order_count'=>19,'status'=>'active',  'created_at'=>'2025-01-22','last_login'=>'2025-01-12','admin_notes'=>'','address'=>'222 Spruce Way, CO'],
    ['id'=>9,'customer_id'=>'CUST009','name'=>'Aisha Patel',   'email'=>'a.patel@example.com',     'phone'=>'+1234567898','order_count'=>7, 'status'=>'active',  'created_at'=>'2025-01-23','last_login'=>'2025-01-08','admin_notes'=>'','address'=>'333 Walnut St, IL'],
    ['id'=>10,'customer_id'=>'CUST010','name'=>'Tom Wilson',   'email'=>'t.wilson@example.com',    'phone'=>'+1234567899','order_count'=>55,'status'=>'vip',    'created_at'=>'2025-01-24','last_login'=>'2025-01-15','admin_notes'=>'VIP since day one.','address'=>'444 Ash Ave, GA'],
];

// Client-side filter for demo
$filtered = array_filter($allCustomers, function($c) use ($filterStatus, $search) {
    if ($filterStatus !== 'all' && $c['status'] !== $filterStatus) return false;
    if ($search && stripos($c['name'], $search) === false
               && stripos($c['email'], $search) === false
               && stripos($c['customer_id'], $search) === false) return false;
    return true;
});
$filtered = array_values($filtered);

$totalAll      = count($allCustomers);
$totalActive   = count(array_filter($allCustomers, fn($c) => $c['status'] === 'active'));
$totalInactive = count(array_filter($allCustomers, fn($c) => $c['status'] === 'inactive'));
$totalVip      = count(array_filter($allCustomers, fn($c) => $c['status'] === 'vip'));

$total      = count($filtered);
$totalPages = max(1, ceil($total / $perPage));
$paged      = array_slice($filtered, $offset, $perPage);

$adminName = $_SESSION['admin_name'] ?? 'Admin';

function statusStyle(string $s): array {
    return match($s) {
        'active'   => ['status-active',   'Active'],
        'vip'      => ['status-vip',      'VIP'],
        'inactive' => ['status-inactive', 'Inactive'],
        default    => ['status-inactive', ucfirst($s)],
    };
}

// Pass all customers to JS for the detail panel (in real app, use AJAX /get endpoint)
$customersJson = json_encode($allCustomers);
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

    /* ── Shell ──────────────────────────────────────── */
    .admin-shell { display: grid; grid-template-columns: 180px 1fr; min-height: 100vh; }

    /* ── Sidebar ────────────────────────────────────── */
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
    .sidebar-logo img { width: 34px; height: 34px; object-fit: contain; border-radius: 8px; }
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

    /* ── Content area ───────────────────────────────── */
    .content-area {
      display: grid;
      grid-template-columns: 1fr 0px;
      transition: grid-template-columns .3s cubic-bezier(.4,0,.2,1);
      overflow: hidden;
      min-height: 100vh;
    }
    .content-area.panel-open {
      grid-template-columns: 1fr 320px;
    }

    /* ── Main ───────────────────────────────────────── */
    .main-content {
      display: flex; flex-direction: column;
      padding: 28px 24px 48px; gap: 22px;
      overflow-y: auto; min-width: 0;
    }

    /* ── Page header ────────────────────────────────── */
    .page-header { display: flex; align-items: center; justify-content: space-between; }
    .page-title { font-size: 22px; font-weight: 700; letter-spacing: -.4px; }

    .btn-admin {
      display: flex; align-items: center; gap: 9px; padding: 9px 18px;
      background: var(--white); border: 1.5px solid var(--border); border-radius: 12px;
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
    }
    .btn-admin:hover { background: #f5f5f5; }
    .btn-admin svg { width: 17px; height: 17px; }

    /* ── Table card ─────────────────────────────────── */
    .table-card {
      background: var(--white); border-radius: var(--radius);
      box-shadow: var(--shadow); border: 1px solid var(--border); overflow: hidden;
    }

    /* ── Toolbar ────────────────────────────────────── */
    .toolbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 16px 20px; border-bottom: 1px solid var(--border);
      gap: 12px; flex-wrap: wrap;
    }

    .filter-tabs { display: flex; align-items: center; gap: 4px; }

    .filter-tab {
      padding: 6px 14px; border-radius: 8px; font-family: var(--font);
      font-size: 12px; font-weight: 500; border: none;
      background: transparent; color: var(--muted); cursor: pointer;
      transition: background .15s, color .15s; text-decoration: none; white-space: nowrap;
    }
    .filter-tab .tab-count { font-size: 11px; color: var(--muted); margin-left: 3px; }
    .filter-tab:hover { background: #f5f5f5; color: var(--text); }
    .filter-tab.active { background: #f0fdf4; color: #16a34a; font-weight: 600; }
    .filter-tab.active .tab-count { color: #16a34a; }

    .toolbar-right { display: flex; align-items: center; gap: 8px; }

    .search-wrap {
      display: flex; align-items: center; gap: 8px; padding: 7px 14px;
      border: 1.5px solid var(--border); border-radius: 10px;
      background: #fafafa; transition: border-color .18s;
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

    /* ── Table ──────────────────────────────────────── */
    .cust-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .cust-table thead tr { background: #f9fafb; border-bottom: 1px solid var(--border); }
    .cust-table th {
      padding: 12px 18px; text-align: left; font-size: 11px; font-weight: 700;
      text-transform: uppercase; letter-spacing: .06em; color: var(--muted); white-space: nowrap;
    }
    .cust-table td {
      padding: 13px 18px; border-bottom: 1px solid #f5f5f5;
      color: var(--text); vertical-align: middle;
    }
    .cust-table tbody tr:last-child td { border-bottom: none; }
    .cust-table tbody tr { transition: background .12s; cursor: pointer; }
    .cust-table tbody tr:hover { background: #f5fffe; }
    .cust-table tbody tr.selected { background: #f0fdf4; }

    .col-id    { color: var(--muted); font-family: monospace; font-size: 12px; }
    .col-email { color: var(--muted); font-size: 12px; }
    .col-count { font-weight: 600; }

    /* Avatar + name */
    .name-cell { display: flex; align-items: center; gap: 10px; }
    .avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: #e0f2fe; color: #0369a1;
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; font-weight: 700; flex-shrink: 0;
      text-transform: uppercase;
    }
    .avatar.vip-av    { background: #fef9c3; color: #92400e; }
    .avatar.inactive-av { background: #f3f4f6; color: #9ca3af; }

    /* Status badges */
    .status-badge {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 4px 11px; border-radius: 20px;
      font-size: 11px; font-weight: 600; white-space: nowrap;
    }
    .status-badge::before {
      content: ''; width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0;
    }
    .status-active   { background: #f0fdf4; color: #16a34a; }
    .status-active::before   { background: #16a34a; }
    .status-vip      { background: #fef9c3; color: #92400e; }
    .status-vip::before      { background: #d97706; }
    .status-inactive { background: #f3f4f6; color: #6b7280; }
    .status-inactive::before { background: #9ca3af; }

    /* Row actions */
    .row-actions { display: flex; align-items: center; gap: 6px; }
    .action-btn {
      width: 28px; height: 28px; border-radius: 7px;
      border: 1.5px solid var(--border); background: var(--white);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted);
      transition: background .15s, color .15s, border-color .15s;
    }
    .action-btn:hover { background: #f5f5f5; color: var(--text); }
    .action-btn.archive:hover { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .action-btn svg { width: 13px; height: 13px; }

    /* Empty state */
    .empty-state { padding: 60px 20px; text-align: center; color: var(--muted); font-size: 14px; }

    /* ── Pagination ─────────────────────────────────── */
    .pagination-wrap {
      display: flex; justify-content: space-between; align-items: center;
      padding: 16px 20px; border-top: 1px solid var(--border);
    }
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

    /* ── Detail panel ───────────────────────────────── */
    .detail-panel {
      background: var(--white); border-left: 1px solid var(--border);
      overflow-y: auto; overflow-x: hidden;
      width: 320px;
      opacity: 0; pointer-events: none;
      transition: opacity .25s;
    }
    .content-area.panel-open .detail-panel {
      opacity: 1; pointer-events: auto;
    }

    .panel-header {
      display: flex; align-items: center; justify-content: space-between;
      padding: 20px 20px 0;
    }
    .panel-close {
      width: 28px; height: 28px; border-radius: 8px;
      border: 1.5px solid var(--border); background: var(--white);
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; color: var(--muted); transition: background .15s;
      flex-shrink: 0;
    }
    .panel-close:hover { background: #f5f5f5; color: var(--text); }
    .panel-close svg { width: 13px; height: 13px; }

    /* Avatar section */
    .panel-avatar-section {
      display: flex; align-items: center; gap: 14px;
      padding: 20px 20px 0;
    }
    .panel-avatar {
      width: 52px; height: 52px; border-radius: 50%;
      background: #e0f2fe; color: #0369a1;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; font-weight: 700; flex-shrink: 0;
      text-transform: uppercase;
    }
    .panel-avatar.vip-av    { background: #fef9c3; color: #92400e; }
    .panel-avatar.inactive-av { background: #f3f4f6; color: #9ca3af; }
    .panel-cust-name { font-size: 15px; font-weight: 700; line-height: 1.3; }
    .panel-cust-id   { font-size: 11px; color: var(--muted); font-family: monospace; margin-top: 2px; }

    /* Panel sections */
    .panel-section { padding: 18px 20px; border-bottom: 1px solid var(--border); }
    .panel-section:last-child { border-bottom: none; }
    .panel-section-title {
      font-size: 10px; font-weight: 700; text-transform: uppercase;
      letter-spacing: .08em; color: var(--muted); margin-bottom: 12px;
    }

    /* Edit fields */
    .panel-field { margin-bottom: 12px; }
    .panel-field:last-child { margin-bottom: 0; }
    .panel-field-label { font-size: 10px; font-weight: 600; color: var(--muted); margin-bottom: 5px; text-transform: uppercase; letter-spacing:.05em; }

    .panel-input {
      width: 100%; font-family: var(--font); font-size: 12px; color: var(--text);
      background: #fafafa; border: 1.5px solid var(--border);
      border-radius: 8px; padding: 8px 11px; outline: none;
      transition: border-color .18s, background .18s;
    }
    .panel-input:focus { border-color: #888; background: #fff; }

    .panel-select-wrap { position: relative; }
    .panel-select-wrap svg.pchev {
      position: absolute; right: 9px; top: 50%; transform: translateY(-50%);
      width: 11px; height: 11px; color: var(--muted); pointer-events: none;
    }
    .panel-select {
      width: 100%; font-family: var(--font); font-size: 12px; color: var(--text);
      background: #fafafa; border: 1.5px solid var(--border);
      border-radius: 8px; padding: 8px 28px 8px 11px;
      appearance: none; outline: none; cursor: pointer;
      transition: border-color .18s;
    }
    .panel-select:focus { border-color: #888; background: #fff; }

    .panel-textarea {
      width: 100%; font-family: var(--font); font-size: 12px; color: var(--text);
      background: #fafafa; border: 1.5px solid var(--border);
      border-radius: 8px; padding: 8px 11px; outline: none; resize: vertical;
      min-height: 72px; line-height: 1.5; transition: border-color .18s;
    }
    .panel-textarea:focus { border-color: #888; background: #fff; }

    /* Order overview mini cards */
    .order-overview { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
    .order-stat {
      background: #f9fafb; border-radius: 10px; padding: 12px 10px;
      text-align: center; border: 1px solid var(--border);
    }
    .order-stat-val  { font-size: 18px; font-weight: 700; }
    .order-stat-lbl  { font-size: 9px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing:.05em; margin-top: 3px; }

    /* Activity rows */
    .activity-row { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 8px; }
    .activity-row:last-child { margin-bottom: 0; }
    .activity-row .alabel { color: var(--muted); }
    .activity-row .aval   { font-weight: 500; }

    /* Save button */
    .panel-save-btn {
      width: 100%; padding: 10px; border-radius: 10px;
      border: none; background: var(--blue); color: #fff;
      font-family: var(--font); font-size: 13px; font-weight: 600;
      cursor: pointer; transition: background .15s;
      display: flex; align-items: center; justify-content: center; gap: 7px;
    }
    .panel-save-btn:hover { background: #1d4ed8; }
    .panel-save-btn svg { width: 14px; height: 14px; }

    .panel-archive-btn {
      width: 100%; padding: 9px; border-radius: 10px; margin-top: 8px;
      border: 1.5px solid #fde68a; background: #fffbeb; color: #92400e;
      font-family: var(--font); font-size: 12px; font-weight: 600;
      cursor: pointer; transition: background .15s;
      display: flex; align-items: center; justify-content: center; gap: 7px;
    }
    .panel-archive-btn:hover { background: #fef3c7; }
    .panel-archive-btn svg { width: 13px; height: 13px; }

    /* ── Toast ──────────────────────────────────────── */
    .toast {
      position: fixed; bottom: 28px; right: 28px;
      background: #1a1a1a; color: #fff;
      padding: 13px 20px; border-radius: 10px;
      font-size: 13px; font-weight: 500;
      display: flex; align-items: center; gap: 10px;
      box-shadow: 0 8px 28px rgba(0,0,0,0.16);
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
  <aside class="sidebar">
    <a href="admin-dashboard.php" class="sidebar-logo">
      <img src="../imgs/icons/ecommercelogo.png" alt="logo"/>
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
    <a href="admin-products.php" class="nav-item">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
      Product List
    </a>
    <a href="admin-customers.php" class="nav-item active">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
      Customers
    </a>
  </aside>

  <!-- ── Content area (table + panel) ─────────────── -->
  <div class="content-area" id="contentArea">

    <!-- Main -->
    <main class="main-content">

      <div class="page-header">
        <h1 class="page-title">Customers</h1>
        <button class="btn-admin">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
          <?= htmlspecialchars($adminName) ?>
        </button>
      </div>

      <div class="table-card">
        <!-- Toolbar -->
        <div class="toolbar">
          <div class="filter-tabs">
            <?php
            $tabs = [
              'all'      => ['All Customers', $totalAll],
              'active'   => ['Active',        $totalActive],
              'vip'      => ['VIP',           $totalVip],
              'inactive' => ['Inactive',      $totalInactive],
            ];
            foreach ($tabs as $key => [$label, $count]):
              $active = $filterStatus === $key ? 'active' : '';
              $qs = http_build_query(['status'=>$key,'search'=>$search,'page'=>1]);
            ?>
              <a href="?<?= $qs ?>" class="filter-tab <?= $active ?>">
                <?= $label ?><span class="tab-count">(<?= $count ?>)</span>
              </a>
            <?php endforeach; ?>
          </div>
          <div class="toolbar-right">
            <form method="GET" style="display:contents">
              <input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>">
              <div class="search-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" name="search" placeholder="Search by name, email, ID…"
                       value="<?= htmlspecialchars($search) ?>" onchange="this.form.submit()"/>
              </div>
            </form>
            <button class="icon-btn" title="Export" onclick="showToast('Export coming soon.')">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </button>
          </div>
        </div>

        <!-- Table -->
        <table class="cust-table">
          <thead>
            <tr>
              <th>Customer ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Orders</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($paged)): ?>
              <tr><td colspan="7"><div class="empty-state">No customers found.</div></td></tr>
            <?php else: ?>
              <?php foreach ($paged as $c):
                [$sCls, $sLabel] = statusStyle($c['status']);
                $avCls = $c['status'] === 'vip' ? 'vip-av' : ($c['status'] === 'inactive' ? 'inactive-av' : '');
                $initial = strtoupper(substr($c['name'], 0, 1));
              ?>
              <tr onclick="openPanel(<?= $c['id'] ?>)" id="row-<?= $c['id'] ?>">
                <td class="col-id">#<?= htmlspecialchars($c['customer_id']) ?></td>
                <td>
                  <div class="name-cell">
                    <div class="avatar <?= $avCls ?>"><?= $initial ?></div>
                    <span><?= htmlspecialchars($c['name']) ?></span>
                  </div>
                </td>
                <td class="col-email"><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['phone']) ?></td>
                <td class="col-count"><?= $c['order_count'] ?></td>
                <td><span class="status-badge <?= $sCls ?>"><?= $sLabel ?></span></td>
                <td>
                  <div class="row-actions" onclick="event.stopPropagation()">
                    <button class="action-btn" title="Message customer"
                            onclick="showToast('Message customer <?= htmlspecialchars($c['name']) ?> (coming soon)')">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </button>
                    <button class="action-btn archive" title="Archive customer"
                            onclick="confirmArchive(<?= $c['id'] ?>, '<?= addslashes(htmlspecialchars($c['name'])) ?>')">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>

        <!-- Pagination -->
        <div class="pagination-wrap">
          <span class="pagination-info">
            Showing <?= $total === 0 ? 0 : $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?> customers
          </span>
          <div class="pagination-btns">
            <?php
            $prevQs = http_build_query(array_merge($_GET, ['page' => $page - 1]));
            $nextQs = http_build_query(array_merge($_GET, ['page' => $page + 1]));
            ?>
            <a href="<?= $page > 1 ? '?'.$prevQs : '#' ?>"
               class="page-btn <?= $page <= 1 ? 'disabled' : '' ?>">← Prev</a>

            <?php for ($p = 1; $p <= $totalPages; $p++):
              $pQs = http_build_query(array_merge($_GET, ['page' => $p]));
              if ($totalPages > 7 && abs($p - $page) > 1 && $p !== 1 && $p !== $totalPages):
                if ($p === $page - 2 || $p === $page + 2) echo '<span class="page-ellipsis">…</span>';
                continue;
              endif;
            ?>
              <a href="?<?= $pQs ?>" class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>

            <a href="<?= $page < $totalPages ? '?'.$nextQs : '#' ?>"
               class="page-btn <?= $page >= $totalPages ? 'disabled' : '' ?>">Next →</a>
          </div>
        </div>
      </div><!-- /table-card -->
    </main>

    <!-- ── Detail panel ────────────────────────────── -->
    <aside class="detail-panel" id="detailPanel">

      <div class="panel-header">
        <span style="font-size:13px;font-weight:700;">Customer Details</span>
        <button class="panel-close" onclick="closePanel()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <!-- Avatar + name -->
      <div class="panel-avatar-section">
        <div class="panel-avatar" id="pAvatar"></div>
        <div>
          <div class="panel-cust-name" id="pName"></div>
          <div class="panel-cust-id"  id="pId"></div>
        </div>
      </div>

      <!-- Editable Info -->
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
        <div class="panel-field">
          <div class="panel-field-label">Account Status</div>
          <div class="panel-select-wrap">
            <select class="panel-select" id="editStatus">
              <option value="active">Active</option>
              <option value="vip">VIP</option>
              <option value="inactive">Inactive</option>
            </select>
            <svg class="pchev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"/></svg>
          </div>
        </div>
      </div>

      <!-- Activity -->
      <div class="panel-section">
        <div class="panel-section-title">Activity</div>
        <div class="activity-row">
          <span class="alabel">Registered</span>
          <span class="aval" id="pRegistered">—</span>
        </div>
        <div class="activity-row">
          <span class="alabel">Last Login</span>
          <span class="aval" id="pLastLogin">—</span>
        </div>
      </div>

      <!-- Order overview -->
      <div class="panel-section">
        <div class="panel-section-title">Order Overview</div>
        <div class="order-overview">
          <div class="order-stat">
            <div class="order-stat-val" id="pOrderCount">—</div>
            <div class="order-stat-lbl">Total</div>
          </div>
          <div class="order-stat">
            <div class="order-stat-val" id="pCompleted" style="color:#16a34a">—</div>
            <div class="order-stat-lbl">Completed</div>
          </div>
          <div class="order-stat">
            <div class="order-stat-val" id="pCancelled" style="color:#dc2626">—</div>
            <div class="order-stat-lbl">Cancelled</div>
          </div>
        </div>
      </div>

      <!-- Admin Notes -->
      <div class="panel-section">
        <div class="panel-section-title">Admin Notes</div>
        <textarea class="panel-textarea" id="editNotes" placeholder="Add internal notes about this customer…"></textarea>
      </div>

      <!-- Actions -->
      <div class="panel-section">
        <button class="panel-save-btn" onclick="saveCustomer()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
          Save Changes
        </button>
        <button class="panel-archive-btn" onclick="archiveFromPanel()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
          Archive Customer
        </button>
      </div>

    </aside>
  </div><!-- /content-area -->
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
  <span id="toastMsg">Done</span>
</div>

<script>
// ── Customer data from PHP ─────────────────────────────
const customers = <?= $customersJson ?>;
// In production, replace with:
// async function fetchCustomer(id) {
//   const res = await fetch(`?action=get&id=${id}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
//   return res.json();
// }

let activeCustId  = null;
let activeRowEl   = null;

// ── Open panel ─────────────────────────────────────────
function openPanel(id) {
  const cust = customers.find(c => c.id === id);
  if (!cust) return;

  activeCustId = id;

  // Highlight row
  document.querySelectorAll('.cust-table tbody tr').forEach(r => r.classList.remove('selected'));
  const row = document.getElementById('row-' + id);
  if (row) { row.classList.add('selected'); activeRowEl = row; }

  // Avatar
  const avEl  = document.getElementById('pAvatar');
  avEl.textContent = cust.name.charAt(0).toUpperCase();
  avEl.className   = 'panel-avatar' + (cust.status === 'vip' ? ' vip-av' : cust.status === 'inactive' ? ' inactive-av' : '');

  // Header
  document.getElementById('pName').textContent = cust.name;
  document.getElementById('pId').textContent   = '#' + cust.customer_id;

  // Editable fields
  document.getElementById('editName').value    = cust.name;
  document.getElementById('editEmail').value   = cust.email;
  document.getElementById('editPhone').value   = cust.phone;
  document.getElementById('editAddress').value = cust.address || '';
  document.getElementById('editStatus').value  = cust.status;
  document.getElementById('editNotes').value   = cust.admin_notes || '';

  // Activity
  document.getElementById('pRegistered').textContent = formatDate(cust.created_at);
  document.getElementById('pLastLogin').textContent  = formatDate(cust.last_login);

  // Order overview (real data comes from JOIN query)
  document.getElementById('pOrderCount').textContent = cust.order_count ?? '—';
  document.getElementById('pCompleted').textContent  = cust.completed_orders ?? Math.floor(cust.order_count * 0.7);
  document.getElementById('pCancelled').textContent  = cust.cancelled_orders ?? Math.floor(cust.order_count * 0.05);

  // Show panel
  document.getElementById('contentArea').classList.add('panel-open');
}

// ── Close panel ────────────────────────────────────────
function closePanel() {
  document.getElementById('contentArea').classList.remove('panel-open');
  if (activeRowEl) activeRowEl.classList.remove('selected');
  activeCustId = null; activeRowEl = null;
}

// ── Save customer ──────────────────────────────────────
async function saveCustomer() {
  if (!activeCustId) return;

  const payload = {
    action:  'update',
    id:      activeCustId,
    name:    document.getElementById('editName').value.trim(),
    email:   document.getElementById('editEmail').value.trim(),
    phone:   document.getElementById('editPhone').value.trim(),
    address: document.getElementById('editAddress').value.trim(),
    status:  document.getElementById('editStatus').value,
    notes:   document.getElementById('editNotes').value.trim(),
  };

  // Update local demo data
  const idx = customers.findIndex(c => c.id === activeCustId);
  if (idx !== -1) {
    Object.assign(customers[idx], { name: payload.name, email: payload.email, phone: payload.phone, status: payload.status, admin_notes: payload.notes });
    // Refresh panel header
    document.getElementById('pName').textContent = payload.name;
    const avEl = document.getElementById('pAvatar');
    avEl.textContent  = payload.name.charAt(0).toUpperCase();
    avEl.className    = 'panel-avatar' + (payload.status === 'vip' ? ' vip-av' : payload.status === 'inactive' ? ' inactive-av' : '');
  }

  // Real AJAX call:
  // const res  = await fetch('admin-customers.php', {
  //   method: 'POST',
  //   headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
  //   body: new URLSearchParams(payload),
  // });
  // const json = await res.json();
  // if (!json.success) { showToast('Error: ' + json.error, true); return; }

  showToast('Customer updated successfully.');
}

// ── Archive ────────────────────────────────────────────
function confirmArchive(id, name) {
  if (!confirm(`Archive "${name}"?\n\nThis will hide them from active listings but preserve all their order history.`)) return;
  doArchive(id, name);
}

function archiveFromPanel() {
  if (!activeCustId) return;
  const cust = customers.find(c => c.id === activeCustId);
  if (cust) confirmArchive(cust.id, cust.name);
}

async function doArchive(id, name) {
  // Real AJAX call:
  // const res  = await fetch('admin-customers.php', {
  //   method: 'POST',
  //   headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
  //   body: new URLSearchParams({ action: 'archive', id }),
  // });
  // const json = await res.json();

  // Demo: remove row
  const row = document.getElementById('row-' + id);
  if (row) row.remove();
  if (activeCustId === id) closePanel();
  showToast(`"${name}" has been archived.`);
}

// ── Helpers ────────────────────────────────────────────
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