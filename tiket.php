<?php
// tiket.php – Halaman Riwayat Tiket Pendakian User NdakiYuk
session_start();
include 'config/koneksi.php';
include "auth/cek_role.php";
hanyaPendakiRoot();

// JIKA BELUM LOGIN, LEMPAR KE LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ── QUERY UTAMA: AMBIL TIKET AKTIF (SELAIN SELESAI / DITOLAK) ─────────────
// Hanya menampilkan: Menunggu Pembayaran, Menunggu Verifikasi, dan Diterima
$queryTiket = mysqli_query($conn, "
    SELECT 
        p.*, 
        g.nama_gunung, g.lokasi, g.gambar, g.tinggi
    FROM pemesanan p
    JOIN gunung g ON p.id_gunung = g.id_gunung
    WHERE p.id_user = '$id_user' 
      AND p.status_pemesanan IN ('Menunggu Pembayaran', 'Menunggu Verifikasi', 'Diterima')
    ORDER BY p.created_at DESC
");

function formatRupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// Helper untuk penentuan class warna lencana status
function getStatusClass(string $status): string
{
    return match ($status) {
        'Menunggu Pembayaran' => 'status-pembayaran',
        'Menunggu Verifikasi' => 'status-verifikasi',
        'Diterima'           => 'status-lunas',
        'Ditolak'            => 'status-ditolak',
        'Selesai'            => 'status-selesai',
        default              => 'status-pembayaran',
    };
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Tiketku</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/tiket.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <header class="tiket-header">
        <div class="header-container">
            <h1 class="header-title">Tiket Keberangkatan Anda</h1>
            <p class="header-sub">Pantau status pembayaran, unduh e-tiket resmi, dan persiapkan petualangan pendakian Anda.</p>
        </div>
    </header>

    <main class="page-main">
        <div class="page-container">

            <?php if (mysqli_num_rows($queryTiket) === 0): ?>
                <div class="empty-ticket-card">
                    <div class="empty-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v2a2 2 0 0 0 0 4v2a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3v-2a2 2 0 0 0 0-4V9z" />
                            <path d="M12 6v12" />
                        </svg>
                    </div>
                    <h3 class="empty-title">Tidak Ada Tiket Aktif</h3>
                    <p class="empty-desc">Anda sedang tidak memiliki tiket pendakian yang aktif. Riwayat pendakian yang sudah selesai dapat dilihat di halaman History.</p>
                    <a href="gunung.php" class="btn-explore">Jelajahi Gunung</a>
                </div>
            <?php else: ?>

                <div class="tiket-grid">
                    <?php while ($t = mysqli_fetch_assoc($queryTiket)):
                        $tgl_naik = new DateTime($t['tanggal_naik']);
                        $tgl_turun = new DateTime($t['tanggal_turun']);
                    ?>
                        <div class="tiket-card">
                            <div class="card-image-section">
                                <img src="assets/img/gunung/<?= htmlspecialchars($t['gambar']) ?>" alt="<?= htmlspecialchars($t['nama_gunung']) ?>" class="gunung-img">
                                <div class="img-overlay"></div>
                                <span class="height-badge"><?= number_format($t['tinggi'], 0, ',', '.') ?> MDPL</span>
                            </div>

                            <div class="card-details-section">
                                <div class="meta-top">
                                    <span class="booking-code">#<?= htmlspecialchars($t['kode_booking']) ?></span>
                                    <span class="status-badge <?= getStatusClass($t['status_pemesanan']) ?>">
                                        <?= htmlspecialchars($t['status_pemesanan']) ?>
                                    </span>
                                </div>

                                <h2 class="gunung-title">Gunung <?= htmlspecialchars($t['nama_gunung']) ?></h2>
                                <p class="gunung-lokasi"><?= htmlspecialchars($t['lokasi']) ?>, Jawa Timur</p>

                                <div class="info-row-grid">
                                    <div class="info-item">
                                        <span class="info-label">TANGGAL NAIK</span>
                                        <span class="info-value">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                                <line x1="16" y1="2" x2="16" y2="6" />
                                                <line x1="8" y1="2" x2="8" y2="6" />
                                                <line x1="3" y1="10" x2="21" y2="10" />
                                            </svg>
                                            <?= $tgl_naik->format('d M Y') ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">JUMLAH ROMBONGAN</span>
                                        <span class="info-value">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                                <circle cx="9" cy="7" r="4" />
                                            </svg>
                                            <?= $t['jumlah_pendaki'] ?> Orang
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">JALUR POS</span>
                                        <span class="info-value">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="3 11 22 2 13 21 11 13 3 11" />
                                            </svg>
                                            <?= htmlspecialchars($t['pos_masuk']) ?>
                                        </span>
                                    </div>
                                    <div class="info-item">
                                        <span class="info-label">TOTAL BIAYA</span>
                                        <span class="info-value price-tag"><?= formatRupiah($t['total_harga']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="card-actions-section">
                                <?php if ($t['status_pemesanan'] === 'Menunggu Pembayaran'): ?>
                                    <a href="konfirmasi.php?kode=<?= $t['kode_booking'] ?>" class="btn-action btn-pay">
                                        Bayar Sekarang
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <line x1="5" y1="12" x2="19" y2="12" />
                                            <polyline points="12 5 19 12 12 19" />
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <a href="konfirmasi.php?kode=<?= $t['kode_booking'] ?>" class="btn-action btn-view">
                                        Lihat Detail Tiket
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10" />
                                            <polyline points="12 16 16 12 12 8" />
                                            <line x1="8" y1="12" x2="16" y2="12" />
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>

            <?php endif; ?>

        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        // Efek kelas scroll navigasi navbar
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 30);
            });
        }
    </script>
</body>

</html>