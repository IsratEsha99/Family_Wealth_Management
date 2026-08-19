<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
if (!empty($_SESSION['user_id'])) { header('Location: ' . app_url('dashboard.php')); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid name and email.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must contain at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'An account already exists with this email.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (full_name,email,password) VALUES (?,?,?)");
            $stmt->execute([$name,$email,$hash]);
            header('Location: login.php?registered=1');
            exit;
        }
    }
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Register - FamilyWealth</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="auth-page"><div class="auth-box">
<div class="auth-brand"><div class="brand-icon">FW</div><strong>FamilyWealth</strong></div>
<h1>Create account</h1><div class="help">Register to access your family wealth dashboard</div>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST">
<div class="auth-field"><label>Full Name</label><input type="text" name="full_name" required></div>
<div class="auth-field"><label>Email</label><input type="email" name="email" required></div>
<div class="auth-field"><label>Password</label><input type="password" name="password" required minlength="6"></div>
<div class="auth-field"><label>Confirm Password</label><input type="password" name="confirm_password" required minlength="6"></div>
<button class="auth-submit">Register</button>
</form>
<div class="auth-switch">Already have an account? <a href="login.php">Login</a></div>
</div></body></html>
