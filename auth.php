<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function app_base_path() {
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $projectRoot = dirname(__DIR__);

    if ($documentRoot !== '' && strpos($projectRoot, $documentRoot) === 0) {
        $relative = substr($projectRoot, strlen($documentRoot));
        $relative = str_replace('\\', '/', $relative);
        return rtrim($relative, '/');
    }

    return '';
}

function app_url($path = '') {
    $base = app_base_path();
    $base = $base !== '' ? '/' . ltrim($base, '/') : '';
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function require_login() {
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . app_url('auth/login.php'));
        exit;
    }
}
?>