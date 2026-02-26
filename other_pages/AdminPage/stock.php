<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$records_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

$pdo = db();

$total_products = (int)$pdo->query('SELECT COUNT(*) FROM product')->fetchColumn();
$total_pages = (int)ceil($total_products / $records_per_page);

$stmt = $pdo->prepare('SELECT product_id, name, image, quantity FROM product ORDER BY product_id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h4 class="mb-0">Inventory Management</h4>
                </div>
                <div class="card-body" id="product_table">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Product Image</th>
                            <th>Stock</th>
                            <th>Stock Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (count($products) > 0) {
                            foreach ($products as $product) {
                                $stock = (int)($product['quantity'] ?? 0);

                                // Determine stock status
                                if ($stock <= 0) {
                                    $stockStatus = '<span class="badge bg-danger">Out of Stock</span>';
                                } elseif ($stock > 0 && $stock <= 50) {
                                    $stockStatus = '<span class="badge bg-warning text-dark">Low Stock</span>';
                                } else {
                                    $stockStatus = '<span class="badge bg-success">In Stock</span>';
                                }

                                // Display row
                                echo '<tr>';
                                echo '<td>' . htmlspecialchars((string)$product['name']) . '</td>';
                                echo '<td><img src="' . htmlspecialchars(assetImageSrc((string)$product['image'], 'product'), ENT_QUOTES) . '" width="50px" height="50px" alt="' . htmlspecialchars((string)$product['name'], ENT_QUOTES) . '"></td>';
                                echo '<td>' . $stock . '</td>';
                                echo '<td>' . $stockStatus . '</td>';
                                echo '</tr>';
                            }
                        } else {
                            echo '<tr><td colspan="4">No products found!</td></tr>';
                        }
                        ?>
                        </tbody>
                    </table>

                    <!-- Pagination links -->
                    <?php
                    // Display pagination links
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
