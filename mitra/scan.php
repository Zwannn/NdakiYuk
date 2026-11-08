<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_mitra = $_SESSION['id_user'];

$kode_booking = isset($_GET['kode']) ? mysqli_real_escape_string($conn, $_GET['kode']) : '';
$data = null;
$anggota = null;
$error = '';

if ($kode_booking != '') {
    $query = mysqli_query($conn, "
        SELECT 
            p.id_pemesanan, p.id_user, p.id_gunung, p.kode_booking, p.tanggal_naik, p.tanggal_turun, p.jumlah_pendaki, p.total_harga, p.status_pemesanan, p.status_checkin, p.status_checkout, p.waktu_checkin, p.waktu_checkout, p.pos_masuk, p.pos_keluar,
            g.nama_gunung, g.lokasi, g.estimasi_pendakian,
            u.username, u.email,
            up.nama_lengkap, up.no_hp, up.foto_profile,
            pb.status_verifikasi, pb.metode_pembayaran, pb.tanggal_bayar
        FROM pemesanan p
        JOIN gunung_mitra gm ON p.id_gunung = gm.id_gunung
        JOIN gunung g ON p.id_gunung = g.id_gunung
        JOIN users u ON p.id_user = u.id_user
        LEFT JOIN user_profile up ON u.id_user = up.id_user
        LEFT JOIN pembayaran pb ON p.id_pemesanan = pb.id_pemesanan
        WHERE p.kode_booking = '$kode_booking'
        AND gm.id_user = '$id_mitra'
        ORDER BY pb.id_pembayaran DESC
        LIMIT 1
    ");

    if (!$query) {
        die("Query scan error: " . mysqli_error($conn));
    }

    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        $error = "Kode booking tidak ditemukan atau bukan untuk gunung yang Anda kelola.";
    } else {
        $id_pemesanan = $data['id_pemesanan'];

        $anggota = mysqli_query($conn, "
            SELECT nama_pendaki, nik, no_hp, umur, jenis_kelamin
            FROM detail_pendaki
            WHERE id_pemesanan = '$id_pemesanan'
            ORDER BY id_detail ASC
        ");

        if (!$anggota) {
            die("Query anggota error: " . mysqli_error($conn));
        }
    }
}

// Statistik kecil harian
$query_today = mysqli_query($conn, "
    SELECT 
        COUNT(*) AS total_booking,
        COALESCE(SUM(CASE WHEN status_checkin = 'Sudah Check-in' THEN jumlah_pendaki ELSE 0 END), 0) AS checkin_aktif
    FROM pemesanan p
    JOIN gunung_mitra gm ON p.id_gunung = gm.id_gunung
    WHERE gm.id_user = '$id_mitra'
    AND p.tanggal_naik = CURDATE()
");

$stat = mysqli_fetch_assoc($query_today);
$total_hari_ini = $stat['total_booking'] ?? 0;
$checkin_aktif = $stat['checkin_aktif'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem QR Scanner Validasi - NdakiYuk Mitra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mitra.css">

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
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
                    <small class="header-pretitle">SISTEM VALIDASI SCANNER REALTIME</small>
                    <h1>Pintu Check-in & Check-out</h1>
                    <p class="header-sub">Dekatkan QR Code kelompok pendaki pada kamera loket, atau masukkan kode booking manifes secara manual.</p>
                </div>
            </div>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'checkin_berhasil'): ?>
                <div class="content-section" style="border-left: 4px solid var(--color-diterima); background-color: var(--green-light); margin-bottom: 24px; padding: 16px 24px;">
                    <strong style="color: var(--color-diterima); font-size: 0.92rem;">✓ Registrasi Masuk Berhasil! Check-in kelompok pendaki telah dikonfirmasi pos registrasi.</strong>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['status']) && $_GET['status'] == 'checkout_berhasil'): ?>
                <div class="content-section" style="border-left: 4px solid var(--color-selesai); background-color: var(--bg-selesai); margin-bottom: 24px; padding: 16px 24px;">
                    <strong style="color: var(--color-selesai); font-size: 0.92rem;">✓ Registrasi Turun Selesai! Check-out terverifikasi, rombongan dilaporkan kembali dengan selamat.</strong>
                </div>
            <?php endif; ?>

            <div class="scan-grid">

                <div>
                    <div class="content-section" style="margin-bottom: 24px;">
                        <div class="scanner-visual" id="reader" style="margin-bottom: 20px; overflow: hidden;">
                            <div class="scanner-frame">
                                <span style="display: flex; flex-direction: column; align-items: center; gap: 8px;">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 3s infinite linear;">
                                        <path d="M23 12a11 11 0 1 1-22 0 11 11 0 0 1 22 0z" />
                                    </svg>
                                    Menginisialisasi Akses Kamera...
                                </span>
                            </div>
                        </div>

                        <form method="GET" action="scan.php" class="scan-form" id="inputManualForm">
                            <div class="form-group">
                                <label style="font-size: 0.75rem; font-weight: 800; color: var(--text-muted); letter-spacing: 0.3px;">Input Manual Kode Booking Manifes</label>
                                <div style="display: flex; gap: 10px; width: 100%;">
                                    <input
                                        type="text"
                                        name="kode"
                                        id="kodeBookingField"
                                        placeholder="Contoh: NY-20260601ABCDEF"
                                        value="<?= htmlspecialchars($kode_booking); ?>"
                                        style="flex: 1;"
                                        required>
                                    <button type="submit" class="btn-primary" style="padding: 0 24px; height: 44px; font-size: 0.85rem; border-radius: 10px; flex-shrink: 0;">Periksa Berkas</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="cards-grid" style="grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div class="stat-card">
                            <div class="scard-icon-wrap icon-booking">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                            </div>
                            <div class="scard-data">
                                <span class="scard-label">Target Booking Hari Ini</span>
                                <h2 class="scard-value"><?= $total_hari_ini; ?> <small>Grup</small></h2>
                            </div>
                        </div>

                        <div class="stat-card">
                            <div class="scard-icon-wrap icon-pendaki">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                </svg>
                            </div>
                            <div class="scard-data">
                                <span class="scard-label">Rombongan Aktif di Gunung</span>
                                <h2 class="scard-value" style="color: var(--green-main);"><?= $checkin_aktif; ?> <small>Orang</small></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div>
                    <?php if ($error): ?>
                        <div class="content-section" style="border: 1px solid var(--border); min-height: 350px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 10px;">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#dc3545" stroke-width="1.5">
                                <circle cx="12" cy="12" r="10" />
                                <line x1="12" y1="8" x2="12" y2="12" />
                                <line x1="12" y1="16" x2="12.01" y2="16" />
                            </svg>
                            <h3 style="color: var(--color-ditolak); font-weight: 700;">Manifes Gagal Terverifikasi</h3>
                            <p style="color: var(--text-muted); font-size: 0.88rem; max-width: 280px;"><?= htmlspecialchars($error); ?></p>
                        </div>

                    <?php elseif ($data): ?>
                        <?php
                        $nama_ketua = $data['nama_lengkap'] ?: $data['username'];
                        $status_pemesanan = $data['status_pemesanan'];
                        $status_checkin = $data['status_checkin'] ?? 'Belum Check-in';
                        $status_checkout = $data['status_checkout'] ?? 'Belum Check-out';
                        $status_bayar = $data['status_verifikasi'] ?? 'Belum Bayar';

                        $aksi_scan = '';
                        if ($status_checkout == 'Sudah Check-out' || $status_pemesanan == 'Selesai') {
                            $aksi_scan = 'selesai';
                        } elseif ($status_checkin == 'Sudah Check-in' && $status_checkout == 'Belum Check-out') {
                            $aksi_scan = 'checkout';
                        } else {
                            $aksi_scan = 'checkin';
                        }

                        $payment_badge = ($status_bayar == 'Terverifikasi' || $status_bayar == 'Valid') ? 'status-diterima' : 'status-pending';
                        $booking_badge = ($status_pemesanan == 'Diterima' || $status_pemesanan == 'Selesai') ? 'status-diterima' : 'status-pending';
                        $ci_badge = ($status_checkin == 'Sudah Check-in') ? 'status-diterima' : 'status-pending';
                        $co_badge = ($status_checkout == 'Sudah Check-out') ? 'status-selesai' : 'status-pending';
                        ?>

                        <div class="content-section">
                            <div class="section-header" style="padding-bottom: 14px; margin-bottom: 16px;">
                                <div class="section-title-block">
                                    <h3 class="section-title-text" style="font-size: 1rem;">Manifes Terverifikasi</h3>
                                    <p class="section-title-sub">Grup Log: <?= htmlspecialchars($data['nama_gunung']); ?></p>
                                </div>
                                <span class="bold-code" style="font-size: 0.88rem; background-color: var(--bg-page); padding: 4px 10px; border-radius: 6px;">#<?= htmlspecialchars($data['kode_booking']); ?></span>
                            </div>

                            <div class="detail-row" style="padding: 8px 0; border-bottom: 1.5px dashed var(--border); margin-bottom: 10px;">
                                <span>Ketua Rombongan</span>
                                <strong class="hiker-name" style="font-size: 0.95rem;"><?= htmlspecialchars($nama_ketua); ?></strong>
                            </div>

                            <div class="detail-row" style="padding: 8px 0;">
                                <span>Ukuran Kelompok</span>
                                <strong><?= htmlspecialchars($data['jumlah_pendaki']); ?> Anggota</strong>
                            </div>

                            <div class="detail-row" style="padding: 8px 0;">
                                <span>Jalur Administrasi</span>
                                <span class="badge-status <?= $payment_badge; ?>" style="font-size: 0.68rem; padding: 2px 8px;"><?= htmlspecialchars($status_bayar); ?></span>
                            </div>

                            <div class="detail-row" style="padding: 8px 0;">
                                <span>Izin Simaksi</span>
                                <span class="badge-status <?= $booking_badge; ?>" style="font-size: 0.68rem; padding: 2px 8px;"><?= htmlspecialchars($status_pemesanan); ?></span>
                            </div>

                            <div class="detail-row" style="padding: 8px 0;">
                                <span>Gerbang Check-in</span>
                                <span class="badge-status <?= $ci_badge; ?>" style="font-size: 0.68rem; padding: 2px 8px;"><?= htmlspecialchars($status_checkin == 'Sudah Check-in' ? 'BUKA' : 'BELUM'); ?></span>
                            </div>

                            <div class="detail-row" style="padding: 8px 0;">
                                <span>Gerbang Check-out</span>
                                <span class="badge-status <?= $co_badge; ?>" style="font-size: 0.68rem; padding: 2px 8px;"><?= htmlspecialchars($status_checkout == 'Sudah Check-out' ? 'TUTUP' : 'BELUM'); ?></span>
                            </div>

                            <div class="detail-row" style="padding: 8px 0;">
                                <span>Rencana Tanggal Naik</span>
                                <strong class="date-text">📅 <?= date('d M Y', strtotime($data['tanggal_naik'])); ?></strong>
                            </div>

                            <div style="margin-top: 16px; padding: 12px; background-color: var(--bg-page); border: 1px solid var(--border); border-radius: 12px;">
                                <h4 style="font-size: 0.78rem; font-weight: 800; color: var(--text-mid); text-transform: uppercase; letter-spacing: 0.3px; margin-bottom: 8px;">Daftar Fisik Anggota Kelompok</h4>
                                <?php if ($anggota && mysqli_num_rows($anggota) > 0): ?>
                                    <ul style="list-style: none; display: flex; flex-direction: column; gap: 6px;">
                                        <?php while ($a = mysqli_fetch_assoc($anggota)): ?>
                                            <li style="font-size: 0.82rem; font-weight: 700; color: var(--text-dark); display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid rgba(0,0,0,0.02); padding-bottom: 4px;">
                                                <span>👤 <?= htmlspecialchars($a['nama_pendaki']); ?></span>
                                                <span style="font-family: monospace; font-size: 0.75rem; color: var(--text-muted); font-weight: 500;"><?= htmlspecialchars($a['nik']); ?></span>
                                            </li>
                                        <?php endwhile; ?>
                                    </ul>
                                <?php else: ?>
                                    <p style="font-size: 0.8rem; color: var(--text-muted); font-style: italic;">Log manifestasi anggota kelompok kosong.</p>
                                <?php endif; ?>
                            </div>

                            <div style="margin-top: 18px;">
                                <?php if ($aksi_scan == 'checkin'): ?>
                                    <form action="proses_scan.php" method="POST" onsubmit="return confirm('Konfirmasi ACC Check-in untuk rombongan ini?');">
                                        <input type="hidden" name="id_pemesanan" value="<?= $data['id_pemesanan']; ?>">
                                        <input type="hidden" name="aksi" value="checkin">
                                        <button type="submit" name="proses" class="btn-primary" style="width: 100%; height: 44px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; justify-content: center; background-color: #1a5c38;">
                                            Izinkan Rombongan Naik (Check-in)
                                        </button>
                                    </form>

                                <?php elseif ($aksi_scan == 'checkout'): ?>
                                    <form action="proses_scan.php" method="POST" onsubmit="return confirm('Konfirmasi Check-out untuk rombongan ini?');">
                                        <input type="hidden" name="id_pemesanan" value="<?= $data['id_pemesanan']; ?>">
                                        <input type="hidden" name="aksi" value="checkout">
                                        <button type="submit" name="proses" class="btn-primary" style="width: 100%; height: 44px; border-radius: 10px; font-weight: 700; font-size: 0.9rem; justify-content: center; background-color: var(--green-main);">
                                            Tutup Jalur Selesai (Check-out)
                                        </button>
                                    </form>

                                <?php elseif ($aksi_scan == 'selesai'): ?>
                                    <div style="padding: 12px; background-color: var(--bg-selesai); color: var(--color-selesai); font-weight: 700; font-size: 0.82rem; text-align: center; border-radius: 10px;">
                                        ✓ Aktivitas pendakian kelompok ini telah rampung (Closed).
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="content-section" style="border: 1px dashed var(--border); min-height: 350px; display: flex; flex-direction: column; justify-content: center; align-items: center; text-align: center; gap: 10px; color: var(--text-muted);">
                            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                                <line x1="12" y1="22.08" x2="12" y2="12" />
                            </svg>
                            <h3 style="font-weight: 700; color: var(--text-mid);">Menunggu Pembacaan Berkas</h3>
                            <p style="font-size: 0.82rem; max-width: 240px;">Bidik tiket kelompok melalui scanner atau ketikkan kode booking manifes di layar pos kiri.</p>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

        </main>
    </div>

    <script>
        function toggleSidebarMenu() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        }

        document.addEventListener("DOMContentLoaded", function() {
            const html5QrcodeScanner = new Html5Qrcode("reader");
            const qrConfig = {
                fps: 15,
                qrbox: {
                    width: 200,
                    height: 150
                }
            };

            function onScanSuccess(decodedText, decodedResult) {
                html5QrcodeScanner.stop().then(() => {
                    let cleanCode = decodedText.trim();
                    if (cleanCode.includes("kode=")) {
                        const urlParams = new URLSearchParams(cleanCode.split('?')[1]);
                        cleanCode = urlParams.get('kode');
                    }

                    // PERBAIKAN UTAMA: Bersihkan prefix string barcode bawaan aplikasi mobile
                    cleanCode = cleanCode.replace(/^CHECKIN_/i, "");
                    cleanCode = cleanCode.replace(/^CHECKOUT_/i, "");

                    document.getElementById("kodeBookingField").value = cleanCode;
                    window.location.href = "scan.php?kode=" + encodeURIComponent(cleanCode);
                }).catch((err) => {
                    console.error("Gagal menghentikan kamera secara aman: ", err);
                });
            }

            function onScanFailure(error) {}

            Html5Qrcode.getCameras().then(cameras => {
                if (cameras && cameras.length > 0) {
                    let cameraId = cameras[0].id;
                    for (let i = 0; i < cameras.length; i++) {
                        if (cameras[i].label.toLowerCase().includes('back') || cameras[i].label.toLowerCase().includes('environment')) {
                            cameraId = cameras[i].id;
                            break;
                        }
                    }
                    html5QrcodeScanner.start(cameraId, qrConfig, onScanSuccess, onScanFailure)
                        .catch(err => {
                            document.getElementById("reader").innerHTML = "<div class='scanner-frame' style='color:#dc3545; border-color:#dc3545;'>⚠️ Kamera diblokir atau gagal diakses. Pastikan SSL/HTTPS aktif.</div>";
                        });
                } else {
                    document.getElementById("reader").innerHTML = "<div class='scanner-frame' style='color:#dc3545; border-color:#dc3545;'>⚠️ Tidak ada hardware kamera eksternal terdeteksi di perangkat loket.</div>";
                }
            }).catch(err => {
                console.error("Gagal mendapatkan daftar kamera perangkat: ", err);
            });
        });
    </script>
</body>

</html>