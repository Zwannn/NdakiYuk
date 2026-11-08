<?php
// admin/sidebar.php
// Menghitung draf pengajuan pembaruan gunung milik mitra yang masih berstatus 'Menunggu Verifikasi'
$query_badge_pengajuan = mysqli_query($conn, "SELECT COUNT(*) AS total FROM pengajuan_update_gunung WHERE status_pengajuan = 'Menunggu Verifikasi'");
$badge_pengajuan = mysqli_fetch_assoc($query_badge_pengajuan)['total'] ?? 0;

$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="topbar-mobile">
    <div class="mobile-brand-wrap">
        <span class="mbrand-text">NDAKIYUK <span style="color: #2ecc71;">ADMIN</span></span>
    </div>
    <button class="menu-toggle-btn" onclick="toggleSidebarMenu()" aria-label="Toggle Navigation">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
</div>

<aside class="sidebar" id="adminSidebar">
    <div class="logo">
        <div class="logo-text">Ndaki<span style="color: #2ecc71;">Yuk</span></div>
    </div>

    <div class="sidebar-section">Pusat Kendali</div>
    <div class="sidebar-menu">
        <a href="dashboard.php" class="<?= $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            Dashboard Hub
        </a>
    </div>

    <div class="sidebar-section">Manajemen Data</div>
    <div class="sidebar-menu">
        <a href="data_pendaki.php" class="<?= $current_page == 'data_pendaki.php' ? 'active' : ''; ?>">
            Data Pendaki
        </a>
        <a href="data_gunung.php" class="<?= $current_page == 'data_gunung.php' ? 'active' : ''; ?>">
            Data Gunung
        </a>
        <a href="data_user.php" class="<?= $current_page == 'data_user.php' ? 'active' : ''; ?>">
            Data User
        </a>
        <a href="data_logistik.php" class="<?= $current_page == 'data_logistik.php' ? 'active' : ''; ?>">
            Data Logistik
        </a>
    </div>

    <div class="sidebar-bottom">
        <a href="../auth/logout.php" class="btn-logout-admin" style="display: flex; align-items: center; justify-content: center; width: 100%; padding: 12px; background-color: var(--red-soft); color: var(--red-text); border: 1px solid rgba(255, 107, 107, 0.15); border-radius: var(--radius-md); text-decoration: none; font-size: 13px; font-weight: 600; transition: var(--transition);">
            Logout
        </a>
    </div>
</aside>

<script>
function toggleSidebarMenu() {
    const sidebar = document.getElementById('adminSidebar');
    if (sidebar) {
        sidebar.classList.toggle('mobile-open');
    }   
}

document.addEventListener('click', function(event) {
    const sidebar = document.getElementById('adminSidebar');
    const toggleBtn = document.querySelector('.menu-toggle-btn');
    
    if (sidebar && sidebar.classList.contains('mobile-open')) {
        if (!sidebar.contains(event.target) && !toggleBtn.contains(event.target)) {
            sidebar.classList.remove('mobile-open');
        }
    }
});
</script>