<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_login();
$page_title = 'Add Member';
$error='';
$nationality = 'Bangladeshi';
$phone = '';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['full_name']??'');
    $relationship=trim($_POST['relationship']??'');
    $nationality=trim($_POST['nationality'] ?? 'Bangladeshi');
    $phone=trim($_POST['phone'] ?? '');

    if($name===''||$relationship==='') {
        $error='Name and relationship are required.';
    } else {
        if($nationality==='') $nationality='Bangladeshi';
        if($phone!=='') {
            $phone=preg_replace('/\s+/', '', $phone);
            if(!preg_match('/^(?:\+?880|01)\d{9}$/', $phone)) {
                $error='Please enter a valid Bangladeshi phone number.';
            } else {
                if(str_starts_with($phone, '01')) {
                    $phone='+880'.substr($phone, 1);
                }
            }
        }

        if($error==='') {
            $check=$pdo->prepare("SELECT id FROM family_members WHERE LOWER(TRIM(full_name)) = LOWER(?) AND LOWER(TRIM(relationship)) = LOWER(?) LIMIT 1");
            $check->execute([$name, $relationship]);
            if($check->fetch()) {
                $error='A family member with this name and relationship already exists.';
            } else {
                $stmt=$pdo->prepare("INSERT INTO family_members(full_name,relationship,date_of_birth,blood_group,nationality,phone,personal_net_worth) VALUES(?,?,?,?,?,?,?)");
                $stmt->execute([$name,$relationship,$_POST['date_of_birth']?:null,$_POST['blood_group']?:null,$nationality,$phone?:null,(float)($_POST['net_worth']??0)]);
                $id=$pdo->lastInsertId();
                header("Location: index.php?member=$id"); exit;
            }
        }
    }
}
require '../includes/header.php';
?>
<div class="content"><div class="page-head"><div><h1>Add Family Member</h1><div class="subtitle">Create a member profile</div></div><a class="outline-btn" href="index.php">← Back</a></div>
<div class="card" style="max-width:850px">
<?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="POST" class="form-grid">
<div class="field"><label>Full Name *</label><input name="full_name" required></div>
<div class="field"><label>Relationship *</label><select name="relationship" required><option value="">Select</option><option>Head of Family</option><option>Spouse</option><option>Son</option><option>Daughter</option><option>Daughter-in-law</option><option>Other</option></select></div>
<div class="field"><label>Date of Birth</label><input type="date" name="date_of_birth"></div>
<div class="field"><label>Blood Group</label><input name="blood_group" placeholder="e.g. O+"></div>
<div class="field"><label>Nationality</label><input name="nationality" value="<?= htmlspecialchars($nationality) ?>" placeholder="Bangladeshi"></div>
<div class="field"><label>Phone</label><input name="phone" value="<?= htmlspecialchars($phone) ?>" placeholder="e.g. +8801712345678 or 01712345678" pattern="^(?:\+?880|01)\d{9}$" title="Use a Bangladeshi phone number"></div>
<div class="field"><label>Personal Net Worth</label><input type="number" step="0.01" name="net_worth" value="0"></div>
<div class="full"><button class="primary-btn">Save Member</button></div>
</form></div></div>
<?php require '../includes/footer.php'; ?>
