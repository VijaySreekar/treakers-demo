PRAGMA foreign_keys = ON;

BEGIN;

CREATE TABLE IF NOT EXISTS category (
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
);

CREATE TABLE IF NOT EXISTS product (
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
);

CREATE TABLE IF NOT EXISTS user (
  user_id INTEGER PRIMARY KEY AUTOINCREMENT,
  username TEXT NOT NULL,
  email TEXT,
  phone TEXT,
  passwordhash TEXT NOT NULL DEFAULT '',
  role TEXT NOT NULL DEFAULT 'user',
  created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS cart (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL DEFAULT 1,
  created_at TEXT DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES user(user_id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE IF NOT EXISTS orders (
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
);

CREATE TABLE IF NOT EXISTS order_items (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  order_id INTEGER NOT NULL,
  product_id INTEGER NOT NULL,
  quantity INTEGER NOT NULL,
  price REAL NOT NULL DEFAULT 0,
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (product_id) REFERENCES product(product_id)
);

CREATE TABLE IF NOT EXISTS user_review (
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
);

-- Categories
INSERT INTO category (name, slug, description, image, meta_title, meta_description, meta_keywords, status, popular) VALUES
('Nike', 'nike', 'Nike footwear and apparel.', '1711358320_Nike Superfly 9 Elite Mercurial Dream Speed.png', 'Nike', 'Nike category', 'nike,sneakers', 1, 1),
('Adidas', 'adidas', 'Adidas footwear and apparel.', '1711358164_adidas-mens-ultra-boost-20-EF1043-side.jpg', 'Adidas', 'Adidas category', 'adidas,sneakers', 1, 1),
('Jordan', 'jordan', 'Jordan brand sneakers.', '1963800750_zm.jpg', 'Jordan', 'Jordan category', 'jordan,sneakers', 1, 1);

-- Products
INSERT INTO product (category_id, name, slug, image, description, original_price, discounted_price, quantity, trending, status) VALUES
(1, 'Nike Air Max 90', 'nike-air-max-90', '1710972313_NIKEAIRMAX90.png', 'A classic running silhouette with modern comfort.', 150, 120, 50, 1, 1),
(1, 'Nike Air Force 1 ''07 LV8', 'nike-air-force-1-07-lv8', '1710973046_NIKEAIRFORCE107LV8.png', 'Iconic design, everyday wear.', 130, 110, 100, 1, 1),
(1, 'Nike Pegasus Shield', 'nike-pegasus-shield', '1711358973_Nike Pegasus Shield.png', 'Weather-ready trainer built for daily miles.', 140, 140, 80, 0, 1),
(2, 'Adidas Ultra Boost 20', 'adidas-ultra-boost-20', '1711368822_1963800750_zm.jpg', 'Responsive cushioning and a sleek look.', 180, 150, 60, 0, 1),
(3, 'Air Jordan Zoom', 'air-jordan-zoom', '1711369164_1711368822_1963800750_zm.jpg', 'A Jordan-inspired pair with standout comfort.', 200, 175, 40, 1, 1),
(3, 'Jordan Essentials', 'jordan-essentials', '1711346070_NIKEAIRFORCE107LV8.png', 'Everyday style with a Jordan vibe.', 160, 150, 70, 0, 1);

-- Demo user + demo admin (passwordhash is set by connectdb.php on first run)
INSERT INTO user (username, email, phone, role) VALUES
('DemoUser', 'demo@example.com', '0000000000', 'user'),
('DemoAdmin', 'admin@example.com', '0000000000', 'admin');

-- Sample order (gives the UI something to show in demo)
INSERT INTO orders (tracking_no, user_id, name, email, phone, address, pincode, total_price, payment_mode, payment_id, status) VALUES
('demo-order-0001', 1, 'DemoUser', 'demo@example.com', '0000000000', '123 Demo Street, London', 'DEMO1', 230, 'Demo', 'demo', 2);

INSERT INTO order_items (order_id, product_id, quantity, price) VALUES
(1, 1, 1, 120),
(1, 2, 1, 110);

-- Sample reviews
INSERT INTO user_review (user_id, product_id, order_id, rating, comment) VALUES
(1, 1, 1, 5, 'Comfortable and looks great — perfect for a demo!'),
(1, 2, 1, 4, 'Great everyday sneaker.'),
(1, 5, 1, 5, 'Love the style and the fit.');

COMMIT;
