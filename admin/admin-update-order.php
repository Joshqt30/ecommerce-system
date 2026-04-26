<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
include '../config/db.php';

$id = (int)($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($action === 'payment') {
    $pstatus = ($_POST['payment_status'] === 'paid') ? 'paid' : 'unpaid';
    $res = pg_query_params($conn, "UPDATE orders SET payment_status = $1 WHERE id = $2", [$pstatus, $id]);
    echo json_encode(['success' => (bool)$res]);
    exit;
}

if ($action === 'status') {
    $newStatus = $_POST['status'] ?? '';
    $allowed = ['pending', 'shipped', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }
    $res = pg_query_params($conn, "UPDATE orders SET status = $1 WHERE id = $2", [$newStatus, $id]);
    echo json_encode(['success' => (bool)$res]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);