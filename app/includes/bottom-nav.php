<?php $activeNav = $activeNav ?? ''; ?>
<nav class="bottom-nav" aria-label="Primary navigation">
    <a class="<?= $activeNav === 'home' ? 'active' : '' ?>" href="index.php"><span class="nav-icon"><i class="ui-icon icon-house" aria-hidden="true"></i></span><small>Home</small></a>
    <a class="<?= $activeNav === 'categories' ? 'active' : '' ?>" href="index.php#products"><span class="nav-icon"><i class="ui-icon icon-layout-grid" aria-hidden="true"></i></span><small>Categories</small></a>
    <a class="<?= $activeNav === 'orders' ? 'active' : '' ?>" href="orders.php"><span class="nav-icon"><i class="ui-icon icon-package" aria-hidden="true"></i></span><small>My Orders</small></a>
    <a href="mailto:care@comfynest.example"><span class="nav-icon"><i class="ui-icon icon-circle-help" aria-hidden="true"></i></span><small>Help</small></a>
</nav>
