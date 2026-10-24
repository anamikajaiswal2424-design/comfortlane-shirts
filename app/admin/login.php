<?php
require __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/admin-auth.php';

if (adminIsAuthenticated()) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (!validCsrf()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!adminLoginAllowed()) {
        $error = 'Too many attempts. Please wait one minute and try again.';
    } elseif (hash_equals(adminUsername(), $username) && adminPasswordMatches($password)) {
        completeAdminLogin();
        header('Location: index.php');
        exit;
    } else {
        recordAdminLoginFailure();
        $error = 'Incorrect username or password.';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin login — ComfyNest</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=20260828-2">
</head>
<body class="admin-login-page">
<main class="login-shell">
    <section class="login-card">
        <a class="admin-brand" href="../index.php"><span>C</span><b>Comfy<em>Nest</em></b></a>
        <div class="login-copy"><small>STORE MANAGEMENT</small><h1>Welcome back.</h1><p>Sign in to manage products, tracking, visitors and orders.</p></div>
        <?php if ($error): ?><div class="admin-alert error" role="alert"><?= e($error) ?></div><?php endif; ?>
        <form method="post" action="login.php">
            <?= csrfField() ?>
            <label><span>Username</span><input name="username" autocomplete="username" required autofocus></label>
            <label><span>Password</span><input type="password" name="password" autocomplete="current-password" required></label>
            <button class="admin-button primary" type="submit">Sign in securely</button>
        </form>
        <a class="back-store" href="../index.php">← Back to storefront</a>
    </section>
</main>
</body>
</html>
