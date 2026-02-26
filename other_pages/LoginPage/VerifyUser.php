<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/../../Assets/Functions/myfunctions.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$email = trim((string)($_POST['login_email'] ?? ''));
$password = (string)($_POST['login_password'] ?? '');

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Email and password are required.']);
    exit;
}

$stmt = db()->prepare('SELECT user_id, username, role, passwordhash FROM user WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if ($user === false) {
    echo json_encode(['success' => false, 'message' => 'User not found. Please check your email.']);
    exit;
}

$storedHash = (string)($user['passwordhash'] ?? '');
if ($storedHash === '' || !password_verify($password, $storedHash)) {
    echo json_encode(['success' => false, 'message' => 'Incorrect password. Please try again.']);
    exit;
}

session_regenerate_id(true);

$userId = (int)($user['user_id'] ?? 0);
$username = (string)($user['username'] ?? '');
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

echo json_encode([
    'success' => true,
    'message' => 'Login successful',
    'role' => $role,
    'username' => $username,
]);
