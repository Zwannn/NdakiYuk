<?php
// review.php – Halaman Ulasan NdakiYuk Terkelompok per Gunung
session_start();
include 'config/koneksi.php';
include "auth/cek_role.php";
hanyaPendakiRoot();

// ── 1. AMBIL STATISTIK UMUM ─────────────────────────────────────────────
$queryStats = mysqli_query($conn, "
    SELECT COUNT(*) as total_ulasan, COALESCE(AVG(rating), 0) as rata_rating 
    FROM reviews
");
$stats = mysqli_fetch_assoc($queryStats);

// ── 2. QUERY UTAMA: DIURUTKAN BERDASARKAN GUNUNG TERLEBIH DAHULU ────────
// Catatan: ORDER BY g.id_gunung sangat krusial agar pengelompokan berjalan lurus
$queryReviews = mysqli_query($conn, "
    SELECT 
        r.*, 
        g.nama_gunung, g.lokasi as lokasi_gunung, g.gambar, g.tinggi,
        up.nama_lengkap, up.foto_profile
    FROM reviews r
    JOIN gunung g ON r.id_gunung = g.id_gunung
    JOIN user_profile up ON r.id_user = up.id_user
    ORDER BY g.id_gunung ASC, r.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Ulasan per Gunung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/review.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <header class="review-header">
        <div class="header-container">
            <h1 class="header-title">Arsip Ulasan Jalur Gunung</h1>
            <p class="header-sub">Informasi track, mata air, dan medan pendakian yang dikelompokkan secara objektif per wilayah gunung.</p>
        </div>
    </header>

    <main class="page-main">
        <div class="page-container">

            <div class="rating-summary-card">
                <div class="score-box">
                    <span class="score-num"><?= number_format($stats['rata_rating'], 1) ?></span>
                    <div class="score-stars">
                        <?php
                        $stars_floor = floor($stats['rata_rating']);
                        for ($i = 1; $i <= 5; $i++):
                        ?>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="<?= $i <= $stars_floor ? '#f5c842' : '#edf2f7' ?>" stroke="<?= $i <= $stars_floor ? '#f5c842' : '#cbd5e0' ?>">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                            </svg>
                        <?php endfor; ?>
                    </div>
                    <span class="score-total">Total (<?= $stats['total_ulasan'] ?> Komentar Komunitas)</span>
                </div>
                <div class="summary-text-box">
                    <h3>Sistem Manajemen Ulasan Terklasifikasi</h3>
                    <p>Gunakan ulasan di bawah ini sebagai acuan primer mengenai update regulasi pos pendakian, kuota harian, serta estimasi waktu tempuh dari para pendaki pendahulu.</p>
                </div>
            </div>

            <div class="gunung-group-container">
                <?php
                if (mysqli_num_rows($queryReviews) === 0):
                ?>
                    <div class="empty-review-box">
                        <p class="empty-text">Belum ada ulasan petualangan yang terdaftar di database NdakiYuk.</p>
                    </div>
                    <?php
                else:
                    $current_gunung = null;
                    $colors = ['#1a5c38', '#2a4d69', '#4b86b4', '#e8832a', '#d9534f'];

                    while ($r = mysqli_fetch_assoc($queryReviews)):
                        // JIKA MENEMUKAN GUNUNG BARU, BUAT KOTAK GRUP UTAMA NYA
                        if ($current_gunung !== $r['id_gunung']):
                            // Jika bukan gunung pertama, tutup tag div pembungkus list ulasan gunung sebelumnya
                            if ($current_gunung !== null) {
                                echo '</div></div>';
                            }
                            $current_gunung = $r['id_gunung'];
                    ?>
                            <div class="gunung-group-block">
                                <div class="gunung-group-header">
                                    <div class="gunung-info-left">
                                        <h2 class="gunung-group-title">Gunung <?= htmlspecialchars($r['nama_gunung']) ?></h2>
                                        <span class="gunung-group-meta"><?= htmlspecialchars($r['lokasi_gunung']) ?> • <?= number_format($r['tinggi'], 0, ',', '.') ?> MDPL</span>
                                    </div>
                                </div>

                                <div class="gunung-reviews-list">
                                <?php
                            endif;

                            // PROSES RENDER SUB-REVIEW UNTUK GUNUNG YANG SEDANG AKTIF
                            $inisial = strtoupper(substr($r['nama_lengkap'], 0, 2));
                            $warna_avatar = $colors[$r['id_user'] % count($colors)];
                            $tgl_ulasan = new DateTime($r['created_at']);
                                ?>
                                <div class="sub-review-item">
                                    <div class="review-user-row">
                                        <div class="user-avatar" style="background: <?= $warna_avatar ?>">
                                            <?= $inisial ?>
                                        </div>
                                        <div class="user-meta-info">
                                            <h4 class="user-name"><?= htmlspecialchars($r['nama_lengkap']) ?></h4>
                                            <span class="review-date"><?= $tgl_ulasan->format('d M Y') ?></span>
                                        </div>
                                        <div class="user-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="<?= $i <= $r['rating'] ? '#f5c842' : '#edf2f7' ?>" stroke="<?= $i <= $r['rating'] ? '#f5c842' : '#e2e8f0' ?>">
                                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                                </svg>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="review-text-content">
                                        <p>"<?= htmlspecialchars($r['komentar']) ?>"</p>
                                    </div>
                                </div>
                        <?php
                    endwhile;
                    // Tutup div kelompok gunung terakhir setelah loop selesai habis
                    echo '</div></div>';
                endif;
                        ?>
                                </div>

                            </div>
    </main>

    <?php include 'components/footer.php'; ?>

</body>

</html>