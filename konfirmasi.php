<?php
// konfirmasi.php – Halaman Confirm Pemesanan & Pembayaran Tiket NdakiYuk Resmi API KlikQRIS
session_start();
include "config/koneksi.php";

// JIKA BELUM LOGIN, LEMPAR KE LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];

// ── Ambil parameter kode booking dari URL ───────────────────────────────
$kode_booking = mysqli_real_escape_string($conn, $_GET['kode'] ?? '');

if (empty($kode_booking)) {
    header("Location: gunung.php");
    exit;
}

define('DEV_MODE', false);

// ── CONFIG: deteksi otomatis localhost vs production ──────────────────────
$is_production  = isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] === 'ndakiyuk.triall.web.id';
$app_base_url   = $is_production
    ? 'https://ndakiyuk.triall.web.id'
    : 'http://localhost/ndakiyuk2';

// CONFIG INTEGRASI API KLIKQRIS (sesuai dokumentasi resmi)
$merchant_id    = "178038814280";
$api_key        = "7dxKpM1L4ja8FLllgDIj4q6phb0rboW4wSy5NSXx";
$base_url       = "https://klikqris.com/api";
$webhook_url    = $app_base_url . "/webhook_klikqris.php";

$message = "";
$messageType = "";

$cache_dir  = __DIR__ . '/qris_cache/';
if (!is_dir($cache_dir)) {
    mkdir($cache_dir, 0755, true);
}
$cache_file = $cache_dir . "qris_invoice_" . $kode_booking . ".json";

// ── FUNGSI: CEK STATUS — endpoint resmi /qris/status/{order_id} ──────────
function dapatkanStatusPembayaran($conn, $kode_booking, $merchant_id, $api_key, $cache_file, $base_url)
{
    if (DEV_MODE) {
        return 'SUCCESS';
    }

    // Ambil order_id asli dari cache (bukan kode_booking langsung)
    $order_id_to_check = $kode_booking;
    if (file_exists($cache_file)) {
        $cache_data = json_decode(file_get_contents($cache_file), true);
        if (isset($cache_data['klikqris_order_id'])) {
            $order_id_to_check = $cache_data['klikqris_order_id'];
        }
    }

    // Endpoint resmi: GET /qris/status/{order_id}
    $url_status = $base_url . "/qris/status/" . urlencode($order_id_to_check);

    $curl_check = curl_init();
    curl_setopt_array($curl_check, array(
        CURLOPT_URL            => $url_status,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST  => 'GET',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_HTTPHEADER     => array(
            'x-api-key: '   . $api_key,
            'id_merchant: ' . $merchant_id
        ),
    ));
    $res_status = curl_exec($curl_check);
    $curl_err   = curl_error($curl_check);
    curl_close($curl_check);

    if ($curl_err) return 'ERROR';

    $result = json_decode($res_status, true);

    if (isset($result['status']) && $result['status'] == true && isset($result['data']['status'])) {
        return strtoupper($result['data']['status']); // SUCCESS / PENDING / EXPIRED
    }
    return 'PENDING';
}

// ── FUNGSI: Simpan pembayaran ke DB jika belum ada ────────────────────────
function simpanPembayaranKeBD($conn, $kode_booking, $metode, $nama_pengirim)
{
    $kode_esc = mysqli_real_escape_string($conn, $kode_booking);
    $qFind    = mysqli_query($conn, "SELECT id_pemesanan FROM pemesanan WHERE kode_booking = '$kode_esc'");
    if (!$qFind || mysqli_num_rows($qFind) === 0) return false;

    $pData        = mysqli_fetch_assoc($qFind);
    $id_pemesanan = $pData['id_pemesanan'];
    $metode_esc   = mysqli_real_escape_string($conn, $metode);
    $pengirim_esc = mysqli_real_escape_string($conn, $nama_pengirim);

    $check = mysqli_query($conn, "SELECT id_pembayaran FROM pembayaran WHERE id_pemesanan = '$id_pemesanan'");
    if (mysqli_num_rows($check) == 0) {
        mysqli_query($conn, "INSERT INTO pembayaran (id_pemesanan, metode_pembayaran, nama_pengirim, bukti_pembayaran, status_verifikasi) VALUES ('$id_pemesanan', '$metode_esc', '$pengirim_esc', NULL, 'Valid')");
        mysqli_query($conn, "UPDATE pemesanan SET status_pemesanan = 'Menunggu Verifikasi' WHERE id_pemesanan = '$id_pemesanan'");
    }
    return true;
}

// ── AJAX ENDPOINT: Auto polling — cek DB dulu, fallback langsung ke API ──
if (isset($_GET['ajax_check'])) {
    header('Content-Type: application/json');

    $qCheck = mysqli_query($conn, "SELECT status_pemesanan FROM pemesanan WHERE kode_booking = '$kode_booking'");
    $dCheck = mysqli_fetch_assoc($qCheck);

    // Sudah dibayar (dari webhook atau sync sebelumnya)
    if ($dCheck && in_array($dCheck['status_pemesanan'], ['Menunggu Verifikasi', 'Diterima', 'Selesai'])) {
        echo json_encode(['status' => 'SUCCESS', 'source' => 'db']);
        exit;
    }

    // Masih pending → tanya langsung ke API KlikQRIS
    $status_api = dapatkanStatusPembayaran($conn, $kode_booking, $merchant_id, $api_key, $cache_file, $base_url);

    if ($status_api === 'SUCCESS' || $status_api === 'PAID') {
        $nama = mysqli_real_escape_string($conn, $_SESSION['nama_lengkap'] ?? 'User');
        simpanPembayaranKeBD($conn, $kode_booking, 'QRIS KlikQRIS (Auto Polling)', $nama);
        if (file_exists($cache_file)) unlink($cache_file);
        echo json_encode(['status' => 'SUCCESS', 'source' => 'api']);
    } elseif ($status_api === 'EXPIRED') {
        echo json_encode(['status' => 'EXPIRED']);
    } else {
        echo json_encode(['status' => 'PENDING', 'api_status' => $status_api]);
    }
    exit;
}

// ── Handle POST: Proses Tombol Pembayaran & Pembatalan (Cancel) ───────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['konfirmasi_bayar'])) {
        // Manual sync: user klik tombol → cek langsung ke API KlikQRIS
        $status_qris = dapatkanStatusPembayaran($conn, $kode_booking, $merchant_id, $api_key, $cache_file, $base_url);

        if ($status_qris === 'SUCCESS' || $status_qris === 'PAID') {
            $nama_pengirim = mysqli_real_escape_string($conn, $_SESSION['nama_lengkap'] ?? 'User');
            simpanPembayaranKeBD($conn, $kode_booking, 'QRIS KlikQRIS (Manual Sync)', $nama_pengirim);
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
            header("Location: konfirmasi.php?kode=" . $kode_booking . "&success=paid");
            exit;
        } else {
            $pesan_status = match ($status_qris) {
                'PENDING'  => 'Pembayaran belum diterima. Pastikan Anda sudah scan dan konfirmasi di aplikasi e-wallet.',
                'EXPIRED'  => 'QRIS sudah kedaluwarsa. Silakan batalkan dan buat pemesanan baru.',
                'ERROR'    => 'Tidak dapat terhubung ke server KlikQRIS. Coba beberapa saat lagi.',
                default    => 'Status: ' . htmlspecialchars($status_qris) . '. Silakan selesaikan pembayaran terlebih dahulu.'
            };
            $message     = $pesan_status;
            $messageType = "warn";
        }
    }

    if (isset($_POST['batal_beli'])) {
        $qFind = mysqli_query($conn, "SELECT id_pemesanan FROM pemesanan WHERE kode_booking = '$kode_booking' AND status_pemesanan = 'Menunggu Pembayaran'");
        if (mysqli_num_rows($qFind) > 0) {
            $pData = mysqli_fetch_assoc($qFind);
            $id_pemesanan = $pData['id_pemesanan'];
            mysqli_query($conn, "DELETE FROM detail_pendaki WHERE id_pemesanan = '$id_pemesanan'");
            mysqli_query($conn, "DELETE FROM pemesanan WHERE id_pemesanan = '$id_pemesanan'");
            if (file_exists($cache_file)) {
                unlink($cache_file);
            }
            header("Location: tiket.php?status=cancelled");
            exit;
        }
    }
}

// ── Ambil Informasi Detail Transaksi Terkini dari Database ────────────
$queryBooking = mysqli_query($conn, "
    SELECT 
        p.*, 
        g.nama_gunung, g.lokasi, g.gambar, kg.nama_kategori,
        up.nama_lengkap 
    FROM pemesanan p
    JOIN gunung g ON p.id_gunung = g.id_gunung
    JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori
    JOIN user_profile up ON p.id_user = up.id_user
    WHERE p.kode_booking = '$kode_booking'
");

if (mysqli_num_rows($queryBooking) === 0) {
    header("Location: gunung.php");
    exit;
}

$data = mysqli_fetch_assoc($queryBooking);
$id_gunung = $data['id_gunung'];

// GENERATE QRIS CACHE FILE HANDLING
$display_price   = $data['total_harga'];
$qris_image_url  = "";
$qris_expired_at = "";
$qris_error_msg  = "";

if ($data['status_pemesanan'] === 'Menunggu Pembayaran') {

    if (file_exists($cache_file)) {
        // ── Cache ada → pakai langsung ───────────────────────
        $cache_data      = json_decode(file_get_contents($cache_file), true);
        $display_price   = $cache_data['total_amount']  ?? $data['total_harga'];
        $qris_image_url  = $cache_data['qris_url']      ?? '';
        $qris_expired_at = $cache_data['expired_at']    ?? '';

        // Cache rusak / qris_url kosong → hapus dan buat ulang
        if (empty($qris_image_url)) {
            unlink($cache_file);
        }
    }

    // Cache tidak ada atau baru saja dihapus → generate baru
    if (!file_exists($cache_file)) {
        $klikqris_order_id = $kode_booking . "-" . time();

        $curl_create = curl_init();
        curl_setopt_array($curl_create, [
            CURLOPT_URL            => $base_url . '/qris/create',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => json_encode([
                "order_id"    => $klikqris_order_id,
                "id_merchant" => $merchant_id,
                "amount"      => (int)$data['total_harga'],
                "keterangan"  => "Tiket Pendakian " . $data['nama_gunung'] . " #" . $kode_booking,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: '   . $api_key,
                'id_merchant: ' . $merchant_id,
            ],
        ]);

        $res_create    = curl_exec($curl_create);
        $curl_err      = curl_error($curl_create);
        curl_close($curl_create);

        // Log ke file untuk debug
        if (DEV_MODE) {
            file_put_contents(
                __DIR__ . '/qris_debug.log',
                date('Y-m-d H:i:s') . " CREATE order_id=$klikqris_order_id\n" .
                    "curl_err=$curl_err\n" .
                    "response=$res_create\n\n",
                FILE_APPEND
            );
        }

        $result_create = json_decode($res_create, true);

        if (!empty($curl_err)) {
            $qris_error_msg = "Tidak dapat terhubung ke server KlikQRIS. Periksa koneksi internet.";
        } elseif (isset($result_create['status']) && $result_create['status'] == true) {
            // ── Berhasil → simpan cache ────────────────────────
            $display_price   = (int)($result_create['data']['total_amount'] ?? $data['total_harga']);
            $qris_image_url  = $result_create['data']['qris_url']   ?? '';
            $qris_expired_at = $result_create['data']['expired_at'] ?? '';
            $qris_signature  = $result_create['data']['signature']  ?? '';

            file_put_contents($cache_file, json_encode([
                'klikqris_order_id' => $klikqris_order_id,
                'total_amount'      => $display_price,
                'qris_url'          => $qris_image_url,
                'expired_at'        => $qris_expired_at,
                'signature'         => $qris_signature,
            ]));
        } elseif (
            isset($result_create['errors']['order_id']) ||
            (isset($result_create['message']) && stripos($result_create['message'], 'Validation') !== false)
        ) {
            // ── order_id duplikat → coba dengan timestamp +2 ──
            $klikqris_order_id = $kode_booking . "-" . (time() + rand(2, 9));

            $curl_r2 = curl_init();
            curl_setopt_array($curl_r2, [
                CURLOPT_URL            => $base_url . '/qris/create',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_POSTFIELDS     => json_encode([
                    "order_id"    => $klikqris_order_id,
                    "id_merchant" => $merchant_id,
                    "amount"      => (int)$data['total_harga'],
                    "keterangan"  => "Tiket Pendakian " . $data['nama_gunung'] . " #" . $kode_booking,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: '   . $api_key,
                    'id_merchant: ' . $merchant_id,
                ],
            ]);
            $res_r2 = curl_exec($curl_r2);
            curl_close($curl_r2);
            $result_r2 = json_decode($res_r2, true);

            file_put_contents(
                'qris_debug.log',
                date('Y-m-d H:i:s') . " RETRY order_id=$klikqris_order_id | response=$res_r2\n\n",
                FILE_APPEND
            );

            if (isset($result_r2['status']) && $result_r2['status'] == true) {
                $display_price   = (int)($result_r2['data']['total_amount'] ?? $data['total_harga']);
                $qris_image_url  = $result_r2['data']['qris_url']   ?? '';
                $qris_expired_at = $result_r2['data']['expired_at'] ?? '';
                $qris_signature  = $result_r2['data']['signature']  ?? '';

                file_put_contents($cache_file, json_encode([
                    'klikqris_order_id' => $klikqris_order_id,
                    'total_amount'      => $display_price,
                    'qris_url'          => $qris_image_url,
                    'expired_at'        => $qris_expired_at,
                    'signature'         => $qris_signature,
                ]));
            } else {
                $qris_error_msg = "Gagal membuat QRIS (duplikat order). Silakan refresh halaman.";
            }
        } else {
            $api_msg        = $result_create['message'] ?? 'Tidak ada response dari server.';
            $qris_error_msg = "Gagal membuat QRIS: " . htmlspecialchars($api_msg) . " — Silakan refresh halaman.";
        }
    }
}

$tgl_naik  = new DateTime($data['tanggal_naik']);
$tgl_turun = new DateTime($data['tanggal_turun']);

$booking = [
    'kode'         => $data['kode_booking'],
    'gunung'       => $data['nama_gunung'],
    'lokasi'       => $data['lokasi'],
    'kesulitan'    => $data['nama_kategori'],
    'image'        => 'assets/img/gunung/' . $data['gambar'],
    'lead_name'    => $data['nama_lengkap'],
    'date_start'   => $tgl_naik->format('d'),
    'date_end'     => $tgl_turun->format('d'),
    'month_year'   => $tgl_naik->format('F Y'),
    'group_size'   => $data['jumlah_pendaki'],
    'total_bayar'  => $display_price,
    'status'       => $data['status_pemesanan'],
    'qris_url'     => $qris_image_url,
    'qris_expired' => $qris_expired_at,
    'qris_error'   => $qris_error_msg,
    'pos_basecamp' => 'Loket pintu masuk perizinan resmi di jalur pos: ' . $data['pos_masuk'],
];

if (($booking['status'] === 'Menunggu Verifikasi' || isset($_GET['success'])) && empty($message)) {
    $message = "✓ Pembayaran terverifikasi lunas secara otomatis via API KlikQRIS! Tiket Anda aktif.";
    $messageType = "success";
}

function formatRp(int $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Status Booking</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <link rel="stylesheet" href="assets/css/konfirmasi.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <main class="page-main">
        <div class="page-container">

            <div class="success-header">
                <?php if ($booking['status'] === 'Selesai'): ?>
                    <div class="success-icon" style="background: #1a5c38;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </div>
                    <h1 class="success-title">Pendakian Selesai!</h1>
                    <p class="success-sub">Terima kasih telah mendaki dengan aman bersama NdakiYuk.</p>

                <?php elseif ($booking['status'] === 'Diterima'): ?>
                    <div class="success-icon" style="background: #17a2b8;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <polyline points="12 8 12 12 14 14" />
                        </svg>
                    </div>
                    <h1 class="success-title">Pendakian Aktif</h1>
                    <p class="success-sub">Anda sedang dalam masa pendakian. Harap jaga keselamatan dan bawa kembali sampah Anda.</p>

                <?php elseif ($booking['status'] === 'Menunggu Verifikasi'): ?>
                    <div class="success-icon" style="background: #1a5c38;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                    </div>
                    <h1 class="success-title">Pembayaran Lunas!</h1>
                    <p class="success-sub">Tiket siap digunakan. Silakan tunjukkan QR Check-In ke petugas loket untuk diverifikasi.</p>

                <?php else: ?>
                    <div class="success-icon" style="background: #e8832a;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                    </div>
                    <h1 class="success-title">Waiting For Payment</h1>
                    <p class="success-sub">Selesaikan proses administrasi untuk tiket pendakian <?= htmlspecialchars($booking['gunung']) ?>.</p>
                <?php endif; ?>
            </div>

            <?php if (!empty($message)): ?>
                <div id="statusAlertBox" style="padding: 14px; margin-bottom: 24px; border-radius: 12px; font-size: 0.88rem; font-weight: 600; text-align: center; 
                <?= $messageType === 'success' || $booking['status'] === 'Menunggu Verifikasi' ? 'background: rgba(40,167,69,0.15); color: #28a745; border: 1px solid #28a745;' : 'background: rgba(220,53,69,0.15); color: #dc3545; border: 1px solid #dc3545;' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <div class="ticket-wrap">
                <div class="ticket-card" id="printableTicketArea" style="background: #ffffff;">

                    <div class="ticket-hero">
                        <img src="<?= htmlspecialchars($booking['image']) ?>" alt="<?= htmlspecialchars($booking['gunung']) ?>" class="ticket-hero-img">
                        <div class="ticket-hero-overlay"></div>
                        <div class="ticket-hero-content">
                            <span class="ticket-diff-badge"><?= htmlspecialchars($booking['kesulitan']) ?></span>
                            <div class="ticket-hero-bottom">
                                <div>
                                    <h2 class="ticket-gunung-name"><?= htmlspecialchars($booking['gunung']) ?></h2>
                                    <p class="ticket-lokasi">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
                                            <circle cx="12" cy="10" r="3" />
                                        </svg>
                                        <?= htmlspecialchars($booking['lokasi']) ?>
                                    </p>
                                </div>
                                <div class="ticket-kode-wrap">
                                    <span class="ticket-kode-label">Nomor Booking</span>
                                    <span class="ticket-kode">#<?= htmlspecialchars($booking['kode']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-notch">
                        <div class="notch-left"></div>
                        <div class="notch-dash"></div>
                        <div class="notch-right"></div>
                    </div>

                    <div class="ticket-meta">
                        <div class="meta-item">
                            <span class="meta-label">LEAD HIKER</span>
                            <span class="meta-val"><?= htmlspecialchars($booking['lead_name']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">DATE OF HIKE</span>
                            <div class="meta-val">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                                <?= $booking['date_start'] ?> – <?= $booking['date_end'] ?> <?= $booking['month_year'] ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">GROUP SIZE</span>
                            <span class="meta-val">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                                Jlh. <?= $booking['group_size'] ?> Orang
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">TOTAL TAGIHAN</span>
                            <span class="meta-val meta-harga"><?= formatRp($booking['total_bayar']) ?></span>
                        </div>
                    </div>

                    <div class="ticket-notch">
                        <div class="notch-left"></div>
                        <div class="notch-dash"></div>
                        <div class="notch-right"></div>
                    </div>

                    <div class="ticket-qr-section">
                        <p class="qr-sub-title" style="font-weight: 800; color: var(--green-dark); margin-bottom: 12px; font-size: 0.95rem;">
                            <?php
                            if ($booking['status'] === 'Menunggu Pembayaran') echo 'Silakan Scan QRIS Ini Untuk Membayar';
                            elseif ($booking['status'] === 'Menunggu Verifikasi') echo 'QR CHECK-IN LOKET BASECAMP';
                            elseif ($booking['status'] === 'Diterima') echo 'QR CHECK-OUT (TURUN GUNUNG)';
                            else echo 'TIKET SELESAI';
                            ?>
                        </p>

                        <div class="qr-box">
                            <?php if ($booking['status'] === 'Menunggu Pembayaran'): ?>

                                <?php if (!empty($booking['qris_url'])): ?>
                                    <img id="qrisImg"
                                        src="<?= htmlspecialchars($booking['qris_url']) ?>"
                                        alt="QRIS Pembayaran"
                                        style="width:210px; height:210px; object-fit:contain; margin:0 auto; display:block; border-radius:8px;"
                                        onerror="this.style.display='none'; document.getElementById('qrisImgErr').style.display='flex';">
                                    <div id="qrisImgErr" style="display:none; width:210px; height:210px; margin:0 auto; background:#fff3cd; border:1px dashed #f59e0b; border-radius:8px; flex-direction:column; align-items:center; justify-content:center; gap:8px; font-size:0.78rem; color:#92400e; text-align:center; padding:16px;">
                                        ⚠️ Gambar QRIS gagal dimuat.<br>Coba refresh halaman.
                                    </div>

                                <?php elseif (!empty($booking['qris_error'])): ?>
                                    <div style="width:210px; min-height:100px; margin:0 auto; background:#fff3cd; border:1px dashed #f59e0b; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:8px; font-size:0.78rem; color:#92400e; text-align:center; padding:16px;">
                                        ⚠️ <?= htmlspecialchars($booking['qris_error']) ?>
                                        <a href="konfirmasi.php?kode=<?= urlencode($booking['kode']) ?>" style="color:#1a5c38; font-weight:700; text-decoration:none;">↻ Refresh Sekarang</a>
                                    </div>

                                <?php else: ?>
                                    <div style="width:210px; height:210px; margin:0 auto; background:#f1f5f9; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:10px;">
                                        <div style="width:32px; height:32px; border:3px solid #e2e8f0; border-top-color:#1a5c38; border-radius:50%; animation:spin 0.8s linear infinite;"></div>
                                        <span style="font-size:0.75rem; color:#64748b;">Memuat QRIS...</span>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($booking['qris_expired'])): ?>
                                    <p style="text-align:center; font-size:0.72rem; color:#64748b; margin-top:8px;">
                                        ⏱ Berlaku hingga: <strong><?= htmlspecialchars($booking['qris_expired']) ?></strong>
                                    </p>
                                <?php endif; ?>

                            <?php else: ?>
                                <div id="qrcode" style="display:flex; justify-content:center; align-items:center; margin:0 auto;"></div>
                            <?php endif; ?>

                            <span class="qr-code-val">#<?= htmlspecialchars($booking['kode']) ?></span>
                        </div>

                        <p class="qr-sub-title" style="margin-top:10px; font-weight:700; font-size:0.8rem; color:var(--text-mid);">
                            <?php
                            if ($booking['status'] === 'Menunggu Pembayaran') echo 'Bayar sesuai nominal tagihan — status tiket akan update otomatis';
                            elseif ($booking['status'] === 'Menunggu Verifikasi') echo 'Tunjukkan ke petugas loket untuk verifikasi data (ACC)';
                            elseif ($booking['status'] === 'Diterima') echo 'Scan ke petugas setelah selesai mendaki';
                            else echo 'Perjalanan Anda telah selesai di-record';
                            ?>
                        </p>
                    </div>

                    <div style="padding: 0 28px 24px; display: flex; flex-direction: column; align-items: center; gap: 12px;">
                        <?php if ($booking['status'] === 'Menunggu Pembayaran'): ?>

                            <!-- STATUS INDICATOR REAL-TIME -->
                            <div id="paymentStatusBar" style="width:100%; max-width:400px; padding:12px 16px; background:rgba(234,179,8,0.1); border:1px solid rgba(234,179,8,0.4); border-radius:10px; display:flex; align-items:center; gap:10px;">
                                <span id="statusDot" style="width:10px; height:10px; border-radius:50%; background:#f59e0b; display:inline-block; animation:pulse-dot 1.4s ease-in-out infinite; flex-shrink:0;"></span>
                                <span id="statusText" style="font-size:0.82rem; font-weight:600; color:#92400e;">
                                    Menunggu pembayaran... sistem mengecek otomatis
                                </span>
                            </div>

                            <!-- TIMER COUNTDOWN 15 MENIT -->
                            <div id="timerBox" style="width:100%; max-width:400px; text-align:center; font-size:0.8rem; color:#6b7280; font-weight:500;">
                                ⏱ Batas waktu bayar: <span id="timerCountdown" style="font-weight:800; color:#dc2626;">15:00</span>
                            </div>

                            <!-- TOMBOL MANUAL SYNC -->
                            <form method="POST" action="" style="width: 100%; max-width: 400px;" id="syncForm">
                                <button type="submit" name="konfirmasi_bayar" id="btnSync"
                                    style="width:100%; padding:13px; background:#1a5c38; color:#ffffff; border:none; border-radius:10px; font-weight:800; font-size:0.88rem; cursor:pointer; transition:0.2s; box-shadow:0 4px 12px rgba(26,92,56,0.2); display:flex; align-items:center; justify-content:center; gap:8px;">
                                    <svg id="syncIcon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="1 4 1 10 7 10" />
                                        <path d="M3.51 15a9 9 0 1 0 .49-4.93" />
                                    </svg>
                                    <span id="syncLabel">Cek Status Pembayaran</span>
                                </button>
                            </form>

                            <!-- TOMBOL CANCEL -->
                            <form method="POST" action="" style="width: 100%; max-width: 400px;" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan pemesanan tiket ini? Seluruh data manifes rombongan akan dihapus permanen.');">
                                <button type="submit" name="batal_beli" style="width:100%; padding:12px; background:#d63b3b; color:#ffffff; border:none; border-radius:10px; font-weight:700; font-size:0.85rem; cursor:pointer; transition:0.2s; box-shadow:0 4px 12px rgba(214,59,59,0.15);">
                                    Batalkan Pemesanan Tiket
                                </button>
                            </form>

                            <style>
                                @keyframes pulse-dot {

                                    0%,
                                    100% {
                                        opacity: 1;
                                        transform: scale(1);
                                    }

                                    50% {
                                        opacity: 0.4;
                                        transform: scale(1.3);
                                    }
                                }

                                @keyframes spin {
                                    from {
                                        transform: rotate(0deg);
                                    }

                                    to {
                                        transform: rotate(360deg);
                                    }
                                }
                            </style>

                        <?php endif; ?>
                    </div>

                    <div class="ticket-actions">
                        <button class="btn-download" onclick="handleDownload()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                <polyline points="7 10 12 15 17 10" />
                                <line x1="12" y1="15" x2="12" y2="3" />
                            </svg>
                            Download Tiket (PDF Resmi)
                        </button>
                        <button class="btn-share" onclick="handleShare()">
                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="18" cy="5" r="3" />
                                <circle cx="6" cy="12" r="3" />
                                <circle cx="18" cy="19" r="3" />
                                <line x1="8.59" y1="13.51" x2="15.42" y2="17.49" />
                                <line x1="15.41" y1="6.51" x2="8.59" y2="10.49" />
                            </svg>
                            Share dengan Rombongan
                        </button>
                    </div>

                </div>
            </div>

            <div class="info-grid">
                <div class="info-card" style="animation-delay:0.15s">
                    <div class="info-card-icon info-icon-health">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                        </svg>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Surat Keterangan Sehat</h4>
                        <p class="info-card-desc">Jangan lupa membawa salinan surat keterangan sehat dokter yang valid ke Basecamp.</p>
                    </div>
                </div>

                <div class="info-card" style="animation-delay:0.22s">
                    <div class="info-card-icon info-icon-basecamp">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                            <polyline points="9 22 9 12 15 12 15 22" />
                        </svg>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Lokasi Basecamp</h4>
                        <p class="info-card-desc"><?= htmlspecialchars($booking['pos_basecamp']) ?></p>
                    </div>
                </div>

                <div class="info-card" style="animation-delay:0.29s">
                    <div class="info-card-icon info-icon-safety">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z" />
                            <line x1="12" y1="9" x2="12" y2="13" />
                            <line x1="12" y1="17" x2="12.01" y2="17" />
                        </svg>
                    </div>
                    <div class="info-card-body">
                        <h4 class="info-card-title">Safety Briefing</h4>
                        <p class="info-card-desc">Briefing wajib pendakian dimulai setiap pagi pukul 05:00 WIB oleh tim Ranger.</p>
                    </div>
                </div>
            </div>

            <div class="back-wrap">
                <a href="index.php" class="btn-back">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="19" y1="12" x2="5" y2="12" />
                        <polyline points="12 19 5 12 12 5" />
                    </svg>
                    Kembali ke Beranda
                </a>
                <a href="gunung.php" class="btn-explore">
                    Jelajahi Gunung Lain
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="5" y1="12" x2="19" y2="12" />
                        <polyline points="12 5 19 12 12 19" />
                    </svg>
                </a>
            </div>

        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <div class="toast" id="toast"></div>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        });

        // ═══════════════════════════════════════════════════════════
        // SISTEM VERIFIKASI PEMBAYARAN TANPA WEBHOOK (Auto Polling)
        // Bekerja penuh di localhost tanpa perlu ngrok/server publik
        // ═══════════════════════════════════════════════════════════
        let statusPemesananAwal = '<?= $booking['status'] ?>';

        if (statusPemesananAwal === 'Menunggu Pembayaran') {

            // ── COUNTDOWN TIMER BERDASARKAN EXPIRED_AT QRIS ────────────────────────────
            const elTimer = document.getElementById('timerCountdown');

            // Ambil expired_at dari PHP/cache KlikQRIS
            const qrisExpiredAt = '<?= $booking['qris_expired'] ?? '' ?>';

            // Fallback kalau expired_at kosong
            let expiredTime = qrisExpiredAt ? new Date(qrisExpiredAt).getTime() : Date.now() + (15 * 60 * 1000);

            const timerInterval = setInterval(() => {
                const now = Date.now();
                let sisaDetik = Math.floor((expiredTime - now) / 1000);

                if (sisaDetik <= 0) {
                    clearInterval(timerInterval);
                    elTimer.textContent = '00:00';
                    elTimer.style.color = '#dc2626';

                    document.getElementById('timerBox').innerHTML =
                        '⚠️ Waktu pembayaran habis. Silakan <strong>batalkan</strong> dan buat pemesanan baru.';

                    clearInterval(pollingInterval);
                    setStatusBar('timeout');
                    return;
                }

                const m = String(Math.floor(sisaDetik / 60)).padStart(2, '0');
                const s = String(sisaDetik % 60).padStart(2, '0');

                elTimer.textContent = m + ':' + s;

                if (sisaDetik <= 60) {
                    elTimer.style.color = '#dc2626';
                }
            }, 1000);

            // ── HELPER: Update status bar ───────────────────────────
            function setStatusBar(state) {
                const bar = document.getElementById('paymentStatusBar');
                const dot = document.getElementById('statusDot');
                const text = document.getElementById('statusText');
                const states = {
                    waiting: {
                        bg: 'rgba(234,179,8,0.1)',
                        border: 'rgba(234,179,8,0.4)',
                        dotColor: '#f59e0b',
                        dotAnim: 'pulse-dot 1.4s ease-in-out infinite',
                        textColor: '#92400e',
                        msg: 'Menunggu pembayaran... sistem mengecek otomatis'
                    },
                    checking: {
                        bg: 'rgba(59,130,246,0.1)',
                        border: 'rgba(59,130,246,0.4)',
                        dotColor: '#3b82f6',
                        dotAnim: 'pulse-dot 0.6s ease-in-out infinite',
                        textColor: '#1e40af',
                        msg: 'Sedang mengecek status ke server KlikQRIS...'
                    },
                    success: {
                        bg: 'rgba(34,197,94,0.1)',
                        border: 'rgba(34,197,94,0.4)',
                        dotColor: '#22c55e',
                        dotAnim: 'none',
                        textColor: '#166534',
                        msg: '✓ Pembayaran terdeteksi! Memuat tiket...'
                    },
                    error: {
                        bg: 'rgba(239,68,68,0.1)',
                        border: 'rgba(239,68,68,0.4)',
                        dotColor: '#ef4444',
                        dotAnim: 'none',
                        textColor: '#7f1d1d',
                        msg: 'Gagal terhubung ke server. Coba klik tombol di bawah.'
                    },
                    timeout: {
                        bg: 'rgba(239,68,68,0.1)',
                        border: 'rgba(239,68,68,0.4)',
                        dotColor: '#ef4444',
                        dotAnim: 'none',
                        textColor: '#7f1d1d',
                        msg: '⏱ Waktu pembayaran habis.'
                    },
                };
                const s = states[state] || states.waiting;
                bar.style.background = s.bg;
                bar.style.border = '1px solid ' + s.border;
                dot.style.background = s.dotColor;
                dot.style.animation = s.dotAnim;
                text.style.color = s.textColor;
                text.textContent = s.msg;
            }

            // ── AUTO POLLING TIAP 4 DETIK ──────────────────────────
            // Setiap polling → cek DB dulu, kalau PENDING otomatis hit API KlikQRIS
            let pollingCount = 0;
            const pollingInterval = setInterval(() => {
                pollingCount++;
                setStatusBar('checking');

                fetch(`konfirmasi.php?kode=<?= urlencode($kode_booking) ?>&ajax_check=true`)
                    .then(r => r.json())
                    .then(res => {
                        if (res.status === 'SUCCESS') {
                            clearInterval(pollingInterval);
                            clearInterval(timerInterval);
                            setStatusBar('success');
                            setTimeout(() => window.location.reload(), 1200);
                        } else if (res.status === 'EXPIRED') {
                            clearInterval(pollingInterval);
                            clearInterval(timerInterval);
                            setStatusBar('timeout');
                            document.getElementById('timerBox').innerHTML =
                                '⚠️ QRIS sudah kedaluwarsa. Silakan <strong>batalkan</strong> dan buat pemesanan baru.';
                        } else {
                            setStatusBar('waiting');
                        }
                    })
                    .catch(() => {
                        setStatusBar('error');
                    });
            }, 4000);

            // ── TOMBOL MANUAL SYNC: Loading state ──────────────────
            const syncForm = document.getElementById('syncForm');
            const btnSync = document.getElementById('btnSync');
            const syncIcon = document.getElementById('syncIcon');
            const syncLabel = document.getElementById('syncLabel');

            if (syncForm) {
                syncForm.addEventListener('submit', () => {
                    btnSync.disabled = true;
                    btnSync.style.opacity = '0.75';
                    syncIcon.style.animation = 'spin 0.8s linear infinite';
                    syncLabel.textContent = 'Mengecek ke KlikQRIS...';
                    setStatusBar('checking');
                });
            }
        }

        window.addEventListener('load', () => {
            let status = '<?= $booking['status'] ?>';
            let kodeBooking = '<?= $booking["kode"] ?>';

            if (status !== 'Menunggu Pembayaran') {
                let qrText = '';
                if (status === 'Menunggu Verifikasi') {
                    qrText = 'CHECKIN_' + kodeBooking;
                } else if (status === 'Diterima') {
                    qrText = 'CHECKOUT_' + kodeBooking;
                } else {
                    qrText = kodeBooking;
                }

                const qrContainer = document.getElementById('qrcode');
                if (qrContainer) {
                    qrContainer.innerHTML = '';
                    new QRCode(qrContainer, {
                        text: qrText,
                        width: 140,
                        height: 140,
                        colorDark: '#0d3b22',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.H
                    });
                }
            }
        });

        function showToast(msg, type = 'success') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast show ' + type;
            clearTimeout(t._t);
            t._t = setTimeout(() => t.className = 'toast', 3000);
        }

        function handleDownload() {
            showToast('⏳ Menyiapkan berkas dokumen PDF standar A4…', 'info');
            const targetElement = document.getElementById('printableTicketArea');
            const actionButtons = targetElement.querySelector('.ticket-actions');
            const paymentForm = targetElement.querySelector('div[style*="flex-direction: column"]');

            if (actionButtons) actionButtons.style.visibility = 'hidden';
            if (paymentForm) paymentForm.style.display = 'none';

            setTimeout(() => {
                html2canvas(targetElement, {
                    scale: 2.5,
                    useCORS: true,
                    backgroundColor: '#ffffff',
                    logging: false
                }).then(canvas => {
                    const imgData = canvas.toDataURL('image/png');
                    const statusFile = '<?= $booking["status"] ?>' === 'Diterima' ? 'NDAKIYUK_TIKET_RESMI' : 'NDAKIYUK_INVOICE_BILL';
                    const namaFile = statusFile + '_#<?= $booking["kode"] ?>.pdf';

                    const opsiPdf = {
                        margin: [0, 10, 10, 10],
                        filename: namaFile,
                        image: {
                            type: 'jpeg',
                            quality: 1.0
                        },
                        html2canvas: {
                            scale: 1
                        },
                        jsPDF: {
                            unit: 'mm',
                            format: 'a4',
                            orientation: 'portrait'
                        }
                    };

                    html2pdf().set(opsiPdf).from(canvas).toPdf().get('pdf').then(function(pdf) {
                        const pdfWidth = pdf.internal.pageSize.getWidth() - 20;
                        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;

                        pdf.deletePage(1);
                        pdf.addPage('a4', 'portrait');
                        pdf.addImage(imgData, 'PNG', 10, 0, pdfWidth, pdfHeight);

                        const blobPDF = pdf.output('bloburl');
                        window.open(blobPDF, '_blank');

                    }).then(() => {
                        if (document.getElementById('statusAlertBox')) document.getElementById('statusAlertBox').style.display = 'block';
                        showToast('✓ Berhasil memuat dokumen PDF di device!', 'success');
                        if (actionButtons) actionButtons.style.visibility = 'visible';
                        if (paymentForm && '<?= $booking["status"] ?>' === 'Menunggu Pembayaran') paymentForm.style.display = 'flex';
                    });

                }).catch(err => {
                    showToast('❌ Gagal mengekspor berkas dokumen PDF.', 'warn');
                    if (actionButtons) actionButtons.style.visibility = 'visible';
                    if (paymentForm && '<?= $booking["status"] ?>' === 'Menunggu Pembayaran') paymentForm.style.display = 'flex';
                });
            }, 400);
        }

        function handleShare() {
            if (navigator.share) {
                navigator.share({
                    title: 'Tiket NdakiYuk – <?= htmlspecialchars($booking['gunung']) ?>',
                    text: 'Booking #<?= htmlspecialchars($booking['kode']) ?> sudah dikonfirmasi!',
                    url: window.location.href
                }).catch(() => {});
            } else {
                navigator.clipboard.writeText(window.location.href)
                    .then(() => showToast('✓ Link disalin ke clipboard!', 'success'))
                    .catch(() => showToast('Salin URL halaman ini untuk berbagi.', 'info'));
            }
        }
    </script>
</body>

</html>