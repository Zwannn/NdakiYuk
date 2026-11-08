<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_POST['proses'])) {
    header("Location: scan.php");
    exit;
}

$id_mitra = $_SESSION['id_user'];
$id_pemesanan = (int) $_POST['id_pemesanan'];
$aksi = mysqli_real_escape_string($conn, $_POST['aksi']);

// Ambil pemesanan dan pastikan milik gunung mitra login
$query = mysqli_query($conn, "
    SELECT 
        p.id_pemesanan,
        p.kode_booking,
        p.status_pemesanan,
        p.status_checkin,
        p.status_checkout
    FROM pemesanan p
    JOIN gunung_mitra gm ON p.id_gunung = gm.id_gunung
    WHERE p.id_pemesanan = '$id_pemesanan'
    AND gm.id_user = '$id_mitra'
    LIMIT 1
");

if (!$query) {
    die("Query proses scan error: " . mysqli_error($conn));
}

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data booking tidak ditemukan atau bukan untuk gunung yang Anda kelola.");
}

$kode_booking = $data['kode_booking'];

// ─── AMAN: PROSES EKSEKUSI CHECK-IN ───
if ($aksi == 'checkin') {

    // Izinkan status 'Diterima' atau 'Menunggu Verifikasi' selama dia belum pernah check-in
    $status_valid_pemesanan = ['Diterima', 'Menunggu Verifikasi'];
    
    // PERBAIKAN SINTAKS: Typo "!in_gradient =" dibersihkan total menjadi fungsi in_array murni yang valid
    if (!in_array($data['status_pemesanan'], $status_valid_pemesanan) || $data['status_checkin'] != 'Belum Check-in') {
        die("Booking ini tidak valid untuk proses Check-in.");
    }

    $update = mysqli_query($conn, "
        UPDATE pemesanan
        SET 
            status_pemesanan = 'Diterima',
            status_checkin = 'Sudah Check-in',
            waktu_checkin = NOW()
        WHERE id_pemesanan = '$id_pemesanan'
    ");

    if (!$update) {
        die("Gagal update Check-in: " . mysqli_error($conn));
    }

    header("Location: scan.php?kode=" . urlencode($kode_booking) . "&status=checkin_berhasil");
    exit;

// ─── AMAN: PROSES EKSEKUSI CHECK-OUT ───
} elseif ($aksi == 'checkout') {

    // Izinkan check-out untuk data lama yang statusnya 'Diterima' maupun 'Selesai' selama status_checkout belum 'Sudah Check-out'
    $status_valid_checkout = ['Diterima', 'Selesai'];

    if (!in_array($data['status_pemesanan'], $status_valid_checkout) || $data['status_checkin'] != 'Sudah Check-in' || $data['status_checkout'] == 'Sudah Check-out') {
        die("Booking ini tidak valid untuk proses Check-out.");
    }

    $update = mysqli_query($conn, "
        UPDATE pemesanan
        SET 
            status_pemesanan = 'Selesai',
            status_checkout = 'Sudah Check-out',
            waktu_checkout = NOW()
        WHERE id_pemesanan = '$id_pemesanan'
    ");

    if (!$update) {
        die("Gagal update Check-out: " . mysqli_error($conn));
    }

    header("Location: scan.php?kode=" . urlencode($kode_booking) . "&status=checkout_berhasil");
    exit;

} else {
    die("Aksi tidak valid.");
}
?>