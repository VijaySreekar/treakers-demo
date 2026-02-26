<?php
declare(strict_types=1);

$projectRoot = realpath(__DIR__ . '/..');
if ($projectRoot === false) {
    http_response_code(500);
    echo 'Server misconfiguration.';
    exit;
}

// Vercel serverless functions have a writable temp directory; use it for PHP sessions.
if (is_dir(sys_get_temp_dir())) {
    ini_set('session.save_path', sys_get_temp_dir());
}

$routedPath = $_GET['__path'] ?? null;
if (!is_string($routedPath)) {
    $routedPath = '';
} else {
    unset($_GET['__path']);
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = $routedPath !== '' ? $routedPath : parse_url($requestUri, PHP_URL_PATH);
if ($path === null || $path === false) {
    $path = '/';
}

$path = rawurldecode($path);
$path = str_replace("\0", '', $path);
if ($path !== '' && $path[0] !== '/') {
    $path = '/' . $path;
}

if ($path === '' || $path === '/') {
    $path = '/index.php';
}

if (strpos($path, '..') !== false) {
    http_response_code(400);
    echo 'Bad request.';
    exit;
}

$target = $projectRoot . $path;
if (!preg_match('/\\.php$/i', $target) && file_exists($target . '.php')) {
    $target .= '.php';
}

$targetReal = realpath($target);
if ($targetReal === false || strpos($targetReal, $projectRoot) !== 0) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

if (is_dir($targetReal)) {
    $indexReal = realpath(rtrim($targetReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'index.php');
    if ($indexReal === false) {
        http_response_code(404);
        echo 'Not found.';
        exit;
    }
    $targetReal = $indexReal;
}

$ext = strtolower(pathinfo($targetReal, PATHINFO_EXTENSION));
if ($ext !== 'php') {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

if ($targetReal === __FILE__) {
    $indexReal = realpath($projectRoot . DIRECTORY_SEPARATOR . 'index.php');
    if ($indexReal === false) {
        http_response_code(500);
        echo 'Server misconfiguration.';
        exit;
    }
    $targetReal = $indexReal;
}

chdir(dirname($targetReal));

$_SERVER['DOCUMENT_ROOT'] = $projectRoot;
$_SERVER['SCRIPT_FILENAME'] = $targetReal;

require $targetReal;
