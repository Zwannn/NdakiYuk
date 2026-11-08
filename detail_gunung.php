<?php
// detail_gunung.php – Detail Lengkap Gunung Berbasis Database, Logistik & Fasilitas Dinamis
session_start();
include "auth/cek_role.php";
blokirAdminMitraRoot();
include 'config/koneksi.php';

// ── Ambil ID dari URL (default: 1 jika tidak ada) ──────────────────
$id = (int)($_GET['id'] ?? 1);

// ── Query Ambil Data Gunung Sesuai Database ────────────────────────
$query = mysqli_query($conn, "
    SELECT 
        gunung.*, 
        kategori_gunung.nama_kategori,
        COALESCE(AVG(reviews.rating), 0) AS avg_rating
    FROM gunung
    JOIN kategori_gunung ON gunung.id_kategori = kategori_gunung.id_kategori
    LEFT JOIN reviews ON gunung.id_gunung = reviews.id_gunung
    WHERE gunung.id_gunung = $id
    GROUP BY gunung.id_gunung
");

// Jika ID tidak ditemukan di database, redirect kembali ke gunung.php
if (mysqli_num_rows($query) === 0) {
    header('Location: gunung.php');
    exit;
}

$row = mysqli_fetch_assoc($query);

// ── QUERY KEDUA: AMBIL DATA ULASAN ORGANIK DARI DATABASE ────────────────
$queryReviews = mysqli_query($conn, "
    SELECT 
        r.rating, 
        r.komentar, 
        r.created_at, 
        up.nama_lengkap 
    FROM reviews r
    JOIN user_profile up ON r.id_user = up.id_user
    WHERE r.id_gunung = $id
    ORDER BY r.created_at DESC
");

$array_ulasan = [];

if (mysqli_num_rows($queryReviews) > 0) {
    $colors = ['#1a5c38', '#2a4d69', '#4b86b4', '#adcbe3', '#e8832a', '#d9534f', '#5cb85c'];

    while ($rev = mysqli_fetch_assoc($queryReviews)) {
        $inisial = strtoupper(substr($rev['nama_lengkap'], 0, 2));
        $warna_acak = $colors[array_rand($colors)];

        $date_parsed = new DateTime($rev['created_at']);
        $tanggal_teks = $date_parsed->format('d M Y');

        $array_ulasan[] = [
            'nama'    => $rev['nama_lengkap'],
            'avatar'  => $inisial,
            'color'   => $warna_acak,
            'tanggal' => $tanggal_teks,
            'bintang' => (int)$rev['rating'],
            'teks'    => '"' . $rev['komentar'] . '"',
        ];
    }
} else {
    $array_ulasan[] = [
        'nama'    => 'Sistem NdakiYuk',
        'avatar'  => 'NY',
        'color'   => '#1a5c38',
        'tanggal' => 'Sistem • Baru saja',
        'bintang' => 5,
        'teks'    => '"Belum ada ulasan organik untuk gunung ini. Jadilah pendaki pertama yang membagikan pengalaman ceritamu di sini!"',
    ];
}

// ── QUERY KETIGA: AMBIL DATA LOGISTIK SPESIFIK GUNUNG DARI DATABASE ──────
$queryLogistik = mysqli_query($conn, "
    SELECT l.nama_logistik, gl.status_wajib 
    FROM gunung_logistik gl
    JOIN logistik l ON gl.id_logistik = l.id_logistik
    WHERE gl.id_gunung = $id
    ORDER BY gl.status_wajib DESC, l.nama_logistik ASC
");

$array_logistik_gunung = [];
while ($log = mysqli_fetch_assoc($queryLogistik)) {
    $array_logistik_gunung[] = [
        'nama'   => $log['nama_logistik'],
        'status' => $log['status_wajib'] // 'Wajib' atau 'Disarankan'
    ];
}

if (empty($array_logistik_gunung)) {
    $array_logistik_gunung = [
        ['nama' => 'Sepatu Gunung (Trekking Boots)', 'status' => 'Wajib'],
        ['nama' => 'Jaket Thermal / Windbreaker', 'status' => 'Wajib'],
        ['nama' => 'Tas Carrier sesuai kapasitas', 'status' => 'Wajib'],
        ['nama' => 'Sleeping Bag & Matras', 'status' => 'Wajib'],
        ['transform' => 'Headlamp / Senter Cadangan', 'status' => 'Wajib'],
        ['nama' => 'Jas Hujan / Raincoat', 'status' => 'Wajib'],
        ['nama' => 'Obat-obatan Pribadi (P3K)', 'status' => 'Wajib']
    ];
}

// ── PROSES AMBIL DATA FASILITAS DARI KOLOM BARU DATABASE ──────────────────
if (!empty($row['fasilitas_gunung'])) {
    $array_fasilitas = explode(',', $row['fasilitas_gunung']);
    $array_fasilitas = array_map('trim', $array_fasilitas);
} else {
    $array_fasilitas = ['Basecamp Registrasi', 'Camping Ground', 'Papan Informasi Jalur', 'Toilet Umum'];
}

// ── Pemetaan Data Database ke Variabel Komponen UI ─────────────────
$g = [
    'nama'         => $row['nama_gunung'],
    'lokasi'       => $row['lokasi'],
    'tagline'      => $row['deskripsi'],
    'kesulitan'    => $row['nama_kategori'],
    'provinsi'     => 'Jawa Timur',
    'status'       => $row['status_gunung'],
    'ketinggian'   => number_format($row['tinggi'], 0, ',', '.'),
    'jalur'        => !empty($row['pos_perizinan']) ? 'Via ' . explode(',', $row['pos_perizinan'])[0] : 'Jalur Utama',
    'rating'       => number_format($row['avg_rating'], 1),
    'estimasi'     => $row['estimasi_pendakian'],
    'image_hero'   => 'assets/img/gunung/' . $row['gambar'],
    'peta_gunung'  => $row['peta_gunung'],

    // PEMETAAN BARU: Ambil data dari kolom baru `info_terkini`
    'info_terkini' => !empty($row['info_terkini']) ? $row['info_terkini'] : '',

    'deskripsi'    => [
        !empty($row['deskripsi']) ? $row['deskripsi'] : 'Belum tersedia deskripsi informasi geografis lengkap mengenai wilayah pendakian ini.',
        "Kawasan destinasi ini membatasi daya tampung kunjungan pendaki demi menjaga kelestarian ekosistem vegetasi hutan setempat. Kuota harian aman yang disediakan untuk alur registrasi resmi di pangkalan sistem NdakiYuk adalah sebanyak " . (int)($row['kuota_harian'] ?? 100) . " personel jiwa per harinya. Pastikan kelompok Anda melakukan proses pemesanan tiket SIMAKSI jauh-jauh hari sebelum kuota habis."
    ],

    'info_ai'      => ($row['nama_kategori'] == 'Mudah' || $row['nama_kategori'] == 'Sedang')
        ? 'Rute ini cukup ramah untuk pendaki. Tetap pantau kondisi cuaca lokal sebelum memulai pendakian.'
        : 'Jalur pendakian ini membutuhkan fisik yang sangat prima dan peralatan lengkap. Gunakan jasa porter jika diperlukan.',

    'fasilitas'    => $array_fasilitas,
    'logistik_db'  => $array_logistik_gunung,
    'harga'        => $row['harga_tiket'],
    'tersedia'     => ($row['status_gunung'] === 'Buka'),
    'ulasan'       => $array_ulasan
];

// Count logistik berstatus wajib untuk judul sidebar card
$count_wajib = 0;
foreach ($g['logistik_db'] as $item) {
    if ($item['status'] === 'Wajib') $count_wajib++;
}

// ── Fungsi Helper UI ───────────────────────────────────────────────
function badgeClass(string $k): string
{
    $k = strtolower($k);
    return match ($k) {
        'mudah'   => 'badge-mudah',
        'sedang'  => 'badge-sedang',
        'berat'   => 'badge-berat',
        default   => 'badge-extreme',
    };
}

function statusClass(string $s): string
{
    return $s === 'Buka' ? 'status-buka' : 'status-tutup';
}

function formatRupiah(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – <?= htmlspecialchars($g['nama']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=1000">
    <link rel="stylesheet" href="assets/css/detail_gunung.css">
    <style>
        .logistik-badge-row {
            display: inline-block;
            font-size: 0.65rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 100px;
            text-transform: uppercase;
            margin-left: auto;
        }

        .badge-row-wajib {
            background: rgba(26, 92, 56, 0.12);
            color: #1a5c38;
        }

        .badge-row-saran {
            background: rgba(245, 200, 66, 0.15);
            color: #b89000;
        }

        .barang-item {
            display: flex;
            align-items: center;
            width: 100%;
        }

        /* Styling box notifikasi Info Terkini dari Database */
        .info-box.info-terkini {
            background-color: rgba(217, 119, 6, 0.08);
            border: 1px solid rgba(217, 119, 6, 0.2);
            border-left: 4px solid #d97706;
            margin-bottom: 16px;
        }

        .info-box.info-terkini .info-box-title {
            color: #d97706;
            font-weight: 700;
        }
    </style>
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <section class="hero">
        <div class="hero-bg" style="background-image:url('<?= htmlspecialchars($g['image_hero']) ?>')"></div>
        <div class="hero-overlay"></div>

        <div class="hero-content">
            <div class="hero-badges">
                <span class="hbadge <?= badgeClass($g['kesulitan']) ?>"><?= htmlspecialchars($g['kesulitan']) ?></span>
                <span class="hbadge hbadge-provinsi"><?= htmlspecialchars($g['provinsi']) ?></span>
                <span class="hbadge <?= statusClass($g['status']) ?>">
                    <span class="status-dot"></span>
                    Status: <?= htmlspecialchars($g['status']) ?>
                </span>
            </div>

            <h1 class="hero-title"><?= htmlspecialchars($g['nama']) ?></h1>
            <p class="hero-lokasi">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                    <circle cx="12" cy="10" r="3" />
                </svg>
                <?= htmlspecialchars($g['lokasi']) ?>, <?= htmlspecialchars($g['provinsi']) ?>
            </p>
            <p class="hero-tagline"><?= htmlspecialchars($g['tagline']) ?></p>
        </div>
    </section>

    <div class="stat-bar">
        <div class="stat-container">
            <div class="stat-item">
                <span class="stat-label">KETINGGIAN</span>
                <div class="stat-val">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="18 15 12 9 6 15" />
                    </svg>
                    <strong><?= htmlspecialchars($g['ketinggian']) ?> MDPL</strong>
                </div>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-item">
                <span class="stat-label">JALUR UTAMA</span>
                <div class="stat-val">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 17 Q8 10 12 12 Q16 14 21 7" />
                    </svg>
                    <strong><?= htmlspecialchars($g['jalur']) ?></strong>
                </div>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-item">
                <span class="stat-label">RATING</span>
                <div class="stat-val">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="#f5c842" stroke="#f5c842" stroke-width="1">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                    </svg>
                    <strong><?= htmlspecialchars($g['rating']) ?> / 5.0</strong>
                </div>
            </div>
            <div class="stat-sep"></div>
            <div class="stat-item">
                <span class="stat-label">ESTIMASI TREK</span>
                <div class="stat-val">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" />
                        <polyline points="12 6 12 12 16 14" />
                    </svg>
                    <strong><?= htmlspecialchars($g['estimasi']) ?></strong>
                </div>
            </div>

            <?php if (!empty($g['peta_gunung']) && $g['peta_gunung'] != 'default.pdf'): ?>
                <div class="stat-dl">
                    <a href="#" class="btn-download" onclick="handleDownload(event)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="7 10 12 15 17 10" />
                            <line x1="12" y1="15" x2="12" y2="3" />
                        </svg>
                        Download Peta Offline (PDF)
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="body-wrap">
        <div class="body-container">

            <div class="col-left">
                <section class="content-section" style="animation-delay:0.05s">
                    <h2 class="section-heading">Deskripsi Destinasi</h2>
                    <?php foreach ($g['deskripsi'] as $par): ?>
                        <p class="desc-text" style="line-height: 1.6; margin-bottom: 14px; text-align: justify;"><?= htmlspecialchars($par) ?></p>
                    <?php endforeach; ?>

                    <div class="info-boxes">
                        <?php if (!empty($g['info_terkini'])): ?>
                            <div class="info-box info-terkini" style="grid-column: span 2; width: 100%;">
                                <div class="info-box-title">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                                        <line x1="12" y1="9" x2="12" y2="13" />
                                        <line x1="12" y1="17" x2="12.01" y2="17" />
                                    </svg>
                                    Laporan Situasi & Info Terkini Basecamp
                                </div>
                                <p style="font-weight: 600; color: var(--text-dark);"><?= htmlspecialchars($g['info_terkini']) ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="info-box info-ai">
                            <div class="info-box-title">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10" />
                                    <line x1="12" y1="8" x2="12" y2="12" />
                                    <line x1="12" y1="16" x2="12.01" y2="16" />
                                </svg>
                                Rekomendasi Keamanan Jalur
                            </div>
                            <p><?= htmlspecialchars($g['info_ai']) ?></p>
                        </div>
                        <div class="info-box info-fasilitas">
                            <div class="info-box-title">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                    <polyline points="9 22 9 12 15 12 15 22" />
                                </svg>
                                Fasilitas Pos Basecamp
                            </div>
                            <ul class="fasilitas-list">
                                <?php foreach ($g['fasilitas'] as $f): ?>
                                    <li>
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                            <polyline points="20 6 9 17 4 12" />
                                        </svg>
                                        <?= htmlspecialchars($f) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </section>

                <section class="content-section" style="animation-delay:0.10s">
                    <div class="section-heading-row">
                        <h2 class="section-heading">Ulasan Cerita Pendaki</h2>
                        <a href="review.php?id_gunung=<?= $id ?>" class="link-all">
                            Lihat Semua
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <line x1="5" y1="12" x2="19" y2="12" />
                                <polyline points="12 5 19 12 12 19" />
                            </svg>
                        </a>
                    </div>

                    <div class="ulasan-list">
                        <?php foreach ($g['ulasan'] as $u): ?>
                            <div class="ulasan-card">
                                <div class="ulasan-header">
                                    <div class="ulasan-avatar" style="background:<?= htmlspecialchars($u['color']) ?>">
                                        <?= htmlspecialchars($u['avatar']) ?>
                                    </div>
                                    <div class="ulasan-meta">
                                        <span class="ulasan-nama"><?= htmlspecialchars($u['nama']) ?></span>
                                        <span class="ulasan-tanggal"><?= htmlspecialchars($u['tanggal']) ?></span>
                                    </div>
                                    <div class="ulasan-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <svg width="13" height="13" viewBox="0 0 24 24"
                                                fill="<?= $i <= $u['bintang'] ? '#f5c842' : '#dce8e1' ?>"
                                                stroke="<?= $i <= $u['bintang'] ? '#f5c842' : '#dce8e1' ?>"
                                                stroke-width="1">
                                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
                                            </svg>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <?php
                                // Menghapus tag [NY-XXXXXX] di awal kalimat menggunakan Regex sebelum ditampilkan
                                $teks_bersih = preg_replace('/\[NY-[A-Z0-9]+\]\s*/', '', $u['teks']);
                                ?>
                                <p class="ulasan-teks"><?= htmlspecialchars($teks_bersih) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            </div>

            <div class="col-right">
                <div class="sidebar-card barang-card" style="animation-delay:0.08s">
                    <div class="sidebar-card-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                            <line x1="3" y1="6" x2="21" y2="6" />
                            <path d="M16 10a4 4 0 0 1-8 0" />
                        </svg>
                        <?= count($g['logistik_db']) ?> Logistik Manajemen Kelompok
                    </div>

                    <ul class="barang-list">
                        <?php foreach ($g['logistik_db'] as $item): ?>
                            <li class="barang-item">
                                <span class="barang-check">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                        <polyline points="20 6 9 17 4 12" />
                                    </svg>
                                </span>
                                <span class="barang-name-txt"><?= htmlspecialchars($item['nama']) ?></span>

                                <span class="logistik-badge-row <?= $item['status'] === 'Wajib' ? 'badge-row-wajib' : 'badge-row-saran' ?>">
                                    <?= htmlspecialchars($item['status']) ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="sidebar-card booking-card" style="animation-delay:0.12s">
                    <div class="harga-label">RETRIBUSI SIMAKSI</div>
                    <div class="harga-row">
                        <span class="harga-angka"><?= formatRupiah($g['harga']) ?></span>
                        <span class="harga-per">/ orang</span>
                        <?php if ($g['tersedia']): ?>
                            <span class="avail-badge">Tersedia</span>
                        <?php else: ?>
                            <span class="avail-badge avail-full" style="background-color: var(--bg-ditolak); color: var(--color-ditolak);">Penuh</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($g['tersedia']): ?>
                        <a href="pemesanan.php?id=<?= $id ?>" class="btn-booking">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="4" width="22" height="16" rx="2" />
                                <line x1="1" y1="10" x2="23" y2="10" />
                            </svg>
                            Beli Tiket Sekarang
                        </a>
                    <?php else: ?>
                        <button type="button" class="btn-booking" style="background-color: #cbd5e1; color: #94a3b8; cursor: not-allowed;" disabled>
                            Registrasi Online Ditutup
                        </button>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <div class="toast" id="toast"></div>

    <script>
        const navbar = document.getElementById('navbar');
        if (navbar) {
            window.addEventListener('scroll', () => {
                navbar.classList.toggle('scrolled', window.scrollY > 30);
            });
        }

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast show ' + type;
            setTimeout(() => t.className = 'toast', 3000);
        }

        function handleDownload(e) {
            e.preventDefault();
            showToast('⬇ Mengunduh peta offline…', 'info');

            const file_map = '<?= !empty($g['peta_gunung']) && $g['peta_gunung'] != 'default.pdf' ? 'assets/peta%20gunung/' . rawurlencode($g['peta_gunung']) : '' ?>';
            const file_name = '<?= !empty($g['peta_gunung']) ? htmlspecialchars($g['peta_gunung']) : '' ?>';

            if (file_map !== '') {
                setTimeout(() => {
                    const temp_link = document.createElement('a');
                    temp_link.href = file_map;
                    temp_link.download = file_name;
                    document.body.appendChild(temp_link);
                    temp_link.click();
                    document.body.removeChild(temp_link);
                }, 600);
            } else {
                showToast('❌ Berkas peta belum tersedia untuk gunung ini.', 'warn');
            }
        }

        document.querySelectorAll('.barang-item').forEach(item => {
            item.addEventListener('click', function() {
                this.classList.toggle('checked');
            });
        });
    </script>
</body>

</html>