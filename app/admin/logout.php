<?php
require __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && validCsrf()) {
    unset($_SESSION['admin_authenticated']);
    session_regenerate_id(true);
}
header('Location: login.php');
exit;
