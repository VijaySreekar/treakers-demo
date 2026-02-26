<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$records_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

$pdo = db();

$total_orders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$total_pages = (int)ceil($total_orders / $records_per_page);

$stmt = $pdo->prepare('SELECT id, tracking_no, name, total_price, created_at, status FROM orders ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span class="fw-bold fs-2">Order History</span>
                    <a href="allorders.php" class="btn btn-info float-end"><i class="fa fa-back"></i> Back</a>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>OrderID</th>
                            <th>User</th>
                            <th>Tracking No</th>
                            <th>Price</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (count($orders) > 0) {
                            foreach ($orders as $order) {
                                ?>
                                <tr>
                                    <td><?= (int)$order['id']; ?></td>
                                    <td><?= htmlspecialchars((string)$order['name']); ?></td>
                                    <td><?= htmlspecialchars((string)$order['tracking_no']); ?></td>
                                    <td>£<?= number_format((float)$order['total_price'], 2); ?></td>
                                    <td><?= htmlspecialchars((string)$order['created_at']); ?></td>
                                    <td><a href="view_order_admin.php?t=<?= htmlspecialchars((string)$order['tracking_no'], ENT_QUOTES); ?>" class="btn btn-primary">View Details</a></td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="6">No Orders Yet</td>
                            </tr>
                            <?php
                        }
                        ?>
                        </tbody>
                    </table>

                    <!-- Pagination links -->
                    <?php
                    if ($total_pages > 1) {
                        echo '<ul class="pagination justify-content-center">';
                        for ($i = 1; $i <= $total_pages; $i++) {
                            echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                        }
                        echo '</ul>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../Includes/admin_footer.php'; ?>
