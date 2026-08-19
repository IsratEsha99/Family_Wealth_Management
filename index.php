<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

require_login();

$page_title = 'Savings';

$members=$pdo->query("SELECT m.*, COALESCE(SUM(a.balance),0) total_savings, COUNT(a.id) account_count
FROM family_members m LEFT JOIN savings_accounts a ON a.member_id=m.id
GROUP BY m.id ORDER BY m.id")->fetchAll();

$accounts=$pdo->query("SELECT a.*,m.full_name FROM savings_accounts a JOIN family_members m ON m.id=a.member_id ORDER BY a.id")->fetchAll();
$transactions=$pdo->query("SELECT t.*,a.account_name FROM savings_transactions t JOIN savings_accounts a ON a.id=t.account_id ORDER BY t.transaction_date DESC,t.id DESC LIMIT 10")->fetchAll();

function money($n){return '৳'.number_format((float)$n,0);}
require '../includes/header.php';
?>
<div class="content">
<div class="page-head"><div><h1>Family Savings</h1><div class="subtitle">Individual member savings balances · Live balances</div></div><a class="primary-btn" href="add_account.php">+ New Account</a></div>

<div class="grid savings-cards">
<?php foreach($members as $m):
  $stmt=$pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM savings_transactions t JOIN savings_accounts a ON a.id=t.account_id WHERE a.member_id=? AND t.transaction_type='credit' AND t.transaction_date >= DATE_FORMAT(CURDATE(),'%Y-%m-01')");
  $stmt->execute([$m['id']]); $month=(float)$stmt->fetchColumn();
?>
<div class="card saving-card">
<div class="member-head"><div class="avatar" style="width:38px;height:38px"><?=strtoupper(substr($m['full_name'],0,2))?></div><div><h3><?=htmlspecialchars($m['full_name'])?></h3><p><?=htmlspecialchars($m['relationship'])?></p></div></div>
<div class="saving-total"><small>Total Savings</small><strong><?=money($m['total_savings'])?></strong><div class="account-count"><?=$m['account_count']?> account(s)</div></div>
<div class="saving-line"></div><div class="this-month"><span>This month</span><span class="positive">+<?=money($month)?></span></div>
<div class="sparkline"><i style="height:30%"></i><i style="height:45%"></i><i style="height:40%"></i><i style="height:65%"></i><i style="height:55%"></i><i style="height:75%"></i><i style="height:68%"></i></div>
</div>
<?php endforeach; ?>
</div>

<div class="card" style="margin-top:16px">
<div class="card-title">Savings Accounts Detail</div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>MEMBER</th><th>ACCOUNT NAME</th><th>BANK / INSTITUTION</th><th>BALANCE</th><th>LAST CREDIT</th><th>TYPE</th></tr></thead><tbody>
<?php foreach($accounts as $a):
$stmt=$pdo->prepare("SELECT transaction_date FROM savings_transactions WHERE account_id=? AND transaction_type='credit' ORDER BY transaction_date DESC LIMIT 1");$stmt->execute([$a['id']]);$last=$stmt->fetchColumn();
?>
<tr><td><?=htmlspecialchars(str_replace(' Al-Rashid','',$a['full_name']))?></td><td><?=htmlspecialchars($a['account_name'])?></td><td><?=htmlspecialchars($a['bank_institution'])?></td><td><?=money($a['balance'])?></td><td><?=$last?date('M d, Y',strtotime($last)):'—'?></td><td><?=htmlspecialchars($a['account_type'])?></td></tr>
<?php endforeach; ?></tbody></table></div>
</div>

<div class="card" style="margin-top:16px">
<div class="card-title">Recent Savings Transactions</div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>DATE</th><th>ACCOUNT</th><th>AMOUNT</th><th>TYPE</th><th>NOTE</th></tr></thead><tbody>
<?php foreach($transactions as $t): ?><tr><td><?=date('M d, Y',strtotime($t['transaction_date']))?></td><td><?=htmlspecialchars($t['account_name'])?></td><td><?=($t['transaction_type']==='credit'?'+':'-').money($t['amount'])?></td><td><?=ucfirst($t['transaction_type'])?></td><td><?=htmlspecialchars($t['note']??'')?></td></tr><?php endforeach; ?>
</tbody></table></div>
</div>
</div>
<?php require '../includes/footer.php'; ?>
