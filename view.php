<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE id=?");
$stmt->execute([$id]);
$n = $stmt->fetch();

if ($n) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=?")->execute([$id]);
    header('Location: ' . ($n['related_url'] ?: 'index.php'));
    exit;
}

header('Location: index.php');
exit;
