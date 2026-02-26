<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$records_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

$pdo = db();

$total_categories = (int)$pdo->query('SELECT COUNT(*) FROM category')->fetchColumn();
$total_pages = (int)ceil($total_categories / $records_per_page);

$stmt = $pdo->prepare('SELECT category_id, name, image, status FROM category ORDER BY category_id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h3 class="mb-0">Categories</h3>
                </div>
                <div class="card-body" id="category_table">
                    <?php
                    if (count($categories) > 0) {
                        ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>Category ID</th>
                                <th>Category Name</th>
                                <th>Category Image</th>
                                <th>Category Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($categories as $category) {
                                ?>
                                <tr>
                                    <td><?= (int)$category['category_id'] ?></td>
                                    <td><?= htmlspecialchars((string)$category['name']) ?></td>
                                    <td>
                                        <img src="<?= htmlspecialchars(assetImageSrc((string)$category['image'], 'category'), ENT_QUOTES) ?>" width="50px" height="50px" alt="<?= htmlspecialchars((string)$category['name'], ENT_QUOTES) ?>">
                                    </td>
                                    <td>
                                        <?= (int)$category['status'] === 1 ? "Visible" : "Hidden" ?>
                                    </td>
                                    <td>
                                        <a href="edit-category.php?id=<?= (int)$category['category_id']; ?>" class="btn btn-primary">Edit</a>
                                        <button type="button" class="btn btn-danger delete_categorybtn" data-category_id="<?= (int)$category['category_id']; ?>">Delete</button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        echo "<p>No categories found!</p>";
                    }
                    ?>

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
