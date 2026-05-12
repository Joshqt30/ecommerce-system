<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
include '../config/db.php';
/** @var resource|\PgSql\Connection $conn */

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

    // Fetch current status and cancelled_by
    $curRes = pg_query_params($conn,
        "SELECT status, cancelled_by FROM orders WHERE id = $1", [$id]
    );
    $row = pg_fetch_assoc($curRes);
    $currentStatus = $row['status'];
    $cancelledBy = $row['cancelled_by'];

    // If the order was cancelled by the user, lock it permanently
    if ($cancelledBy === 'user') {
        echo json_encode(['success' => false, 'error' => 'Cannot change a user-cancelled order']);
        exit;
    }

    // If the order is currently cancelled by admin and the new status is not cancelled, that's an "undo" – allowed.
    // When setting to cancelled, record the admin as the canceller.
    $updateFields = "status = $1";
    $params = [$newStatus, $id];
    if ($newStatus === 'cancelled') {
        // If the order isn't already cancelled by user (we already checked), set cancelled_by to admin
        $updateFields = "status = $1, cancelled_by = 'admin'";
        $params = [$newStatus, $id];
    } else {
        // Changing away from cancelled – remove the cancelled_by flag (optional)
        $updateFields = "status = $1, cancelled_by = NULL";
        $params = [$newStatus, $id];
    }

    $res = pg_query_params($conn, "UPDATE orders SET $updateFields WHERE id = $2", $params);
    echo json_encode(['success' => (bool)$res]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Unknown action']);