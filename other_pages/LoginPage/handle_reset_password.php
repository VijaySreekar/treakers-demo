<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$email = trim((string)($_POST['email'] ?? ''));
$newPassword = (string)($_POST['new_password'] ?? '');
$confirmPassword = (string)($_POST['confirm_password'] ?? '');

if ($email === '' || $newPassword === '' || $confirmPassword === '') {
    $_SESSION['message'] = 'Please fill all fields.';
    $_SESSION['alert_type'] = 'error';
    header('Location: forgot_password.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['message'] = 'Please enter a valid email address.';
    $_SESSION['alert_type'] = 'error';
    header('Location: forgot_password.php');
    exit;
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['message'] = 'Passwords do not match.';
    $_SESSION['alert_type'] = 'error';
    header('Location: forgot_password.php');
    exit;
}

if (strlen($newPassword) < 6) {
    $_SESSION['message'] = 'Password must be at least 6 characters.';
    $_SESSION['alert_type'] = 'error';
    header('Location: forgot_password.php');
    exit;
}

$stmt = db()->prepare('SELECT user_id FROM user WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$userId = $stmt->fetchColumn();

if ($userId === false) {
    $_SESSION['message'] = 'No account found with that email address.';
    $_SESSION['alert_type'] = 'error';
    header('Location: forgot_password.php');
    exit;
}

$hash = password_hash($newPassword, PASSWORD_DEFAULT);
$update = db()->prepare('UPDATE user SET passwordhash = :hash WHERE user_id = :id');
$update->execute(['hash' => $hash, 'id' => (int)$userId]);

$_SESSION['message'] = 'Password updated. You can now log in.';
$_SESSION['alert_type'] = 'success';
header('Location: login_page.php');
exit;

