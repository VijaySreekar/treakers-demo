<?php
session_start();
include 'adminauth.php';
?>
<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-body">
                    <h4 class="card-title">Add Category</h4>
                    <form action="add_category_code.php" method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input type="text" class="form-control" name="name" placeholder="Enter Category Name" required>
                        </div>
                        <div class="form-group">
                            <label for="slug">Slug</label>
                            <input type="text" class="form-control" name="slug" placeholder="Enter Category Slug" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control" name="description" placeholder="Enter Category Description" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="image_url">Image URL (recommended on Vercel)</label>
                            <input type="text" class="form-control" name="image_url" placeholder="https://example.com/image.png or /Assets/Images/Category_Images/file.png">
                            <small class="text-muted">If you provide an Image URL, the upload field can be left empty.</small>
                        </div>
                        <div class="form-group">
                            <label for="image">Upload Image (local dev only)</label>
                            <input type="file" class="form-control-file" name="image">
                        </div>
                        <div class="form-group">
                            <label for="meta_title">Meta Title</label>
                            <input type="text" class="form-control" name="meta_title" placeholder="Enter Category Meta Title">
                        </div>
                        <div class="form-group">
                            <label for="meta_description">Meta Description</label>
                            <textarea rows="3" class="form-control" name="meta_description" placeholder="Enter Category Meta Description"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="meta_keywords">Meta Keywords</label>
                            <textarea rows="3" class="form-control" name="meta_keywords" placeholder="Enter Category Meta Keywords"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select class="form-control" name="status">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="popular">Popular</label>
                            <select class="form-control" name="popular">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block" name="add_categorybtn">Add Category</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../Includes/admin_footer.php'; ?>
