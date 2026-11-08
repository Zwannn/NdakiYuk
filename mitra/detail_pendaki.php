<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_mitra = $_SESSION['id_user'];
$id_pemesanan = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_pemesanan <= 0) {
    die("ID pemesanan tidak valid.");
}

// Ambil detail pemesanan & pastikan dikelola oleh mitra login
$query = mysqli_query($conn, "
    SELECT 
        p.*,
        g.nama_gunung, g.lokasi, g.tinggi, g.estimasi_pendakian, g.harga_tiket,
        u.username, u.email,
        up.nama_lengkap, up.no_hp, up.foto_profile,
        pb.metode_pembayaran, pb.nama_pengirim, pb.bukti_pembayaran, pb.status_verifikasi, pb.tanggal_bayar
    FROM pemesanan p
    JOIN gunung_mitra gm ON p.id_gunung = gm.id_gunung
    JOIN gunung g ON p.id_gunung = g.id_gunung
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
    LEFT JOIN pembayaran pb ON p.id_pemesanan = pb.id_pemesanan
    WHERE p.id_pemesanan = '$id_pemesanan'
    AND gm.id_user = '$id_mitra'
    ORDER BY pb.id_pembayaran DESC
    LIMIT 1
");

if (!$query) {
    die("Query detail pemesanan error: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data pemesanan tidak ditemukan atau bukan untuk gunung yang Anda kelola.");
}

// Ambil anggota rombongan dari detail_pendaki
$query_anggota = mysqli_query($conn, "
    SELECT id_detail, nama_pendaki, nik, no_hp, umur, jenis_kelamin
    FROM detail_pendaki
    WHERE id_pemesanan = '$id_pemesanan'
    ORDER BY id_detail ASC
");

$total_anggota = mysqli_num_rows($query_anggota);

$status_pemesanan = $data['status_pemesanan'];
$status_checkin = $data['status_checkin'] ?? 'Belum Check-in';
$status_checkout = $data['status_checkout'] ?? 'Belum Check-out';

$classStatus = 'status-pending';
if ($status_pemesanan == 'Diterima') {
    $classStatus = 'status-diterima';
} elseif ($status_pemesanan == 'Ditolak') {
    $classStatus = 'status-ditolak';
} elseif ($status_pemesanan == 'Selesai') {
    $classStatus = 'status-selesai';
}

$checkinClass = $status_checkin == 'Sudah Check-in' ? 'status-diterima' : 'status-pending';
$checkoutClass = $status_checkout == 'Sudah Check-out' ? 'status-selesai' : 'status-pending';

$status_bayar = $data['status_verifikasi'] ?? 'Belum Bayar';
$paymentClass = 'status-pending';
if ($status_bayar == 'Valid' || $status_bayar == 'Terverifikasi') {
    $paymentClass = 'status-diterima';
} elseif ($status_bayar == 'Tidak Valid') {
    $paymentClass = 'status-ditolak';
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pendaki Pendaki #<?= htmlspecialchars($data['kode_booking']); ?> - NdakiYuk Mitra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mitra.css">
    <style>
        /* CSS Modal Pop-up Emergency */
        .emergency-modal {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(9, 38, 22, 0.7);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .em-modal-content {
            background-color: #fff;
            padding: 32px;
            border-radius: 16px;
            max-width: 480px;
            width: 100%;
            border: 2px solid #dc3545;
            box-shadow: var(--shadow-md);
            text-align: center;
        }

        .btn-reveal-nik {
            background: none;
            border: none;
            color: #1a5c38;
            font-family: monospace;
            font-weight: 700;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: underline;
        }

        .nik-password-wrap {
            position: relative;
            width: 230px;
            max-width: 100%;
        }

        .nik-password-input {
            width: 100%;
            height: 42px;
            padding: 0 44px 0 14px;
            border: 1.5px solid #dce8e1;
            border-radius: 10px;
            background: #eef4f0;
            color: #0f2419;
            font-family: monospace;
            font-size: 0.9rem;
            font-weight: 700;
            outline: none;
        }

        .nik-password-input:focus {
            border-color: #1a5c38;
            box-shadow: 0 0 0 3px rgba(26, 92, 56, 0.12);
        }

        .btn-eye-nik {
            position: absolute;
            top: 50%;
            right: 12px;
            transform: translateY(-50%);
            width: 26px;
            height: 26px;
            border: none;
            background: transparent;
            color: #7a9487;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .btn-eye-nik:hover {
            color: #1a5c38;
        }

        .btn-eye-nik svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }
    </style>
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
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="header-banner">
                <div class="header-info-text">
                    <small class="header-pretitle">BERKAS IDENTITAS MANIFES PENDIDIKAN</small>
                    <h1 class="header-welcome">Kode Booking: #<?= htmlspecialchars($data['kode_booking']); ?></h1>
                    <p class="header-sub">Data log rombongan pendakian resmi pos registrasi <strong><?= htmlspecialchars($data['nama_gunung']); ?></strong>.</p>
                </div>
                <div style="display: flex; flex-direction: column; gap: 8px; align-items: flex-end;">
                    <span class="badge-status <?= $classStatus; ?>" style="font-size: 0.85rem; padding: 8px 18px;">
                        Izin: <?= htmlspecialchars($status_pemesanan); ?>
                    </span>
                    <a href="download_emergency_pdf.php?id=<?= $data['id_pemesanan']; ?>" class="btn-danger" style="font-size: 0.78rem; padding: 8px 14px; background-color: #1a5c38; color:#fff;" onclick="return confirm('Peringatan: Dokumen PDF ini berisi data privasi murni rombongan. Hanya unduh untuk keadaan darurat / SAR!');">
                        Unduh PDF Dokumen Darurat
                    </a>
                </div>
            </div>

            <div class="detail-grid">
                <div class="content-section">
                    <div class="section-header">
                        <div class="section-title-block">
                            <h3 class="section-title-text">Ketua Rombongan</h3>
                            <p class="section-title-sub">Akun penanggung jawab kontak utama kelompok.</p>
                        </div>
                        <span class="section-badge-info" style="background-color: var(--yellow-light); color: #b7791f;">Kontak Inti</span>
                    </div>
                    <div class="detail-row"><span>Nama Lengkap</span><strong><?= htmlspecialchars($data['nama_lengkap'] ?? $data['username']); ?></strong></div>
                    <div class="detail-row"><span>Username Akun</span><strong style="color: var(--text-muted);">@<?= htmlspecialchars($data['username']); ?></strong></div>
                    <div class="detail-row"><span>Alamat Email</span><strong><?= htmlspecialchars($data['email']); ?></strong></div>
                    <div class="detail-row"><span>No. Handphone</span><strong><?= htmlspecialchars($data['no_hp'] ?? '-'); ?></strong></div>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <div class="section-title-block">
                            <h3 class="section-title-text">Log Alur Pos Logistik</h3>
                            <p class="section-title-sub">Status validasi checkpoint administrasi pendaki.</p>
                        </div>
                    </div>
                    <div class="detail-row"><span>Verifikasi Administrasi</span><span class="badge-status <?= $paymentClass; ?>"><?= htmlspecialchars($status_bayar); ?></span></div>
                    <div class="detail-row"><span>Checkpoint Check-in</span><span class="badge-status <?= $checkinClass; ?>"><?= htmlspecialchars($status_checkin); ?></span></div>
                    <div class="detail-row"><span>Waktu Gerbang Naik</span><strong class="date-text"><?= !empty($data['waktu_checkin']) ? date('d M Y - H:i', strtotime($data['waktu_checkin'])) : '—'; ?></strong></div>
                    <div class="detail-row"><span>Checkpoint Check-out</span><span class="badge-status <?= $checkoutClass; ?>"><?= htmlspecialchars($status_checkout); ?></span></div>
                    <div class="detail-row"><span>Waktu Gerbang Turun</span><strong class="date-text"><?= !empty($data['waktu_checkout']) ? date('d M Y - H:i', strtotime($data['waktu_checkout'])) : '—'; ?></strong></div>
                </div>
            </div>

            <div class="detail-grid" style="margin-top: 20px;">
                <div class="content-section">
                    <div class="section-header">
                        <div class="section-title-block">
                            <h3 class="section-title-text">Rencana Rute Jalur</h3>
                            <p class="section-title-sub">Peta navigasi rute naik turun gerbang izin simaksi.</p>
                        </div>
                    </div>
                    <div class="detail-row"><span>Wilayah Konservasi</span><strong><?= htmlspecialchars($data['nama_gunung']); ?> (<?= number_format($data['tinggi'], 0, ',', '.'); ?> MDPL)</strong></div>
                    <div class="detail-row"><span>Pos Gerbang Masuk</span><strong class="bold-code">Pos <?= htmlspecialchars($data['pos_masuk'] ?? 'Utama'); ?></strong></div>
                    <div class="detail-row"><span>Pos Gerbang Keluar</span><strong class="bold-code" style="color: #742a2a;">Pos <?= htmlspecialchars($data['pos_keluar'] ?? 'Utama'); ?></strong></div>
                    <div class="detail-row"><span>Rencana Keberangkatan</span><strong class="date-text"> <?= date('d M Y', strtotime($data['tanggal_naik'])); ?></strong></div>
                    <div class="detail-row"><span>Rencana Turun Rombongan</span><strong class="date-text"> <?= $data['tanggal_turun'] ? date('d M Y', strtotime($data['tanggal_turun'])) : '—'; ?></strong></div>
                </div>

                <div class="content-section">
                    <div class="section-header">
                        <div class="section-title-block">
                            <h3 class="section-title-text">Rincian Finansial & Kas</h3>
                            <p class="section-title-sub">Audit data dana simaksi masuk via payment gateway.</p>
                        </div><span class="section-badge-info">Grup: <?= htmlspecialchars($data['jumlah_pendaki']); ?> Orang</span>
                    </div>
                    <div class="detail-row"><span>Tarif Tiket Per Orang</span><strong>Rp <?= number_format($data['harga_tiket'], 0, ',', '.'); ?></strong></div>
                    <div class="detail-row"><span>Metode Transaksi</span><strong> <?= htmlspecialchars($data['metode_pembayaran'] ?? '—'); ?></strong></div>
                    <div class="detail-row"><span>Nama Akun Pengirim</span><strong><?= htmlspecialchars($data['nama_pengirim'] ?? '—'); ?></strong></div>
                    <div class="detail-row"><span>Tanggal Dana Masuk</span><strong class="date-text"><?= $data['tanggal_bayar'] ? date('d M Y - H:i', strtotime($data['tanggal_bayar'])) : '—'; ?></strong></div>
                    <div class="detail-row" style="border-top: 1.5px dashed var(--border); margin-top: 8px; padding-top: 14px;"><span style="font-weight: 800; color: var(--green-dark);">Total Kas Bersih Masuk</span><strong class="price-table" style="font-size: 1.1rem;">Rp <?= number_format($data['total_harga'], 0, ',', '.'); ?></strong></div>
                </div>
            </div>

            <div class="content-section" style="margin-top: 20px; margin-bottom: 40px;">
                <div class="section-header">
                    <div class="section-title-block">
                        <h3 class="section-title-text">Pendaki Seluruh Anggota Kelompok</h3>
                        <p class="section-title-sub">Wajib lakukan pencocokan berkas kartu identitas fisik pendaki asli.</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 60px; text-align: center;">No</th>
                                <th>Nama Lengkap Anggota</th>
                                <th>Usia / Umur</th>
                                <th>Jenis Kelamin</th>
                                <th>Nomor Induk Kependudukan (NIK)</th>
                                <th>No. Telepon Aktif</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_anggota > 0): ?>
                                <?php $no = 1; ?>
                                <?php while ($anggota = mysqli_fetch_assoc($query_anggota)): ?>
                                    <?php
                                    $raw_nik = $anggota['nik'] ?? '';

                                    // Masking NIK agar tidak langsung terbuka penuh
                                    if (!empty($raw_nik) && strlen($raw_nik) >= 8) {
                                        $masked_nik = substr($raw_nik, 0, 6) . "XXXXXXXX" . substr($raw_nik, -2);
                                    } else {
                                        $masked_nik = '—';
                                    }

                                    $umur = !empty($anggota['umur']) ? $anggota['umur'] . ' Tahun' : '—';

                                    if (!empty($anggota['jenis_kelamin'])) {
                                        if ($anggota['jenis_kelamin'] == 'L') {
                                            $jenis_kelamin = 'Laki-laki';
                                        } elseif ($anggota['jenis_kelamin'] == 'P') {
                                            $jenis_kelamin = 'Perempuan';
                                        } else {
                                            $jenis_kelamin = $anggota['jenis_kelamin'];
                                        }
                                    } else {
                                        $jenis_kelamin = '—';
                                    }
                                    ?>
                                    <tr>
                                        <td style="text-align: center; color: var(--text-muted); font-weight: 700;">
                                            <?= $no++; ?>
                                        </td>

                                        <td>
                                            <strong class="hiker-name">
                                                <?= htmlspecialchars($anggota['nama_pendaki'] ?? '—'); ?>
                                            </strong>
                                        </td>

                                        <td class="date-text">
                                            <?= htmlspecialchars($umur); ?>
                                        </td>

                                        <td class="date-text">
                                            <?= htmlspecialchars($jenis_kelamin); ?>
                                        </td>

                                        <td>
                                            <?php if (!empty($raw_nik)): ?>
                                                <div class="nik-password-wrap">
                                                    <input
                                                        type="password"
                                                        class="nik-password-input"
                                                        value="<?= htmlspecialchars($raw_nik); ?>"
                                                        readonly>
                                                    <button
                                                        type="button"
                                                        class="btn-eye-nik"
                                                        onclick="triggerEmergencyReveal('<?= htmlspecialchars($raw_nik); ?>', '<?= htmlspecialchars($anggota['nama_pendaki'] ?? 'Pendaki'); ?>')"
                                                        title="Lihat NIK">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                                                            <circle cx="12" cy="12" r="3"></circle>
                                                        </svg>
                                                    </button>
                                                </div>
                                            <?php else: ?>
                                                <span class="date-text">—</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="date-text">
                                            <?= htmlspecialchars($anggota['no_hp'] ?? '—'); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="empty-row-text">
                                        Daftar identitas rombongan gagal ditarik dari database log Pendaki.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="form-actions" style="background-color: var(--white); padding: 20px; border-radius: 12px; border: 1px solid var(--border); box-shadow: var(--shadow);">
                <a href="dashboard.php" class="btn-secondary" style="margin-right: auto;">← Kembali ke Antrean</a>
                <?php if ($status_pemesanan == 'Menunggu Verifikasi' && $status_checkin == 'Belum Check-in'): ?>
                    <a href="scan.php?kode=<?= urlencode($data['kode_booking']); ?>" class="btn-primary" style="background-color: #1a5c38;">Validasi & ACC Check-in</a>
                    <a href="verifikasi_pendaki.php?id=<?= $data['id_pemesanan']; ?>&aksi=tolak" class="btn-danger" style="background-color:rgb(160, 175, 65); color: #fff;">Tolak Booking Rombongan</a>
                <?php elseif ($status_pemesanan == 'Diterima' && $status_checkin == 'Sudah Check-in' && $status_checkout == 'Belum Check-out'): ?>
                    <a href="scan.php?kode=<?= urlencode($data['kode_booking']); ?>" class="btn-primary" style="background-color: var(--green-main);">Jalur Turun Selesai (Check-out)</a>
                <?php elseif ($status_pemesanan == 'Selesai'): ?>
                    <span class="badge-status status-selesai" style="font-size: 0.85rem; padding: 10px 24px;">✓ Kelompok Telah Turun</span>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="emergencyModal" class="emergency-modal">
        <div class="em-modal-content">
            <h2 style="color: #dc3545; font-weight: 800; font-size: 1.4rem;">⚠️ PERINGATAN DARURAT (SAR)</h2>
            <p style="color: var(--text-dark); font-size: 0.9rem; margin: 14px 0 20px 0; line-height: 1.6;">
                Fitur pengungkapan data kependudukan NIK ini **HANYA** diperbolehkan untuk kebutuhan krusial evakuasi, laporan kecelakaan, atau verifikasi tim evakuasi lapangan.
            </p>
            <div style="background-color: #fff5f5; padding: 16px; border-radius: 10px; border: 1px solid #fed7d7; margin-bottom: 24px;">
                <span style="font-size: 0.78rem; font-weight: 800; color: #c53030; display: block; text-transform: uppercase; letter-spacing: 0.5px;">Pendaki: <span id="emHikerName"></span></span>
                <strong id="emDisplayedNik" style="font-family: monospace; font-size: 1.4rem; color: #9b2c2c; letter-spacing: 1px; display: block; margin-top: 6px;"></strong>
            </div>
            <button type="button" class="btn-primary" style="background-color: var(--green-dark); width: 100%;" onclick="closeEmergencyModal()">Saya Mengerti, Tutup</button>
        </div>
    </div>

    <script>
        function toggleSidebarMenu() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        }

        // Handler Fungsi Interaksi Modal Reveal Privasi NIK
        function triggerEmergencyReveal(rawNik, hikerName) {
            document.getElementById("emHikerName").innerText = hikerName;
            document.getElementById("emDisplayedNik").innerText = rawNik;
            document.getElementById("emergencyModal").style.display = "flex";
        }

        function closeEmergencyModal() {
            document.getElementById("emergencyModal").style.display = "none";
        }

        function toggleNikPassword(btn) {
            const input = btn.closest('.nik-password-wrap').querySelector('.nik-password-input');

            if (input.type === 'password') {
                input.type = 'text';

                btn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <path d="M17.94 17.94A10.94 10.94 0 0 1 12 19C5 19 1 12 1 12a20.29 20.29 0 0 1 5.06-5.94"></path>
                <path d="M9.9 4.24A10.78 10.78 0 0 1 12 4c7 0 11 8 11 8a20.35 20.35 0 0 1-3.22 4.31"></path>
                <path d="M14.12 14.12A3 3 0 0 1 9.88 9.88"></path>
                <line x1="1" y1="1" x2="23" y2="23"></line>
            </svg>
        `;
            } else {
                input.type = 'password';

                btn.innerHTML = `
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"></path>
                <circle cx="12" cy="12" r="3"></circle>
            </svg>
        `;
            }
        }
    </script>
</body>

</html>