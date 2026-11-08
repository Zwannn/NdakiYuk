<?php
// pemesanan.php – Halaman Pemesanan Tiket NdakiYuk Terintegrasi Database
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

// ── Ambil ID Gunung dari URL (default: 1 jika parameter kosong) ─────────
$id = (int)($_GET['id'] ?? 1);

// ── Ambil Data Gunung dari Database ─────────────────────────────────────
$queryGunung = mysqli_query($conn, "SELECT * FROM gunung WHERE id_gunung = $id");
if (mysqli_num_rows($queryGunung) === 0) {
    header('Location: gunung.php');
    exit;
}
$rowGunung = mysqli_fetch_assoc($queryGunung);

// Pecah string pos perizinan dari database menjadi bentuk array
$ambil_pos = !empty($rowGunung['pos_perizinan']) ? explode(',', $rowGunung['pos_perizinan']) : ['Jalur Utama'];
$array_pos = array_map('trim', $ambil_pos);

$g = [
    'nama'           => $rowGunung['nama_gunung'],
    'tinggi'         => number_format($rowGunung['tinggi'], 0, ',', '.'),
    'image'          => 'assets/img/gunung/' . $rowGunung['gambar'],
    'hero'           => 'assets/img/gunung/' . $rowGunung['gambar'],
    'tagline'        => $rowGunung['deskripsi'],
    'pos_main'       => $array_pos,
    'pos_masuk'      => $array_pos,
    'pos_keluar'     => $array_pos,
    'harga_simaksi'  => $rowGunung['harga_tiket'],
    'harga_asuransi' => 0,
    'harga_layanan'  => 0,
];

function formatRp(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}

// ── Ambil Data Ketua (User yang sedang login) dari Database ─────────────
$queryKetua = mysqli_query($conn, "SELECT nama_lengkap, nik, no_hp FROM user_profile WHERE id_user = '$id_user'");
$dataKetua = mysqli_fetch_assoc($queryKetua);

// ── Handle Action POST: Simpan ke Tabel Pemesanan & Detail Pendaki ──────
$success = false;
$errors  = [];
$kode_booking_terbuat = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pos_masuk     = mysqli_real_escape_string($conn, trim($_POST['pos_masuk'] ?? ''));
    $pos_keluar    = mysqli_real_escape_string($conn, trim($_POST['pos_keluar'] ?? ''));
    $tgl_berangkat = trim($_POST['tgl_berangkat'] ?? '');
    $tgl_pulang    = trim($_POST['tgl_pulang'] ?? '');
    $jml_pendaki   = (int)($_POST['jml_pendaki'] ?? 1);
    $pendaki_data  = $_POST['pendaki'] ?? [];

    // Validasi Form input utama
    if (!$pos_masuk)     $errors[] = 'Pilih pos perizinan masuk.';
    if (!$pos_keluar)    $errors[] = 'Pilih pos perizinan keluar.';
    if (!$tgl_berangkat) $errors[] = 'Isi tanggal & waktu berangkat rencana perjalanan.';
    if (!$tgl_pulang)    $errors[] = 'Isi tanggal & waktu rencana kepulangan.';
    if ($tgl_berangkat && $tgl_pulang) {
        $date_masuk  = new DateTime($tgl_berangkat);
        $date_pulang = new DateTime($tgl_pulang);

        if ($date_pulang < $date_masuk) {
            $errors[] = 'Tanggal pulang tidak boleh kurang atau sebelum tanggal berangkat.';
        }
    }
    if ($jml_pendaki < 1 || $jml_pendaki > 10) $errors[] = 'Jumlah rombongan harus di antara 1–10 orang.';

    // ── VALIDASI DATA PENDIDIKAN BARU: UMUR DAN GENDER ──
    for ($i = 0; $i < $jml_pendaki; $i++) {
        $nomor_urut = $i + 1;
        if (!isset($pendaki_data[$i]) || empty(trim($pendaki_data[$i]['nama'])) || empty(trim($pendaki_data[$i]['nik']))) {
            $errors[] = "Data identitas Rombongan ke-" . $nomor_urut . " belum lengkap (Nama & NIK wajib diisi).";
        }
        if (empty($pendaki_data[$i]['umur']) || (int)$pendaki_data[$i]['umur'] <= 0) {
            $errors[] = "Usia pendaki ke-" . $nomor_urut . " harus diisi berupa angka valid.";
        }
        if (empty($pendaki_data[$i]['gender']) || !in_array($pendaki_data[$i]['gender'], ['Laki-laki', 'Perempuan'])) {
            $errors[] = "Jenis Kelamin pendaki ke-" . $nomor_urut . " belum dipilih.";
        }
    }

    if (empty($errors)) {
        // Generate Kode Booking Acak Unik
        $kode_booking = "NY-" . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));

        // Kalkulasi Total Harga Pembayaran Keseluruhan
        $total_harga = ($g['harga_simaksi'] * $jml_pendaki) + ($g['harga_asuransi'] * $jml_pendaki) + $g['harga_layanan'];

        // 1. Insert Data ke Tabel `pemesanan`
        $insertPemesanan = mysqli_query($conn, "
            INSERT INTO pemesanan (id_user, id_gunung, pos_masuk, pos_keluar, kode_booking, tanggal_naik, tanggal_turun, jumlah_pendaki, total_harga, status_pemesanan) 
            VALUES ('$id_user', '$id', '$pos_masuk', '$pos_keluar', '$kode_booking', '$tgl_berangkat', '$tgl_pulang', '$jml_pendaki', '$total_harga', 'Menunggu Pembayaran')
        ");

        if ($insertPemesanan) {
            $id_pemesanan_baru = mysqli_insert_id($conn);

            // 2. Looping Insert Anggota/Ketua ke Tabel `detail_pendaki`
            for ($i = 0; $i < $jml_pendaki; $i++) {
                if (isset($pendaki_data[$i])) {
                    $nama_p   = mysqli_real_escape_string($conn, trim($pendaki_data[$i]['nama']));
                    $nik_p    = mysqli_real_escape_string($conn, trim($pendaki_data[$i]['nik']));
                    $hp_p     = mysqli_real_escape_string($conn, trim($pendaki_data[$i]['phone']));
                    $umur_p   = (int)$pendaki_data[$i]['umur'];
                    $gender_p = mysqli_real_escape_string($conn, $pendaki_data[$i]['gender']);

                    // SINKRONISASI COUPLING INPUT DATA MENUJU DATABASE TERBARU
                    mysqli_query($conn, "
                        INSERT INTO detail_pendaki (id_pemesanan, nama_pendaki, nik, no_hp, umur, jenis_kelamin) 
                        VALUES ('$id_pemesanan_baru', '$nama_p', '$nik_p', '$hp_p', '$umur_p', '$gender_p')
                    ");
                }
            }

            header("Location: konfirmasi.php?kode=" . $kode_booking);
            exit;
        } else {
            $errors[] = 'Gagal memproses transaksi ke database. Silakan coba kembali.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Pesan Tiket <?= htmlspecialchars($g['nama']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pemesanan.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <section class="hero">
        <div class="hero-bg" style="background-image:url('<?= htmlspecialchars($g['hero']) ?>')"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <div class="hero-breadcrumb">
                <a href="gunung.php">Gunung</a>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
                <a href="detail_gunung.php?id=<?= $id ?>"><?= htmlspecialchars($g['nama']) ?></a>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <polyline points="9 18 15 12 9 6" />
                </svg>
                <span>Pemesanan</span>
            </div>
            <h1 class="hero-title">Pendakian <?= htmlspecialchars($g['nama']) ?></h1>
            <p class="hero-tagline"><?= htmlspecialchars($g['tagline']) ?></p>
        </div>
    </section>

    <?php if (!empty($errors)): ?>
        <div class="alert-error" id="alertError" style="margin: 20px auto; max-width: 1200px; padding: 14px 20px; background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 8px; display: flex; align-items: center; gap: 10px;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10" />
                <line x1="12" y1="8" x2="12" y2="12" />
                <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <div style="flex: 1; font-size: 13.5px;"><?= implode(' &nbsp;·&nbsp; ', array_map('htmlspecialchars', $errors)) ?></div>
            <button onclick="this.parentElement.remove()" class="alert-close" style="background:none; border:none; color:inherit; font-size:18px; cursor:pointer;">×</button>
        </div>
    <?php endif; ?>

    <main class="page-main">
        <div class="page-container">

            <div class="col-form">
                <div class="form-card" style="animation-delay:0.05s">
                    <div class="form-card-title">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="3 11 22 2 13 21 11 13 3 11" />
                        </svg>
                        Informasi Rencana Perjalanan
                    </div>

                    <form method="POST" action="pemesanan.php?id=<?= $id ?>" id="mainForm" novalidate>

                        <div class="field-row">
                            <div class="field-group">
                                <label class="field-label">Pos Perizinan Masuk</label>
                                <div class="select-wrap">
                                    <select name="pos_masuk" class="form-select" required>
                                        <?php foreach ($g['pos_masuk'] as $p): ?>
                                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <svg class="select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Pos Perizinan Keluar</label>
                                <div class="select-wrap">
                                    <select name="pos_keluar" class="form-select" required>
                                        <?php foreach ($g['pos_keluar'] as $p): ?>
                                            <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <svg class="select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </div>
                            </div>
                        </div>

                        <div class="field-row">
                            <div class="field-group">
                                <label class="field-label">Tanggal Berangkat</label>
                                <div class="input-wrap">
                                    <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                    <input type="date" name="tgl_berangkat" class="form-input has-icon" value="<?= htmlspecialchars($_POST['tgl_berangkat'] ?? '') ?>" required>
                                </div>
                            </div>

                            <div class="field-group">
                                <label class="field-label">Tanggal Pulang</label>
                                <div class="input-wrap">
                                    <svg class="input-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="4" width="18" height="18" rx="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                    <input type="date" name="tgl_pulang" class="form-input has-icon" value="<?= htmlspecialchars($_POST['tgl_pulang'] ?? '') ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="field-group" style="max-width:220px">
                            <label class="field-label">Jumlah Pendaki</label>
                            <div class="counter-wrap">
                                <button type="button" class="counter-btn" id="btnMinus" onclick="changeCount(-1)">−</button>
                                <input type="number" name="jml_pendaki" id="jmlPendaki" class="counter-input" value="<?= (int)($_POST['jml_pendaki'] ?? 1) ?>" min="1" max="10" readonly>
                                <button type="button" class="counter-btn" id="btnPlus" onclick="changeCount(1)">+</button>
                                <span class="counter-hint">Maksimal 10 orang per kelompok</span>
                            </div>
                        </div>

                        <div class="section-sep"></div>

                        <div class="form-sub-title">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            Data Identitas Pendaki
                            <span class="pendaki-counter-badge" id="pendakiBadge">1/10 Terdaftar</span>
                        </div>

                        <div class="pendaki-list" id="pendakiList">
                            <div class="pendaki-block" data-idx="0">
                                <div class="pendaki-block-header">
                                    <span class="pendaki-role-badge">KETUA KELOMPOK</span>
                                </div>
                                <div class="field-row">
                                    <div class="field-group">
                                        <label class="field-label">NAMA LENGKAP</label>
                                        <input type="text" name="pendaki[0][nama]" class="form-input" value="<?= htmlspecialchars($dataKetua['nama_lengkap'] ?? '') ?>" placeholder="Sesuai KTP" required>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">NOMOR ID / NIK</label>
                                        <input type="text" name="pendaki[0][nik]" class="form-input" value="<?= htmlspecialchars($dataKetua['nik'] ?? '') ?>" placeholder="16 Digit Angka" maxlength="16" required>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">NOMOR TELEPON</label>
                                        <input type="tel" name="pendaki[0][phone]" class="form-input" value="<?= htmlspecialchars($dataKetua['no_hp'] ?? '') ?>" placeholder="08xxxxxxxxx">
                                    </div>
                                </div>
                                <div class="field-row" style="margin-top: 12px;">
                                    <div class="field-group">
                                        <label class="field-label">USIA / UMUR (TAHUN)</label>
                                        <input type="number" name="pendaki[0][umur]" class="form-input" placeholder="Contoh: 23" min="1" max="90" required>
                                    </div>
                                    <div class="field-group">
                                        <label class="field-label">JENIS KELAMIN</label>
                                        <div class="select-wrap">
                                            <select name="pendaki[0][gender]" class="form-select" required>
                                                <option value="" disabled selected>-- Pilih Jenis Kelamin --</option>
                                                <option value="Laki-laki">Laki-laki</option>
                                                <option value="Perempuan">Perempuan</option>
                                            </select>
                                            <svg class="select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <polyline points="6 9 12 15 18 9" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn-tambah" onclick="tambahAnggota()">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="16" />
                                <line x1="8" y1="12" x2="16" y2="12" />
                            </svg>
                            Tambah Anggota Kelompok
                        </button>

                    </form>
                </div>

                <div class="banner-grid" style="animation-delay:0.12s">
                    <div class="banner-card banner-stamina">
                        <div class="banner-bg" style="background-image:url('https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?auto=format&fit=crop&w=800&q=80')"></div>
                        <div class="banner-overlay"></div>
                        <div class="banner-content">
                            <h3 class="banner-title">Persiapkan Stamina Anda</h3>
                            <p class="banner-desc">Medan berpasir dan cuaca ekstrem membutuhkan persiapan physical yang matang.</p>
                        </div>
                    </div>
                    <div class="banner-card banner-guide">
                        <div class="banner-bg banner-guide-bg"></div>
                        <div class="banner-overlay banner-overlay-dark"></div>
                        <div class="banner-content">
                            <div class="guide-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <polygon points="3 11 22 2 13 21 11 13 3 11" />
                                </svg>
                            </div>
                            <h3 class="banner-title">Guide Lokal Profesional</h3>
                            <p class="banner-desc">Dapatkan pengalaman terbaik Anda dengan panduan bersertifikat Ndakiyuk.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-summary">
                <div class="summary-card" style="animation-delay:0.08s">
                    <div class="summary-title">Rincian Pembayaran</div>

                    <div class="summary-mountain">
                        <div class="summary-mountain-info">
                            <div class="summary-mountain-label">TUJUAN</div>
                            <div class="summary-mountain-name"><?= htmlspecialchars($g['nama']) ?></div>
                        </div>
                        <img src="<?= htmlspecialchars($g['image']) ?>" alt="<?= htmlspecialchars($g['nama']) ?>" class="summary-mountain-img">
                    </div>

                    <div class="summary-sep"></div>

                    <div class="summary-info-rows">
                        <div class="summary-info-row">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                <line x1="16" y1="2" x2="16" y2="6" />
                                <line x1="8" y1="2" x2="8" y2="6" />
                                <line x1="3" y1="10" x2="21" y2="10" />
                            </svg>
                            <span id="summaryTanggal">Belum ditentukan</span>
                        </div>
                        <div class="summary-info-row">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            <span id="summaryPendaki">1 Orang (Domestik)</span>
                        </div>
                    </div>

                    <div class="summary-sep"></div>

                    <div class="summary-biaya">
                        <div class="biaya-row">
                            <span class="biaya-label">Simaksi (Tiket Masuk)</span>
                            <span class="biaya-val" id="biayaSimaksi"><?= formatRp($g['harga_simaksi']) ?></span>
                        </div>
                        <div class="biaya-row">
                            <span class="biaya-label">Asuransi Jiwa & Evakuasi</span>
                            <span class="biaya-val" id="biayaAsuransi"><?= formatRp($g['harga_asuransi']) ?></span>
                        </div>
                        <div class="biaya-row">
                            <span class="biaya-label">Biaya Layanan Ndakiyuk</span>
                            <span class="biaya-val"><?= formatRp($g['harga_layanan']) ?></span>
                        </div>
                    </div>

                    <div class="summary-sep"></div>

                    <div class="summary-total-row">
                        <span class="total-label">Total Bayar</span>
                        <span class="total-angka" id="totalBayar"><?= formatRp($g['harga_simaksi'] + $g['harga_asuransi'] + $g['harga_layanan']) ?></span>
                    </div>

                    <button type="submit" name="proses_booking" form="mainForm" class="btn-bayar">
                        Lanjut ke Pembayaran
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>

                    <div class="summary-sep"></div>

                    <div class="syarat-box">
                        <div class="syarat-title">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                            </svg>
                            Syarat Dokumen
                        </div>
                        <p class="syarat-text">
                            Wajib membawa <strong>Surat Keterangan Sehat</strong>, asli dari Dokter yang diterbitkan maksimal H-3 keberangkatan.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        });

        const hargaSimaksi = <?= $g['harga_simaksi'] ?>;
        const hargaAsuransi = <?= $g['harga_asuransi'] ?>;
        const hargaLayanan = <?= $g['harga_layanan'] ?>;

        function formatRp(n) {
            return 'Rp ' + n.toLocaleString('id-ID');
        }

        function changeCount(delta) {
            const inp = document.getElementById('jmlPendaki');
            let val = parseInt(inp.value) + delta;
            val = Math.max(1, Math.min(10, val));
            inp.value = val;
            updateSummary();
            updatePendakiBadge();
        }

        function updateSummary() {
            const n = parseInt(document.getElementById('jmlPendaki').value) || 1;
            const s = hargaSimaksi * n;
            const a = hargaAsuransi * n;
            const total = s + a + hargaLayanan;

            document.getElementById('biayaSimaksi').textContent = formatRp(s);
            document.getElementById('biayaAsuransi').textContent = formatRp(a);
            document.getElementById('totalBayar').textContent = formatRp(total);
            document.getElementById('summaryPendaki').textContent = n + ' Orang (Domestik)';
        }

        const inputBerangkat = document.querySelector('[name="tgl_berangkat"]');
        const inputPulang = document.querySelector('[name="tgl_pulang"]');

        const hariIni = new Date().toISOString().split('T')[0];
        inputBerangkat.setAttribute('min', hariIni);

        inputBerangkat.addEventListener('change', function() {
            const tanggalDipilih = this.value;

            if (tanggalDipilih) {
                inputPulang.setAttribute('min', tanggalDipilih);

                if (inputPulang.value && inputPulang.value < tanggalDipilih) {
                    inputPulang.value = tanggalDipilih;
                }

                const d = new Date(tanggalDipilih);
                document.getElementById('summaryTanggal').textContent =
                    d.toLocaleDateString('id-ID', {
                        day: 'numeric',
                        month: 'long',
                        year: 'numeric'
                    });
            } else {
                document.getElementById('summaryTanggal').textContent = 'Belum ditentukan';
                inputPulang.removeAttribute('min');
            }
        });

        let anggotaCount = 1;

        function updatePendakiBadge() {
            const jml = parseInt(document.getElementById('jmlPendaki').value);
            document.getElementById('pendakiBadge').textContent =
                anggotaCount + '/' + jml + ' Terdaftar';
        }

        // ── PERBAIKAN FUNGSIONAL JAVASCRIPT: Render Form Dinamik Anggota Baris Baru ──
        function tambahAnggota() {
            const maxJml = parseInt(document.getElementById('jmlPendaki').value);
            if (anggotaCount >= maxJml) {
                alert('Sudah mencapai jumlah pendaki maksimal sesuai dengan counter di atas.');
                return;
            }
            anggotaCount++;
            const idx = anggotaCount - 1;
            const list = document.getElementById('pendakiList');
            const block = document.createElement('div');
            block.className = 'pendaki-block';
            block.dataset.idx = idx;
            block.innerHTML = `
        <div class="pendaki-block-header">
            <span class="pendaki-role-badge anggota">ANGGOTA ${idx}</span>
            <button type="button" class="btn-remove-anggota" onclick="hapusAnggota(this)" style="background:none; border:none; color:#ef4444; font-size:12px; font-weight:600; cursor:pointer;">
                Hapus
            </button>
        </div>
        <div class="field-row">
            <div class="field-group">
                <label class="field-label">NAMA LENGKAP</label>
                <input type="text" name="pendaki[${idx}][nama]" class="form-input" placeholder="Sesuai KTP" required>
            </div>
            <div class="field-group">
                <label class="field-label">NOMOR ID / NIK</label>
                <input type="text" name="pendaki[${idx}][nik]" class="form-input" placeholder="16 Digit Angka" maxlength="16" required>
            </div>
            <div class="field-group">
                <label class="field-label">NOMOR TELEPON</label>
                <input type="tel" name="pendaki[${idx}][phone]" class="form-input" placeholder="08xxxxxxxxx">
            </div>
        </div>
        <div class="field-row" style="margin-top: 12px;">
            <div class="field-group">
                <label class="field-label">USIA / UMUR (TAHUN)</label>
                <input type="number" name="pendaki[${idx}][umur]" class="form-input" placeholder="Contoh: 21" min="1" max="90" required>
            </div>
            <div class="field-group">
                <label class="field-label">JENIS KELAMIN</label>
                <div class="select-wrap">
                    <select name="pendaki[${idx}][gender]" class="form-select" required>
                        <option value="" disabled selected>-- Pilih Jenis Kelamin --</option>
                        <option value="Laki-laki">Laki-laki</option>
                        <option value="Perempuan">Perempuan</option>
                    </select>
                    <svg class="select-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
                </div>
            </div>
        </div>`;
            list.appendChild(block);
            updatePendakiBadge();
        }

        function hapusAnggota(btn) {
            const block = btn.closest('.pendaki-block');
            block.remove();
            anggotaCount--;
            updatePendakiBadge();
        }

        document.getElementById('mainForm').addEventListener('submit', function(e) {
            const jmlPilihan = parseInt(document.getElementById('jmlPendaki').value) || 1;

            if (anggotaCount !== jmlPilihan) {
                e.preventDefault();
                alert('⚠️ Jumlah formulir identitas pendaki (' + anggotaCount + ') belum sesuai dengan target Jumlah Pendaki (' + jmlPilihan + ') yang Anda pilih di atas. Silakan klik tombol "Tambah Anggota Kelompok" dan lengkapi datanya terlebih dahulu.');
                return false;
            }
        });
    </script>
</body>

</html>