<?php
// admin/dashboard.php
session_start();
include "../config/koneksi.php";

// 🔥 FIX ABSOLUT BUG MULTI-TAB: Validasi Pengunci Token Hak Akses Lintas Sesi Global Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] != 3) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?error=unauthorized_admin");
    exit;
}

// ─────────────────────────────────────────────────────────────────────────
// AGREGASI METRIK TOTAL (GENERAL REPORT)
// ─────────────────────────────────────────────────────────────────────────
$total_user    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE id_role = 1"))['total'] ?? 0;
$total_mitra   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users WHERE id_role = 2"))['total'] ?? 0;
$total_gunung  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM gunung"))['total'] ?? 0;
$total_simaksi = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM pemesanan WHERE status_pemesanan='Selesai' OR status_pemesanan='Diterima'"))['total'] ?? 0;

// TREN PENDAFTARAN USER (30 DATA TERBARU)
$sql_grafik_user = mysqli_query($conn, "
    SELECT DATE(created_at) AS tanggal, COUNT(*) AS jumlah 
    FROM users 
    WHERE id_role = 1 
    GROUP BY DATE(created_at) 
    ORDER BY DATE(created_at) DESC 
    LIMIT 30
");
$arr_tgl_user = []; $arr_jml_user = [];
while($r = mysqli_fetch_assoc($sql_grafik_user)) {
    $arr_tgl_user[] = date('d M', strtotime($r['tanggal']));
    $arr_jml_user[] = (int)$r['jumlah'];
}
$arr_tgl_user = array_reverse($arr_tgl_user);
$arr_jml_user = array_reverse($arr_jml_user);

// DISTRIBUSI PENGUNJUNG JALUR PENDIDIKAN
$sql_grafik_gunung = mysqli_query($conn, "
    SELECT g.nama_gunung, COALESCE(SUM(p.jumlah_pendaki), 0) AS total_hiker
    FROM gunung g
    LEFT JOIN pemesanan p ON g.id_gunung = p.id_gunung AND p.status_pemesanan IN ('Diterima', 'Selesai')
    GROUP BY g.id_gunung
    ORDER BY total_hiker DESC
    LIMIT 5
");
$arr_nama_gunung = []; $arr_vol_gunung  = [];
while($g = mysqli_fetch_assoc($sql_grafik_gunung)) {
    $arr_nama_gunung[] = $g['nama_gunung'];
    $arr_vol_gunung[]  = (int)$g['total_hiker'];
}

// ─────────────────────────────────────────────────────────────────────────
// AMBIL DATA 3 GUNUNG TERBANYAK DIDAKI PADA BULAN INI / BERJALAN
// ─────────────────────────────────────────────────────────────────────────
$bulan_ini = date('m');
$tahun_ini = date('Y');
$nama_bulan_array = [
    '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
    '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
    '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
];
$nama_bulan_ini = $nama_bulan_array[$bulan_ini];

$query_top_3 = mysqli_query($conn, "
    SELECT 
        g.nama_gunung, 
        g.lokasi, 
        g.tinggi,
        SUM(p.jumlah_pendaki) AS akumulasi_pendaki,
        COUNT(p.id_pemesanan) AS total_kelompok
    FROM pemesanan p
    JOIN gunung g ON p.id_gunung = g.id_gunung
    WHERE p.status_pemesanan IN ('Diterima', 'Selesai')
      AND MONTH(p.tanggal_naik) = '$bulan_ini'
      AND YEAR(p.tanggal_naik) = '$tahun_ini'
    GROUP BY g.id_gunung
    ORDER BY akumulasi_pendaki DESC
    LIMIT 3
");
$total_top_3 = mysqli_num_rows($query_top_3);

// DATA ANTREAN REVISI MITRA
$query_pengajuan = mysqli_query($conn, "
    SELECT pug.*, g.nama_gunung, up.nama_lengkap AS nama_mitra
    FROM pengajuan_update_gunung pug
    JOIN gunung g ON pug.id_gunung = g.id_gunung
    JOIN user_profile up ON pug.id_mitra = up.id_user
    WHERE pug.status_pengajuan = 'Menunggu Verifikasi'
    ORDER BY pug.id_pengajuan ASC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pusat Kendali - NdakiYuk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .main-wrapper { width: 100%; overflow-x: hidden; }
        .top-hiker-list { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; }
        .top-hiker-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 14px 16px; background: rgba(255, 255, 255, 0.02);
            border: 1px solid var(--border-color); border-radius: var(--radius-md);
        }
        .rank-badge {
            width: 28px; height: 28px; border-radius: 50%; display: grid; place-items: center;
            font-weight: 700; font-size: 12px; font-family: 'Space Grotesk', sans-serif;
        }
        .rank-1 { background-color: rgba(245, 197, 24, 0.15); color: var(--yellow-text); border: 1px solid rgba(245, 197, 24, 0.3); }
        .rank-2 { background-color: rgba(96, 197, 241, 0.15); color: var(--blue-text); border: 1px solid rgba(96, 197, 241, 0.3); }
        .rank-3 { background-color: rgba(255, 107, 107, 0.15); color: var(--red-text); border: 1px solid rgba(255, 107, 107, 0.3); }
        
        /* Layout Grid Taktis */
        .mid-dashboard-row { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 20px; width: 100%; margin-bottom: 32px; }
        .charts-stacked-column { display: flex; flex-direction: column; gap: 20px; min-width: 0; }
        @media(max-width: 1200px) { .mid-dashboard-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<div class="layout">
    <?php include 'sidebar.php'; ?>

    <main class="main">
        <div class="main-wrapper">
            
            <div class="header-banner">
                <small class="header-pretitle">SISTEM INTEGRASI KENDALI DATA UTAMA</small>
                <h1 class="header-welcome">Panel Monitoring Data Keseluruhan</h1>
            </div>

            <div class="cards-grid">
                <div class="stat-card">
                    <div class="scard-icon-wrap">Users</div>
                    <div class="scard-data">
                        <span class="scard-label">Akun Pendaki Aktif</span>
                        <h2 class="scard-value"><?= $total_user; ?> <small>User</small></h2>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="scard-icon-wrap">Mitra</div>
                    <div class="scard-data">
                        <span class="scard-label">Mitra Basecamp Tergabung</span>
                        <h2 class="scard-value"><?= $total_mitra; ?> <small>Instansi</small></h2>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="scard-icon-wrap">Mountains</div>
                    <div class="scard-data">
                        <span class="scard-label">Total Destinasi Kawasan</span>
                        <h2 class="scard-value"><?= $total_gunung; ?> <small>Kawasan</small></h2>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="scard-icon-wrap">Manifests</div>
                    <div class="scard-data">
                        <span class="scard-label">Akumulasi Manifes Sukses</span>
                        <h2 class="scard-value"><?= $total_simaksi; ?> <small>Berkas</small></h2>
                    </div>
                </div>
            </div>

            <div class="mid-dashboard-row">
                
                <div class="charts-stacked-column">
                    <div class="chart-card">
                        <div class="chart-header"><h3 class="chart-title">Tren Pendaftaran Pengguna Baru</h3></div>
                        <div class="chart-container"><canvas id="userLineChart"></canvas></div>
                    </div>
                    <div class="chart-card">
                        <div class="chart-header"><h3 class="chart-title">Distribusi Pengunjung Jalur</h3></div>
                        <div class="chart-container"><canvas id="mountainDonutChart"></canvas></div>
                    </div>
                </div>

                <div class="chart-card" style="display: flex; flex-direction: column; justify-content: flex-start;">
                    <div class="chart-header" style="margin-bottom: 10px;">
                        <h3 class="chart-title">Top 3 Terpopuler Bulan <?= $nama_bulan_ini; ?></h3>
                    </div>
                    <div class="top-hiker-list">
                        <?php if ($total_top_3 > 0): ?>
                            <?php 
                            $rank = 1; 
                            while ($top = mysqli_fetch_assoc($query_top_3)): 
                            ?>
                                <div class="top-hiker-item">
                                    <div style="display: flex; align-items: center; gap: 14px;">
                                        <div class="rank-badge rank-<?= $rank; ?>"><?= $rank++; ?></div>
                                        <div>
                                            <strong style="color: var(--text-title); font-size: 0.9rem; display: block;"><?= htmlspecialchars($top['nama_gunung']); ?></strong>
                                            <small style="color: var(--text-muted); font-size: 0.75rem;"><?= htmlspecialchars($top['lokasi']); ?> • <?= number_format($top['tinggi'], 0, ',', '.'); ?> MDPL</small>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="bold-code" style="font-size: 0.95rem; color: var(--green-text); display: block;"><?= number_format($top['akumulasi_pendaki'], 0, ',', '.'); ?></span>
                                        <small style="color: var(--text-muted); font-size: 0.7rem;"><?= $top['total_kelompok']; ?> Rombongan</small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div style="text-align: center; color: var(--text-muted); font-style: italic; padding: 40px 0; font-size: 0.85rem;">
                                Belum ada riwayat pendakian yang diselesaikan pada bulan ini.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Antrean Ubah data dari Mitra</h3>
                    <span class="section-badge">Butuh Validasi: <?= mysqli_num_rows($query_pengajuan); ?></span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Instansi Pengaju</th>
                                <th>Target Kawasan</th>
                                <th>Usulan Tiket</th>
                                <th>Kuota Baru</th>
                                <th>Status Gerbang</th>
                                <th style="text-align: center;">Konfigurasi Tindakan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($query_pengajuan) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_pengajuan)): ?>
                                    <tr>
                                        <td><strong class="hiker-name"><?= htmlspecialchars($row['nama_mitra']); ?></strong></td>
                                        <td><span class="bold-code"><?= htmlspecialchars($row['nama_gunung']); ?></span></td>
                                        <td class="price-table">Rp <?= number_format($row['harga_tiket'], 0, ',', '.'); ?></td>
                                        <td class="count-text"><strong><?= $row['kuota_harian']; ?></strong> / Hari</td>
                                        <td><span class="badge-status <?= $row['status_gunung'] == 'Buka' ? 'status-success' : 'status-danger'; ?>">Jalur <?= $row['status_gunung']; ?></span></td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 8px; justify-content: center;">
                                                <a href="proses_persetujuan.php?id=<?= $row['id_pengajuan']; ?>&status=Diterima" class="btn-action-acc">ACC</a>
                                                <a href="proses_persetujuan.php?id=<?= $row['id_pengajuan']; ?>&status=Ditolak" class="btn-action-reject">Tolak</a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="empty-row-text">Antrean sinkronisasi berkas wilayah kosong.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h3 class="section-title">Data User</h3>
                    <span class="section-badge">Otoritas Tertinggi</span>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Lengkap User</th>
                                <th>Username</th>
                                <th>Alamat Email</th>
                                <th>Nomor KTP (NIK - Masked)</th>
                                <th>No. Handphone (Masked)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql_users = mysqli_query($conn, "
                                SELECT u.username, u.email, up.nama_lengkap, up.nik, up.no_hp 
                                FROM users u 
                                JOIN user_profile up ON u.id_user = up.id_user 
                                WHERE u.id_role = 1 
                                ORDER BY u.id_user DESC
                            ");
                            if(mysqli_num_rows($sql_users) > 0):
                                while($usr = mysqli_fetch_assoc($sql_users)):
                                    $raw_nik = $usr['nik'];
                                    $masked_nik = (!empty($raw_nik)) ? substr($raw_nik, 0, 6) . "XXXXXXXX" . substr($raw_nik, -2) : '—';
                                    
                                    $raw_hp = $usr['no_hp'];
                                    $masked_hp = (!empty($raw_hp)) ? substr($raw_hp, 0, 4) . "XXXX" . substr($raw_hp, -3) : '—';
                            ?>
                                    <tr>
                                        <td><strong class="hiker-name"><?= htmlspecialchars($usr['nama_lengkap']); ?></strong></td>
                                        <td class="date-text">@<?= htmlspecialchars($usr['username']); ?></td>
                                        <td class="date-text"><?= htmlspecialchars($usr['email']); ?></td>
                                        <td class="bold-code"><?= $masked_nik; ?></td>
                                        <td class="count-text"><?= $masked_hp; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="5" class="empty-row-text">Belum ada pendaki yang terdaftar.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<script>
    const userCtx = document.getElementById('userLineChart').getContext('2d');
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($arr_tgl_user); ?>,
            datasets: [{
                label: 'User Baru',
                data: <?= json_encode($arr_jml_user); ?>,
                borderColor: '#1a5c38',
                backgroundColor: 'rgba(26, 92, 56, 0.06)',
                borderWidth: 2,
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#1a5c38',
                pointBorderColor: '#ffffff'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { display: false },
                tooltip: {
                    padding: 10,
                    backgroundColor: '#0d1510',
                    titleColor: '#ffffff',
                    bodyColor: '#a3b3aa',
                    borderColor: 'rgba(224, 232, 227, 0.1)',
                    borderWidth: 1
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.02)', drawBorder: false },
                    ticks: { color: '#63736b', font: { size: 10 } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.02)', drawBorder: false },
                    ticks: { color: '#63736b', font: { size: 11 }, stepSize: 1, precision: 0 }
                }
            }
        }
    });

    const mountainCtx = document.getElementById('mountainDonutChart').getContext('2d');
    new Chart(mountainCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($arr_nama_gunung); ?>,
            datasets: [{
                data: <?= json_encode($arr_vol_gunung); ?>,
                backgroundColor: ['#1a5c38', '#246b43', '#f5c518', '#60c5f1', '#ff6b6b'],
                borderWidth: 2,
                borderColor: '#121b15'
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            cutout: '75%', 
            plugins: { 
                legend: { 
                    display: true,
                    position: 'bottom',
                    labels: {
                        color: '#a3b3aa',
                        padding: 10,
                        font: { size: 11 },
                        boxWidth: 8,
                        usePointStyle: true
                    }
                },
                tooltip: {
                    padding: 10,
                    backgroundColor: '#0d1510',
                    borderColor: 'rgba(224, 232, 227, 0.1)',
                    borderWidth: 1
                }
            } 
        }
    });
</script>
</body>
</html>