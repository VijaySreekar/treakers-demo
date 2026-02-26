<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$records_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

$searchTerm = trim((string)($_GET['search'] ?? ''));
$pdo = db();

$where = '';
if ($searchTerm !== '') {
    $where = 'WHERE name LIKE :q';
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM product {$where}");
if ($searchTerm !== '') {
    $countStmt->execute(['q' => '%' . $searchTerm . '%']);
} else {
    $countStmt->execute();
}
$total_products = (int)$countStmt->fetchColumn();
$total_pages = (int)ceil($total_products / $records_per_page);

$listStmt = $pdo->prepare(
    "SELECT product_id, name, image, status
     FROM product
     {$where}
     ORDER BY product_id DESC
     LIMIT :limit OFFSET :offset"
);

if ($searchTerm !== '') {
    $listStmt->bindValue(':q', '%' . $searchTerm . '%', PDO::PARAM_STR);
}
$listStmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$products = $listStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h3 class="mb-0">Products</h3>
                </div>
                <div class="card-body" id="product_table">
                    <table class="table table-bordered table-striped">
                        <thead>
                        <tr>
                            <th>Product ID</th>
                            <th>Product Name</th>
                            <th>Product Image</th>
                            <th>Product Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        if (count($products) > 0) {
                            foreach ($products as $item) {
                                ?>
                                <tr>
                                    <td><?= (int)$item['product_id'] ?></td>
                                    <td><?= htmlspecialchars((string)$item['name']) ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars(assetImageSrc((string)$item['image'], 'product'), ENT_QUOTES) ?>" width="50px" height="50px" alt="<?= htmlspecialchars((string)$item['name'], ENT_QUOTES) ?>">
                                    </td>
                                    <td>
                                        <?= (int)$item['status'] === 1 ? "Visible" : "Hidden" ?>
                                    </td>
                                    <td>
                                        <a href="edit-product.php?id=<?= (int)$item['product_id']; ?>" class="btn btn-primary">Edit</a>
                                        <button type="button" class="btn btn-danger deleteproduct_btn" data-product_id="<?= (int)$item['product_id']; ?>">Delete</button>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            echo "<tr><td colspan='5'>No products found!</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>

                    <!-- Pagination links -->
                    <?php if ($total_pages > 1) { ?>
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++) { ?>
                                <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($searchTerm) ?>"><?= $i ?></a>
                                </li>
                            <?php } ?>
                        </ul>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../Includes/admin_footer.php'; ?>
