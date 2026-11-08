<?php
// admin/proses_persetujuan.php
session_start();
include "../config/koneksi.php";

// PROTEKSI OTORITAS SEKURITI ADMIN GATEWAY
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) {
    header("Location: ../login.php");
    exit;
}

$id_pengajuan = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status_keputusan = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

if ($id_pengajuan <= 0 || !in_array($status_keputusan, ['Diterima', 'Ditolak'])) {
    die("Parameter keputusan verifikasi tidak valid.");
}

// Ambil data pengajuan usulan mitra
$query_p = mysqli_query($conn, "SELECT * FROM pengajuan_update_gunung WHERE id_pengajuan = '$id_pengajuan' AND status_pengajuan = 'Menunggu Verifikasi' LIMIT 1");
$pengajuan = mysqli_fetch_assoc($query_p);

if (!$pengajuan) {
    die("Data pengajuan tidak ditemukan atau sudah diproses sebelumnya.");
}

$id_gunung = $pengajuan['id_gunung'];

if ($status_keputusan == 'Diterima') {
    // ---------------------------------------------------------------------
    // 1. SINKRONISASI DATA BARU KE TABEL GUNUNG UTAMA (FIXED: info_terbaru dibuang)
    // ---------------------------------------------------------------------
    $update_gunung = mysqli_query($conn, "
        UPDATE gunung 
        SET 
            id_kategori = '{$pengajuan['id_kategori']}',
            tinggi = '{$pengajuan['tinggi']}',
            status_gunung = '{$pengajuan['status_gunung']}',
            estimasi_pendakian = '{$pengajuan['estimasi_pendakian']}',
            kuota_harian = '{$pengajuan['kuota_harian']}',
            harga_tiket = '{$pengajuan['harga_tiket']}',
            pos_perizinan = '{$pengajuan['pos_perizinan']}',
            fasilitas_gunung = '{$pengajuan['fasilitas']}'
        WHERE id_gunung = '$id_gunung'
    ");

    if (!$update_gunung) {
        die("Gagal melakukan sinkronisasi ke tabel gunung utama: " . mysqli_error($conn));
    }

    // ---------------------------------------------------------------------
    // 2. SINKRONISASI REGULASI LOGISTIK WAJIB KELOMPOK
    // ---------------------------------------------------------------------
    // Bersihkan dulu regulasi barang bawaan yang lama milik gunung ini
    mysqli_query($conn, "DELETE FROM gunung_logistik WHERE id_gunung = '$id_gunung'");

    // Ambil list logistik yang diusulkan mitra dari tabel pengajuan_update_logistik
    $query_l = mysqli_query($conn, "SELECT id_logistik, status_wajib FROM pengajuan_update_logistik WHERE id_pengajuan = '$id_pengajuan'");
    
    if ($query_l) {
        while ($logistik = mysqli_fetch_assoc($query_l)) {
            mysqli_query($conn, "
                INSERT INTO gunung_logistik (id_gunung, id_logistik, status_wajib)
                VALUES ('$id_gunung', '{$logistik['id_logistik']}', '{$logistik['status_wajib']}')
            ");
        }
    }

    // Update status pengajuan menjadi Diterima di tabel log pengajuan
    mysqli_query($conn, "UPDATE pengajuan_update_gunung SET status_pengajuan = 'Diterima', updated_at = NOW() WHERE id_pengajuan = '$id_pengajuan'");

} else {
    // Jika keputusan ditolak, cukup ubah status pengajuan menjadi Ditolak tanpa mengubah tabel gunung utama
    mysqli_query($conn, "UPDATE pengajuan_update_gunung SET status_pengajuan = 'Ditolak', updated_at = NOW() WHERE id_pengajuan = '$id_pengajuan'");
}

// Kembalikan halaman ke dashboard kontrol admin
header("Location: dashboard.php");
exit;
?>