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

$username = trim((string)($_POST['reg_name'] ?? ''));
$phone = trim((string)($_POST['reg_phone'] ?? ''));
$email = trim((string)($_POST['reg_email'] ?? ''));
$password = (string)($_POST['reg_password'] ?? '');
$confirm = (string)($_POST['reg_confirmpassword'] ?? '');

if ($username === '' || $email === '' || $password === '' || $confirm === '') {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

if ($password !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'Password and Confirm Password do not match!']);
    exit;
}

// Keep demo simple: minimal password policy, but avoid empty/very short passwords.
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
    exit;
}

$check = db()->prepare('SELECT user_id FROM user WHERE email = :email LIMIT 1');
$check->execute(['email' => $email]);
if ($check->fetchColumn() !== false) {
    echo json_encode(['success' => false, 'message' => 'Email already exists!']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$insert = db()->prepare(
    "INSERT INTO user (username, phone, email, passwordhash, role)
     VALUES (:username, :phone, :email, :passwordhash, 'user')"
);

$insert->execute([
    'username' => $username,
    'phone' => $phone,
    'email' => $email,
    'passwordhash' => $hash,
]);

echo json_encode(['success' => true, 'message' => 'User registered successfully. Please log in.']);
