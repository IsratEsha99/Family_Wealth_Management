<?php
require_once '../includes/auth.php';
require_once '../config/database.php';
if (!empty($_SESSION['user_id'])) { header('Location: ' . app_url('dashboard.php')); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        header('Location: ' . app_url('dashboard.php'));
        exit;
    }
    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Login - FamilyWealth</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="auth-page"><div class="auth-box">
<div class="auth-brand"><div class="brand-icon">FW</div><strong>FamilyWealth</strong></div>
<h1>Welcome back</h1><div class="help">Sign in to your Family Wealth Management System</div>
<?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="POST">
<div class="auth-field"><label>Email</label><input type="email" name="email" required placeholder="you@example.com"></div>
<div class="auth-field"><label>Password</label><input type="password" name="password" required placeholder="Enter password"></div>
<button class="auth-submit">Login</button>
</form>
<div class="auth-switch">Don't have an account? <a href="register.php">Register</a></div>
</div></body></html>
