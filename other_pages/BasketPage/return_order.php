<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

$userId = currentUserId();
if (!isset($_SESSION['authenticated']) || $userId === null) {
    header('Location: ../LoginPage/login_page.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['return_button'], $_POST['id'], $_POST['tracking_no'])) {
    header('Location: my_orders.php');
    exit;
}

$orderId = (int)$_POST['id'];
$trackingNo = trim((string)$_POST['tracking_no']);

if ($orderId <= 0 || $trackingNo === '') {
    $_SESSION['message'] = 'Invalid return request.';
    $_SESSION['alert_type'] = 'error';
    header('Location: my_orders.php');
    exit;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $orderStmt = $pdo->prepare(
        'SELECT id, status
         FROM orders
         WHERE id = :id AND tracking_no = :t AND user_id = :uid
         LIMIT 1'
    );
    $orderStmt->execute(['id' => $orderId, 't' => $trackingNo, 'uid' => $userId]);
    $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

    if ($order === false) {
        throw new RuntimeException('Order not found.');
    }

    $status = (int)($order['status'] ?? -1);
    if ($status !== 2) {
        throw new RuntimeException('Only delivered orders can be returned.');
    }

    $itemsStmt = $pdo->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = :oid');
    $itemsStmt->execute(['oid' => $orderId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) === 0) {
        throw new RuntimeException('Order items not found.');
    }

    $updateProduct = $pdo->prepare('UPDATE product SET quantity = quantity + :q WHERE product_id = :pid');
    foreach ($items as $item) {
        $pid = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }
        $updateProduct->execute(['q' => $qty, 'pid' => $pid]);
    }

    $updateOrder = $pdo->prepare('UPDATE orders SET status = 4 WHERE id = :id');
    $updateOrder->execute(['id' => $orderId]);

    $pdo->commit();

    $_SESSION['message'] = 'Order returned successfully.';
    $_SESSION['alert_type'] = 'success';
    header('Location: view_order.php?t=' . rawurlencode($trackingNo));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = $e instanceof RuntimeException ? $e->getMessage() : 'Failed to return order.';
    $_SESSION['message'] = $message;
    $_SESSION['alert_type'] = 'error';
    header('Location: view_order.php?t=' . rawurlencode($trackingNo));
    exit;
}

