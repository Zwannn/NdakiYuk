<?php
// WAJIB: Aktifkan sesi di setiap halaman yang menggunakan data login
session_start();
include "auth/cek_role.php";
blokirAdminMitraRoot();
// koneksi database
include 'config/koneksi.php';

// ── Filter & Sort ─────────────────────────────────────────
$filter  = $_GET['filter'] ?? 'Semua';
$sort    = $_GET['sort']   ?? 'rating';

$valid_filters = ['Semua', 'Mudah', 'Sedang', 'Berat', 'Extreme'];
$valid_sorts   = ['rating', 'nama', 'harga'];
if (!in_array($filter, $valid_filters)) $filter = 'Semua';
if (!in_array($sort, $valid_sorts))     $sort   = 'rating';

// ================================
// QUERY DATABASE
// ================================
$where = "";
if ($filter !== 'Semua') {
    $filterSafe = mysqli_real_escape_string($conn, $filter);
    $where = "WHERE kategori_gunung.nama_kategori = '$filterSafe'";
}

$orderBy = "avg_rating DESC";
if ($sort == 'nama') {
    $orderBy = "gunung.nama_gunung ASC";
} elseif ($sort == 'harga') {
    $orderBy = "gunung.harga_tiket ASC";
}

$query = mysqli_query($conn, "
    SELECT
        gunung.*,
        kategori_gunung.nama_kategori,
        COALESCE(AVG(reviews.rating),0) AS avg_rating
    FROM gunung
    JOIN kategori_gunung ON gunung.id_kategori = kategori_gunung.id_kategori
    LEFT JOIN reviews ON gunung.id_gunung = reviews.id_gunung
    $where
    GROUP BY gunung.id_gunung
    ORDER BY $orderBy
");

$list = [];
while ($row = mysqli_fetch_assoc($query)) {
    $list[] = $row;
}

// ... (Sisa fungsi badgeClass, formatRupiah, dan queryKategori tetap sama) ...
function badgeClass(string $k): string
{
    $k = strtolower($k);
    return match ($k) {
        'mudah'  => 'badge-mudah',
        'sedang' => 'badge-sedang',
        'berat'  => 'badge-berat',
        default  => 'badge-extreme',
    };
}

function formatRupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

$jumlahKategori = [];
$qKategori = mysqli_query($conn, "
    SELECT kategori_gunung.nama_kategori, COUNT(gunung.id_gunung) as total
    FROM kategori_gunung
    LEFT JOIN gunung ON kategori_gunung.id_kategori = gunung.id_kategori
    GROUP BY kategori_gunung.id_kategori
");

while ($k = mysqli_fetch_assoc($qKategori)) {
    $jumlahKategori[$k['nama_kategori']] = $k['total'];
}
$totalGunung = array_sum($jumlahKategori);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – List Gunung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1000">
    <link rel="stylesheet" href="assets/css/gunung.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <!-- ===================================================
     PAGE HEADER
==================================================== -->
    <div class="page-header">
        <div class="container">
            <div class="page-header-inner">
                <div>
                    <h1 class="page-title">LIST GUNUNG</h1>
                    <div class="page-title-bar"></div>
                    <p class="page-desc">
                        Jelajahi keindahan puncak-puncak megah di Jawa Timur. Temukan rute pendakian<br>
                        terbaik dan rencanakan petualangan Anda bersama Ndakiyuk.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================
     FILTER + SORT BAR
==================================================== -->
    <div class="filter-bar-wrap">
        <div class="container">
            <form method="GET" action="gunung.php" class="filter-bar" id="filterForm">

                <!-- Filter Kesulitan -->
                <div class="filter-group">
                    <div class="filter-group-label">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="4" y1="6" x2="20" y2="6" />
                            <line x1="8" y1="12" x2="16" y2="12" />
                            <line x1="11" y1="18" x2="13" y2="18" />
                        </svg>
                        Filter Kesulitan
                    </div>
                    <div class="filter-pills">
                        <?php foreach ($valid_filters as $f): ?>
                            <button
                                type="submit"
                                name="filter" value="<?= $f ?>"
                                class="pill-btn <?= $filter === $f ? 'active' : '' ?>"
                                onclick="this.form.elements['sort'].value='<?= htmlspecialchars($sort) ?>'">
                                <?= $f ?>
                                <?php if ($f == 'Semua'): ?>
                                    (<?= $totalGunung ?>)
                                <?php else: ?>
                                    (<?= $jumlahKategori[$f] ?? 0 ?>)
                                <?php endif; ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Sort -->
                <div class="sort-group">
                    <div class="filter-group-label">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="8" y1="6" x2="21" y2="6" />
                            <line x1="8" y1="12" x2="21" y2="12" />
                            <line x1="8" y1="18" x2="21" y2="18" />
                            <line x1="3" y1="6" x2="3.01" y2="6" />
                            <line x1="3" y1="12" x2="3.01" y2="12" />
                            <line x1="3" y1="18" x2="3.01" y2="18" />
                        </svg>
                        Urutkan
                    </div>
                    <div class="sort-select-wrap">
                        <select name="sort" class="sort-select" onchange="this.form.submit()" id="sortSelect">
                            <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating Tertinggi</option>
                            <option value="nama" <?= $sort === 'nama'   ? 'selected' : '' ?>>Nama (A–Z)</option>
                            <option value="harga" <?= $sort === 'harga'  ? 'selected' : '' ?>>Harga Terendah</option>
                        </select>
                        <svg class="sort-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </div>
                </div>
            </form>

            <p class="result-count">Menampilkan <strong><?= count($list) ?></strong> gunung</p>
        </div>
    </div>

    <!-- ===================================================
     GRID GUNUNG
==================================================== -->
    <main class="main-content">
        <div class="container">
            <?php if (empty($list)): ?>
                <div class="empty-state">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                    <p>Tidak ada gunung ditemukan untuk filter ini.</p>
                    <a href="gunung.php" class="btn-reset">Reset Filter</a>
                </div>
            <?php else: ?>
                <div class="gunung-grid">
                    <?php foreach ($list as $i => $g): ?>
                        <a href="detail_gunung.php?id=<?= $g['id_gunung'] ?>" class="gunung-card" style="animation-delay:<?= $i * 0.05 ?>s">
                            <div class="card-img-wrap">
                                <img
                                    src="assets/img/gunung/<?= htmlspecialchars($g['gambar']) ?>"
                                    alt="<?= htmlspecialchars($g['nama_gunung']) ?>"
                                    class="card-img"
                                    loading="lazy">
                                <div class="card-overlay"></div>
                                <span class="card-badge <?= badgeClass($g['nama_kategori']) ?>"><?= $g['nama_kategori'] ?></span>
                                <div class="card-foot-info">
                                    <span class="card-nama"><?= htmlspecialchars($g['nama_gunung']) ?></span>
                                    <span class="card-tinggi">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                            <polyline points="18 15 12 9 6 15" />
                                        </svg>
                                        <?= number_format($g['tinggi'], 0, ',', '.') ?> mdpl
                                    </span>
                                </div>
                            </div>
                            <div class="card-bottom">
                                <div class="card-rating">
                                    ⭐ <?= number_format($g['avg_rating'], 1) ?>
                                </div>
                                <span class="card-harga"><?= formatRupiah($g['harga_tiket']) ?></span>
                                <span class="card-arrow">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <line x1="5" y1="12" x2="19" y2="12" />
                                        <polyline points="12 5 19 12 12 19" />
                                    </svg>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        // Navbar scroll
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        });

        // CTA form
        function handleCta(e) {
            e.preventDefault();
            const btn = e.target.querySelector('.cta-btn');
            btn.textContent = '✓ Terdaftar!';
            btn.style.background = '#2d8653';
            setTimeout(() => {
                btn.textContent = 'Ikuti';
                btn.style.background = '';
            }, 3000);
            e.target.reset();
        }
    </script>
</body>

</html>