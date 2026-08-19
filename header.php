<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notifications.php';
$current = basename($_SERVER['PHP_SELF']);
$__pending_expenses = pending_expenses_count($pdo);
$__unread_notifs = !empty($_SESSION['user_id']) ? unread_notifications_count($pdo, (int)$_SESSION['user_id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>FamilyWealth Management System</title>
<link rel="stylesheet" href="<?= app_url('assets/css/style.css') ?>">
</head>
<body>
<div class="app-shell">
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon">FW</div>
        <div>
            <strong>FamilyWealth</strong>
            <span>MANAGEMENT SYSTEM</span>
        </div>
    </div>

    <nav class="side-nav">
        <a href="<?= app_url('dashboard.php') ?>">▦ <span>Dashboard</span></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/family/') ? 'active':'' ?>" href="<?= app_url('family/index.php') ?>">◇ <span>Family Structure</span></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/savings/') ? 'active':'' ?>" href="<?= app_url('savings/index.php') ?>">◎ <span>Savings</span></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/income/') ? 'active':'' ?>" href="<?= app_url('income/index.php') ?>">↑ <span>Income</span></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/expenses/') ? 'active':'' ?>" href="<?= app_url('expenses/index.php') ?>">↓ <span>Expenses</span><?php if($__pending_expenses>0): ?><span class="nav-badge"><?= $__pending_expenses ?></span><?php endif; ?></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/funding_codes/') ? 'active':'' ?>" href="<?= app_url('funding_codes/index.php') ?>">▭ <span>Funding Codes</span></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/document_vault/') ? 'active':'' ?>" href="<?= app_url('document_vault/index.php') ?>">▤ <span>Document Vault</span></a>
        <a class="<?= str_contains($_SERVER['REQUEST_URI'],'/notifications/') ? 'active':'' ?>" href="<?= app_url('notifications/index.php') ?>">▯ <span>Notifications</span><?php if($__unread_notifs>0): ?><span class="nav-badge danger"><?= $__unread_notifs ?></span><?php endif; ?></a>
    </nav>

    <div class="sidebar-bottom">
        <?php if (!empty($_SESSION['user_id'])): ?>
            <div class="user-mini">
                <div class="avatar small"><?= strtoupper(substr($_SESSION['full_name'],0,2)) ?></div>
                <div>
                    <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>
                    <span>Administrator</span>
                </div>
            </div>
            <a class="logout-link" href="<?= app_url('auth/logout.php') ?>">↪ Logout</a>
        <?php endif; ?>
    </div>
</aside>

<main class="main">
<header class="topbar">
    <div class="breadcrumb">Family Wealth Management System <b>/</b> <?= htmlspecialchars($page_title ?? 'Dashboard') ?></div>
    <div class="top-actions">
        <a class="export-btn" href="#">↓ Export</a>
        <div class="global-search-wrap" data-endpoint="<?= app_url('global_search.php') ?>">
            <div class="search-mini" id="globalSearchWrap">
                <span class="search-icon">⌕</span>
                <input type="search" id="globalSearch" placeholder="Quick search..." autocomplete="off" aria-label="Quick search">
            </div>
            <div class="search-suggest global-search-suggest" id="globalSearchSuggest" role="listbox"></div>
        </div>
        <a class="notification-dot <?= $__unread_notifs>0 ? 'has-unread':'' ?>" style="text-decoration:none" href="<?= app_url('notifications/index.php') ?>"><?= $__unread_notifs ?></a>
    </div>
</header>
