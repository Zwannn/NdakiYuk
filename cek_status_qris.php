<?php
// cek_status_qris.php – Endpoint polling status pembayaran QRIS KlikQRIS
session_start();
include "config/koneksi.php";

header('Content-Type: application/json');

// Hanya izinkan user yang sudah login
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$kode_booking = mysqli_real_escape_string($conn, $_GET['kode'] ?? '');

if (empty($kode_booking)) {
    echo json_encode(['success' => false, 'message' => 'Kode booking tidak valid']);
    exit;
}

// CONFIG – harus sama persis dengan konfirmasi.php
define('DEV_MODE', false);
$merchant_id = "178038814280";
$api_key     = "7dxKpM1L4ja8FLllgDIj4q6phb0rboW4wSy5NSXx";

$cache_dir  = "qris_cache/";
$cache_file = $cache_dir . "qris_invoice_" . $kode_booking . ".json";

// ── 1. Validasi pemesanan milik user yang login & statusnya masih menunggu ──
$id_user = $_SESSION['id_user'];
$qCheck  = mysqli_query($conn, "
    SELECT id_pemesanan, status_pemesanan 
    FROM pemesanan 
    WHERE kode_booking = '$kode_booking' 
      AND id_user = '$id_user'
");

if (mysqli_num_rows($qCheck) === 0) {
    echo json_encode(['success' => false, 'message' => 'Pemesanan tidak ditemukan']);
    exit;
}

$pemesanan = mysqli_fetch_assoc($qCheck);

// Kalau sudah bukan "Menunggu Pembayaran", berarti sudah dibayar sebelumnya
if ($pemesanan['status_pemesanan'] !== 'Menunggu Pembayaran') {
    echo json_encode(['success' => true, 'paid' => true, 'message' => 'Sudah dibayar']);
    exit;
}

$id_pemesanan = $pemesanan['id_pemesanan'];

// ── 2. Ambil klikqris_order_id dari cache JSON ───────────────────────────
if (!file_exists($cache_file)) {
    echo json_encode(['success' => false, 'paid' => false, 'message' => 'Cache invoice tidak ditemukan']);
    exit;
}

$cache_data       = json_decode(file_get_contents($cache_file), true);
$klikqris_order_id = $cache_data['klikqris_order_id'] ?? '';

if (empty($klikqris_order_id)) {
    echo json_encode(['success' => false, 'paid' => false, 'message' => 'Order ID tidak valid di cache']);
    exit;
}

// ── 3. Cek status ke API KlikQRIS (atau bypass di DEV_MODE) ─────────────
$status_qris = '';

if (DEV_MODE) {
    // DEV_MODE: jangan auto-paid di sini, biarkan tombol bypass yang handle
    // Polling hanya aktif untuk pembayaran QRIS real
    echo json_encode(['success' => true, 'paid' => false, 'message' => 'DEV_MODE aktif – gunakan tombol bypass']);
    exit;
}

$url_status = "https://klikqris.com/api/qrisv2/status/" . $merchant_id . "/" . $klikqris_order_id;

$curl = curl_init();
curl_setopt_array($curl, [
    CURLOPT_URL            => $url_status,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST  => 'GET',
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => [
        'x-api-key: ' . $api_key,
        'id_merchant: ' . $merchant_id
    ],
]);

$response = curl_exec($curl);
$curl_err  = curl_error($curl);
curl_close($curl);

if ($curl_err) {
    echo json_encode(['success' => false, 'paid' => false, 'message' => 'cURL error: ' . $curl_err]);
    exit;
}

$result = json_decode($response, true);

if (!isset($result['status']) || $result['status'] != true) {
    echo json_encode(['success' => false, 'paid' => false, 'message' => 'Respons API tidak valid']);
    exit;
}

$status_qris = strtoupper($result['data']['status'] ?? '');

// ── 4. Kalau PAID/SUCCESS, update database ───────────────────────────────
if ($status_qris === 'PAID' || $status_qris === 'SUCCESS') {

    // Cegah double-insert: cek apakah pembayaran sudah pernah diinsert
    $qCekBayar = mysqli_query($conn, "SELECT id_pembayaran FROM pembayaran WHERE id_pemesanan = '$id_pemesanan'");
    if (mysqli_num_rows($qCekBayar) === 0) {

        $metode       = "QRIS API KlikQRIS";
        $nama_pengirim = mysqli_real_escape_string($conn, $_SESSION['nama_lengkap'] ?? 'User');

        $insertBayar = mysqli_query($conn, "
            INSERT INTO pembayaran (id_pemesanan, metode_pembayaran, nama_pengirim, bukti_pembayaran, status_verifikasi)
            VALUES ('$id_pemesanan', '$metode', '$nama_pengirim', NULL, 'Valid')
        ");

        if (!$insertBayar) {
            echo json_encode(['success' => false, 'paid' => false, 'message' => 'Gagal insert pembayaran: ' . mysqli_error($conn)]);
            exit;
        }
    }

    // Update status pemesanan
    mysqli_query($conn, "
        UPDATE pemesanan 
        SET status_pemesanan = 'Menunggu Verifikasi' 
        WHERE id_pemesanan = '$id_pemesanan'
    ");

    // Hapus cache invoice
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }

    echo json_encode(['success' => true, 'paid' => true, 'message' => 'Pembayaran terverifikasi']);
    exit;
}

// ── 5. Belum dibayar ─────────────────────────────────────────────────────
echo json_encode(['success' => true, 'paid' => false, 'status_qris' => $status_qris]);
