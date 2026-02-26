<?php
declare(strict_types=1);

$projectRoot = dirname(__DIR__, 2);

// Load environment variables from `.env` for local development.
$dotenvPath = $projectRoot . DIRECTORY_SEPARATOR . '.env';
if (is_file($dotenvPath)) {
    $lines = file($dotenvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($lines)) {
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || substr($trimmed, 0, 1) === '#') {
                continue;
            }

            $pos = strpos($trimmed, '=');
            if ($pos === false) {
                continue;
            }

            $key = trim(substr($trimmed, 0, $pos));
            $value = trim(substr($trimmed, $pos + 1));
            $value = trim($value, "\"'");

            if ($key === '' || getenv($key) !== false) {
                continue;
            }

            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

if (isset($db) && $db instanceof PDO) {
    return;
}

$dbDriver = strtolower(getenv('DB_DRIVER') ?: 'sqlite');
if (!defined('APP_DB_DRIVER')) {
    define('APP_DB_DRIVER', $dbDriver);
}
if (!defined('APP_DEMO')) {
    define('APP_DEMO', $dbDriver === 'sqlite');
}

if ($dbDriver === 'sqlite') {
    $defaultSqlitePath = $projectRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'treakers-demo.db';
    if (getenv('VERCEL')) {
        $defaultSqlitePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'treakers-demo.db';
    }

    $sqlitePath = getenv('SQLITE_PATH') ?: $defaultSqlitePath;
    $sqliteDir = dirname($sqlitePath);
    if (!is_dir($sqliteDir)) {
        mkdir($sqliteDir, 0777, true);
    }

    $db = new PDO('sqlite:' . $sqlitePath, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('PRAGMA foreign_keys = ON;');
    $db->exec('PRAGMA busy_timeout = 5000;');

    $sqliteColumnExists = static function (PDO $pdo, string $table, string $column): bool {
        $stmt = $pdo->query("PRAGMA table_info(" . $pdo->quote($table) . ")");
        if ($stmt === false) {
            return false;
        }
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            if (($row['name'] ?? null) === $column) {
                return true;
            }
        }
        return false;
    };

    $sqliteEnsureColumn = static function (PDO $pdo, string $table, string $column, string $definition) use ($sqliteColumnExists): void {
        if ($sqliteColumnExists($pdo, $table, $column)) {
            return;
        }
        $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
    };

    $ensureSqliteSchema = static function (PDO $pdo) use ($sqliteEnsureColumn): void {
        // Create missing tables (older demo DBs may not have these).
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS category (
                category_id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                description TEXT,
                image TEXT,
                meta_title TEXT,
                meta_description TEXT,
                meta_keywords TEXT,
                status INTEGER NOT NULL DEFAULT 1,
                popular INTEGER NOT NULL DEFAULT 0
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS product (
                product_id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                image TEXT,
                description TEXT,
                original_price REAL NOT NULL DEFAULT 0,
                discounted_price REAL NOT NULL DEFAULT 0,
                quantity INTEGER NOT NULL DEFAULT 0,
                trending INTEGER NOT NULL DEFAULT 0,
                status INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (category_id) REFERENCES category(category_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                passwordhash TEXT NOT NULL DEFAULT '',
                role TEXT NOT NULL DEFAULT 'user',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS cart (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user(user_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tracking_no TEXT NOT NULL UNIQUE,
                user_id INTEGER NOT NULL,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                phone TEXT,
                address TEXT,
                pincode TEXT,
                total_price REAL NOT NULL DEFAULT 0,
                payment_mode TEXT,
                payment_id TEXT,
                status INTEGER NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user(user_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                price REAL NOT NULL DEFAULT 0,
                FOREIGN KEY (order_id) REFERENCES orders(id),
                FOREIGN KEY (product_id) REFERENCES product(product_id)
            )"
        );

        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS user_review (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                order_id INTEGER,
                rating INTEGER NOT NULL DEFAULT 5,
                comment TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES user(user_id),
                FOREIGN KEY (product_id) REFERENCES product(product_id),
                FOREIGN KEY (order_id) REFERENCES orders(id)
            )"
        );

        // Add columns for older tables created by previous versions.
        $sqliteEnsureColumn($pdo, 'category', 'description', 'TEXT');
        $sqliteEnsureColumn($pdo, 'category', 'meta_title', 'TEXT');
        $sqliteEnsureColumn($pdo, 'category', 'meta_description', 'TEXT');
        $sqliteEnsureColumn($pdo, 'category', 'meta_keywords', 'TEXT');
        $sqliteEnsureColumn($pdo, 'category', 'popular', 'INTEGER NOT NULL DEFAULT 0');

        $sqliteEnsureColumn($pdo, 'product', 'quantity', 'INTEGER NOT NULL DEFAULT 0');

        $sqliteEnsureColumn($pdo, 'user', 'passwordhash', "TEXT NOT NULL DEFAULT ''");
        $sqliteEnsureColumn($pdo, 'user', 'role', "TEXT NOT NULL DEFAULT 'user'");
        $sqliteEnsureColumn($pdo, 'user', 'created_at', 'TEXT DEFAULT CURRENT_TIMESTAMP');

        $sqliteEnsureColumn($pdo, 'user_review', 'order_id', 'INTEGER');

        // Helpful indexes for demo usage.
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_cart_user_product ON cart(user_id, product_id)');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_orders_tracking_no ON orders(tracking_no)');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_product_slug ON product(slug)');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS idx_category_slug ON category(slug)');
    };

    $ensureDemoData = static function (PDO $pdo): void {
        // Categories
        $categories = [
            [
                'name' => 'Nike',
                'slug' => 'nike',
                'description' => 'Nike footwear and apparel.',
                'image' => '1711358320_Nike Superfly 9 Elite Mercurial Dream Speed.png',
                'meta_title' => 'Nike',
                'meta_description' => 'Nike category',
                'meta_keywords' => 'nike,sneakers',
                'status' => 1,
                'popular' => 1,
            ],
            [
                'name' => 'Adidas',
                'slug' => 'adidas',
                'description' => 'Adidas footwear and apparel.',
                'image' => '1711358164_adidas-mens-ultra-boost-20-EF1043-side.jpg',
                'meta_title' => 'Adidas',
                'meta_description' => 'Adidas category',
                'meta_keywords' => 'adidas,sneakers',
                'status' => 1,
                'popular' => 1,
            ],
            [
                'name' => 'Jordan',
                'slug' => 'jordan',
                'description' => 'Jordan brand sneakers.',
                'image' => '1963800750_zm.jpg',
                'meta_title' => 'Jordan',
                'meta_description' => 'Jordan category',
                'meta_keywords' => 'jordan,sneakers',
                'status' => 1,
                'popular' => 1,
            ],
        ];

        $insertCategory = $pdo->prepare(
            'INSERT INTO category (name, slug, description, image, meta_title, meta_description, meta_keywords, status, popular)
             VALUES (:name, :slug, :description, :image, :meta_title, :meta_description, :meta_keywords, :status, :popular)'
        );

        foreach ($categories as $cat) {
            $stmt = $pdo->prepare('SELECT category_id FROM category WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $cat['slug']]);
            $existing = $stmt->fetchColumn();
            if ($existing === false) {
                $insertCategory->execute($cat);
            }
        }

        // Products
        $getCategoryId = static function (string $slug) use ($pdo): ?int {
            $stmt = $pdo->prepare('SELECT category_id FROM category WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $id = $stmt->fetchColumn();
            return $id === false ? null : (int)$id;
        };

        $products = [
            [
                'category_slug' => 'nike',
                'name' => 'Nike Air Max 90',
                'slug' => 'nike-air-max-90',
                'image' => '1710972313_NIKEAIRMAX90.png',
                'description' => 'A classic running silhouette with modern comfort.',
                'original_price' => 150,
                'discounted_price' => 120,
                'quantity' => 50,
                'trending' => 1,
                'status' => 1,
            ],
            [
                'category_slug' => 'nike',
                'name' => "Nike Air Force 1 '07 LV8",
                'slug' => 'nike-air-force-1-07-lv8',
                'image' => '1710973046_NIKEAIRFORCE107LV8.png',
                'description' => 'Iconic design, everyday wear.',
                'original_price' => 130,
                'discounted_price' => 110,
                'quantity' => 100,
                'trending' => 1,
                'status' => 1,
            ],
            [
                'category_slug' => 'nike',
                'name' => 'Nike Pegasus Shield',
                'slug' => 'nike-pegasus-shield',
                'image' => '1711358973_Nike Pegasus Shield.png',
                'description' => 'Weather-ready trainer built for daily miles.',
                'original_price' => 140,
                'discounted_price' => 140,
                'quantity' => 80,
                'trending' => 0,
                'status' => 1,
            ],
            [
                'category_slug' => 'adidas',
                'name' => 'Adidas Ultra Boost 20',
                'slug' => 'adidas-ultra-boost-20',
                'image' => '1711368822_1963800750_zm.jpg',
                'description' => 'Responsive cushioning and a sleek look.',
                'original_price' => 180,
                'discounted_price' => 150,
                'quantity' => 60,
                'trending' => 0,
                'status' => 1,
            ],
            [
                'category_slug' => 'jordan',
                'name' => 'Air Jordan Zoom',
                'slug' => 'air-jordan-zoom',
                'image' => '1711369164_1711368822_1963800750_zm.jpg',
                'description' => 'A Jordan-inspired pair with standout comfort.',
                'original_price' => 200,
                'discounted_price' => 175,
                'quantity' => 40,
                'trending' => 1,
                'status' => 1,
            ],
            [
                'category_slug' => 'jordan',
                'name' => 'Jordan Essentials',
                'slug' => 'jordan-essentials',
                'image' => '1711346070_NIKEAIRFORCE107LV8.png',
                'description' => 'Everyday style with a Jordan vibe.',
                'original_price' => 160,
                'discounted_price' => 150,
                'quantity' => 70,
                'trending' => 0,
                'status' => 1,
            ],
        ];

        $insertProduct = $pdo->prepare(
            'INSERT INTO product (category_id, name, slug, image, description, original_price, discounted_price, quantity, trending, status)
             VALUES (:category_id, :name, :slug, :image, :description, :original_price, :discounted_price, :quantity, :trending, :status)'
        );

        foreach ($products as $product) {
            $stmt = $pdo->prepare('SELECT product_id FROM product WHERE slug = :slug LIMIT 1');
            $stmt->execute(['slug' => $product['slug']]);
            $existing = $stmt->fetchColumn();
            if ($existing !== false) {
                continue;
            }

            $categoryId = $getCategoryId($product['category_slug']);
            if ($categoryId === null) {
                continue;
            }

            $insertProduct->execute([
                'category_id' => $categoryId,
                'name' => $product['name'],
                'slug' => $product['slug'],
                'image' => $product['image'],
                'description' => $product['description'],
                'original_price' => $product['original_price'],
                'discounted_price' => $product['discounted_price'],
                'quantity' => $product['quantity'],
                'trending' => $product['trending'],
                'status' => $product['status'],
            ]);
        }

        // Demo users (passwords can be rotated later; keep them simple for portfolio demo)
        $demoUserId = null;
        $demoAccounts = [
            [
                'username' => 'DemoUser',
                'email' => 'demo@example.com',
                'phone' => '0000000000',
                'role' => 'user',
                'password' => 'demo1234',
            ],
            [
                'username' => 'DemoAdmin',
                'email' => 'admin@example.com',
                'phone' => '0000000000',
                'role' => 'admin',
                'password' => 'admin1234',
            ],
        ];

        $selectUser = $pdo->prepare('SELECT user_id, passwordhash, role FROM user WHERE email = :email LIMIT 1');
        $insertUser = $pdo->prepare(
            'INSERT INTO user (username, email, phone, passwordhash, role)
             VALUES (:username, :email, :phone, :passwordhash, :role)'
        );
        $updateUserPassword = $pdo->prepare('UPDATE user SET passwordhash = :passwordhash WHERE user_id = :id');
        $updateUserRole = $pdo->prepare('UPDATE user SET role = :role WHERE user_id = :id');

        foreach ($demoAccounts as $account) {
            $selectUser->execute(['email' => $account['email']]);
            $row = $selectUser->fetch(PDO::FETCH_ASSOC);
            $hash = password_hash($account['password'], PASSWORD_DEFAULT);

            if ($row === false) {
                $insertUser->execute([
                    'username' => $account['username'],
                    'email' => $account['email'],
                    'phone' => $account['phone'],
                    'passwordhash' => $hash,
                    'role' => $account['role'],
                ]);
                if ($account['email'] === 'demo@example.com') {
                    $demoUserId = (int)$pdo->lastInsertId();
                }
                continue;
            }

            $userId = (int)($row['user_id'] ?? 0);
            if ($account['email'] === 'demo@example.com' && $userId > 0) {
                $demoUserId = $userId;
            }
            $currentHash = (string)($row['passwordhash'] ?? '');
            if ($userId > 0 && trim($currentHash) === '') {
                $updateUserPassword->execute(['passwordhash' => $hash, 'id' => $userId]);
            }
            if ($userId > 0 && ($row['role'] ?? '') !== $account['role']) {
                $updateUserRole->execute(['role' => $account['role'], 'id' => $userId]);
            }
        }

        if (!is_int($demoUserId) || $demoUserId <= 0) {
            $selectUser->execute(['email' => 'demo@example.com']);
            $row = $selectUser->fetch(PDO::FETCH_ASSOC);
            $demoUserId = $row === false ? null : (int)($row['user_id'] ?? 0);
        }

        // Sample order + items (makes Orders pages feel "alive" in demo)
        if (is_int($demoUserId) && $demoUserId > 0) {
            $trackingNo = 'demo-order-0001';

            $selectOrder = $pdo->prepare('SELECT id FROM orders WHERE tracking_no = :t LIMIT 1');
            $selectOrder->execute(['t' => $trackingNo]);
            $orderId = $selectOrder->fetchColumn();
            $orderId = $orderId === false ? null : (int)$orderId;

            if (!is_int($orderId) || $orderId <= 0) {
                $insertOrder = $pdo->prepare(
                    'INSERT INTO orders (tracking_no, user_id, name, email, phone, address, pincode, total_price, payment_mode, payment_id, status)
                     VALUES (:tracking_no, :user_id, :name, :email, :phone, :address, :pincode, :total_price, :payment_mode, :payment_id, :status)'
                );
                $insertOrder->execute([
                    'tracking_no' => $trackingNo,
                    'user_id' => $demoUserId,
                    'name' => 'DemoUser',
                    'email' => 'demo@example.com',
                    'phone' => '0000000000',
                    'address' => '123 Demo Street, London',
                    'pincode' => 'DEMO1',
                    'total_price' => 230,
                    'payment_mode' => 'Demo',
                    'payment_id' => 'demo',
                    'status' => 2,
                ]);
                $orderId = (int)$pdo->lastInsertId();
            }

            if (is_int($orderId) && $orderId > 0) {
                $sampleOrderItems = [
                    ['slug' => 'nike-air-max-90', 'quantity' => 1],
                    ['slug' => 'nike-air-force-1-07-lv8', 'quantity' => 1],
                ];

                $selectProduct = $pdo->prepare('SELECT product_id, discounted_price FROM product WHERE slug = :slug LIMIT 1');
                $selectOrderItem = $pdo->prepare('SELECT id FROM order_items WHERE order_id = :oid AND product_id = :pid LIMIT 1');
                $insertOrderItem = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, price)
                     VALUES (:order_id, :product_id, :quantity, :price)'
                );

                foreach ($sampleOrderItems as $item) {
                    $selectProduct->execute(['slug' => $item['slug']]);
                    $prod = $selectProduct->fetch(PDO::FETCH_ASSOC);
                    if ($prod === false) {
                        continue;
                    }
                    $pid = (int)($prod['product_id'] ?? 0);
                    $price = (float)($prod['discounted_price'] ?? 0);
                    if ($pid <= 0) {
                        continue;
                    }
                    $selectOrderItem->execute(['oid' => $orderId, 'pid' => $pid]);
                    $existingItemId = $selectOrderItem->fetchColumn();
                    if ($existingItemId !== false) {
                        continue;
                    }
                    $insertOrderItem->execute([
                        'order_id' => $orderId,
                        'product_id' => $pid,
                        'quantity' => (int)$item['quantity'],
                        'price' => $price,
                    ]);
                }

                // Sample reviews (only if there are none yet)
                $reviewCount = (int)$pdo->query('SELECT COUNT(*) FROM user_review')->fetchColumn();
                if ($reviewCount === 0) {
                    $sampleReviews = [
                        ['slug' => 'nike-air-max-90', 'rating' => 5, 'comment' => 'Comfortable and looks great — perfect for a demo!'],
                        ['slug' => 'nike-air-force-1-07-lv8', 'rating' => 4, 'comment' => 'Great everyday sneaker.'],
                        ['slug' => 'air-jordan-zoom', 'rating' => 5, 'comment' => 'Love the style and the fit.'],
                    ];

                    $insertReview = $pdo->prepare(
                        'INSERT INTO user_review (user_id, product_id, order_id, rating, comment)
                         VALUES (:user_id, :product_id, :order_id, :rating, :comment)'
                    );

                    foreach ($sampleReviews as $review) {
                        $selectProduct->execute(['slug' => $review['slug']]);
                        $prod = $selectProduct->fetch(PDO::FETCH_ASSOC);
                        if ($prod === false) {
                            continue;
                        }
                        $pid = (int)($prod['product_id'] ?? 0);
                        if ($pid <= 0) {
                            continue;
                        }
                        $insertReview->execute([
                            'user_id' => $demoUserId,
                            'product_id' => $pid,
                            'order_id' => $orderId,
                            'rating' => (int)$review['rating'],
                            'comment' => $review['comment'],
                        ]);
                    }
                }
            }
        }
    };

    $hasSchema = false;
    try {
        $schemaCheck = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='product'");
        $hasSchema = $schemaCheck !== false && $schemaCheck->fetch() !== false;
    } catch (Throwable $e) {
        $hasSchema = false;
    }

    if (!$hasSchema) {
        $initSqlPath = $projectRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'sqlite_demo.sql';
        if (!is_file($initSqlPath)) {
            http_response_code(500);
            echo 'Missing demo database seed file.';
            exit;
        }

        $initSql = file_get_contents($initSqlPath);
        if ($initSql === false) {
            http_response_code(500);
            echo 'Failed to read demo database seed file.';
            exit;
        }

        $db->exec($initSql);
    }

    $ensureSqliteSchema($db);
    $ensureDemoData($db);

    // Compatibility variable (legacy code may reference this).
    $conn = null;

    return;
}

if ($dbDriver !== 'mysql') {
    http_response_code(500);
    echo 'Unsupported DB_DRIVER. Use "sqlite" (demo) or "mysql".';
    exit;
}

$dbhost = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: '';
$dbusername = getenv('DB_USER') ?: '';
$dbpassword = getenv('DB_PASSWORD') ?: '';

if ($dbname === '' || $dbusername === '') {
    http_response_code(500);
    echo 'Database is not configured. Please set DB_HOST, DB_NAME, DB_USER, and DB_PASSWORD.';
    exit;
}

try {
    $db = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbusername, $dbpassword, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Failed to connect to the database.';
    exit;
}

// Compatibility variable (legacy code may reference this).
$conn = $db;
