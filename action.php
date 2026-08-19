<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();

$id = (int)($_GET['id'] ?? 0);
$do = $_GET['do'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM income WHERE id=?");
$stmt->execute([$id]);
$row = $stmt->fetch();

if ($row) {
    if ($do === 'approve' && $row['status'] === 'Pending') {
        $pdo->prepare("UPDATE income SET status='Approved' WHERE id=?")->execute([$id]);
    } elseif ($do === 'credit' && $row['status'] === 'Approved') {
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE income SET status='Credited' WHERE id=?")->execute([$id]);
        $pdo->prepare("UPDATE savings_accounts SET balance = balance + ? WHERE id=?")->execute([$row['amount'], $row['credit_account_id']]);
        $pdo->prepare("INSERT INTO savings_transactions (account_id, amount, transaction_type, transaction_date, note) VALUES (?,?,'credit',?,?)")
            ->execute([$row['credit_account_id'], $row['amount'], $row['date_received'], $row['category'] . ' (Income module)']);
        $pdo->commit();
    }
}

header('Location: index.php');
exit;
