<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();
$page_title = 'Upload Document';
$error = '';

$renewId = (int)($_GET['renew'] ?? 0);
$renewDoc = null;
if ($renewId) {
    $stmt = $pdo->prepare("SELECT * FROM documents WHERE id=?");
    $stmt->execute([$renewId]);
    $renewDoc = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = (int)($_POST['member_id'] ?? 0) ?: null;
    $category = trim($_POST['category'] ?? '');
    $expiry_date = $_POST['expiry_date'] ?? '';
    $renewOf = (int)($_POST['renew_of'] ?? 0);

    if ($category === '' || empty($_FILES['file']['name'])) {
        $error = 'Category and file are required.';
    } elseif ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'File upload failed. Please try again.';
    } else {
        $allowed = ['pdf','jpg','jpeg','png','doc','docx','xls','xlsx'];
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            $error = 'Unsupported file type.';
        } else {
            $storedName = 'doc_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $_FILES['file']['name']);
            if (move_uploaded_file($_FILES['file']['tmp_name'], '../uploads/' . $storedName)) {
                $version = 1;
                if ($renewOf) {
                    $stmt = $pdo->prepare("SELECT MAX(version) FROM documents WHERE id=? OR (original_name=(SELECT original_name FROM documents WHERE id=?))");
                    $stmt->execute([$renewOf, $renewOf]);
                    $version = (int)$stmt->fetchColumn() + 1;
                }
                $stmt = $pdo->prepare("INSERT INTO documents (member_id, category, original_name, stored_name, file_size, expiry_date, version) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$member_id, $category, $_FILES['file']['name'], $storedName, $_FILES['file']['size'], $expiry_date ?: null, $version]);
                header('Location: index.php');
                exit;
            }
            $error = 'Could not save the uploaded file.';
        }
    }
}

$members = $pdo->query("SELECT id, full_name FROM family_members ORDER BY full_name")->fetchAll();
$categories = ['Personal IDs', 'Property Titles', 'Business Licenses', 'Medical', 'Education', 'Legal'];
require '../includes/header.php';
?>
<div class="content"><div class="page-head"><div><h1>Upload Document</h1><div class="subtitle"><?= $renewDoc ? 'Renewing: ' . htmlspecialchars($renewDoc['original_name']) : 'Add a document to the vault' ?></div></div><a class="outline-btn" href="index.php">← Back</a></div>
<div class="card" style="max-width:850px">
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST" enctype="multipart/form-data" class="form-grid">
  <?php if ($renewDoc): ?><input type="hidden" name="renew_of" value="<?= $renewDoc['id'] ?>"><?php endif; ?>
  <div class="field"><label>Member (optional — leave blank for family-wide)</label>
    <select name="member_id">
      <option value="">Family</option>
      <?php foreach ($members as $m): ?><option value="<?= $m['id'] ?>" <?= ($renewDoc['member_id'] ?? null)==$m['id']?'selected':'' ?>><?= htmlspecialchars($m['full_name']) ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label>Category *</label>
    <select name="category" required>
      <option value="">Select category</option>
      <?php foreach ($categories as $c): ?><option <?= ($renewDoc['category'] ?? '')===$c?'selected':'' ?>><?= $c ?></option><?php endforeach; ?>
    </select>
  </div>
  <div class="field"><label>Expiry Date (optional)</label><input type="date" name="expiry_date" value="<?= $renewDoc['expiry_date'] ?? '' ?>"></div>
  <div class="field"><label>File * (PDF, image, Word, Excel)</label><input type="file" name="file" required></div>
  <div class="full"><button class="primary-btn">Upload Document</button></div>
</form></div></div>
<?php require '../includes/footer.php'; ?>
