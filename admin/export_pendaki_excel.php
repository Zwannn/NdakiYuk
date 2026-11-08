<?php
// admin/export_pendaki_excel.php
session_start();
include "../config/koneksi.php";

// PROTEKSI OTORITAS SEKURITI ADMIN GATEWAY
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) {
    die("Akses Ilegal ditolak.");
}

// Handler Filter Gunung (Jika excel didownload saat posisi sedang difilter)
$filter_gunung = isset($_GET['gunung']) ? mysqli_real_escape_string($conn, $_GET['gunung']) : '';
$where_clause = "";
$nama_file_suffix = "Global";

if (!empty($filter_gunung)) {
    $where_clause = " WHERE p.id_gunung = '$filter_gunung' ";
    $nama_file_suffix = "Filtered_Gunung_ID_" . $filter_gunung;
}

// TENTUKAN HEADER MIME TYPE UNTUK MENSTRIGGER DOWNLOAD EXCEL VIA BROWSER
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Manifes_Pendaki_NdakiYuk_" . $nama_file_suffix . "_" . date('Ymd_His') . ".xls");
header("Pragma: no-cache");
header("Expires: 0");

// AMBIL SELURUH DATA TANPA LIMIT PAGINATION
$query_all = mysqli_query($conn, "
    SELECT 
        dp.*, 
        p.kode_booking, p.pos_masuk, p.pos_keluar, p.tanggal_naik, p.tanggal_turun, p.status_checkin, p.status_checkout,
        g.nama_gunung, g.lokasi
    FROM detail_pendaki dp
    JOIN pemesanan p ON dp.id_pemesanan = p.id_pemesanan
    JOIN gunung g     ON p.id_gunung = g.id_gunung
    $where_clause
    ORDER BY dp.id_detail DESC
");
?>

<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta charset="UTF-8">
    <style>
        .title { font-family: sans-serif; font-size: 16px; font-weight: bold; text-align: center; }
        .subtitle { font-family: sans-serif; font-size: 11px; text-align: center; color: #555; }
        table { border-collapse: collapse; width: 100%; font-family: sans-serif; font-size: 12px; }
        th { background-color: #1a5c38; color: #ffffff; border: 1px solid #000000; padding: 8px; text-align: center; }
        td { border: 1px solid #000000; padding: 6px; }
        .text-center { text-align: center; }
        /* Memaksa Excel membaca NIK sebagai Text String agar angka nol didepan atau angka panjang tidak dikonversi ke Scientific Notation (E+) */
        .string-cell { mso-number-format:"\@"; }
    </style>
</head>
<body>

    <div class="title">LAPORAN DATA MANIFES KESELURUHAN PENDAKI</div>
    <div class="subtitle">Sistem Integrasi NdakiYuk V2 - Dicetak pada: <?= date('d-m-Y H:i:s'); ?> WIB</div>
    <br>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Lengkap Anggota</th>
                <th>Kode Booking</th>
                <th>Target Destinasi</th>
                <th>Umur</th>
                <th>Jenis Kelamin</th>
                <th>Nomor KTP / NIK (Original)</th>
                <th>No. Handphone Aktif</th>
                <th>Tanggal Naik</th>
                <th>Tanggal Turun</th>
                <th>Status Lapangan</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            if (mysqli_num_rows($query_all) > 0) {
                while ($row = mysqli_fetch_assoc($query_all)) {
                    // Penentuan Log Status Lapangan
                    $status = "Belum Naik";
                    if ($row['status_checkin'] == 'Sudah Check-in' && $row['status_checkout'] == 'Belum Check-out') {
                        $status = (strtotime($row['tanggal_turun']) < strtotime(date('Y-m-d'))) ? "OVERDUE SAR" : "Di Gunung";
                    } elseif ($row['status_checkout'] == 'Sudah Check-out') {
                        $status = "Sudah Kembali";
                    }
                    
                    echo "<tr>";
                    echo "<td class='text-center'>".$no++."</td>";
                    echo "<td>".htmlspecialchars($row['nama_pendaki'])."</td>";
                    echo "<td class='text-center'>#".htmlspecialchars($row['kode_booking'])."</td>";
                    echo "<td>".htmlspecialchars($row['nama_gunung'])."</td>";
                    echo "<td class='text-center'>".(!empty($row['umur']) ? $row['umur'] : '—')."</td>";
                    echo "<td class='text-center'>".(!empty($row['jenis_kelamin']) ? $row['jenis_kelamin'] : '—')."</td>";
                    // Class string-cell menjaga angka KTP 16 digit tidak hancur berantakan di Excel
                    echo "<td class='string-cell'>'".$row['nik']."</td>"; 
                    echo "<td class='string-cell'>".$row['no_hp']."</td>";
                    echo "<td class='text-center'>".$row['tanggal_naik']."</td>";
                    echo "<td class='text-center'>".$row['tanggal_turun']."</td>";
                    echo "<td class='text-center'>".$status."</td>";
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='11' style='text-align:center; font-style:italic;'>Tidak ada rekaman data pendaki.</td></tr>";
            }
            ?>
        </tbody>
    </table>

</body>
</html>