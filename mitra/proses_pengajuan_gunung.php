<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

if (!isset($_POST['ajukan'])) {
    header("Location: gunung_saya.php");
    exit;
}

$id_mitra = $_SESSION['id_user'];
$id_gunung = (int) $_POST['id_gunung'];

$harga_tiket = (int) $_POST['harga_tiket'];
$id_kategori = (int) $_POST['id_kategori'];
$tinggi = (int) $_POST['tinggi'];
$status_gunung = mysqli_real_escape_string($conn, $_POST['status_gunung']);
$estimasi_pendakian = mysqli_real_escape_string($conn, $_POST['estimasi_pendakian']);
$kuota_harian = (int) $_POST['kuota_harian'];
$pos_perizinan = mysqli_real_escape_string($conn, $_POST['pos_perizinan']);
$fasilitas = mysqli_real_escape_string($conn, $_POST['fasilitas']);
$info_terbaru = mysqli_real_escape_string($conn, $_POST['info_terbaru'] ?? '');

// Logistik sekarang bentuknya array dari checkbox
$logistik_dipilih = $_POST['logistik'] ?? [];
$status_logistik = $_POST['status_logistik'] ?? [];

// Pastikan gunung ini memang milik mitra yang login
$cek_gunung = mysqli_query($conn, "
    SELECT *
    FROM gunung_mitra
    WHERE id_user = '$id_mitra'
    AND id_gunung = '$id_gunung'
");

if (!$cek_gunung) {
    die("Query cek gunung error: " . mysqli_error($conn));
}

if (mysqli_num_rows($cek_gunung) == 0) {
    die("Anda tidak memiliki akses untuk mengubah data gunung ini.");
}

// Cek apakah masih ada pengajuan pending
$cek_pending = mysqli_query($conn, "
    SELECT id_pengajuan
    FROM pengajuan_update_gunung
    WHERE id_mitra = '$id_mitra'
    AND id_gunung = '$id_gunung'
    AND status_pengajuan = 'Menunggu Verifikasi'
");

if (!$cek_pending) {
    die("Query cek pending error: " . mysqli_error($conn));
}

if (mysqli_num_rows($cek_pending) > 0) {
    die("Masih ada pengajuan yang menunggu verifikasi admin.");
}

mysqli_begin_transaction($conn);

try {
    /*
        Simpan data utama pengajuan.
        Catatan:
        - fasilitas tetap masuk ke tabel pengajuan_update_gunung
        - logistik tidak lagi disimpan sebagai teks utama
        - detail logistik disimpan ke tabel pengajuan_update_logistik
    */
    $query = mysqli_query($conn, "
        INSERT INTO pengajuan_update_gunung (
            id_gunung,
            id_mitra,
            harga_tiket,
            id_kategori,
            tinggi,
            status_gunung,
            estimasi_pendakian,
            kuota_harian,
            pos_perizinan,
            fasilitas,
            logistik,
            info_terbaru,
            status_pengajuan
        ) VALUES (
            '$id_gunung',
            '$id_mitra',
            '$harga_tiket',
            '$id_kategori',
            '$tinggi',
            '$status_gunung',
            '$estimasi_pendakian',
            '$kuota_harian',
            '$pos_perizinan',
            '$fasilitas',
            NULL,
            '$info_terbaru',
            'Menunggu Verifikasi'
        )
    ");

    if (!$query) {
        throw new Exception("Gagal menyimpan pengajuan: " . mysqli_error($conn));
    }

    $id_pengajuan = mysqli_insert_id($conn);

    // Simpan detail logistik yang dicentang
    foreach ($logistik_dipilih as $id_logistik) {
        $id_logistik = (int) $id_logistik;

        $status = $status_logistik[$id_logistik] ?? 'Wajib';
        $status = mysqli_real_escape_string($conn, $status);

        $insert_logistik = mysqli_query($conn, "
            INSERT INTO pengajuan_update_logistik (
                id_pengajuan,
                id_logistik,
                status_wajib
            ) VALUES (
                '$id_pengajuan',
                '$id_logistik',
                '$status'
            )
        ");

        if (!$insert_logistik) {
            throw new Exception("Gagal menyimpan logistik pengajuan: " . mysqli_error($conn));
        }
    }

    mysqli_commit($conn);

    header("Location: gunung_saya.php?pengajuan=berhasil");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conn);
    die($e->getMessage());
}
?>