<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_login();
$page_title = 'Dashboard';

function money($n){ return '৳'.number_format((float)$n,0); }

$netWorth = (float)$pdo->query("SELECT COALESCE(SUM(personal_net_worth),0) FROM family_members")->fetchColumn();
$liquidCash = (float)$pdo->query("SELECT COALESCE(SUM(balance),0) FROM savings_accounts")->fetchColumn();
$accountCount = (int)$pdo->query("SELECT COUNT(*) FROM savings_accounts")->fetchColumn();

$monthStart = date('Y-m-01');
$incomeMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM income WHERE date_received >= '$monthStart' AND status IN ('Approved','Credited')")->fetchColumn();
$incomeSources = (int)$pdo->query("SELECT COUNT(DISTINCT category) FROM income WHERE date_received >= '$monthStart'")->fetchColumn();
$expenseMonth = (float)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date >= '$monthStart' AND status='Approved'")->fetchColumn();
$expenseTxns = (int)$pdo->query("SELECT COUNT(*) FROM expenses WHERE expense_date >= '$monthStart'")->fetchColumn();

$netCashFlowMonth = $incomeMonth - $expenseMonth;

$allocation = $pdo->query("SELECT account_type, SUM(balance) total FROM savings_accounts GROUP BY account_type ORDER BY total DESC")->fetchAll();
$allocTotal = array_sum(array_column($allocation, 'total'));
$allocColors = ['#5c7cf5', '#8aa0f0', '#c6cde3', '#9db6ff', '#b6c2e8'];

$months = [];
for ($i = 5; $i >= 0; $i--) { $months[] = date('Y-m', strtotime("-$i months")); }
$incomeByMonth = []; $expenseByMonth = [];
$incStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM income WHERE DATE_FORMAT(date_received,'%Y-%m')=? AND status IN ('Approved','Credited')");
$expStmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM expenses WHERE DATE_FORMAT(expense_date,'%Y-%m')=? AND status='Approved'");
foreach ($months as $ym) {
    $incStmt->execute([$ym]); $incomeByMonth[] = (float)$incStmt->fetchColumn();
    $expStmt->execute([$ym]); $expenseByMonth[] = (float)$expStmt->fetchColumn();
}
$maxFlow = max(array_merge($incomeByMonth, $expenseByMonth, [1]));
$netByMonth = [];
foreach ($months as $i => $ym) { $netByMonth[] = $incomeByMonth[$i] - $expenseByMonth[$i]; }

$fundingCodes = $pdo->query("SELECT fc.*, COALESCE((SELECT SUM(amount) FROM expenses WHERE funding_code_id=fc.id AND status='Approved'),0) spent FROM funding_codes fc ORDER BY fc.code")->fetchAll();

$recentTx = $pdo->query("
    (SELECT expense_date d, description desc_, category cat, amount amt, status st, 'expense' kind FROM expenses ORDER BY expense_date DESC LIMIT 5)
    UNION ALL
    (SELECT date_received d, source desc_, category cat, amount amt, status st, 'income' kind FROM income ORDER BY date_received DESC LIMIT 5)
    ORDER BY d DESC LIMIT 6
")->fetchAll();

require 'includes/header.php';
?>
<div class="content">
<div class="page-head">
  <div><h1>Family Dashboard</h1><div class="subtitle"><?= date('F Y') ?> · Financial Overview</div></div>
  <div class="toolbar">
    <a class="outline-btn" href="dashboard_export.php?type=csv">Export CSV</a>
    <a class="outline-btn" href="dashboard_report.php" target="_blank">Export PDF</a>
    <a class="dark-btn" href="dashboard_report.php?mode=close" target="_blank">Monthly Close</a>
  </div>
</div>

<div class="grid stat-row" style="margin-bottom:14px">
  <div class="card stat-card"><small>Family Net Worth</small><strong><?= money($netWorth) ?></strong><span class="stat-trend" style="color:<?= $netCashFlowMonth>=0?'#079b42':'#e03b3b' ?>"><?= $netCashFlowMonth>=0?'+':'' ?><?= money($netCashFlowMonth) ?> net this month</span></div>
  <div class="card stat-card"><small>Total Income (<?= date('M') ?>)</small><strong><?= money($incomeMonth) ?></strong><span class="subtitle"><?= $incomeSources ?> sources</span></div>
  <div class="card stat-card"><small>Total Expenses (<?= date('M') ?>)</small><strong><?= money($expenseMonth) ?></strong><span class="subtitle"><?= $expenseTxns ?> transactions</span></div>
  <div class="card stat-card"><small>Liquid Cash</small><strong><?= money($liquidCash) ?></strong><span class="subtitle">Across <?= $accountCount ?> accounts</span></div>
</div>

<div class="grid dash-charts" style="margin-bottom:14px">
  <div class="card">
    <div class="card-title">Asset Allocation</div>
    <div class="donut-wrap">
      <svg width="120" height="120" viewBox="0 0 42 42">
        <circle cx="21" cy="21" r="15.9" fill="transparent" stroke="#eef0f5" stroke-width="6"></circle>
        <?php $offset = 25; foreach ($allocation as $i => $a):
            $pct = $allocTotal > 0 ? (100 * $a['total'] / $allocTotal) : 0;
            $color = $allocColors[$i % count($allocColors)];
        ?>
        <circle cx="21" cy="21" r="15.9" fill="transparent" stroke="<?= $color ?>" stroke-width="6"
          stroke-dasharray="<?= round($pct,2) ?> <?= round(100-$pct,2) ?>" stroke-dashoffset="<?= $offset ?>"></circle>
        <?php $offset -= $pct; endforeach; ?>
      </svg>
      <div class="donut-legend">
        <?php foreach ($allocation as $i => $a): ?>
        <div><span class="legend-dot" style="background:<?= $allocColors[$i % count($allocColors)] ?>"></span><?= htmlspecialchars($a['account_type']) ?></div>
        <?php endforeach; ?>
        <?php if (!$allocation): ?><div class="subtitle">No savings accounts yet.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Income vs Expenses</div>
    <div class="bars-row">
      <?php foreach ($months as $i => $ym): ?>
      <div class="bar-pair">
        <i class="income" style="height:<?= max(4, round(100*$incomeByMonth[$i]/$maxFlow)) ?>%" title="Income <?= money($incomeByMonth[$i]) ?>"></i>
        <i class="expense" style="height:<?= max(4, round(100*$expenseByMonth[$i]/$maxFlow)) ?>%" title="Expenses <?= money($expenseByMonth[$i]) ?>"></i>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="bars-axis"><?php foreach ($months as $ym): ?><span><?= date('M', strtotime($ym.'-01')) ?></span><?php endforeach; ?></div>
    <div class="legend-row"><span><span class="legend-dot" style="background:#5c7cf5"></span>Income</span><span><span class="legend-dot" style="background:#d9deea"></span>Expense</span></div>
  </div>

  <div class="card">
    <div class="card-title">Cash Flow (6 months)</div>
    <?php
      $w = 220; $h = 100; $minNet = min($netByMonth); $maxNet = max($netByMonth);
      $range = ($maxNet - $minNet) ?: 1;
      $pts = [];
      foreach ($netByMonth as $i => $v) {
          $x = round($i * ($w / (count($netByMonth)-1)));
          $y = round($h - (($v - $minNet) / $range) * ($h - 10) - 5);
          $pts[] = "$x,$y";
      }
    ?>
    <svg viewBox="0 0 <?= $w ?> <?= $h ?>" width="100%" height="110" preserveAspectRatio="none">
      <polyline fill="none" stroke="#5c7cf5" stroke-width="2.5" points="<?= implode(' ', $pts) ?>"></polyline>
      <?php foreach ($pts as $p): [$px,$py] = explode(',', $p); ?><circle cx="<?= $px ?>" cy="<?= $py ?>" r="2.5" fill="#5c7cf5"></circle><?php endforeach; ?>
    </svg>
    <div class="bars-axis"><?php foreach ($months as $ym): ?><span><?= date('M', strtotime($ym.'-01')) ?></span><?php endforeach; ?></div>
  </div>
</div>

<div class="grid" style="grid-template-columns:1fr 1.4fr">
  <div class="card">
    <div class="card-title">Funding Code Status</div>
    <?php foreach ($fundingCodes as $c): $pct = $c['budget']>0 ? $c['spent']/$c['budget'] : 0;
      $badge = $pct>=1 ? ['OVER BUDGET','overbudget'] : ($pct>=0.8 ? ['WARNING','warning'] : ['On Track','ontrack']); ?>
    <div class="settings-row"><span><?= htmlspecialchars($c['code']) ?> — <?= htmlspecialchars($c['name']) ?></span><span class="pill <?= $badge[1] ?>"><?= $badge[0] ?></span></div>
    <?php endforeach; ?>
    <?php if (!$fundingCodes): ?><p class="subtitle">No funding codes configured yet.</p><?php endif; ?>
  </div>

  <div class="card">
    <div class="card-title">Recent Transactions</div>
    <div class="table-wrap"><table class="data-table"><thead><tr><th>DATE</th><th>DESCRIPTION</th><th>CATEGORY</th><th>AMOUNT</th><th>STATUS</th></tr></thead><tbody>
    <?php foreach ($recentTx as $t): ?>
    <tr>
      <td><?= date('M d', strtotime($t['d'])) ?></td>
      <td><?= htmlspecialchars($t['desc_'] ?: $t['cat']) ?></td>
      <td><?= htmlspecialchars($t['cat']) ?></td>
      <td><?= $t['kind']==='income' ? '+' : '' ?><?= money($t['amt']) ?></td>
      <td><span class="pill <?= strtolower($t['st']) ?>"><?= htmlspecialchars($t['st']) ?></span></td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$recentTx): ?><tr><td colspan="5">No transactions yet.</td></tr><?php endif; ?>
    </tbody></table></div>
  </div>
</div>
</div>
<?php require 'includes/footer.php'; ?>
