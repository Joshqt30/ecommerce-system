<?php
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Get cart and shipping data
$cartData = json_decode($_POST['cart_data'] ?? '[]', true);
$shippingData = json_decode($_POST['shipping_data'] ?? '{}', true);
$paymentMethod = $_POST['payment_method'] ?? 'cod';

if (empty($cartData)) {
    die("Cart is empty.");
}

// Calculate total
pg_query($conn, "BEGIN");

// 1. Calculate total
$total = 0;
foreach ($cartData as $item) {
    $total += $item['price'] * $item['quantity'];
}

// 2. Create order FIRST
$orderRes = pg_query_params(
    $conn,
    "INSERT INTO orders (user_id, total, status, address, payment_method)
     VALUES ($1, $2, 'pending', $3, $4)
     RETURNING id",
    [$userId, $total, json_encode($shippingData), $paymentMethod]
);

if (!$orderRes) {
    pg_query($conn, "ROLLBACK");
    die("Order insert failed: " . pg_last_error($conn));
}

$orderRow = pg_fetch_assoc($orderRes);
$orderId = $orderRow['id'];

// 3. Now process cart items
foreach ($cartData as $item) {

    $productId = $item['productId'];
    $qty = $item['quantity'];

    // Reduce stock
    $updateStock = pg_query_params(
        $conn,
        "UPDATE products 
         SET stock = stock - $1 
         WHERE id = $2 AND stock >= $1",
        [$qty, $productId]
    );

    if (pg_affected_rows($updateStock) === 0) {
        pg_query($conn, "ROLLBACK");
        die("Insufficient stock for product ID: $productId");
    }

    // Insert order item (NOW orderId exists)
    pg_query_params(
        $conn,
        "INSERT INTO order_items 
        (order_id, variant_id, product_name, price, quantity, image_url)
        VALUES ($1, $2, $3, $4, $5, $6)",
        [
            $orderId,
            $item['variantId'] ?? null,
            $item['name'],
            $item['price'],
            $qty,
            $item['image']
        ]
    );
}

// 4. Clear cart
pg_query_params($conn, "DELETE FROM cart WHERE user_id = $1", [$userId]);

// 5. Commit
pg_query($conn, "COMMIT");

// redirect
header("Location: order-success.php?id=" . $orderId);
exit;
?>