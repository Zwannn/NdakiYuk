<?php
session_start();
include "config/koneksi.php";
include "auth/cek_role.php";
hanyaPendakiRoot();

// JIKA BELUM LOGIN, LEMPAR KE LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ── QUERY MENGAMBIL HISTORY PENDAKIAN USER (HANYA YANG SUDAH 'SELESAI') ──
$queryHistory = mysqli_query($conn, "
    SELECT 
        p.*, 
        g.nama_gunung, g.lokasi, g.tinggi, g.gambar, 
        kg.nama_kategori 
    FROM pemesanan p
    JOIN gunung g ON p.id_gunung = g.id_gunung
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori
    WHERE p.id_user = '$id_user' 
      AND p.status_pemesanan = 'Selesai'
    ORDER BY p.created_at DESC
");

$history_list = [];
while ($row = mysqli_fetch_assoc($queryHistory)) {
    $history_list[] = $row;
}

// Helper Format Rupiah
function formatRp(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// Helper Format Tanggal (Contoh: 24 Mei 2026)
function formatTanggal($date): string
{
    if (!$date) return '-';
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $d = date('j', strtotime($date));
    $m = $bulan[date('n', strtotime($date)) - 1];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}

// Helper Badge Status Pemesanan
function badgeStatusClass(string $status): string
{
    return match ($status) {
        'Selesai'             => 'status-selesai',
        'Diterima'            => 'status-diterima',
        'Menunggu Pembayaran' => 'status-menunggu',
        'Menunggu Verifikasi' => 'status-verifikasi',
        'Ditolak'             => 'status-ditolak',
        default               => 'status-default',
    };
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – History Pendakian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/history.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="page-header-inner">
                <div>
                    <h1 class="page-title">HISTORY PENDAKIAN</h1>
                    <div class="page-title-bar"></div>
                    <p class="page-desc">
                        Riwayat perjalanan pendakian Anda yang telah selesai bersama NdakiYuk.<br>
                        Cek kembali kenangan petualangan dan detail tiket Anda di sini.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="container">
            <?php if (empty($history_list)): ?>
                <div class="empty-state">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <h3>Belum Ada Riwayat Pendakian yang Selesai</h3>
                    <p>Anda belum memiliki riwayat pendakian yang telah dikonfirmasi selesai. Jika Anda sedang dalam masa pendakian atau belum membayar tiket, silakan cek halaman Tiketku.</p>
                    <div style="display: flex; gap: 10px; justify-content: center; margin-top: 15px;">
                        <a href="tiket.php" class="btn-explore" style="background: var(--bg-page); color: var(--text-mid); border: 1.5px solid var(--border);">Cek Tiket Aktif</a>
                        <a href="gunung.php" class="btn-explore">Mulai Jelajah Gunung</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="history-list">
                    <?php foreach ($history_list as $index => $item): ?>
                        <div class="history-card" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="hc-image-wrap">
                                <img src="assets/img/gunung/<?= htmlspecialchars($item['gambar']) ?>" alt="<?= htmlspecialchars($item['nama_gunung']) ?>" class="hc-image">
                                <span class="hc-category"><?= htmlspecialchars($item['nama_kategori']) ?></span>
                            </div>

                            <div class="hc-body">
                                <div class="hc-header">
                                    <div class="hc-title-wrap">
                                        <h2 class="hc-title"><?= htmlspecialchars($item['nama_gunung']) ?></h2>
                                        <span class="hc-lokasi">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                                <circle cx="12" cy="10" r="3" />
                                            </svg>
                                            <?= htmlspecialchars($item['lokasi']) ?> • <?= number_format($item['tinggi'], 0, ',', '.') ?> mdpl
                                        </span>
                                    </div>
                                    <span class="hc-status <?= badgeStatusClass($item['status_pemesanan']) ?>">
                                        <?= htmlspecialchars($item['status_pemesanan']) ?>
                                    </span>
                                </div>

                                <div class="hc-divider"></div>

                                <div class="hc-details">
                                    <div class="hcd-item">
                                        <span class="hcd-label">KODE BOOKING</span>
                                        <span class="hcd-val kode-font">#<?= htmlspecialchars($item['kode_booking']) ?></span>
                                    </div>
                                    <div class="hcd-item">
                                        <span class="hcd-label">TANGGAL PENDAKIAN</span>
                                        <span class="hcd-val">
                                            <?= formatTanggal($item['tanggal_naik']) ?>
                                            <?= $item['tanggal_turun'] ? ' - ' . formatTanggal($item['tanggal_turun']) : '' ?>
                                        </span>
                                    </div>
                                    <div class="hcd-item">
                                        <span class="hcd-label">ANGGOTA</span>
                                        <span class="hcd-val"><?= $item['jumlah_pendaki'] ?> Orang</span>
                                    </div>
                                    <div class="hcd-item">
                                        <span class="hcd-label">TOTAL HARGA</span>
                                        <span class="hcd-val harga-font"><?= formatRp($item['total_harga']) ?></span>
                                    </div>
                                </div>

                                <div class="hc-actions">
                                    <a href="konfirmasi.php?kode=<?= $item['kode_booking'] ?>" class="btn-detail">
                                        Lihat Tiket & Detail
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <line x1="5" y1="12" x2="19" y2="12" />
                                            <polyline points="12 5 19 12 12 19" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        });
    </script>
</body>

</html>