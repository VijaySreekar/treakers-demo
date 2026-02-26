<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';
require_once __DIR__ . '/adminauth.php';

$pdo = db();

function setFlash(string $message, string $type = 'success'): void
{
    $_SESSION['message'] = $message;
    $_SESSION['alert_type'] = $type;
}

function redirectTo(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function isVercelEnv(): bool
{
    return (string)getenv('VERCEL') !== '';
}

function isHttpUrl(string $value): bool
{
    return preg_match('#^https?://#i', $value) === 1;
}

function slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value);
    $value = trim($value, '-');
    return $value;
}

function sanitizeUploadFilename(string $name): string
{
    $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name);
    $name = trim((string)$name, '._');
    return $name !== '' ? $name : ('upload_' . bin2hex(random_bytes(3)) . '.bin');
}

function handleImageInput(string $urlField, string $fileField, string $targetDir, ?string $oldValue = null): ?string
{
    $imageUrl = trim((string)($_POST[$urlField] ?? ''));
    if ($imageUrl !== '') {
        // Allow http(s), absolute paths, or plain filenames (local assets).
        if (isHttpUrl($imageUrl) || strpos($imageUrl, '/') === 0) {
            return $imageUrl;
        }
        return $imageUrl;
    }

    if (!isset($_FILES[$fileField]) || !is_array($_FILES[$fileField])) {
        return $oldValue;
    }

    $file = $_FILES[$fileField];
    $error = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return $oldValue;
    }
    if ($error !== UPLOAD_ERR_OK) {
        return $oldValue;
    }

    // Vercel filesystem is read-only for persistent assets; keep old value.
    if (isVercelEnv()) {
        return $oldValue;
    }

    $originalName = trim((string)($file['name'] ?? ''));
    if ($originalName === '') {
        return $oldValue;
    }

    $safe = sanitizeUploadFilename($originalName);
    $filename = time() . '_' . $safe;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $dest = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file((string)$file['tmp_name'], $dest)) {
        return $oldValue;
    }

    // Optionally remove the previous local file (skip URLs).
    if ($oldValue && !isHttpUrl($oldValue) && strpos($oldValue, '/') !== 0) {
        $oldPath = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $oldValue;
        if (is_file($oldPath)) {
            @unlink($oldPath);
        }
    }

    return $filename;
}

function attemptedUpload(string $fileField): bool
{
    return isset($_FILES[$fileField], $_FILES[$fileField]['error']) && (int)$_FILES[$fileField]['error'] !== UPLOAD_ERR_NO_FILE;
}

// --- Category ---
if (isset($_POST['add_categorybtn'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $meta_title = trim((string)($_POST['meta_title'] ?? ''));
    $meta_description = trim((string)($_POST['meta_description'] ?? ''));
    $meta_keywords = trim((string)($_POST['meta_keywords'] ?? ''));
    $status = ((string)($_POST['status'] ?? '1') === '1') ? 1 : 0;
    $popular = ((string)($_POST['popular'] ?? '0') === '1') ? 1 : 0;

    if ($slug === '') {
        $slug = slugify($name);
    }
    if ($slug === '') {
        $slug = 'category-' . bin2hex(random_bytes(3));
    }

    if (isVercelEnv() && attemptedUpload('image') && trim((string)($_POST['image_url'] ?? '')) === '') {
        setFlash('File uploads are disabled on Vercel. Use an Image URL instead.', 'error');
        redirectTo('add_category.php');
    }

    $path = __DIR__ . '/../../Assets/Images/Category_Images/';
    $image = handleImageInput('image_url', 'image', $path, null);
    $image = $image ?? '';

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO category (name, slug, description, image, meta_title, meta_description, meta_keywords, status, popular)
             VALUES (:name, :slug, :description, :image, :meta_title, :meta_description, :meta_keywords, :status, :popular)'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'image' => $image,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'status' => $status,
            'popular' => $popular,
        ]);

        setFlash('Category Added', 'success');
    } catch (Throwable $e) {
        setFlash('Category Not Added', 'error');
    }

    redirectTo('category.php');
}

if (isset($_POST['save_categorybtn'])) {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $meta_title = trim((string)($_POST['meta_title'] ?? ''));
    $meta_description = trim((string)($_POST['meta_description'] ?? ''));
    $meta_keywords = trim((string)($_POST['meta_keywords'] ?? ''));
    $status = ((string)($_POST['status'] ?? '1') === '1') ? 1 : 0;
    $popular = ((string)($_POST['popular'] ?? '0') === '1') ? 1 : 0;
    $old_image = trim((string)($_POST['old_image'] ?? ''));

    if ($category_id <= 0) {
        setFlash('Invalid category.', 'error');
        redirectTo('category.php');
    }

    if ($slug === '') {
        $slug = slugify($name);
    }
    if ($slug === '') {
        $slug = 'category-' . bin2hex(random_bytes(3));
    }

    if (isVercelEnv() && attemptedUpload('image') && trim((string)($_POST['image_url'] ?? '')) === '') {
        setFlash('File uploads are disabled on Vercel. Use an Image URL instead.', 'error');
        redirectTo('edit-category.php?id=' . rawurlencode((string)$category_id));
    }

    $path = __DIR__ . '/../../Assets/Images/Category_Images/';
    $image = handleImageInput('image_url', 'image', $path, $old_image);
    $image = $image ?? '';

    try {
        $stmt = $pdo->prepare(
            'UPDATE category
             SET name = :name,
                 slug = :slug,
                 description = :description,
                 image = :image,
                 meta_title = :meta_title,
                 meta_description = :meta_description,
                 meta_keywords = :meta_keywords,
                 status = :status,
                 popular = :popular
             WHERE category_id = :id'
        );
        $stmt->execute([
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'image' => $image,
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords' => $meta_keywords,
            'status' => $status,
            'popular' => $popular,
            'id' => $category_id,
        ]);
        setFlash('Category Updated', 'success');
    } catch (Throwable $e) {
        setFlash('Category Not Updated', 'error');
    }

    redirectTo('category.php');
}

if (isset($_POST['delete_categorybtn'])) {
    $category_id = (int)($_POST['category_ids'] ?? 0);
    if ($category_id <= 0) {
        echo 500;
        exit;
    }

    try {
        // Soft delete (prevents FK issues in demo).
        $stmt = $pdo->prepare('UPDATE category SET status = 0 WHERE category_id = :id');
        $stmt->execute(['id' => $category_id]);
        echo 200;
    } catch (Throwable $e) {
        echo 500;
    }
    exit;
}

// --- Product ---
if (isset($_POST['addproduct_btn'])) {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $slug = trim((string)($_POST['slug'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $original_price = (float)($_POST['original_price'] ?? 0);
    $discounted_price = (float)($_POST['discounted_price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $status = ((string)($_POST['status'] ?? '1') === '1') ? 1 : 0;
    $trending = ((string)($_POST['trending'] ?? '0') === '1') ? 1 : 0;

    if ($slug === '') {
        $slug = slugify($name);
    }
    if ($slug === '') {
        $slug = 'product-' . bin2hex(random_bytes(3));
    }

    if ($category_id <= 0 || $name === '' || $description === '') {
        setFlash('Please fill all required fields.', 'error');
        redirectTo('add_products.php');
    }

    if (isVercelEnv() && attemptedUpload('image') && trim((string)($_POST['image_url'] ?? '')) === '') {
        setFlash('File uploads are disabled on Vercel. Use an Image URL instead.', 'error');
        redirectTo('add_products.php');
    }

    $path = __DIR__ . '/../../Assets/Images/Product_Images/';
    $image = handleImageInput('image_url', 'image', $path, null);
    $image = $image ?? '';

    try {
        $stmt = $pdo->prepare(
            'INSERT INTO product (category_id, name, slug, description, image, original_price, discounted_price, quantity, status, trending)
             VALUES (:category_id, :name, :slug, :description, :image, :original_price, :discounted_price, :quantity, :status, :trending)'
        );
        $stmt->execute([
            'category_id' => $category_id,
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'image' => $image,
            'original_price' => $original_price,
            'discounted_price' => $discounted_price,
            'quantity' => $quantity,
            'status' => $status,
            'trending' => $trending,
        ]);

        setFlash('Product Added', 'success');
    } catch (Throwable $e) {
        setFlash('Product Not Added', 'error');
    }

    redirectTo('allproducts.php');
}

if (isset($_POST['editproduct_btn'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $original_price = (float)($_POST['original_price'] ?? 0);
    $discounted_price = (float)($_POST['discounted_price'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $status = ((string)($_POST['status'] ?? '1') === '1') ? 1 : 0;
    $trending = ((string)($_POST['trending'] ?? '0') === '1') ? 1 : 0;
    $old_image = trim((string)($_POST['old_image'] ?? ''));

    if ($product_id <= 0) {
        setFlash('Invalid product.', 'error');
        redirectTo('allproducts.php');
    }

    if (isVercelEnv() && attemptedUpload('image') && trim((string)($_POST['image_url'] ?? '')) === '') {
        setFlash('File uploads are disabled on Vercel. Use an Image URL instead.', 'error');
        redirectTo('edit-product.php?id=' . rawurlencode((string)$product_id));
    }

    $path = __DIR__ . '/../../Assets/Images/Product_Images/';
    $image = handleImageInput('image_url', 'image', $path, $old_image);
    $image = $image ?? '';

    try {
        $stmt = $pdo->prepare(
            'UPDATE product
             SET category_id = :category_id,
                 name = :name,
                 description = :description,
                 image = :image,
                 original_price = :original_price,
                 discounted_price = :discounted_price,
                 quantity = :quantity,
                 status = :status,
                 trending = :trending
             WHERE product_id = :id'
        );
        $stmt->execute([
            'category_id' => $category_id,
            'name' => $name,
            'description' => $description,
            'image' => $image,
            'original_price' => $original_price,
            'discounted_price' => $discounted_price,
            'quantity' => $quantity,
            'status' => $status,
            'trending' => $trending,
            'id' => $product_id,
        ]);

        setFlash('Product Updated', 'success');
    } catch (Throwable $e) {
        setFlash('Product Not Updated', 'error');
    }

    redirectTo('allproducts.php');
}

if (isset($_POST['deleteproduct_btn'])) {
    $product_id = (int)($_POST['product_id'] ?? 0);
    if ($product_id <= 0) {
        echo 500;
        exit;
    }

    try {
        // Soft delete (prevents FK issues in demo).
        $stmt = $pdo->prepare('UPDATE product SET status = 0 WHERE product_id = :id');
        $stmt->execute(['id' => $product_id]);
        echo 200;
    } catch (Throwable $e) {
        echo 500;
    }
    exit;
}

// --- User ---
if (isset($_POST['edituser_btn'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $name = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));

    if ($user_id <= 0) {
        setFlash('Invalid user.', 'error');
        redirectTo('allusers.php');
    }

    try {
        $stmt = $pdo->prepare('UPDATE user SET username = :name, email = :email, phone = :phone WHERE user_id = :id');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'id' => $user_id,
        ]);
        setFlash('User Updated', 'success');
    } catch (Throwable $e) {
        setFlash('User Not Updated', 'error');
    }

    redirectTo('allusers.php');
}

if (isset($_POST['deleteuser_btn'])) {
    $user_id = (int)($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo 500;
        exit;
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM user WHERE user_id = :id');
        $stmt->execute(['id' => $user_id]);
        echo $stmt->rowCount() > 0 ? 200 : 500;
    } catch (Throwable $e) {
        echo 500;
    }
    exit;
}

// --- Orders ---
if (isset($_POST['updateOrderButton'])) {
    $tracking_no = trim((string)($_POST['tracking_no'] ?? ''));
    $order_status = (int)($_POST['order_status'] ?? 0);
    $order_status = max(0, min(4, $order_status));

    try {
        $stmt = $pdo->prepare('UPDATE orders SET status = :s WHERE tracking_no = :t');
        $stmt->execute(['s' => $order_status, 't' => $tracking_no]);
        setFlash('Order Status Updated', 'success');
    } catch (Throwable $e) {
        setFlash('Order Status Not Updated', 'error');
    }

    redirectTo('view_order_admin.php?t=' . rawurlencode($tracking_no));
}

redirectTo('adminpage.php');
