<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

$query = mysqli_query($conn, "
SELECT 
    u.username, u.email,
    up.nama_lengkap, up.foto_profile,
    g.id_gunung, g.nama_gunung, g.lokasi, g.tinggi, g.estimasi_pendakian, g.kuota_harian, g.harga_tiket, g.pos_perizinan, g.status_gunung, g.gambar,
    kg.nama_kategori
FROM gunung_mitra gm
JOIN users u ON gm.id_user = u.id_user
LEFT JOIN user_profile up ON u.id_user = up.id_user
JOIN gunung g ON gm.id_gunung = g.id_gunung
LEFT JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori
WHERE gm.id_user = '$id_user'
");

if (!$query) {
    die("Query gunung saya error: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data gunung yang dikelola belum ditemukan.");
}

$id_gunung = $data['id_gunung'];

// Hitung jumlah pendaki aktif/booking untuk gunung ini
$query_current_hikers = mysqli_query($conn, "
    SELECT COALESCE(SUM(jumlah_pendaki), 0) AS current_hikers
    FROM pemesanan
    WHERE id_gunung = '$id_gunung'
    AND status_pemesanan IN ('Menunggu Verifikasi', 'Diterima')
");

if (!$query_current_hikers) {
    die("Query current hikers error: " . mysqli_error($conn));
}

$current_hikers = mysqli_fetch_assoc($query_current_hikers)['current_hikers'] ?? 0;

$kuota = (int) $data['kuota_harian'];
$persentase_kuota = 0;

if ($kuota > 0) {
    $persentase_kuota = ($current_hikers / $kuota) * 100;
    if ($persentase_kuota > 100) {
        $persentase_kuota = 100;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Otoritas Gunung Saya - NdakiYuk Mitra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mitra.css">
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
                <button class="menu-toggle-btn" onclick="toggleSidebarMenu()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
                </button>
            </div>

            <div class="header-banner">
                <div class="header-info-text">
                    <small class="header-pretitle">BASECAMP AUTHORITY > <?= strtoupper(htmlspecialchars($data['nama_gunung'])); ?></small>
                    <h1 class="header-welcome"><?= htmlspecialchars($data['nama_gunung']); ?> Management Portal</h1>
                    <p class="header-sub">Halaman otorisasi terpusat untuk memantau status gerbang jalur, kuota pendakian harian, serta informasi spasial kawasan.</p>
                </div>
                <div class="header-status-badge <?= $data['status_gunung'] == 'Buka' ? 'status-aktif' : 'status-nonaktif' ?>">
                    <span class="pulse-dot"></span>
                    Jalur <?= $data['status_gunung'] == 'Buka' ? 'Buka' : 'Tutup'; ?>
                </div>
            </div>

            <div class="cards-grid">
                <div class="stat-card">
                    <div class="scard-icon-wrap icon-booking">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    </div>
                    <div class="scard-data">
                        <span class="scard-label">Total Kelolaan</span>
                        <h2 class="scard-value">1 <small>Kawasan</small></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="scard-icon-wrap icon-pending">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
                    </div>
                    <div class="scard-data">
                        <span class="scard-label">Status Gerbang</span>
                        <h2 class="scard-value" style="color: <?= $data['status_gunung'] == 'Buka' ? 'var(--color-diterima)' : 'var(--color-ditolak)' ?>;">
                            <?= $data['status_gunung'] == 'Buka' ? 'Buka' : 'Tutup'; ?>
                        </h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="scard-icon-wrap icon-pendaki">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <div class="scard-data">
                        <span class="scard-label">Current Hikers</span>
                        <h2 class="scard-value alert-count" style="color: var(--green-main);"><?= $current_hikers; ?> <small>Orang</small></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="scard-icon-wrap icon-transaksi">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                    </div>
                    <div class="scard-data">
                        <span class="scard-label">Tingkat Akses</span>
                        <h2 class="scard-value" style="color: #475569;">Mitra</h2>
                    </div>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <div class="section-title-block">
                        <h3 class="section-title-text">Authorized Mountain Records</h3>
                        <p class="section-title-sub">Hak pengelolaan aset digital yang terkunci pada profil instansi Anda.</p>
                    </div>
                    <span class="section-badge-info">Akses: Terverifikasi</span>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Gunung / Kawasan</th>
                                <th>Status Jalur</th>
                                <th>Ketinggian</th>
                                <th>Utilisasi Kuota Aktif</th>
                                <th>Tingkat Kesulitan</th>
                                <th style="text-align: center;">Konfigurasi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="mountain-info">
                                        <?php if (!empty($data['gambar'])): ?>
                                            <img src="../assets/img/gunung/<?= htmlspecialchars($data['gambar']); ?>" alt="<?= htmlspecialchars($data['nama_gunung']); ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: 16px; box-shadow: var(--shadow-sm); border: 1px solid var(--border);">
                                        <?php else: ?>
                                            <div class="mountain-placeholder" style="width: 150px; height: 150px; border-radius: 16px;"></div>
                                        <?php endif; ?>

                                        <div style="display: flex; flex-direction: column; justify-content: center; margin-left: 8px;">
                                            <strong class="hiker-name" style="font-size: 1.1rem;"><?= htmlspecialchars($data['nama_gunung']); ?></strong>
                                            <small class="date-text" style="display: block; margin-top: 6px; font-size: 0.85rem;">📍 <?= htmlspecialchars($data['lokasi']); ?></small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-status <?= $data['status_gunung'] == 'Buka' ? 'status-diterima' : 'status-ditolak'; ?>">
                                        <?= $data['status_gunung'] == 'Buka' ? 'BUKA' : 'TUTUP'; ?>
                                    </span>
                                </td>

                                <td class="price-table"><?= number_format($data['tinggi'], 0, ',', '.'); ?> mdpl</td>

                                <td>
                                    <div class="quota-wrap" style="width: 160px;">
                                        <div class="quota-bar">
                                            <div class="quota-fill" style="width: <?= $persentase_kuota; ?>%; background-color: var(--green-main);"></div>
                                        </div>
                                        <small class="count-text" style="font-weight: 600; font-size: 0.78rem; display: block; margin-top: 4px;">
                                            <?= $current_hikers; ?> / <?= $kuota; ?> Anggota Naik
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="badge-status status-pending" style="background-color: var(--green-light); color: var(--green-main);">
                                        <?= htmlspecialchars($data['nama_kategori'] ?? 'Belum ada kategori'); ?>
                                    </span>
                                </td>

                                <td style="text-align: center;">
                                    <a href="edit_gunung.php?id=<?= $data['id_gunung']; ?>" class="btn-action-view" style="background-color: var(--yellow); color: var(--green-dark); font-weight: 700; padding: 12px 22px; border-radius: 10px;">
                                        Edit Informasi
                                    </a>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mountain-note" style="margin-top: 20px;">
                    🔒 Sistem mengunci pengubahan data dasar. Modifikasi parameter wajib melalui persetujuan verifikasi tim Administrator NdakiYuk.
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