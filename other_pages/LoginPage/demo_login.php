<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

$stmt = db()->prepare("SELECT user_id, username, role FROM user WHERE email = :email LIMIT 1");
$stmt->execute(['email' => 'demo@example.com']);
$user = $stmt->fetch();

$userId = (int)($user['user_id'] ?? 1);
$username = (string)($user['username'] ?? 'DemoUser');
$role = (string)($user['role'] ?? 'user');

$_SESSION['authenticated'] = true;
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
$_SESSION['role'] = $role;
$_SESSION['auth_user'] = [
    'user_id' => $userId,
    'username' => $username,
    'role' => $role,
];

header('Location: /');
exit;
