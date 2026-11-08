<?php
// index.php – Dashboard Utama NdakiYuk
session_start();

if (isset($_SESSION['id_user']) && isset($_SESSION['role'])) {

    // Jika admin membuka index.php, arahkan ke dashboard admin
    if ($_SESSION['role'] == 3) {
        header("Location: admin/dashboard.php");
        exit;
    }

    // Jika mitra membuka index.php, arahkan ke dashboard mitra
    if ($_SESSION['role'] == 2) {
        header("Location: mitra/dashboard.php");
        exit;
    }

    // Jika pendaki, tetap boleh berada di index.php
    if ($_SESSION['role'] == 1) {
        $_SESSION['pendaki_logged_in'] = true;
    }
}

include 'config/koneksi.php';
// =========================================================================
// 1. QUERY SLIDER: Mengambil Tepat 1 Gunung dari Setiap Kategori (Mudah, Sedang, Berat, Extreme)
// =========================================================================
$querySlides = mysqli_query($conn, "
    (SELECT g.*, kg.nama_kategori, COALESCE(AVG(r.rating), 0) AS avg_rating 
    FROM gunung g 
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori 
    LEFT JOIN reviews r ON g.id_gunung = r.id_gunung 
    WHERE g.id_kategori = 1 GROUP BY g.id_gunung LIMIT 1)
    UNION ALL
    (SELECT g.*, kg.nama_kategori, COALESCE(AVG(r.rating), 0) AS avg_rating 
    FROM gunung g 
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori 
    LEFT JOIN reviews r ON g.id_gunung = r.id_gunung 
    WHERE g.id_kategori = 2 GROUP BY g.id_gunung LIMIT 1)
    UNION ALL
    (SELECT g.*, kg.nama_kategori, COALESCE(AVG(r.rating), 0) AS avg_rating 
    FROM gunung g 
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori 
    LEFT JOIN reviews r ON g.id_gunung = r.id_gunung 
    WHERE g.id_kategori = 3 GROUP BY g.id_gunung LIMIT 1)
    UNION ALL
    (SELECT g.*, kg.nama_kategori, COALESCE(AVG(r.rating), 0) AS avg_rating 
    FROM gunung g 
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori 
    LEFT JOIN reviews r ON g.id_gunung = r.id_gunung 
    WHERE g.id_kategori = 4 GROUP BY g.id_gunung LIMIT 1)
");

$slides = [];
while ($row = mysqli_fetch_assoc($querySlides)) {
    $kat = strtolower($row['nama_kategori']);
    $badge_cls = match ($kat) {
        'mudah'   => 'badge-mudah',
        'sedang'  => 'badge-sedang',
        'berat'   => 'badge-berat',
        default   => 'badge-extreme',
    };

    $slides[] = [
        'title'     => $row['nama_gunung'] . ' — ' . number_format($row['tinggi'], 0, ',', '.') . ' mdpl',
        'desc'      => $row['deskripsi'],
        'image'     => 'assets/img/gunung/' . $row['gambar'],
        'badge'     => strtoupper($row['nama_kategori']),
        'badge_cls' => $badge_cls
    ];
}

// Fallback jika database gunung kosong
if (empty($slides)) {
    $slides = [[
        'title' => 'Mulai Petualanganmu',
        'desc' => 'Jelajahi keindahan alam Indonesia.',
        'image' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=1600&q=80',
        'badge' => 'NDAKIYUK',
        'badge_cls' => 'badge-mudah'
    ]];
}

// =========================================================================
// 2. QUERY REKOMENDASI: Mengambil 8 Gunung Secara Acak/Terbaru
// =========================================================================
$queryRekomendasi = mysqli_query($conn, "
    SELECT g.*, kg.nama_kategori, COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM gunung g
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori
    LEFT JOIN reviews r ON g.id_gunung = r.id_gunung
    GROUP BY g.id_gunung
    ORDER BY g.id_gunung ASC
    LIMIT 8
");

$gunung = [];
while ($row = mysqli_fetch_assoc($queryRekomendasi)) {
    $kat = strtolower($row['nama_kategori']);
    $badge_cls = match ($kat) {
        'mudah'   => 'badge-mudah',
        'sedang'  => 'badge-sedang',
        'berat'   => 'badge-berat',
        default   => 'badge-extreme',
    };

    $gunung[] = [
        'id'        => $row['id_gunung'],
        'nama'      => $row['nama_gunung'],
        'lokasi'    => $row['lokasi'] . ', Jawa Timur',
        'harga'     => 'Rp ' . number_format($row['harga_tiket'], 0, ',', '.'),
        'badge'     => strtoupper($row['nama_kategori']),
        'badge_cls' => $badge_cls,
        'image'     => 'assets/img/gunung/' . $row['gambar'],
        'rating'    => number_format($row['avg_rating'], 1)
    ];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Jelajahi Gunung Nusantara</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1000">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <section class="slider-section" id="slider">
        <div class="slider-track" id="sliderTrack">
            <?php foreach ($slides as $i => $s): ?>
                <div class="slide <?= $i === 0 ? 'active' : '' ?>">
                    <div class="slide-bg" style="background-image:url('<?= $s['image'] ?>')"></div>
                    <div class="slide-overlay"></div>
                    <div class="slide-content">
                        <span class="card-badge <?= $s['badge_cls'] ?>" style="position:static; margin-bottom:12px; width:fit-content;"><?= $s['badge'] ?></span>
                        <h1 class="slide-title"><?= htmlspecialchars($s['title']) ?></h1>
                        <p class="slide-desc"><?= htmlspecialchars($s['desc']) ?></p>
                        <a href="gunung.php" class="btn-slide">Selengkapnya</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <button class="slider-arrow arrow-prev" id="prevBtn" aria-label="Sebelumnya">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6" />
            </svg>
        </button>
        <button class="slider-arrow arrow-next" id="nextBtn" aria-label="Berikutnya">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="9 18 15 12 9 6" />
            </svg>
        </button>

        <div class="slider-dots" id="sliderDots">
            <?php foreach ($slides as $i => $s): ?>
                <button class="dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" aria-label="Slide <?= $i + 1 ?>"></button>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="menu-section">
        <div class="container">
            <div class="menu-grid">
                <a href="gunung.php" class="menu-item">
                    <div class="menu-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <polygon points="3 11 22 2 13 21 11 13 3 11" />
                        </svg>
                    </div>
                    <span class="menu-label">Gunung</span>
                </a>
                <a href="tiket.php" class="menu-item">
                    <div class="menu-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="1" y="4" width="22" height="16" rx="2" />
                            <line x1="1" y1="10" x2="23" y2="10" />
                        </svg>
                    </div>
                    <span class="menu-label">Tiketku</span>
                </a>
                <a href="history.php" class="menu-item">
                    <div class="menu-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 6 12 12 16 14" />
                        </svg>
                    </div>
                    <span class="menu-label">History</span>
                </a>
                <a href="checklist.php" class="menu-item">
                    <div class="menu-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <polyline points="9 11 12 14 22 4" />
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                        </svg>
                    </div>
                    <span class="menu-label">Checklist</span>
                </a>
                <a href="peta.php" class="menu-item">
                    <div class="menu-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6" />
                            <line x1="8" y1="2" x2="8" y2="18" />
                            <line x1="16" y1="6" x2="16" y2="22" />
                        </svg>
                    </div>
                    <span class="menu-label">Peta</span>
                </a>
                <a href="review.php" class="menu-item">
                    <div class="menu-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                        </svg>
                    </div>
                    <span class="menu-label">Review</span>
                </a>
            </div>
        </div>
    </section>

    <section class="klasifikasi-section">
        <div class="container">
            <h2 class="section-title">Klasifikasi Gunung</h2>
            <div class="klasifikasi-grid">
                <a href="gunung.php?filter=Mudah" class="klas-pill klas-mudah">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 8 12 12 14 14" />
                    </svg>
                    Mudah
                </a>
                <a href="gunung.php?filter=Sedang" class="klas-pill klas-sedang">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 8 12 12 14 14" />
                    </svg>
                    Sedang
                </a>
                <a href="gunung.php?filter=Berat" class="klas-pill klas-berat">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 8 12 12 14 14" />
                    </svg>
                    Berat
                </a>
                <a href="gunung.php?filter=Extreme" class="klas-pill klas-extreme">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 8 12 12 14 14" />
                    </svg>
                    Extreme
                </a>
            </div>
        </div>
    </section>

    <section class="rekomendasi-section" id="gunung">
        <div class="container">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Rekomendasi Gunung</h2>
                    <p class="section-sub">Destinasi favorit para pendaki bulan ini</p>
                </div>
                <a href="gunung.php" class="link-all">
                    Lihat Semua
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </a>
            </div>

            <div class="gunung-grid">
                <?php foreach ($gunung as $g): ?>
                    <a href="detail_gunung.php?id=<?= $g['id'] ?>" class="gunung-card" style="text-decoration: none; color: inherit; display: block;">
                        <div class="card-img-wrap">
                            <img src="<?= $g['image'] ?>" alt="<?= htmlspecialchars($g['nama']) ?>" class="card-img" loading="lazy">
                            <span class="card-badge <?= $g['badge_cls'] ?>"><?= $g['badge'] ?></span>
                            <div class="card-img-overlay"></div>
                        </div>
                        <div class="card-body">
                            <div class="card-info">
                                <h3 class="card-nama"><?= htmlspecialchars($g['nama']) ?></h3>
                                <p class="card-lokasi">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                        <circle cx="12" cy="10" r="3" />
                                    </svg>
                                    <?= htmlspecialchars($g['lokasi']) ?>
                                </p>
                                <div class="card-rating" style="display: flex; align-items: center; gap: 4px; margin-bottom: 12px; font-size: 0.75rem; color: #f59e0b; font-weight: 600;">
                                    <span>⭐ <?= $g['rating'] ?></span>
                                </div>
                            </div>
                            <div class="card-footer">
                                <span class="card-harga"><?= $g['harga'] ?></span>
                                <button class="btn-save" aria-label="Simpan" onclick="event.preventDefault(); event.stopPropagation();">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <?php include 'components/footer.php'; ?>

    <script>
        // ===== NAVBAR SCROLL =====
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 30);
            });
        }

        // ===== HERO SLIDER =====
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        let current = 0;
        let timer;

        function goTo(idx) {
            if (!slides.length) return;
            slides[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');
            current = (idx + slides.length) % slides.length;
            slides[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
        }

        function startTimer() {
            if (!slides.length) return;
            clearInterval(timer);
            timer = setInterval(() => goTo(current + 1), 5000);
        }

        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');

        if (prevBtn) prevBtn.addEventListener('click', () => {
            goTo(current - 1);
            startTimer();
        });
        if (nextBtn) nextBtn.addEventListener('click', () => {
            goTo(current + 1);
            startTimer();
        });

        dots.forEach(d => d.addEventListener('click', () => {
            goTo(+d.dataset.index);
            startTimer();
        }));

        startTimer();

        // ===== SAVE BUTTON TOGGLE =====
        document.querySelectorAll('.btn-save').forEach(btn => {
            btn.addEventListener('click', function() {
                this.classList.toggle('saved');
                const path = this.querySelector('path');
                if (this.classList.contains('saved')) {
                    path.setAttribute('fill', '#f5c842');
                    path.setAttribute('stroke', '#f5c842');
                } else {
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke', 'currentColor');
                }
            });
        });
    </script>
</body>

</html>