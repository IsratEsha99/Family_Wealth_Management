<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();
$page_title = 'Configure Alerts';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codes = $pdo->query("SELECT id FROM funding_codes")->fetchAll();
    $stmt = $pdo->prepare("UPDATE funding_codes SET alert_80=?, alert_90=?, alert_100=? WHERE id=?");
    foreach ($codes as $c) {
        $id = $c['id'];
        $stmt->execute([
            isset($_POST["a80_$id"]) ? 1 : 0,
            isset($_POST["a90_$id"]) ? 1 : 0,
            isset($_POST["a100_$id"]) ? 1 : 0,
            $id,
        ]);
    }
    header('Location: index.php');
    exit;
}

$codes = $pdo->query("SELECT * FROM funding_codes ORDER BY code")->fetchAll();
require '../includes/header.php';
?>
<div class="content"><div class="page-head"><div><h1>Configure Alert Thresholds</h1><div class="subtitle">Choose which utilization thresholds trigger a notification, per code</div></div><a class="outline-btn" href="index.php">← Back</a></div>
<div class="card" style="max-width:850px">
<form method="POST">
<div class="table-wrap"><table class="data-table"><thead><tr><th>CODE</th><th>NAME</th><th>80%</th><th>90%</th><th>100%</th></tr></thead><tbody>
<?php foreach ($codes as $c): ?>
<tr>
  <td><?= htmlspecialchars($c['code']) ?></td>
  <td><?= htmlspecialchars($c['name']) ?></td>
  <td><input type="checkbox" name="a80_<?= $c['id'] ?>" <?= $c['alert_80']?'checked':'' ?>></td>
  <td><input type="checkbox" name="a90_<?= $c['id'] ?>" <?= $c['alert_90']?'checked':'' ?>></td>
  <td><input type="checkbox" name="a100_<?= $c['id'] ?>" <?= $c['alert_100']?'checked':'' ?>></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<div style="margin-top:16px"><button class="primary-btn">Save Thresholds</button></div>
</form>
</div></div>
<?php require '../includes/footer.php'; ?>
