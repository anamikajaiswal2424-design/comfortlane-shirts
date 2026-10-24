<?php

function adminUsername(): string
{
    return (string) (getenv('COMFYNEST_ADMIN_USERNAME') ?: 'admin');
}

function adminPasswordMatches(string $password): bool
{
    $environmentPassword = getenv('COMFYNEST_ADMIN_PASSWORD');
    if (is_string($environmentPassword) && $environmentPassword !== '') {
        return hash_equals($environmentPassword, $password);
    }
    if (getenv('VERCEL')) return false; // Require a private admin password in Vercel.
    return password_verify($password, '$2y$12$.TkJRlqF2RNlcxgVzsniO.wturLGsieqVccQ1CLavAZjZW0.qRCUW');
}

function adminIsAuthenticated(): bool
{
    return !empty($_SESSION['admin_authenticated']);
}

function requireAdmin(): void
{
    if (!adminIsAuthenticated()) {
        header('Location: login.php');
        exit;
    }
}

function adminLoginAllowed(): bool
{
    return (int) ($_SESSION['admin_lock_until'] ?? 0) <= time();
}

function recordAdminLoginFailure(): void
{
    $_SESSION['admin_login_failures'] = (int) ($_SESSION['admin_login_failures'] ?? 0) + 1;
    if ($_SESSION['admin_login_failures'] >= 5) {
        $_SESSION['admin_lock_until'] = time() + 60;
        $_SESSION['admin_login_failures'] = 0;
    }
}

function completeAdminLogin(): void
{
    session_regenerate_id(true);
    $_SESSION['admin_authenticated'] = true;
    unset($_SESSION['admin_login_failures'], $_SESSION['admin_lock_until']);
}
