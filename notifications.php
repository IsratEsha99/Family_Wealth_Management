<?php
// Module 8 — Notifications
// Notifications are computed from live data (documents, funding
// codes, expenses, income, savings) and upserted into the
// `notifications` table by ref_key, so read/unread state survives
// across page loads while the underlying facts stay in sync.

function get_notification_settings(PDO $pdo, int $userId): array {
    $defaults = [
        'passport_id_expiry' => 1, 'license_expiry' => 1, 'insurance_expiry' => 1,
        'low_cash' => 1, 'funding_thresholds' => 1, 'pending_approvals' => 1,
        'missing_documents' => 0, 'monthly_close_report' => 1,
    ];
    $stmt = $pdo->prepare("SELECT * FROM notification_settings WHERE user_id=?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        $stmt = $pdo->prepare("INSERT INTO notification_settings (user_id,passport_id_expiry,license_expiry,insurance_expiry,low_cash,funding_thresholds,pending_approvals,missing_documents,monthly_close_report) VALUES (?,1,1,1,1,1,1,0,1)");
        $stmt->execute([$userId]);
        return $defaults;
    }
    return $row;
}

function save_notification_settings(PDO $pdo, int $userId, array $data): void {
    get_notification_settings($pdo, $userId);
    $fields = ['passport_id_expiry','license_expiry','insurance_expiry','low_cash','funding_thresholds','pending_approvals','missing_documents','monthly_close_report'];
    $set = [];
    $params = [];
    foreach ($fields as $f) { $set[] = "$f=?"; $params[] = !empty($data[$f]) ? 1 : 0; }
    $params[] = $userId;
    $pdo->prepare("UPDATE notification_settings SET " . implode(',', $set) . " WHERE user_id=?")->execute($params);
}

function notif_upsert(PDO $pdo, string $refKey, string $type, string $severity, string $title, string $message, ?string $url): void {
    $stmt = $pdo->prepare("INSERT INTO notifications (ref_key,type,severity,title,message,related_url) VALUES (?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE severity=VALUES(severity), title=VALUES(title), message=VALUES(message), related_url=VALUES(related_url)");
    $stmt->execute([$refKey, $type, $severity, $title, $message, $url]);
}

function notif_cleanup(PDO $pdo, string $type, array $activeKeys): void {
    if (empty($activeKeys)) {
        $pdo->prepare("DELETE FROM notifications WHERE type=?")->execute([$type]);
        return;
    }
    $placeholders = implode(',', array_fill(0, count($activeKeys), '?'));
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE type=? AND ref_key NOT IN ($placeholders)");
    $stmt->execute(array_merge([$type], $activeKeys));
}

function sync_notifications(PDO $pdo, ?int $userId = null): void {
    $settings = $userId ? get_notification_settings($pdo, $userId) : array_fill_keys(
        ['passport_id_expiry','license_expiry','insurance_expiry','low_cash','funding_thresholds','pending_approvals','missing_documents','monthly_close_report'], 1
    );

    // Budget threshold alerts
    $activeBudget = [];
    if (!empty($settings['funding_thresholds'])) {
        $codes = $pdo->query("SELECT fc.*, COALESCE(SUM(CASE WHEN e.status='Approved' THEN e.amount ELSE 0 END),0) spent
            FROM funding_codes fc LEFT JOIN expenses e ON e.funding_code_id=fc.id GROUP BY fc.id")->fetchAll();
        foreach ($codes as $c) {
            $pct = $c['budget'] > 0 ? $c['spent'] / $c['budget'] : 0;
            $sev = null; $verb = null;
            if ($pct >= 1 && $c['alert_100']) { $sev = 'danger'; $verb = 'exceeded 100%'; }
            elseif ($pct >= 0.9 && $c['alert_90']) { $sev = 'warning'; $verb = 'reached 90%'; }
            elseif ($pct >= 0.8 && $c['alert_80']) { $sev = 'warning'; $verb = 'reached 80%'; }
            if ($sev) {
                $key = 'budget_' . $c['id'];
                $activeBudget[] = $key;
                notif_upsert($pdo, $key, 'budget_alert', $sev,
                    'Funding Code ' . $c['code'] . ' ' . ($sev === 'danger' ? 'Exceeded Budget' : 'Nearing Budget'),
                    $c['name'] . ' budget (৳' . number_format($c['budget']) . ') ' . $verb . ' — current spend: ৳' . number_format($c['spent']) . '.',
                    '../funding_codes/index.php');
            }
        }
    }
    notif_cleanup($pdo, 'budget_alert', $activeBudget);

    // Document expiry alerts (identity docs + vault docs expiring within 60 days)
    $activeExpiry = [];
    if (!empty($settings['passport_id_expiry']) || !empty($settings['license_expiry']) || !empty($settings['insurance_expiry'])) {
        $docs = $pdo->query("SELECT d.id, d.document_type, d.expiry_date, m.id member_id, m.full_name FROM member_documents d
            JOIN family_members m ON m.id=d.member_id
            WHERE d.expiry_date IS NOT NULL AND d.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetchAll();
        foreach ($docs as $d) {
            $key = 'expiry_member_' . $d['id'];
            $activeExpiry[] = $key;
            $days = (int)((strtotime($d['expiry_date']) - time()) / 86400);
            $sev = $days < 0 ? 'danger' : 'warning';
            $when = $days < 0 ? 'expired' : ('expires in ' . $days . ' days');
            notif_upsert($pdo, $key, 'expiry', $sev,
                $d['document_type'] . ' Expiring — ' . $d['full_name'],
                $d['document_type'] . ' ' . $when . ' (' . date('M Y', strtotime($d['expiry_date'])) . '). Please initiate renewal.',
                '../family/index.php?member=' . $d['member_id']);
        }
        $vault = $pdo->query("SELECT id, category, original_name, expiry_date, member_id FROM documents
            WHERE expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)")->fetchAll();
        foreach ($vault as $d) {
            $key = 'expiry_doc_' . $d['id'];
            $activeExpiry[] = $key;
            $days = (int)((strtotime($d['expiry_date']) - time()) / 86400);
            $sev = $days < 0 ? 'danger' : 'warning';
            $when = $days < 0 ? 'expired' : ('expires ' . date('M Y', strtotime($d['expiry_date'])));
            notif_upsert($pdo, $key, 'expiry', $sev,
                $d['category'] . ' Expiring — ' . $d['original_name'],
                $d['category'] . ' document ' . $when . '. Auto-reminder set.',
                '../document_vault/index.php');
        }
    }
    notif_cleanup($pdo, 'expiry', $activeExpiry);

    // Pending approvals (expenses + income)
    $activeApproval = [];
    if (!empty($settings['pending_approvals'])) {
        $exp = $pdo->query("SELECT id, ref, description, amount FROM expenses WHERE status='Pending'")->fetchAll();
        foreach ($exp as $e) {
            $key = 'approval_exp_' . $e['id'];
            $activeApproval[] = $key;
            notif_upsert($pdo, $key, 'approval', 'info',
                'Expense ' . $e['ref'] . ' Awaiting Your Approval',
                ($e['description'] ?: 'Expense') . ' (৳' . number_format($e['amount']) . ') is pending approval.',
                '../expenses/index.php');
        }
        $inc = $pdo->query("SELECT id, source, amount FROM income WHERE status='Pending'")->fetchAll();
        foreach ($inc as $i) {
            $key = 'approval_inc_' . $i['id'];
            $activeApproval[] = $key;
            notif_upsert($pdo, $key, 'approval', 'info',
                'Income Entry Awaiting Approval',
                $i['source'] . ' (৳' . number_format($i['amount']) . ') is pending approval.',
                '../income/index.php');
        }
    }
    notif_cleanup($pdo, 'approval', $activeApproval);

    // Low cash balances
    $activeLowCash = [];
    if (!empty($settings['low_cash'])) {
        $threshold = 50000;
        $stmt = $pdo->prepare("SELECT a.id, a.account_name, a.balance, m.full_name FROM savings_accounts a
            JOIN family_members m ON m.id=a.member_id WHERE a.balance < ?");
        $stmt->execute([$threshold]);
        foreach ($stmt->fetchAll() as $a) {
            $key = 'lowcash_' . $a['id'];
            $activeLowCash[] = $key;
            notif_upsert($pdo, $key, 'low_cash', 'danger',
                'Low Cash Balance — ' . $a['account_name'],
                'Balance fell below threshold (৳' . number_format($threshold) . '). Current: ৳' . number_format($a['balance']) . '.',
                '../savings/index.php');
        }
    }
    notif_cleanup($pdo, 'low_cash', $activeLowCash);

    // Missing documents (no Medical category document on file)
    $activeMissing = [];
    if (!empty($settings['missing_documents'])) {
        $members = $pdo->query("SELECT m.id, m.full_name FROM family_members m
            WHERE NOT EXISTS (SELECT 1 FROM documents d WHERE d.member_id=m.id AND d.category='Medical')")->fetchAll();
        foreach ($members as $m) {
            $key = 'missing_doc_' . $m['id'];
            $activeMissing[] = $key;
            notif_upsert($pdo, $key, 'missing_doc', 'info',
                'Missing Document — ' . $m['full_name'] . ' Medical',
                'Medical insurance document not found for ' . $m['full_name'] . '.',
                '../document_vault/index.php');
        }
    }
    notif_cleanup($pdo, 'missing_doc', $activeMissing);
}

function unread_notifications_count(PDO $pdo, ?int $userId = null): int {
    // Modules 1-2 also load this file via header.php; if modules_3_8.sql
    // hasn't been imported yet the new tables won't exist, so fail quiet
    // rather than fatal-erroring pages that don't otherwise need them.
    try {
        sync_notifications($pdo, $userId);
        return (int)$pdo->query("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}

function pending_expenses_count(PDO $pdo): int {
    try {
        return (int)$pdo->query("SELECT COUNT(*) FROM expenses WHERE status='Pending'")->fetchColumn();
    } catch (PDOException $e) {
        return 0;
    }
}
