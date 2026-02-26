<?php
session_start();
include '../../Assets/Functions/myfunctions.php';
include 'adminauth.php';

$id = (int)($_GET['id'] ?? 0);
$category = null;
if ($id > 0) {
    $stmt = db()->prepare('SELECT * FROM category WHERE category_id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $category = $row === false ? null : $row;
}
?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-body">
                    <h4 class="card-title">Edit Category</h4>
                    <?php if (is_array($category)) { ?>
                        <form action="add_category_code.php" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="category_id" value="<?= (int)$category['category_id'] ?>">
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars((string)$category['name'], ENT_QUOTES) ?>" placeholder="Enter Category Name">
                            </div>
                            <div class="form-group">
                                <label for="slug">Slug</label>
                                <input type="text" class="form-control" name="slug" value="<?= htmlspecialchars((string)$category['slug'], ENT_QUOTES) ?>" placeholder="Enter Slug">
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" placeholder="Enter Description"><?= htmlspecialchars((string)$category['description']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image_url">Image URL (optional)</label>
                                <input type="text" class="form-control" name="image_url" placeholder="https://example.com/image.png or /Assets/Images/Category_Images/file.png">
                                <small class="text-muted">Leave blank to keep the current image.</small>

                                <label for="image" class="mt-3">Upload New Image (local dev only)</label>
                                <input type="file" class="form-control-file" name="image">
                                <input type="hidden" name="old_image" value="<?= htmlspecialchars((string)$category['image'], ENT_QUOTES) ?>">
                                <img src="<?= htmlspecialchars(assetImageSrc((string)$category['image'], 'category'), ENT_QUOTES) ?>" height="75px" width="75px" alt="">
                            </div>
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" class="form-control" name="meta_title" value="<?= htmlspecialchars((string)$category['meta_title'], ENT_QUOTES) ?>" placeholder="Enter Meta Title">
                            </div>
                            <div class="form-group">
                                <label for="meta_description">Meta Description</label>
                                <textarea rows="3" class="form-control" name="meta_description" placeholder="Enter Meta Description"><?= htmlspecialchars((string)$category['meta_description']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="meta_keywords">Meta Keywords</label>
                                <textarea rows="3" class="form-control" name="meta_keywords" placeholder="Enter Meta Keywords"><?= htmlspecialchars((string)$category['meta_keywords']) ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select class="form-control" name="status">
                                    <option value="1" <?= (int)$category['status'] === 1 ? 'selected' : '' ?>>Active</option>
                                    <option value="0" <?= (int)$category['status'] === 0 ? 'selected' : '' ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="popular">Popular</label>
                                <select class="form-control" name="popular">
                                    <option value="1" <?= (int)$category['popular'] === 1 ? 'selected' : '' ?>>Yes</option>
                                    <option value="0" <?= (int)$category['popular'] === 0 ? 'selected' : '' ?>>No</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block" name="save_categorybtn">Update Category</button>
                            <a href="category.php" class="btn btn-secondary btn-block">Back</a>
                        </form>
                    <?php } else {
                        echo "Cannot find category with this ID!";
                    } ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../Includes/admin_footer.php'; ?>
