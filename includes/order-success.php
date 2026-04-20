<?php
session_start();
include '../config/db.php';

$orderId = $_GET['id'] ?? 0;
$userId = $_SESSION['user_id'] ?? 0;

// Fetch order details
$orderRes = pg_query_params($conn,
    "SELECT o.*, 
            (SELECT json_agg(json_build_object('name', oi.product_name, 'qty', oi.quantity, 'price', oi.price, 'image', oi.image_url))
             FROM order_items oi WHERE oi.order_id = o.id) as items
     FROM orders o 
     WHERE o.id = $1 AND o.user_id = $2",
    [$orderId, $userId]
);
$order = pg_fetch_assoc($orderRes);

if (!$order) {
    header('Location: ../user/dashboard.php');
    exit;
}

$items = json_decode($order['items'] ?? '[]', true);
$shipping = json_decode($order['address'] ?? '{}', true);
$paymentMethod = $order['payment_method'] ?? 'cod';

$methodLabels = [
    'cod' => 'Cash on Delivery',
    'gcash' => 'GCash',
    'maya' => 'Maya',
    'bank' => 'Bank Transfer',
    'card' => 'Credit/Debit Card'
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; background: #f9fafb; }</style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-xl p-8">
        <!-- Success header -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">Order Confirmed!</h1>
            <p class="text-gray-500 mt-2">Thank you for your purchase. Your order is being processed.</p>
        </div>

        <!-- Order details card -->
        <div class="bg-gray-50 rounded-xl p-6 mb-6">
            <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                <span class="text-gray-600">Order #</span>
                <span class="font-mono font-bold text-lg"><?= str_pad($order['id'], 6, '0', STR_PAD_LEFT) ?></span>
            </div>
            <div class="grid grid-cols-2 gap-4 py-4 border-b border-gray-200">
                <div>
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Date</span>
                    <p class="font-medium"><?= date('M d, Y', strtotime($order['created_at'])) ?></p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Payment Method</span>
                    <p class="font-medium"><?= $methodLabels[$paymentMethod] ?? 'Cash on Delivery' ?></p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Status</span>
                    <p class="font-medium">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </p>
                </div>
                <div>
                    <span class="text-xs text-gray-400 uppercase tracking-wider">Total</span>
                    <p class="font-bold text-indigo-600 text-xl">₱<?= number_format($order['total'], 2) ?></p>
                </div>
            </div>

            <!-- Shipping address -->
            <div class="py-4 border-b border-gray-200">
                <span class="text-xs text-gray-400 uppercase tracking-wider">Shipping Address</span>
                <p class="mt-1 text-gray-700">
                    <?= htmlspecialchars($shipping['first_name'] . ' ' . $shipping['last_name']) ?><br>
                    <?= htmlspecialchars($shipping['address']) ?><br>
                    <?= htmlspecialchars($shipping['city'] . ', ' . $shipping['state'] . ' ' . $shipping['postal_code']) ?><br>
                    <?= htmlspecialchars($shipping['phone']) ?>
                </p>
            </div>

            <!-- Items -->
            <div class="pt-4">
                <span class="text-xs text-gray-400 uppercase tracking-wider">Items</span>
                <div class="mt-3 space-y-3">
                    <?php foreach ($items as $item): ?>
                    <div class="flex items-center gap-4">
                        <img src="/ecommerce-system/imgs/products/<?= htmlspecialchars($item['image']) ?>" 
                             class="w-14 h-14 object-contain bg-white rounded-lg border p-1">
                        <div class="flex-1">
                            <p class="font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
                            <p class="text-sm text-gray-500">Qty: <?= $item['qty'] ?> × ₱<?= number_format($item['price'], 2) ?></p>
                        </div>
                        <p class="font-semibold">₱<?= number_format($item['price'] * $item['qty'], 2) ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Estimated delivery -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                </svg>
                <div>
                    <p class="font-semibold text-gray-800">Estimated Delivery</p>
                    <p class="text-sm text-gray-600"><?= date('M d', strtotime('+3 days')) ?> – <?= date('M d', strtotime('+5 days')) ?></p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="flex gap-4">
            <a href="../user/profile.php?tab=orders" class="flex-1 text-center bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium py-3 rounded-xl transition">
                View My Orders
            </a>
            <a href="../user/dashboard.php" class="flex-1 text-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 rounded-xl transition">
                Continue Shopping
            </a>
        </div>
    </div>
</body>
</html>