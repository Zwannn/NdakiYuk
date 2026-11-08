<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// Ambil data mitra dan gunung yang dikelola
$query_mitra = mysqli_query($conn, "
    SELECT 
        gm.id_gunung,
        g.nama_gunung,
        up.nama_lengkap AS nama_mitra,
        u.username
    FROM gunung_mitra gm
    JOIN gunung g ON gm.id_gunung = g.id_gunung
    JOIN users u ON gm.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
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

// Filter pencarian
$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, $_GET['keyword']) : '';
$status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

$whereSearch = "";

if ($keyword != '') {
    $whereSearch .= "
        AND (
            p.kode_booking LIKE '%$keyword%'
            OR u.username LIKE '%$keyword%'
            OR up.nama_lengkap LIKE '%$keyword%'
        )
    ";
}

if ($status != '') {
    $whereSearch .= " AND p.status_pemesanan = '$status' ";
}

// Ambil data booking/rombongan
$query_pendaki = mysqli_query($conn, "
    SELECT 
        p.id_pemesanan, p.kode_booking, p.tanggal_naik, p.tanggal_turun, p.jumlah_pendaki, p.total_harga, p.status_pemesanan, p.status_checkin, p.status_checkout,
        u.username, up.nama_lengkap
    FROM pemesanan p
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
    WHERE p.id_gunung = '$id_gunung'
    $whereSearch
    ORDER BY p.id_pemesanan DESC
");

if (!$query_pendaki) {
    die("Query data pendaki error: " . mysqli_error($conn));
}

$total_data = mysqli_num_rows($query_pendaki);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Log Registrasi Pendaki - NdakiYuk Mitra</title>
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
                <small class="header-pretitle">HIKER CENTRAL DATABASE</small>
                <h1 class="header-welcome">Database Manifes - <?= htmlspecialchars($mitra['nama_gunung']); ?></h1>
                <p class="header-sub">Daftar arsip seluruh berkas rombongan kelompok yang terdaftar resmi pada wilayah pos perizinan Anda.</p>
            </div>
        </div>

        <div class="content-section" style="margin-bottom: 24px; padding: 24px;">
            <form method="GET" class="filter-form" style="width: 100%;">
                <div style="display: flex; gap: 16px; flex-wrap: wrap; align-items: center; justify-content: space-between; width: 100%;">
                    
                    <div class="form-group" style="flex: 2; min-width: 280px; gap: 6px;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Kata Kunci Pencarian</label>
                        <input type="text" name="keyword" placeholder="Cari nama ketua atau kode booking..." value="<?= htmlspecialchars($keyword); ?>" style="width: 100%;">
                    </div>

                    <div class="form-group" style="flex: 1; min-width: 200px; gap: 6px;">
                        <label style="font-size: 0.75rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Izin Kelompok</label>
                        <select name="status" style="width: 100%;">
                            <option value="">Semua Status Izin</option>
                            <option value="Menunggu Pembayaran" <?= $status == 'Menunggu Pembayaran' ? 'selected' : ''; ?>>Menunggu Pembayaran</option>
                            <option value="Menunggu Verifikasi" <?= $status == 'Menunggu Verifikasi' ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                            <option value="Diterima" <?= $status == 'Diterima' ? 'selected' : ''; ?>>Diterima (ACC)</option>
                            <option value="Ditolak" <?= $status == 'Ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                            <option value="Selesai" <?= $status == 'Selesai' ? 'selected' : ''; ?>>Selesai (Turun)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: flex-end; margin-top: auto; padding-top: 4px; min-width: 180px;">
                        <button type="submit" class="btn-primary" style="padding: 12px 24px; font-size: 0.85rem; height: 44px; display: flex; align-items: center; justify-content: center;">Cari Data</button>
                        <a href="data_pendaki.php" class="btn-secondary" style="padding: 12px 18px; font-size: 0.85rem; height: 44px; display: flex; align-items: center; justify-content: center; text-decoration: none;">Reset</a>
                    </div>

                </div>
            </form>
        </div>

        <div class="content-section">
            <div class="section-header">
                <div class="section-title-block">
                    <h3 class="section-title-text">Daftar Arsip Booking Pendaki</h3>
                    <p class="section-title-sub">Gunakan parameter penyaringan di atas untuk validasi dokumen secara kolektif.</p>
                </div>
                <span class="section-badge-info">Total Terarsip: <?= $total_data; ?> Grup</span>
            </div>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Ketua Rombongan</th>
                            <th>Booking ID</th>
                            <th>Jumlah Anggota</th>
                            <th>Tanggal Naik</th>
                            <th>Status Izin</th>
                            <th style="text-align: center;">Check-In</th>
                            <th style="text-align: center;">Check-Out</th>
                            <th style="text-align: center;">Tindakan</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if ($total_data > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query_pendaki)): ?>
                                <?php
                                    $nama_ketua = $row['nama_lengkap'] ?: $row['username'];

                                    $status_booking = $row['status_pemesanan'];
                                    $classStatus = 'status-pending';

                                    if ($status_booking == 'Diterima') {
                                        $classStatus = 'status-diterima';
                                    } elseif ($status_booking == 'Ditolak') {
                                        $classStatus = 'status-ditolak';
                                    } elseif ($status_booking == 'Selesai') {
                                        $classStatus = 'status-selesai';
                                    }
                                    
                                    // Logika parameter adaptif status gerbang pintu pendaki (Bhs Indonesia)
                                    $gate_checkin = ($row['status_checkin'] == 'Sudah Check-in') ? 'SUDAH' : 'BELUM';
                                    $checkin_badge = ($row['status_checkin'] == 'Sudah Check-in') ? 'status-diterima' : 'status-pending';

                                    $gate_checkout = ($row['status_checkout'] == 'Sudah Check-out') ? 'SUDAH' : 'BELUM';
                                    $checkout_badge = ($row['status_checkout'] == 'Sudah Check-out') ? 'status-selesai' : 'status-pending';
                                ?>

                                <tr>
                                    <td>
                                        <strong class="hiker-name"><?= htmlspecialchars($nama_ketua); ?></strong>
                                    </td>

                                    <td class="bold-code">#<?= htmlspecialchars($row['kode_booking']); ?></td>

                                    <td class="count-text"><strong><?= htmlspecialchars($row['jumlah_pendaki']); ?></strong> Pendaki</td>

                                    <td class="date-text">📅 <?= date('d M Y', strtotime($row['tanggal_naik'])); ?></td>

                                    <td>
                                        <span class="badge-status <?= $classStatus; ?>">
                                            <?= htmlspecialchars($status_booking); ?>
                                        </span>
                                    </td>

                                    <td style="text-align: center;">
                                        <span class="badge-status <?= $checkin_badge; ?>" style="font-size: 0.7rem; padding: 4px 10px;">
                                            <?= $gate_checkin; ?>
                                        </span>
                                    </td>

                                    <td style="text-align: center;">
                                        <span class="badge-status <?= $checkout_badge; ?>" style="font-size: 0.7rem; padding: 4px 10px;">
                                            <?= $gate_checkout; ?>
                                        </span>
                                    </td>

                                    <td style="text-align: center;">
                                        <a href="detail_pendaki.php?id=<?= $row['id_pemesanan']; ?>" class="btn-action-view" style="font-weight: 700; font-size: 0.78rem;">
                                            Periksa Berkas
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="empty-row-text">Data berkas rombongan pendaki tidak ditemukan dalam riwayat log gerbang pos ini.</td>
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