<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h2>NdakiYuk Mitra</h2>
        <p>Basecamp Authority</p>
    </div>

    <nav class="sidebar-menu">
        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            Dashboard
        </a>

        <a href="gunung_saya.php" class="<?= $current_page == 'gunung_saya.php' ? 'active' : ''; ?>">
            Mountain Management
        </a>

        <a href="data_pendaki.php" class="<?= $current_page == 'data_pendaki.php' ? 'active' : ''; ?>">
            Pendaki
        </a>

        <a href="scan.php" class="<?= $current_page == 'scan.php' ? 'active' : ''; ?>">
            QR Scanner
        </a>
    </nav>

    <div class="sidebar-bottom">
        <a href="profile.php" class="<?= $current_page == 'profile.php' ? 'active' : ''; ?>">
            Profile
        </a>

        <a href="../auth/logout.php">
            Logout
        </a>
    </div>
</aside>