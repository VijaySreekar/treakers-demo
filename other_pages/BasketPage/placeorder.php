<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

$isAjax = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';

$userId = currentUserId();
if (!isset($_SESSION['authenticated']) || $userId === null) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required.']);
        exit;
    }

    header('Location: ../LoginPage/login_page.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !isset($_POST['placeOrderButton'])) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Invalid request.']);
        exit;
    }

    header('Location: checkout.php');
    exit;
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$pincode = trim((string)($_POST['pincode'] ?? ''));
$address = trim((string)($_POST['address'] ?? ''));
$paymentMode = trim((string)($_POST['payment_mode'] ?? 'Card'));
$paymentId = trim((string)($_POST['payment_id'] ?? ''));

if ($name === '' || $email === '' || $phone === '' || $pincode === '' || $address === '') {
    $message = 'Please fill all the fields.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    $_SESSION['message'] = $message;
    $_SESSION['alert_type'] = 'error';
    header('Location: checkout.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $message = 'Please enter a valid email address.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    $_SESSION['message'] = $message;
    $_SESSION['alert_type'] = 'error';
    header('Location: checkout.php');
    exit;
}

$cartItems = myCartItems();
if (count($cartItems) === 0) {
    $message = 'Your cart is empty.';
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
    $_SESSION['message'] = $message;
    $_SESSION['alert_type'] = 'error';
    header('Location: cart.php');
    exit;
}

function generateTrackingNo(): string
{
    $random = bin2hex(random_bytes(3));
    return 'treakers-' . date('Ymd-His') . '-' . $random;
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // Re-check stock inside the transaction.
    $stockStmt = $pdo->prepare('SELECT quantity FROM product WHERE product_id = :pid LIMIT 1');
    $reserveUpdates = [];
    $totalPrice = 0.0;

    foreach ($cartItems as $item) {
        $productId = (int)($item['product_id'] ?? 0);
        $qty = (int)($item['quantity'] ?? 0);
        $price = (float)($item['discounted_price'] ?? 0);

        if ($productId <= 0 || $qty <= 0) {
            throw new RuntimeException('Invalid cart item.');
        }

        $stockStmt->execute(['pid' => $productId]);
        $currentStock = $stockStmt->fetchColumn();
        $currentStock = $currentStock === false ? 0 : (int)$currentStock;

        if ($currentStock < $qty) {
            $productName = (string)($item['name'] ?? 'Item');
            throw new RuntimeException("Not enough stock for {$productName}.");
        }

        $reserveUpdates[] = ['pid' => $productId, 'new_stock' => $currentStock - $qty];
        $totalPrice += $price * $qty;
    }

    $insertOrder = $pdo->prepare(
        'INSERT INTO orders (tracking_no, user_id, name, email, phone, address, pincode, total_price, payment_mode, payment_id, status)
         VALUES (:tracking_no, :user_id, :name, :email, :phone, :address, :pincode, :total_price, :payment_mode, :payment_id, 0)'
    );

    $trackingNo = '';
    $orderId = 0;
    for ($i = 0; $i < 5; $i++) {
        $trackingNo = generateTrackingNo();
        try {
            $insertOrder->execute([
                'tracking_no' => $trackingNo,
                'user_id' => $userId,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address,
                'pincode' => $pincode,
                'total_price' => $totalPrice,
                'payment_mode' => $paymentMode,
                'payment_id' => $paymentId !== '' ? $paymentId : null,
            ]);
            $orderId = (int)$pdo->lastInsertId();
            break;
        } catch (PDOException $e) {
            // Likely unique tracking_no collision. Try again a few times.
            if ($i === 4) {
                throw $e;
            }
        }
    }

    if ($orderId <= 0 || $trackingNo === '') {
        throw new RuntimeException('Failed to create order.');
    }

    $insertItem = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, quantity, price)
         VALUES (:order_id, :product_id, :quantity, :price)'
    );

    foreach ($cartItems as $item) {
        $insertItem->execute([
            'order_id' => $orderId,
            'product_id' => (int)$item['product_id'],
            'quantity' => (int)$item['quantity'],
            'price' => (float)($item['discounted_price'] ?? 0),
        ]);
    }

    $updateStock = $pdo->prepare('UPDATE product SET quantity = :q WHERE product_id = :pid');
    foreach ($reserveUpdates as $update) {
        $updateStock->execute(['q' => $update['new_stock'], 'pid' => $update['pid']]);
    }

    $clearCart = $pdo->prepare('DELETE FROM cart WHERE user_id = :uid');
    $clearCart->execute(['uid' => $userId]);

    $pdo->commit();

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => 'Order placed.',
            'tracking_no' => $trackingNo,
            'redirect' => 'view_order.php?t=' . rawurlencode($trackingNo),
        ]);
        exit;
    }

    $_SESSION['message'] = 'Order placed successfully!';
    $_SESSION['alert_type'] = 'success';
    header('Location: view_order.php?t=' . rawurlencode($trackingNo));
    exit;
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = $e instanceof RuntimeException ? $e->getMessage() : 'Order could not be placed.';

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    $_SESSION['message'] = $message;
    $_SESSION['alert_type'] = 'error';
    header('Location: cart.php');
    exit;
}

