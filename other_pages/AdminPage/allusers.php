<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';
require_once 'adminauth.php';

$records_per_page = 10;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $records_per_page;

$pdo = db();

$total_users = (int)$pdo->query('SELECT COUNT(*) FROM user')->fetchColumn();
$total_pages = (int)ceil($total_users / $records_per_page);

$stmt = $pdo->prepare('SELECT user_id, username, email, phone, role FROM user ORDER BY user_id DESC LIMIT :limit OFFSET :offset');
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<?php include '../../Includes/admin_header.php'; ?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-transparent">
                    <h3 class="mb-0">Users</h3>
                </div>
                <div class="card-body" id="user_table">
                    <?php
                    if (count($users) > 0) {
                        ?>
                        <table class="table table-bordered table-striped">
                            <thead>
                            <tr>
                                <th>User ID</th>
                                <th>Username</th>
                                <th>User Email</th>
                                <th>User Phone Number</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($users as $user) {
                                ?>
                                <tr>
                                    <td><?= (int)$user['user_id'] ?></td>
                                    <td><?= htmlspecialchars((string)$user['username']) ?></td>
                                    <td><?= htmlspecialchars((string)$user['email']) ?></td>
                                    <td><?= htmlspecialchars((string)$user['phone']) ?></td>
                                    <td><?= htmlspecialchars((string)($user['role'] ?? 'user')) ?></td>
                                    <td>
                                        <a href="edituser.php?id=<?= (int)$user['user_id']; ?>" class="btn btn-primary">Edit</a>
                                        <button type="button" class="btn btn-danger deleteuser_btn" data-user_id="<?= (int)$user['user_id']; ?>">Delete</button>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                            </tbody>
                        </table>
                        <?php
                    } else {
                        echo "<p>No users found!</p>";
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
