<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();

$rows = $pdo->query("
    (SELECT expense_date d, 'Expense' t, description desc_, category cat, amount amt, status st FROM expenses)
    UNION ALL
    (SELECT date_received d, 'Income' t, source desc_, category cat, amount amt, status st FROM income)
    ORDER BY d DESC
")->fetchAll();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="familywealth_transactions_' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['Date', 'Type', 'Description', 'Category', 'Amount', 'Status']);
foreach ($rows as $r) {
    fputcsv($out, [$r['d'], $r['t'], $r['desc_'], $r['cat'], $r['amt'], $r['st']]);
}
fclose($out);
exit;
