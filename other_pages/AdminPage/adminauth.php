<?php
declare(strict_types=1);

$role = strtolower((string)($_SESSION['auth_user']['role'] ?? $_SESSION['role'] ?? ''));
if ($role !== 'admin') {
    header('Location: ../LoginPage/login_page.php');
    exit;
}
