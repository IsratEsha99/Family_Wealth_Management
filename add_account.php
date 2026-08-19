<?php
require_once '../includes/auth.php';
require_login();
$page_title='New Savings Account';
$members=$pdo->query("SELECT id,full_name FROM family_members ORDER BY full_name")->fetchAll();
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $member=(int)($_POST['member_id']??0);$name=trim($_POST['account_name']??'');$bank=trim($_POST['bank']??'');
    if(!$member||$name===''||$bank==='') $error='Member, account name and bank are required.';
    else{
        $stmt=$pdo->prepare("INSERT INTO savings_accounts(member_id,account_name,bank_institution,balance,account_type) VALUES(?,?,?,?,?)");
        $stmt->execute([$member,$name,$bank,(float)($_POST['balance']??0),$_POST['account_type']??'Savings']);
        header('Location: index.php');exit;
    }
}
require '../includes/header.php';
?>
<div class="content"><div class="page-head"><div><h1>New Savings Account</h1><div class="subtitle">Add an individual family member savings account</div></div><a class="outline-btn" href="index.php">← Back</a></div>
<div class="card" style="max-width:850px">
<?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="POST" class="form-grid">
<div class="field"><label>Member *</label><select name="member_id" required><option value="">Select member</option><?php foreach($members as $m): ?><option value="<?=$m['id']?>"><?=htmlspecialchars($m['full_name'])?></option><?php endforeach; ?></select></div>
<div class="field"><label>Account Name *</label><input name="account_name" required placeholder="Main Savings"></div>
<div class="field"><label>Bank / Institution *</label><input name="bank" required placeholder="Emirates NBD"></div>
<div class="field"><label>Balance</label><input type="number" step="0.01" name="balance" value="0"></div>
<div class="field"><label>Account Type</label><select name="account_type"><option>Savings</option><option>Investment</option><option>Education</option><option>Current</option></select></div>
<div class="full"><button class="primary-btn">Save Account</button></div>
</form></div></div>
<?php require '../includes/footer.php'; ?>
