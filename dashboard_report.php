<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();
$page_title = 'Financial Report';
$isClose = ($_GET['mode'] ?? '') === 'close';

function money($n){ return '৳'.number_format((float)$n,0); }

$netWorth = (float)$pdo->query("SELECT COALESCE(SUM(personal_net_worth),0) FROM family_members")->fetchColumn();
$liquidCash = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM savings_accounts")->fetchColumn();
$monthStart = date('Y-m-01');
$incomeMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM income WHERE date_received >= '$monthStart' AND status IN ('Approved','Credited')")->fetchColumn();
$expenseMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= '$monthStart' AND status='Approved'")->fetchColumn();

$expenseByCode = $pdo->query("SELECT fc.code, fc.name, COALESCE(SUM(e.amount),0) spent FROM funding_codes fc
    LEFT JOIN expenses e ON e.funding_code_id=fc.id AND e.status='Approved' AND e.expense_date >= '$monthStart'
    GROUP BY fc.id ORDER BY fc.code")->fetchAll();

$txns = $pdo->query("
    (SELECT expense_date d, description desc_, category cat, amount amt, 'Expense' t FROM expenses WHERE expense_date >= '$monthStart' AND status='Approved')
    UNION ALL
    (SELECT date_received d, source desc_, category cat, amount amt, 'Income' t FROM income WHERE date_received >= '$monthStart' AND status IN ('Approved','Credited'))
    ORDER BY d
")->fetchAll();

require 'includes/header.php';
?>
<div class="content">
<div class="page-head no-print">
  <div><h1><?= $isClose ? 'Monthly Close Report' : 'Financial Report' ?></h1><div class="subtitle">Generated <?= date('M d, Y H:i') ?></div></div>
  <button class="primary-btn" onclick="window.print()">🖨 Print / Save as PDF</button>
</div>

<div class="card">
  <h2 style="margin-top:0"><?= $isClose ? 'Monthly Financial Close — ' . date('F Y') : 'Family Financial Report' ?></h2>
  <p class="subtitle">Family Wealth Management System · Prepared for <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></p>

  <div class="grid stat-row" style="margin:20px 0">
    <div><small class="subtitle">Family Net Worth</small><br><strong><?= money($netWorth) ?></strong></div>
    <div><small class="subtitle">Income (<?= date('M') ?>)</small><br><strong><?= money($incomeMonth) ?></strong></div>
    <div><small class="subtitle">Expenses (<?= date('M') ?>)</small><br><strong><?= money($expenseMonth) ?></strong></div>
    <div><small class="subtitle">Liquid Cash</small><br><strong><?= money($liquidCash) ?></strong></div>
  </div>

  <div class="section-label">Spend by Funding Code</div>
  <div class="table-wrap"><table class="data-table"><thead><tr><th>CODE</th><th>NAME</th><th>SPENT THIS MONTH</th></tr></thead><tbody>
  <?php foreach ($expenseByCode as $c): ?><tr><td><?= htmlspecialchars($c['code']) ?></td><td><?= htmlspecialchars($c['name']) ?></td><td><?= money($c['spent']) ?></td></tr><?php endforeach; ?>
  </tbody></table></div>

  <div class="section-label">Approved Transactions — <?= date('F Y') ?></div>
  <div class="table-wrap"><table class="data-table"><thead><tr><th>DATE</th><th>TYPE</th><th>DESCRIPTION</th><th>CATEGORY</th><th>AMOUNT</th></tr></thead><tbody>
  <?php foreach ($txns as $t): ?><tr><td><?= date('M d, Y', strtotime($t['d'])) ?></td><td><?= htmlspecialchars($t['t']) ?></td><td><?= htmlspecialchars($t['desc_']) ?></td><td><?= htmlspecialchars($t['cat']) ?></td><td><?= ($t['t']==='Income'?'+':'-').money($t['amt']) ?></td></tr><?php endforeach; ?>
  <?php if (!$txns): ?><tr><td colspan="5">No approved transactions this month.</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
</div>
<?php require 'includes/footer.php'; ?>
