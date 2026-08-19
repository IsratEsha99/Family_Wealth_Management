<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if ($q === '') { echo '[]'; exit; }

$stmt = $pdo->prepare("SELECT d.original_name, d.stored_name, d.category, m.full_name FROM documents d
    LEFT JOIN family_members m ON m.id=d.member_id
    WHERE d.original_name LIKE ? OR d.category LIKE ? OR m.full_name LIKE ?
    ORDER BY d.uploaded_at DESC LIMIT 8");
$like = '%' . $q . '%';
$stmt->execute([$like, $like, $like]);

$out = [];
foreach ($stmt->fetchAll() as $r) {
    $out[] = [
        'label' => $r['original_name'] . ' — ' . $r['category'] . ($r['full_name'] ? ' · ' . $r['full_name'] : ''),
        'file' => $r['stored_name'],
    ];
}
echo json_encode($out);
