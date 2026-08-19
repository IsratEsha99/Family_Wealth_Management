<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();
$page_title = 'New Funding Code';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = strtoupper(trim($_POST['code'] ?? ''));
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $budget = (float)($_POST['budget'] ?? 0);
    $a80 = isset($_POST['alert_80']) ? 1 : 0;
    $a90 = isset($_POST['alert_90']) ? 1 : 0;
    $a100 = isset($_POST['alert_100']) ? 1 : 0;

    if ($code === '' || $name === '' || $category === '') {
        $error = 'Code, name and category are required.';
    } else {
        $check = $pdo->prepare("SELECT id FROM funding_codes WHERE code=?");
        $check->execute([$code]);
        if ($check->fetch()) {
            $error = 'A funding code with this code already exists.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO funding_codes (code,name,category,budget,alert_80,alert_90,alert_100) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$code, $name, $category, $budget, $a80, $a90, $a100]);
            header('Location: index.php');
            exit;
        }
    }
}
require '../includes/header.php';
?>
<div class="content"><div class="page-head"><div><h1>New Funding Code</h1><div class="subtitle">Create an admin-managed budget code</div></div><a class="outline-btn" href="index.php">← Back</a></div>
<div class="card" style="max-width:850px">
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST" class="form-grid">
  <div class="field"><label>Code *</label><input name="code" required placeholder="e.g. TRV-004"></div>
  <div class="field"><label>Name *</label><input name="name" required placeholder="e.g. Travel"></div>
  <div class="field"><label>Category *</label><input name="category" required placeholder="e.g. Flights · Hotels"></div>
  <div class="field"><label>Budget</label><input type="number" step="0.01" min="0" name="budget" value="0"></div>
  <div class="full">
    <label>Alert Thresholds</label>
    <div class="threshold-badges">
      <label class="threshold-badge on"><input type="checkbox" name="alert_80" checked style="margin-right:6px">80%</label>
      <label class="threshold-badge on"><input type="checkbox" name="alert_90" checked style="margin-right:6px">90%</label>
      <label class="threshold-badge on"><input type="checkbox" name="alert_100" checked style="margin-right:6px">100%</label>
    </div>
  </div>
  <div class="full"><button class="primary-btn">Save Funding Code</button></div>
</form></div></div>
<?php require '../includes/footer.php'; ?>
