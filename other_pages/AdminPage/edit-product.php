<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$id = (int)($_GET['id'] ?? 0);
$product = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM product WHERE product_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $product = $row === false ? null : $row;
}

$categories = getAll('category');
?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <?php
                if(isset($_SESSION['message'])):
                    ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <strong>Hey!</strong> <?= $_SESSION['message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php
                    unset($_SESSION['message']);
                endif;
                ?>
                <div class="card-header">
                    <h4>Edit Product
                        <a href="allproducts.php" class="btn btn-primary float-end">Back</a>
                    </h4>
                </div>
                <div class="card-body">
                    <?php
                    if (is_array($product)) {
                    ?>
                    <form action="add_category_code.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category_id" class="form-select">
                                <option selected>Select Category</option>
                                <?php
                                if(count($categories) > 0) {
                                    foreach($categories as $item) {
                                        ?>
                                        <option value="<?= (int)$item['category_id']; ?>" <?= (int)$product['category_id'] === (int)$item['category_id'] ? 'selected' : ''; ?>><?= htmlspecialchars((string)$item['name']); ?></option>
                                        <?php
                                    }
                                } else {
                                    echo "<option>No Category Found</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <input type="hidden" name="product_id" value="<?= (int)$product['product_id']; ?>">
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars((string)$product['name'], ENT_QUOTES); ?>" placeholder="Enter Product Name" required>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea class="form-control" name="description" placeholder="Enter Description" required><?= htmlspecialchars((string)$product['description']); ?></textarea>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Original Price</label>
                                <input type="text" class="form-control" name="original_price" value="<?= htmlspecialchars((string)$product['original_price'], ENT_QUOTES); ?>" placeholder="Enter Original Price" required>
                            </div>
                            <div class="form-group col-md-6">
                                <label>Discounted Price</label>
                                <input type="text" class="form-control" name="discounted_price" value="<?= htmlspecialchars((string)$product['discounted_price'], ENT_QUOTES); ?>" placeholder="Enter Discounted Price" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Image URL (optional)</label>
                            <input type="text" class="form-control" name="image_url" placeholder="https://example.com/image.png or /Assets/Images/Product_Images/file.png">
                            <small class="text-muted">Leave blank to keep the current image.</small>

                            <label class="mt-3">Upload Image (local dev only)</label>
                            <input type="file" class="form-control-file" name="image">
                            <div>Current Image</div>
                            <img src="<?= htmlspecialchars(assetImageSrc((string)$product['image'], 'product'), ENT_QUOTES) ?>" height="75px" width="75px" alt="">
                            <input type="hidden" name="old_image" value="<?= htmlspecialchars((string)$product['image'], ENT_QUOTES) ?>">
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Quantity</label>
                                <input type="number" class="form-control" name="quantity" value="<?= (int)$product['quantity']; ?>" placeholder="Enter Quantity">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Status</label>
                                <select class="form-control" name="status">
                                    <option value="1" <?= ((int)$product['status'] === 1) ? 'selected' : ''; ?>>Active</option>
                                    <option value="0" <?= ((int)$product['status'] === 0) ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group col-md-4">
                                <label>Trending</label>
                                <select class="form-control" name="trending">
                                    <option value="1" <?= ((int)$product['trending'] === 1) ? 'selected' : ''; ?>>Yes</option>
                                    <option value="0" <?= ((int)$product['trending'] === 0) ? 'selected' : ''; ?>>No</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary" name="editproduct_btn">Update</button>
                        </div>
                    </form>
                        <?php
                    } else {
                        echo "<div class='alert alert-danger' role='alert'>Cannot find product with this ID!</div>";
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

</div>
<?php include '../../Includes/admin_footer.php'; ?>
