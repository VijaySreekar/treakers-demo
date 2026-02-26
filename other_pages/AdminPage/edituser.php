<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$id = (int)($_GET['id'] ?? 0);
$user = $id > 0 ? getUserbyID($id) : null;
?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header">
                    <h4>Edit User
                        <a href="allusers.php" class="btn btn-primary float-end">Back</a>
                    </h4>
                </div>
                <div class="card-body">
                    <?php if (is_array($user)): ?>
                        <form action="add_category_code.php" method="POST">
                            <input type="hidden" name="user_id" value="<?= (int)$user['user_id']; ?>">
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" class="form-control" name="username" value="<?= htmlspecialchars((string)$user['username'], ENT_QUOTES); ?>" placeholder="Enter User Name" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars((string)$user['email'], ENT_QUOTES); ?>" placeholder="Enter User Email" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars((string)$user['phone'], ENT_QUOTES); ?>" placeholder="Enter User Phone Number" required>
                            </div>
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary" name="edituser_btn">Update</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-danger" role="alert">Cannot find user with this ID!</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../Includes/admin_footer.php'; ?>
