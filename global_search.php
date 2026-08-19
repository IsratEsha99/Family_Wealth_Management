<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$q = trim($_GET['q'] ?? '');

if ($q === '' || mb_strlen($q) < 2) {
    echo json_encode([], JSON_UNESCAPED_UNICODE);
    exit;
}

$like = '%' . $q . '%';
$results = [];

try {
    // Family members
    $stmt = $pdo->prepare("
        SELECT id, full_name, relationship
        FROM family_members
        WHERE full_name LIKE ? OR relationship LIKE ?
        ORDER BY full_name
        LIMIT 5
    ");
    $stmt->execute([$like, $like]);

    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type' => 'Family',
            'label' => $r['full_name'],
            'meta' => $r['relationship'],
            'url' => app_url('family/index.php') . '?member=' . (int)$r['id']
        ];
    }

    // Income
    $stmt = $pdo->prepare("
        SELECT i.id, i.source, i.category, i.amount, m.full_name
        FROM income i
        JOIN family_members m ON m.id = i.member_id
        WHERE i.source LIKE ? OR i.category LIKE ? OR m.full_name LIKE ?
        ORDER BY i.date_received DESC, i.id DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);

    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type' => 'Income',
            'label' => $r['source'],
            'meta' => $r['full_name'] . ' · ৳' . number_format((float)$r['amount'], 0),
            'url' => app_url('income/index.php')
        ];
    }

    // Expenses
    $stmt = $pdo->prepare("
        SELECT e.id, e.ref, e.category, e.description, e.amount, m.full_name
        FROM expenses e
        LEFT JOIN family_members m ON m.id = e.member_id
        WHERE e.ref LIKE ?
           OR e.category LIKE ?
           OR e.description LIKE ?
           OR m.full_name LIKE ?
        ORDER BY e.expense_date DESC, e.id DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like, $like]);

    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type' => 'Expense',
            'label' => ($r['description'] ?: $r['category']) . ' · ' . $r['ref'],
            'meta' => ($r['full_name'] ?: 'Family') . ' · ৳' . number_format((float)$r['amount'], 0),
            'url' => app_url('expenses/index.php') . '?ref=' . urlencode($r['ref'])
        ];
    }

    // Funding codes
    $stmt = $pdo->prepare("
        SELECT id, code, name, category
        FROM funding_codes
        WHERE code LIKE ? OR name LIKE ? OR category LIKE ?
        ORDER BY code
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);

    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type' => 'Funding Code',
            'label' => $r['code'] . ' — ' . $r['name'],
            'meta' => $r['category'],
            'url' => app_url('funding_codes/index.php') . '#' . rawurlencode($r['code'])
        ];
    }

    // Savings accounts
    $stmt = $pdo->prepare("
        SELECT a.id, a.account_name, a.bank_institution, a.balance, m.full_name
        FROM savings_accounts a
        JOIN family_members m ON m.id = a.member_id
        WHERE a.account_name LIKE ?
           OR a.bank_institution LIKE ?
           OR a.account_type LIKE ?
           OR m.full_name LIKE ?
        ORDER BY a.id
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like, $like]);

    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type' => 'Savings',
            'label' => $r['account_name'],
            'meta' => $r['full_name'] . ' · ' . $r['bank_institution'] . ' · ৳' . number_format((float)$r['balance'], 0),
            'url' => app_url('savings/index.php')
        ];
    }

    // Documents
    $stmt = $pdo->prepare("
        SELECT d.id, d.original_name, d.category, m.full_name
        FROM documents d
        LEFT JOIN family_members m ON m.id = d.member_id
        WHERE d.original_name LIKE ? OR d.category LIKE ? OR m.full_name LIKE ?
        ORDER BY d.uploaded_at DESC
        LIMIT 5
    ");
    $stmt->execute([$like, $like, $like]);

    foreach ($stmt->fetchAll() as $r) {
        $results[] = [
            'type' => 'Document',
            'label' => $r['original_name'],
            'meta' => $r['category'] . ($r['full_name'] ? ' · ' . $r['full_name'] : ''),
            'url' => app_url('document_vault/index.php') . '?q=' . urlencode($q)
        ];
    }

    echo json_encode(
        array_slice($results, 0, 12),
        JSON_UNESCAPED_UNICODE
    );

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Search temporarily unavailable.'
    ], JSON_UNESCAPED_UNICODE);
}
?>
