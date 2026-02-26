<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$tracking_no = trim((string)($_GET['t'] ?? ''));
if ($tracking_no === '') {
    $_SESSION['message'] = 'No order specified.';
    header('Location: allorders.php');
    exit;
}

$orderStmt = db()->prepare('SELECT * FROM orders WHERE tracking_no = :t LIMIT 1');
$orderStmt->execute(['t' => $tracking_no]);
$order_data = $orderStmt->fetch(PDO::FETCH_ASSOC);
if ($order_data === false) {
    $_SESSION['message'] = 'No order found.';
    header('Location: allorders.php');
    exit;
}

$orderId = (int)($order_data['id'] ?? 0);
$itemsStmt = db()->prepare(
    'SELECT oi.product_id, oi.quantity AS order_quantity, oi.price, p.name, p.image
     FROM order_items oi
     JOIN product p ON p.product_id = oi.product_id
     WHERE oi.order_id = :oid
     ORDER BY oi.id ASC'
);
$itemsStmt->execute(['oid' => $orderId]);
$order_items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

include '../../Includes/admin_header.php';
?>


<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card mt-4">
                <div class="card-header bg-primary">
                    <span class="text-white fs-2">View Order</span>
                    <a href="allorders.php" class="btn btn-dark-blue float-end">Back</a>
                </div>
                <div class="card-body">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h4>Delivery Details</h4>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="fw-bold fs-5 mb-0">Name</label>
                                            <div class="border p-1">
                                                <?= $order_data['name']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-bold fs-5 mb-0">Email</label>
                                            <div class="border p-1">
                                                <?= $order_data['email']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-bold fs-5 mb-0">Phone</label>
                                            <div class="border p-1">
                                                <?= $order_data['phone']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-bold fs-5 mb-0">Tracking Number:</label>
                                            <div class="border p-1">
                                                <?= $order_data['tracking_no']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-bold fs-5 mb-0">Address</label>
                                            <div class="border p-1">
                                                <?= $order_data['address']; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="fw-bold fs-5 mb-0">Pin Code</label>
                                            <div class="border p-1">
                                                <?= $order_data['pincode']; ?>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h4>Order Details</h4>

                                    <table class="table text-center">
                                        <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Price</th>
                                            <th>Quantity</th>
                                        </tr>
                                        </thead>
                                        <tbody>

                                        <?php
                                        if (count($order_items) > 0)
                                        {
                                            foreach ($order_items as $item)
                                            {
                                                ?>
                                                <tr>
                                                    <td class="align-middle">
                                                        <img src="<?= htmlspecialchars(assetImageSrc((string)$item['image'], 'product'), ENT_QUOTES); ?>" alt="<?= htmlspecialchars((string)$item['name'], ENT_QUOTES); ?>" style="width: 50px;">
                                                        <?= htmlspecialchars((string)$item['name']); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        £<?= number_format((float)$item['price'], 2); ?>
                                                    </td>
                                                    <td class="align-middle">
                                                        <?= (int)$item['order_quantity']; ?>
                                                    </td>
                                                </tr>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <tr>
                                                <td colspan="3" class="text-muted">No items found.</td>
                                            </tr>
                                            <?php
                                        }
                                        ?>
                                        </tbody>
                                    </table>
                                    <hr>
                                    <h4>Total Price: <span class="float-end">£<?= number_format((float)$order_data['total_price'], 2); ?></span></h4>

                                    <label class="fw-bold fs-6 mb-1 mt-2">Payment Mode:</label>
                                    <div class="border p-3">
                                        <?= htmlspecialchars((string)$order_data['payment_mode']); ?>
                                    </div>
                                    <label class="fw-bold fs-6 mb-1 mt-2">Order Status:</label>
                                    <div class="p-2">
                                        <form action="add_category_code.php" method="POST">
                                            <input type="hidden" name="tracking_no" value="<?= htmlspecialchars((string)$order_data['tracking_no'], ENT_QUOTES); ?>">
                                            <select name="order_status" class="form-select">
                                                <option value="0" <?= (int)$order_data['status'] === 0 ? "selected" : "" ?>>Order Placed</option>
                                                <option value="1" <?= (int)$order_data['status'] === 1 ? "selected" : "" ?>>Order Shipped</option>
                                                <option value="2" <?= (int)$order_data['status'] === 2 ? "selected" : "" ?>>Order Delivered</option>
                                                <option value="3" <?= (int)$order_data['status'] === 3 ? "selected" : "" ?>>Order Cancelled</option>
                                                <option value="4" <?= (int)$order_data['status'] === 4 ? "selected" : "" ?>>Order Returned</option>
                                            </select>
                                            <button type="submit" name='updateOrderButton' class="btn btn-primary mt-2">Update Status</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../Includes/admin_footer.php'; ?>
