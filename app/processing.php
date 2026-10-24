<?php
require __DIR__ . '/includes/init.php';
redirect(!empty($_SESSION['last_order']) && !cartCount() ? 'thankyou.php' : 'payment.php');
