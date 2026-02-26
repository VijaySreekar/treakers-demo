<?php
declare(strict_types=1);

session_start();

require_once '../../Assets/Functions/myfunctions.php';

$userId = currentUserId();
if (!isset($_SESSION['authenticated']) || $userId === null) {
    header('Location: ../LoginPage/login_page.php');
    exit;
}

// Only allow editing your own account (ignore any other id passed in).
$user = getUserbyID($userId);
if (!is_array($user)) {
    $_SESSION['message'] = 'User not found.';
    $_SESSION['alert_type'] = 'error';
    header('Location: profile.php');
    exit;
}

$username = (string)($user['username'] ?? '');
$email = (string)($user['email'] ?? '');
$phone = (string)($user['phone'] ?? '');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['submit'])) {
    $newUsername = trim((string)($_POST['username'] ?? ''));
    $newEmail = trim((string)($_POST['email'] ?? ''));
    $newPhone = trim((string)($_POST['phone'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($newUsername === '' || $newEmail === '' || $newPhone === '' || $password === '') {
        $_SESSION['message'] = 'Please fill all fields.';
        $_SESSION['alert_type'] = 'error';
        header('Location: edit_user.php?updateid=' . rawurlencode((string)$userId));
        exit;
    }

    if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['message'] = 'Please enter a valid email.';
        $_SESSION['alert_type'] = 'error';
        header('Location: edit_user.php?updateid=' . rawurlencode((string)$userId));
        exit;
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);

    try {
        $stmt = db()->prepare(
            'UPDATE user
             SET username = :username,
                 email = :email,
                 phone = :phone,
                 passwordhash = :passwordhash
             WHERE user_id = :id'
        );
        $stmt->execute([
            'username' => $newUsername,
            'email' => $newEmail,
            'phone' => $newPhone,
            'passwordhash' => $hash,
            'id' => $userId,
        ]);

        // Keep session data in sync.
        $_SESSION['username'] = $newUsername;
        if (isset($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
            $_SESSION['auth_user']['username'] = $newUsername;
        }

        $_SESSION['message'] = 'Profile updated.';
        $_SESSION['alert_type'] = 'success';
        header('Location: profile.php');
        exit;
    } catch (Throwable $e) {
        $_SESSION['message'] = 'Failed to update profile.';
        $_SESSION['alert_type'] = 'error';
        header('Location: edit_user.php?updateid=' . rawurlencode((string)$userId));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Your Details</title>

    <link rel="icon" type="image/png" sizes="76x76" href="../../Assets/Images/Treakersfavicon.png">
    <!-- Fonts and icons -->
    <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
    <!-- Include Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Truncleta:wght@400&display=swap">
    <link rel="stylesheet" href="../../Assets/CSS/nav.css">

    <!-- Nucleo Icons -->
    <link href="../../Assets/CSS/nucleo-icons.css" rel="stylesheet" />
    <link href="../../Assets/CSS/nucleo-svg.css" rel="stylesheet" />
    <!-- Font Awesome Icons -->
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    <!-- CSS Files -->
    <link id="pagestyle" href="../../Assets/CSS/material-dashboard.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Alertify JS -->
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/alertify.min.css"/>
    <link rel="stylesheet" href="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/css/themes/bootstrap.min.css"/>

    <link rel="stylesheet" href="../../Assets/CSS/nav.css">
</head>
    <body>
    <?php 
    include '../../Includes/nav.php';
    ?>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Edit Your Details</h3>
                    </div>
                    <div class="card-body">
                        <form id="update" method="POST">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($username, ENT_QUOTES) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email, ENT_QUOTES) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?= htmlspecialchars($phone, ENT_QUOTES) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary" name="submit">Update</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include("../../Includes/footer.php"); ?>
    <script src="../../Assets/JS/jquery-3.7.1.js"></script>
    <script src="../../Assets/JS/bootstrap.bundle.min.js"></script>
    <script src="../../Assets/JS/perfect-scrollbar.min.js"></script>
    <script src="../../Assets/JS/smooth-scrollbar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../Assets/JS/custom.js"></script>
    <script src="../../Assets/JS/searchbar.js"></script>
    <script src="//cdn.jsdelivr.net/npm/alertifyjs@1.13.1/build/alertify.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script type="text/javascript">
        var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
        (function(){
            var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
            s1.async=true;
            s1.src='https://embed.tawk.to/65ff54951ec1082f04da7f5c/1hpmm4q27';
            s1.charset='UTF-8';
            s1.setAttribute('crossorigin','*');
            s0.parentNode.insertBefore(s1,s0);
        })();
    </script>
</body>
</html>
