<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

$userId = currentUserId();
if ($userId === null) {
    echo 401;
    exit;
}

if (!isset($_POST['scope'])) {
    echo 500;
    exit;
}

$scope = (string)$_POST['scope'];

try {
    switch ($scope) {
        case 'add': {
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($productId <= 0) {
                echo 500;
                exit;
            }

            $quantity = max(1, min(10, $quantity));

            // Ensure product exists and is active.
            $p = db()->prepare('SELECT product_id FROM product WHERE product_id = :pid AND status = 1 LIMIT 1');
            $p->execute(['pid' => $productId]);
            if ($p->fetchColumn() === false) {
                echo 500;
                exit;
            }

            $existing = db()->prepare('SELECT id, quantity FROM cart WHERE user_id = :uid AND product_id = :pid LIMIT 1');
            $existing->execute(['uid' => $userId, 'pid' => $productId]);
            $row = $existing->fetch(PDO::FETCH_ASSOC);

            if ($row !== false) {
                $cartId = (int)($row['id'] ?? 0);
                $currentQty = (int)($row['quantity'] ?? 0);
                $newQty = max(1, min(10, $currentQty + $quantity));

                $update = db()->prepare('UPDATE cart SET quantity = :q WHERE id = :id AND user_id = :uid');
                $update->execute(['q' => $newQty, 'id' => $cartId, 'uid' => $userId]);
                echo 201;
                exit;
            }

            $insert = db()->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :q)');
            $insert->execute(['uid' => $userId, 'pid' => $productId, 'q' => $quantity]);
            echo 201;
            exit;
        }

        case 'update': {
            $productId = (int)($_POST['product_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);

            if ($productId <= 0) {
                echo 500;
                exit;
            }

            $quantity = max(1, min(10, $quantity));

            $update = db()->prepare('UPDATE cart SET quantity = :q WHERE user_id = :uid AND product_id = :pid');
            $update->execute(['q' => $quantity, 'uid' => $userId, 'pid' => $productId]);

            if ($update->rowCount() === 0) {
                // If the cart row doesn't exist yet, create it (demo-friendly).
                $insert = db()->prepare('INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :q)');
                $insert->execute(['uid' => $userId, 'pid' => $productId, 'q' => $quantity]);
            }

            echo 200;
            exit;
        }

        case 'delete': {
            $cartId = (int)($_POST['cart_id'] ?? 0);
            if ($cartId <= 0) {
                echo 500;
                exit;
            }

            $delete = db()->prepare('DELETE FROM cart WHERE id = :id AND user_id = :uid');
            $delete->execute(['id' => $cartId, 'uid' => $userId]);

            // Even if the item was already removed, treat as success (idempotent).
            echo 200;
            exit;
        }

        default:
            echo 500;
            exit;
    }
} catch (Throwable $e) {
    echo 500;
    exit;
}

