<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$pdo = db();

$totalBookings = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalUsersCount = (int)$pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();
$totalRevenue = (float)$pdo->query('SELECT COALESCE(SUM(total_price), 0) FROM orders')->fetchColumn();

$inventoryRow = $pdo->query(
    'SELECT
        COALESCE(SUM(original_price * quantity), 0) AS total_inventory_value,
        COALESCE(SUM(quantity), 0) AS total_quantity
     FROM product'
)->fetch(PDO::FETCH_ASSOC);

$totalInventoryValue = (float)($inventoryRow['total_inventory_value'] ?? 0);
$totalQuantity = (int)($inventoryRow['total_quantity'] ?? 0);

$recentOrders = $pdo->query(
    'SELECT o.tracking_no, o.total_price, o.created_at, u.username
     FROM orders o
     LEFT JOIN user u ON u.user_id = o.user_id
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT 5'
)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include '../../Includes/admin_header.php'; ?>
<div class="container">
    <?php if(isset($_SESSION['message'])): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Hey!</strong> <?= $_SESSION['message'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="row">
        <!-- Bookings Card - Light Blue -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm bg-lightblue">
                <div class="card-body">
                    <h5 class="card-title">Bookings</h5>
                    <h2><?= $totalBookings; ?></h2>
                    <p class="text-muted mb-0">Total orders</p>
                </div>
            </div>
        </div>

        <!-- Users Card - Light Green -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm bg-lightgreen">
                <div class="card-body">
                    <h5 class="card-title">Users</h5>
                    <h2><?= $totalUsersCount; ?></h2>
                    <p class="text-muted mb-0">Total registered</p>
                </div>
            </div>
        </div>

        <!-- Revenue Card - Light Yellow -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm bg-lightyellow">
                <div class="card-body">
                    <h5 class="card-title">Revenue</h5>
                    <h2>£<?= number_format($totalRevenue, 2); ?></h2>
                    <p class="text-muted mb-0">Total order value</p>
                </div>
            </div>
        </div>

        <!-- Inventory Value Card - Light Purple -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm bg-lightpurple">
                <div class="card-body">
                    <h5 class="card-title">Inventory Value</h5>
                    <h2>£<?= number_format($totalInventoryValue, 2); ?></h2>
                    <p>Total Quantity: <?= $totalQuantity; ?></p>
                </div>
            </div>
        </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header">
                    <h6>Orders Overview</h6>
                    <p class="text-success"><i class="fas fa-arrow-up"></i> 24% this month</p>
                </div>
                <div class="card-body">
                    <?php if(count($recentOrders) > 0): ?>
                    <div class="list-group">
                        <?php foreach($recentOrders as $rowOrder): ?>
                        <div class="list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">
                                    <?= htmlspecialchars((string)($rowOrder['tracking_no'] ?? '')) ?>
                                    - £<?= number_format((float)($rowOrder['total_price'] ?? 0), 2) ?>
                                </h5>
                                <small><?= htmlspecialchars(date('d M H:i A', strtotime((string)($rowOrder['created_at'] ?? 'now')))); ?></small>
                            </div>
                            <p class="mb-1">Ordered by: <?= htmlspecialchars((string)($rowOrder['username'] ?? '')); ?></p>
                            <a class="btn btn-sm btn-outline-primary" href="view_order_admin.php?t=<?= htmlspecialchars((string)($rowOrder['tracking_no'] ?? ''), ENT_QUOTES) ?>">View</a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                        <p class="text-muted">No recent orders</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../Includes/admin_footer.php'; ?>
