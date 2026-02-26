<?php
declare(strict_types=1);

require_once __DIR__ . '/../Database/connectdb.php';

function db(): PDO
{
    global $db;
    if (!($db instanceof PDO)) {
        throw new RuntimeException('Database connection is not available.');
    }
    return $db;
}

function allowedTableName(string $table): string
{
    // Keep this list small on purpose (prevents SQL injection via table name).
    $allowed = [
        'category',
        'product',
        'user',
        'user_review',
        'orders',
        'cart',
    ];

    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid table name.');
    }

    return $table;
}

function getAll(string $table): array
{
    $table = allowedTableName($table);
    $stmt = db()->query("SELECT * FROM {$table}");
    return $stmt->fetchAll();
}

function getAllActive(string $table): array
{
    $table = allowedTableName($table);
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE status = 1");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getSlugActive(string $table, string $slug): ?array
{
    $table = allowedTableName($table);
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE slug = :slug AND status = 1 LIMIT 1");
    $stmt->execute(['slug' => $slug]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getItembyID(string $table, int $id): array
{
    $table = allowedTableName($table);
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE category_id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll();
}

function getUserbyID(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM user WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getItemActive(string $table, int $id): array
{
    $table = allowedTableName($table);
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE status = 1 AND category_id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->fetchAll();
}

function getProductCategory(int $category_id): array
{
    $stmt = db()->prepare('SELECT * FROM product WHERE category_id = :cid AND status = 1');
    $stmt->execute(['cid' => $category_id]);
    return $stmt->fetchAll();
}

function getProductItembyID(string $table, int $id): ?array
{
    $table = allowedTableName($table);
    $stmt = db()->prepare("SELECT * FROM {$table} WHERE product_id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getAllTrending(): array
{
    $stmt = db()->query('SELECT * FROM product WHERE trending = 1 AND status = 1');
    return $stmt->fetchAll();
}

function getCategories(): array
{
    $stmt = db()->query('SELECT * FROM category WHERE status = 1 ORDER BY name ASC');
    return $stmt->fetchAll();
}

function getProductsWithCategory(string $categorySlug, string $sortOrder = 'ASC'): array
{
    $orderBy = strtolower($sortOrder) === 'desc' || strtolower($sortOrder) === 'high_to_low' ? 'DESC' : 'ASC';

    if ($categorySlug === 'all' || $categorySlug === '') {
        $stmt = db()->query("SELECT * FROM product WHERE status = 1 ORDER BY discounted_price {$orderBy}");
        return $stmt->fetchAll();
    }

    $stmt = db()->prepare(
        "SELECT p.* FROM product p
         JOIN category c ON p.category_id = c.category_id
         WHERE p.status = 1 AND c.slug = :slug
         ORDER BY p.discounted_price {$orderBy}"
    );
    $stmt->execute(['slug' => $categorySlug]);
    return $stmt->fetchAll();
}

function searchProducts(string $searchTerm): array
{
    $stmt = db()->prepare('SELECT * FROM product WHERE status = 1 AND name LIKE :q');
    $stmt->execute(['q' => '%' . $searchTerm . '%']);
    return $stmt->fetchAll();
}

function currentUserId(): ?int
{
    $userId = $_SESSION['auth_user']['user_id'] ?? $_SESSION['user_id'] ?? null;
    if ($userId === null) {
        return null;
    }
    $userId = (int)$userId;
    return $userId > 0 ? $userId : null;
}

function assetImageSrc(?string $image, string $type): string
{
    $image = trim((string)$image);
    if ($image === '') {
        return '/Assets/Images/Treakersfavicon.png';
    }

    if (preg_match('#^https?://#i', $image) === 1) {
        return $image;
    }

    if (strpos($image, '/') === 0) {
        return $image;
    }

    $encoded = rawurlencode($image);
    if ($type === 'product') {
        return '/Assets/Images/Product_Images/' . $encoded;
    }
    if ($type === 'category') {
        return '/Assets/Images/Category_Images/' . $encoded;
    }

    return $image;
}

// --- Demo-safe stubs (write flows can be added later) ---
function myCartItems(): array
{
    $userId = currentUserId();
    if ($userId === null) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT
            c.id AS cid,
            c.id,
            c.product_id,
            c.quantity,
            p.name,
            p.slug,
            p.image,
            p.original_price,
            p.discounted_price,
            p.quantity AS stock
         FROM cart c
         JOIN product p ON p.product_id = c.product_id
         WHERE c.user_id = :uid
         ORDER BY c.id DESC'
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

function myOrders(): array
{
    $userId = currentUserId();
    if ($userId === null) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT id, tracking_no, total_price, status, created_at
         FROM orders
         WHERE user_id = :uid
         ORDER BY created_at DESC, id DESC'
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}

function checkTrackingNo(string $tracking_no): array
{
    $userId = currentUserId();
    if ($userId === null) {
        return [];
    }

    $stmt = db()->prepare(
        'SELECT *
         FROM orders
         WHERE tracking_no = :t AND user_id = :uid
         LIMIT 1'
    );
    $stmt->execute(['t' => $tracking_no, 'uid' => $userId]);
    $row = $stmt->fetch();
    return $row === false ? [] : $row;
}

function AdmincheckTrackingNo(string $tracking_no): array
{
    $stmt = db()->prepare('SELECT * FROM orders WHERE tracking_no = :t LIMIT 1');
    $stmt->execute(['t' => $tracking_no]);
    $row = $stmt->fetch();
    return $row === false ? [] : $row;
}

function getAllOrders(): array
{
    return [];
}

function getOrderHistory(): array
{
    return [];
}

function getUserDetails(int $userId): ?array
{
    $stmt = db()->prepare('SELECT username, email, phone FROM user WHERE user_id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function getViewRecentOrders(int $userId): array
{
    $stmt = db()->prepare(
        'SELECT id, tracking_no, total_price, status, created_at
         FROM orders
         WHERE user_id = :uid
         ORDER BY created_at DESC, id DESC
         LIMIT 5'
    );
    $stmt->execute(['uid' => $userId]);
    return $stmt->fetchAll();
}
