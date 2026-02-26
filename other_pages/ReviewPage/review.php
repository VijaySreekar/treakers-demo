<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

$userId = currentUserId();
if (!isset($_SESSION['authenticated']) || $userId === null) {
    header('Location: ../LoginPage/login_page.php');
    exit;
}

$orderId = (int)($_GET['orderId'] ?? 0);
if ($orderId <= 0) {
    $_SESSION['message'] = 'Order ID is required.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ../ProfilePage/profile.php');
    exit;
}

$orderStmt = db()->prepare('SELECT id, tracking_no FROM orders WHERE id = :id AND user_id = :uid LIMIT 1');
$orderStmt->execute(['id' => $orderId, 'uid' => $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if ($order === false) {
    $_SESSION['message'] = 'Order not found.';
    $_SESSION['alert_type'] = 'error';
    header('Location: ../ProfilePage/profile.php');
    exit;
}

$trackingNo = (string)($order['tracking_no'] ?? '');

// Fetch products in the order (only the current user's order).
$productsStmt = db()->prepare(
    'SELECT p.product_id, p.name, p.description, p.image
     FROM order_items oi
     JOIN product p ON oi.product_id = p.product_id
     WHERE oi.order_id = :oid
     ORDER BY oi.id ASC'
);
$productsStmt->execute(['oid' => $orderId]);
$products = $productsStmt->fetchAll(PDO::FETCH_ASSOC);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $rating = (int)($_POST['rating'] ?? 0);
    $comment = trim((string)($_POST['comment'] ?? ''));

    if ($productId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
        $_SESSION['message'] = 'Please select a product and provide a rating (1-5) and comment.';
        $_SESSION['alert_type'] = 'error';
        header('Location: review.php?orderId=' . rawurlencode((string)$orderId));
        exit;
    }

    // Ensure the selected product actually belongs to this order.
    $belongsStmt = db()->prepare(
        'SELECT 1 FROM order_items WHERE order_id = :oid AND product_id = :pid LIMIT 1'
    );
    $belongsStmt->execute(['oid' => $orderId, 'pid' => $productId]);
    if ($belongsStmt->fetchColumn() === false) {
        $_SESSION['message'] = 'Invalid product selection for this order.';
        $_SESSION['alert_type'] = 'error';
        header('Location: review.php?orderId=' . rawurlencode((string)$orderId));
        exit;
    }

    // One review per (user, order, product) - update if it already exists.
    $existingStmt = db()->prepare(
        'SELECT id FROM user_review WHERE user_id = :uid AND order_id = :oid AND product_id = :pid LIMIT 1'
    );
    $existingStmt->execute(['uid' => $userId, 'oid' => $orderId, 'pid' => $productId]);
    $existingId = $existingStmt->fetchColumn();

    if ($existingId !== false) {
        $update = db()->prepare('UPDATE user_review SET rating = :r, comment = :c WHERE id = :id');
        $update->execute(['r' => $rating, 'c' => $comment, 'id' => (int)$existingId]);
    } else {
        $insert = db()->prepare(
            'INSERT INTO user_review (user_id, product_id, order_id, rating, comment)
             VALUES (:uid, :pid, :oid, :r, :c)'
        );
        $insert->execute(['uid' => $userId, 'pid' => $productId, 'oid' => $orderId, 'r' => $rating, 'c' => $comment]);
    }

    $_SESSION['message'] = 'Review saved. Thanks!';
    $_SESSION['alert_type'] = 'success';
    if ($trackingNo !== '') {
        header('Location: ../BasketPage/view_order.php?t=' . rawurlencode($trackingNo));
        exit;
    }
    header('Location: ../ProfilePage/profile.php');
    exit;
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Leave a Review!</title>

    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <!-- SweetAlert2 CSS file -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <!-- SweetAlert2 JS file -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../AdminPage/assets/js/custom.js"></script>

    <!-- Nucleo Icons -->
    <link href="../../Assets/CSS/nucleo-icons.css" rel="stylesheet" />
    <link href="../../Assets/CSS/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <!-- CSS Files -->
    <link id="pagestyle" href="../../Assets/CSS/material-dashboard.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Alertify JS -->
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>

    <link rel="stylesheet" href="../../Assets/CSS/nav.css">
</head>

<body class="g-sidenav-show  bg-gray-200">
<main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">

    <?php include("../../Includes/nav.php"); ?>

    <nav class="breadcrumbs">
        <a href="../../index.php" class="breadcrumbs__item"><i class="bi bi-house"></i> Home</a>
        <a href="../ProfilePage/profile.php" class="breadcrumbs__item"><i class="bi bi-person"></i> Profile</a>
        <a href="../BasketPage/my_orders.php" class="breadcrumbs__item"><i class="bi bi-clock-history"></i> My Orders</a>
        <?php if ($trackingNo !== ''): ?>
            <a href="../BasketPage/view_order.php?t=<?= htmlspecialchars($trackingNo, ENT_QUOTES) ?>" class="breadcrumbs__item"><i class="bi bi-bag-check"></i> Order</a>
        <?php endif; ?>
        <a href="#" class="breadcrumbs__item is-active"><i class="bi bi-star"></i> Leave a Review</a>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="card card-header">
                <h2 class="mb-0">Leave a Review</h2>
                <small class="text-muted">Order ID: <?= (int)$orderId ?></small>
            </div>
            <div class="card card-body">
                <?php $hasProducts = count($products) > 0; ?>

                <?php if (!$hasProducts): ?>
                    <div class="alert alert-info mb-0">No products found for this order.</div>
                <?php else: ?>
                <form action="review.php?orderId=<?= urlencode((string)$orderId) ?>" method="post">
                    <div class="form-group">
                        <label for="product">Select Product:</label>
                        <select name="product_id" id="product" class="form-control" required>
                            <option value="">--Select a Product--</option>
                            <?php foreach ($products as $product): ?>
                                <option
                                    value="<?= (int)$product['product_id'] ?>"
                                    data-name="<?= htmlspecialchars((string)$product['name'], ENT_QUOTES) ?>"
                                    data-image="<?= htmlspecialchars(assetImageSrc((string)$product['image'], 'product'), ENT_QUOTES) ?>"
                                    data-description="<?= htmlspecialchars((string)$product['description'], ENT_QUOTES) ?>">
                                    <?= htmlspecialchars((string)$product['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="product-info" class="d-none">
                        <div class="card shadow">
                            <div class="row no-gutters">
                                <div class="col-md-4">
                                    <img id="product-image" class="card-img-top" src="" alt="Product Image">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 id="product-name" class="card-title"></h5>
                                        <p id="product-description" class="card-text"></p>
                                        <p id="product-price" class="card-text"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group mt-4">
                        <label for="rating">Rating (1-5):</label>
                        <input type="number" name="rating" id="rating" class="form-control" min="1" max="5" required>
                    </div>
                    <div class="form-group">
                        <label for="comment">Comment:</label>
                        <textarea name="comment" id="comment" rows="5" class="form-control" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>


    <?php include("../../Includes/footer.php"); ?>

</main>
<script src="../../Assets/JS/jquery-3.7.1.js"></script>
<script src="../../Assets/JS/bootstrap.bundle.min.js"></script>
<script src="../../Assets/JS/perfect-scrollbar.min.js"></script>
<script src="../../Assets/JS/smooth-scrollbar.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../Assets/JS/custom.js"></script>
<script src="../../Assets/JS/searchbar.js"></script>
<script src="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script type="text/javascript">
    var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
    (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='https://embed.tawk.to/65ff54951ec1082f04da7f5c/1hpmm4q27';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
    })();
</script>

<script>
    $(document).ready(function() {
        $('#product').change(function() {
            var selectedProduct = $(this).find(':selected');
            var productName = selectedProduct.data('name');
            var productImage = selectedProduct.data('image');
            var productDescription = selectedProduct.data('description');

            $('#product-name').text(productName);
            $('#product-image').attr('src', productImage);
            $('#product-description').text(productDescription);
            $('#product-info').removeClass('d-none');
        });
    });
</script>

</body>
</html>







