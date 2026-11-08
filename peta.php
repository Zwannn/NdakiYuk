<?php
// peta.php – Halaman Pusat Unduh Peta Jalur Offline Rute Gunung NdakiYuk
session_start();
include "auth/cek_role.php";
blokirAdminMitraRoot();
// koneksi database
include 'config/koneksi.php';

// ── Ambil Data Gunung yang Memiliki Peta Jalur dari Database ──────────
$queryPeta = mysqli_query($conn, "
    SELECT id_gunung, nama_gunung, lokasi, tinggi, gambar, peta_gunung 
    FROM gunung 
    WHERE peta_gunung IS NOT NULL AND peta_gunung != ''
    ORDER BY nama_gunung ASC
");

$list_peta = [];
while ($row = mysqli_fetch_assoc($queryPeta)) {
    $list_peta[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Pusat Peta Jalur Pendakian</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/peta.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="page-header-inner">
                <h1 class="page-title">PETA NAVIGASI JALUR</h1>
                <div class="page-title-bar"></div>
                <p class="page-desc">
                    Unduh peta topografi dan rute perizinan resmi secara offline. Pastikan kelompok Anda<br>
                    tetap berada di jalur pendakian yang aman demi keselamatan bersama.
                </p>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="container">
            <div class="section-meta-info">
                <p class="result-count">Tersedia <strong><?= count($list_peta) ?> Peta Jalur Gunung</strong> yang Siap Diunduh</p>
            </div>

            <?php if (empty($list_peta)): ?>
                <div class="empty-state">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                        <line x1="9" y1="9" x2="15" y2="15" />
                        <line x1="15" y1="9" x2="9" y2="15" />
                    </svg>
                    <p>Belum ada berkas dokumen peta rute resmi yang diunggah ke database Mitra.</p>
                </div>
            <?php else: ?>
                <div class="peta-grid">
                    <?php foreach ($list_peta as $i => $p): ?>
                        <div class="peta-card" style="animation-delay: <?= $i * 0.05 ?>s">
                            <div class="card-media">
                                <img src="assets/img/gunung/<?= htmlspecialchars($p['gambar']) ?>" alt="<?= htmlspecialchars($p['nama_gunung']) ?>" loading="lazy">
                                <div class="media-overlay"></div>
                                <span class="geo-badge">📍 <?= htmlspecialchars($p['lokasi']) ?></span>
                            </div>

                            <div class="card-body">
                                <h3 class="mountain-name"><?= htmlspecialchars($p['nama_gunung']) ?></h3>
                                <p class="mountain-height">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="18 15 12 9 6 15" />
                                    </svg>
                                    <?= number_format($p['tinggi'], 0, ',', '.') ?> MDPL
                                </p>

                                <button type="button" class="btn-download-map"
                                    onclick="triggerMapDownload('<?= 'assets/peta%20gunung/' . rawurlencode($p['peta_gunung']) ?>', '<?= htmlspecialchars($p['peta_gunung']) ?>')">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="7 10 12 15 17 10" />
                                        <line x1="12" y1="15" x2="12" y2="3" />
                                    </svg>
                                    Unduh Peta Offline (PDF)
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <div class="toast" id="toast"></div>

    <script>
        // Animasi Navbar Efek Scroll
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 30);
            });
        }

        function showToastNotification(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast show';
            setTimeout(() => t.className = 'toast', 3000);
        }

        // Logika Pemanggilan Download Dokumen Fisik PDF Tanpa Redirect Page
        function triggerMapDownload(fileUrl, fileName) {
            showToastNotification('⬇ Memulai pengunduhan berkas dokumen peta rute…');

            setTimeout(() => {
                const downloadAnchor = document.createElement('a');
                downloadAnchor.href = fileUrl;
                downloadAnchor.download = fileName;
                document.body.appendChild(downloadAnchor);
                downloadAnchor.click();
                document.body.removeChild(downloadAnchor);
            }, 500);
        }
    </script>
</body>

</html>