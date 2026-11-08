<?php
// mitra/dashboard.php
session_start();
include "../config/koneksi.php";

// FIX INTEGRASI BUG BROWSER: Validasi Kunci Token Hak Akses Lintas Tab Khusus Mitra
if (!isset($_SESSION['mitra_logged_in']) || $_SESSION['mitra_logged_in'] !== true || $_SESSION['role'] != 2) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?error=unauthorized_mitra");
    exit;
}

$id_user = $_SESSION['id_user'];

// AMBIL DATA MITRA DAN GUNUNG YANG DIKELOLA
$query_mitra = mysqli_query($conn, "
    SELECT 
        u.id_user, u.username, u.email,
        up.nama_lengkap, up.foto_profile, up.no_hp, up.bio,
        g.id_gunung, g.nama_gunung, g.lokasi, g.tinggi, g.kuota_harian, g.harga_tiket, g.status_gunung, g.gambar
    FROM gunung_mitra gm
    JOIN users u ON gm.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
    JOIN gunung g ON gm.id_gunung = g.id_gunung
    WHERE gm.id_user = '$id_user'
");

if (!$query_mitra) {
    die("Query mitra error: " . mysqli_error($conn));
}

$mitra = mysqli_fetch_assoc($query_mitra);

if (!$mitra) {
    die("Data mitra atau gunung yang dikelola belum ditemukan.");
}

$id_gunung = $mitra['id_gunung'];
$tanggal_hari_ini = date('Y-m-d');

// Tangkap keyword pencarian jika ada parameter GET 'search'
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// TOTAL BOOKING HARI INI
$query_total_booking = mysqli_query($conn, "
    SELECT COUNT(*) AS total_booking
    FROM pemesanan
    WHERE id_gunung = '$id_gunung'
    AND tanggal_naik = '$tanggal_hari_ini'
");
$total_booking = mysqli_fetch_assoc($query_total_booking)['total_booking'] ?? 0;

// TOTAL PENDAKI HARI INI
$query_total_pendaki = mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah_pendaki), 0) AS total_pendaki
    FROM pemesanan
    WHERE id_gunung = '$id_gunung'
    AND tanggal_naik = '$tanggal_hari_ini'
");
$total_pendaki = mysqli_fetch_assoc($query_total_pendaki)['total_pendaki'] ?? 0;

// TOTAL MENUNGGU VERIFIKASI
$query_pending = mysqli_query($conn, "
    SELECT COUNT(*) AS total_pending
    FROM pemesanan
    WHERE id_gunung = '$id_gunung'
    AND status_pemesanan = 'Menunggu Verifikasi'
");
$total_pending = mysqli_fetch_assoc($query_pending)['total_pending'] ?? 0;

// TOTAL TRANSAKSI
$query_transaksi = mysqli_query($conn, "
    SELECT COALESCE(SUM(total_harga), 0) AS total_transaksi
    FROM pemesanan
    WHERE id_gunung = '$id_gunung'
    AND status_pemesanan IN ('Menunggu Verifikasi', 'Diterima', 'Selesai')
");
$total_transaksi = mysqli_fetch_assoc($query_transaksi)['total_transaksi'] ?? 0;

// QUERY AMBIL GRUP YANG TERLAMBAT CHECKOUT (OVERDUE EMERGENCY)
$query_overdue = mysqli_query($conn, "
    SELECT p.id_pemesanan, p.kode_booking, p.tanggal_turun, p.jumlah_pendaki, up.nama_lengkap, up.no_hp
    FROM pemesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
    WHERE p.id_gunung = '$id_gunung'
    AND p.status_checkin = 'Sudah Check-in'
    AND p.status_checkout = 'Belum Check-out'
    AND p.tanggal_turun < '$tanggal_hari_ini'
    ORDER BY p.tanggal_turun ASC
");
$total_overdue = mysqli_num_rows($query_overdue);

// ── 🔥 MODIFIKASI QUERY MANIFES: INTEGRASI SEARCH FILTER BERDASARKAN KODE / NAMA 🔥 ──
$sql_booking = "
    SELECT 
        p.id_pemesanan, p.kode_booking, p.tanggal_naik, p.tanggal_turun, p.jumlah_pendaki, p.total_harga, p.status_pemesanan,
        u.username, up.nama_lengkap
    FROM pemesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
    WHERE p.id_gunung = '$id_gunung'
";

if (!empty($search_keyword)) {
    $sql_booking .= " AND (p.kode_booking LIKE '%$search_keyword%' OR up.nama_lengkap LIKE '%$search_keyword%' OR u.username LIKE '%$search_keyword%')";
}

$sql_booking .= " ORDER BY p.id_pemesanan DESC LIMIT 5";
$query_booking = mysqli_query($conn, $sql_booking);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mitra Pos - NdakiYuk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mitra.css"> 
    <style>
        /* Styling Emergency Alert Box */
        .emergency-alert-container { background: #fee2e2; border: 1px solid #fca5a5; border-radius: 14px; padding: 20px; margin-bottom: 24px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.08); display: flex; flex-direction: column; gap: 14px; }
        .emergency-header-flex { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #fecaca; padding-bottom: 10px; }
        .emergency-title-wrapper { display: flex; align-items: center; gap: 8px; color: #991b1b; font-weight: 800; font-size: 0.95rem; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .pulse-alarm-dot { width: 10px; height: 10px; background-color: #ef4444; border-radius: 50%; display: inline-block; position: relative; }
        .pulse-alarm-dot::after { content: ''; position: absolute; inset: 0; background-color: #ef4444; border-radius: 50%; animation: alarmPulseAnimation 1.5s infinite ease-in-out; }
        @keyframes alarmPulseAnimation { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(2.8); opacity: 0; } }
        
        .emergency-badge-count { background: #1e293b; color: #ffffff; font-weight: 800; font-size: 0.75rem; padding: 4px 10px; border-radius: 6px; }
        .emergency-list-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 12px; }
        .emergency-item-card { background: #ffffff; border: 1px solid #fca5a5; padding: 12px 14px; border-radius: 10px; display: flex; flex-direction: column; gap: 4px; box-shadow: 0 2px 6px rgba(153, 27, 27, 0.02); }
        .em-code { font-family: monospace; font-size: 0.8rem; font-weight: 700; color: #b91c1c; }
        .em-leader { font-size: 0.88rem; font-weight: 800; color: #1e293b; }
        .em-meta { font-size: 0.78rem; color: #64748b; }
        .em-date { font-weight: 700; color: #dc2626; }
        .btn-em-call { display: inline-flex; align-items: center; justify-content: center; gap: 6px; margin-top: 6px; padding: 7px; background: #e0f2fe; color: #0369a1; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-decoration: none; transition: 0.15s; border: 1px solid #bae6fd; }
        .btn-em-call:hover { background: #bae6fd; color: #0284c7; }

        /* ── 🔥 CONFIG SEARCH PANEL DESIGN FOR MITRA DASHBOARD 🔥 ── */
        .search-wrapper-panel { display: flex; gap: 12px; margin-bottom: 18px; width: 100%; }
        .search-input-container { position: relative; flex: 1; display: flex; }
        .search-input-field { width: 100%; padding: 12px 16px 12px 46px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: var(--text-title); font-family: inherit; font-size: 13.5px; outline: none; transition: var(--transition); }
        .search-input-field:focus { border-color: var(--green-text); }
        .search-icon-vector { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .btn-clear-search { display: flex; align-items: center; justify-content: center; padding: 0 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 600; transition: var(--transition); }
        .btn-clear-search:hover { background: var(--red-soft); color: var(--red-text); border-color: rgba(255,107,107,0.15); }
    </style>
</head>
<body>

<div class="layout">
    
    <?php include 'sidebar.php'; ?>

    <main class="main">
        
        <div class="topbar-mobile">
            <div class="mobile-brand-wrap">
                <span class="mbrand-text">NDAKIYUK</span>
                <span class="mrole-badge">MITRA</span>
            </div>
            <button class="menu-toggle-btn" id="menuToggle" onclick="toggleSidebarMenu()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>

        <div class="header-banner">
            <div class="header-info-text">
                <small class="header-pretitle">PANEL OPERASIONAL POS REGISTRASI</small>
                <h1 class="header-welcome">Selamat Datang, <?= htmlspecialchars($mitra['nama_lengkap'] ?? $mitra['username']); ?>!</h1>
                <p class="header-sub">Sistem Pusat Kendali Data Pemesanan Tiket <strong><?= htmlspecialchars($mitra['nama_gunung']); ?></strong> (<?= number_format($mitra['tinggi'], 0, ',', '.'); ?> MDPL).</p>
            </div>
            <div class="header-status-badge <?= $mitra['status_gunung'] == 'Buka' ? 'status-aktif' : 'status-nonaktif' ?>">
                <span class="pulse-dot"></span>
                Jalur <?= htmlspecialchars($mitra['status_gunung']); ?>
            </div>
        </div>

        <?php if ($total_overdue > 0): ?>
            <div class="emergency-alert-container">
                <div class="emergency-header-flex">
                    <div class="emergency-title-wrapper">
                        <span class="pulse-alarm-dot"></span>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Peringatan Evakuasi: Pendaki Terlambat Checkout
                    </div>
                    <span class="emergency-badge-count">SAR Alert: <?= $total_overdue; ?> Grup</span>
                </div>
                <div class="emergency-list-grid">
                    <?php while ($ov = mysqli_fetch_assoc($query_overdue)): ?>
                        <div class="emergency-item-card">
                            <span class="em-code">#<?= htmlspecialchars($ov['kode_booking']); ?></span>
                            <span class="em-leader"><?= htmlspecialchars($ov['nama_lengkap']); ?></span>
                            <span class="em-meta">Rombongan: <strong><?= $ov['jumlah_pendaki']; ?> Orang</strong></span>
                            <span class="em-meta">Jadwal Turun: <span class="em-date"><?= date('d M Y', strtotime($ov['tanggal_turun'])); ?></span></span>
                            
                            <a href="https://wa.me/<?= str_replace(['+', ' ', '-'], '', $ov['no_hp']); ?>?text=Halo%20<?= urlencode($ov['nama_lengkap']); ?>%20dari%20Pos%20Perizinan%20<?= urlencode($mitra['nama_gunung']); ?>.%20Berdasarkan%20sistem%20NdakiYuk,%20rombongan%20Anda%20tercatat%20melewati%20batas%20waktu%20checkout.%20Konfirmasi%20kondisi%20dan%20posisi%20Anda%20sekarang." class="btn-em-call" target="_blank">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                                Hubungi Ketua Grup
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="cards-grid">
            <div class="stat-card">
                <div class="scard-icon-wrap icon-booking">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <div class="scard-data">
                    <span class="scard-label">Booking Hari Ini</span>
                    <h2 class="scard-value"><?= $total_booking; ?> <small>Grup</small></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="scard-icon-wrap icon-pendaki">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="scard-data">
                    <span class="scard-label">Total Anggota Naik</span>
                    <h2 class="scard-value"><?= $total_pendaki; ?> <small>Orang</small></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="scard-icon-wrap icon-pending">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div class="scard-data">
                    <span class="scard-label">Butuh Verifikasi</span>
                    <h2 class="scard-value alert-count" style="color: var(--color-pending);"><?= $total_pending; ?> <small>Kelompok</small></h2>
                </div>
            </div>

            <div class="stat-card">
                <div class="scard-icon-wrap icon-transaksi">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v11a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-5z"/><path d="M16 10a2 2 0 0 1 2 2v1a2 2 0 0 1-2 2H14V10h2z"/></svg>
                </div>
                <div class="scard-data">
                    <span class="scard-label">Kas Masuk Gerbang</span>
                    <h2 class="scard-value price-text">Rp <?= number_format($total_transaksi, 0, ',', '.'); ?></h2>
                </div>
            </div>
        </div>

        <div class="content-section">
            <div class="section-header" style="flex-wrap: wrap; gap: 16px; margin-bottom: 14px;">
                <div class="section-title-block">
                    <h3 class="section-title-text">Manifes Rombongan Terbaru</h3>
                    <p class="section-title-sub">Daftar antrean aktivitas check-in di loket pintu masuk utama.</p>
                </div>
                <span class="section-badge-info">📍 Pos Log: <?= htmlspecialchars($mitra['nama_gunung']); ?></span>
            </div>

            <div class="search-wrapper-panel">
                <form method="GET" action="" class="search-input-container">
                    <svg class="search-icon-vector" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    <input type="text" name="search" class="search-input-field" placeholder="Cari manifes berdasarkan nomor #Kode Booking atau nama ketua rombongan..." value="<?= htmlspecialchars($search_keyword); ?>">
                </form>
                <?php if (!empty($search_keyword)): ?>
                    <a href="dashboard.php" class="btn-clear-search">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                        Clear
                    </a>
                <?php endif; ?>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Kode Booking</th>
                            <th>Ketua Kelompok</th>
                            <th>Jadwal Naik</th>
                            <th>Jumlah</th>
                            <th>Total Dana</th>
                            <th>Status Registrasi</th>
                            <th style="text-align: center;">Aksi Pos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($query_booking) > 0): ?>
                            <?php while ($booking = mysqli_fetch_assoc($query_booking)): ?>
                                <?php
                                    $status = $booking['status_pemesanan'];
                                    $classStatus = 'status-pending';

                                    if ($status == 'Diterima') {
                                        $classStatus = 'status-diterima';
                                    } elseif ($status == 'Ditolak') {
                                        $classStatus = 'status-ditolak';
                                    } elseif ($status == 'Selesai') {
                                        $classStatus = 'status-selesai';
                                    }
                                ?>
                                <tr>
                                    <td class="bold-code">#<?= htmlspecialchars($booking['kode_booking']); ?></td>
                                    <td>
                                        <strong class="hiker-name"><?= htmlspecialchars($booking['nama_lengkap'] ?? $booking['username']); ?></strong>
                                    </td>
                                    <td class="date-text"><?= date('d M Y', strtotime($booking['tanggal_naik'])); ?></td>
                                    <td class="count-text"><strong><?= htmlspecialchars($booking['jumlah_pendaki']); ?></strong> Pendaki</td>
                                    <td class="price-table">Rp <?= number_format($booking['total_harga'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="badge-status <?= $classStatus; ?>">
                                            <?= htmlspecialchars($status); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        <a href="detail_pendaki.php?id=<?= $booking['id_pemesanan']; ?>" class="btn-action-view">
                                            Periksa Berkas
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-row-text">Tidak ada aktivitas data transaksi terdaftar yang cocok dengan pencarian Anda.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<script>
function toggleSidebarMenu() {
    const sidebar = document.querySelector('.sidebar');
    if(sidebar) {
        sidebar.classList.toggle('mobile-open');
    }
}
</script>
</body>
</html>