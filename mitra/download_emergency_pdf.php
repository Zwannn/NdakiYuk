<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_mitra = $_SESSION['id_user'];
$id_pemesanan = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_pemesanan <= 0) {
    die("ID Pemesanan tidak valid.");
}

// Tarik data lengkap manifes untuk dicetak ke PDF
$query = mysqli_query($conn, "
    SELECT 
        p.*, g.nama_gunung, g.lokasi, g.tinggi,
        u.username, u.email, up.nama_lengkap, up.no_hp
    FROM pemesanan p
    JOIN gunung_mitra gm ON p.id_gunung = gm.id_gunung
    JOIN gunung g ON p.id_gunung = g.id_gunung
    JOIN users u ON p.id_user = u.id_user
    LEFT JOIN user_profile up ON u.id_user = up.id_user
    WHERE p.id_pemesanan = '$id_pemesanan' AND gm.id_user = '$id_mitra'
    LIMIT 1
");

$data = mysqli_fetch_assoc($query);
if (!$data) {
    die("Berkas data manifes tidak ditemukan.");
}

// Ambil list rombongan anggota
$query_anggota = mysqli_query($conn, "SELECT nama_pendaki, nik, no_hp FROM detail_pendaki WHERE id_pemesanan = '$id_pemesanan' ORDER BY id_detail ASC");

// DOWNLOAD ATAU INCLUDE FPDF LIBRARY (PASTIKAN FILE fpdf.php SUDAH ADA DI ASSETS/PLUGINS)
// Jika belum ada, kamu bisa taruh file fpdf.php di folder assets/fpdf/
require('../assets/fpdf/fpdf.php');

class PDF_Emergency_Manifest extends FPDF {
    function Header() {
        // Red Emergency Header Accent
        $this->SetFillColor(220, 53, 69);
        $this->Rect(0, 0, 210, 15, 'F');
        
        $this->Ln(10);
        $this->SetFont('Arial', 'B', 16);
        $this->SetTextColor(15, 36, 25);
        $this->Cell(0, 7, 'NDAKIYUK - EMERGENCY OPERATION MANIFEST', 0, 1, 'C');
        $this->SetFont('Arial', 'I', 9);
        $this->SetTextColor(100, 100, 100);
        $this->Cell(0, 5, 'Dokumen Khusus Penanganan Evakuasi, Hukum, dan Operasional SAR Lapangan', 0, 1, 'C');
        $this->Ln(5);
        
        // Garis Pembatas Double Horizontal
        $this->SetDrawColor(220, 53, 69);
        $this->SetLineWidth(0.8);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(120, 120, 120);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Cell(0, 6, 'Halaman ' . $this->PageNo() . ' | Dokumen dicetak pada tanggal: ' . date('d M Y H:i:s'), 0, 0, 'L');
        $this->Cell(0, 6, 'STATUS DATA TERCATAT: RAHASIA / EMERGENCY ONLY', 0, 1, 'R');
    }
}

// Inisialisasi Kertas Ukuran A4 Vertikal
$pdf = new PDF_Emergency_Manifest('P', 'mm', 'A4');
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 25);

// BLOCK 1: INFORMASI GRUP & TIM JALUR
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(238, 247, 242);
$pdf->SetTextColor(13, 59, 34);
$pdf->Cell(0, 7, ' RESUME OTORITAS WILAYAH PERIZINAN', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell(45, 6, 'Kawasan Gunung', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 6, $data['nama_gunung'] . ' (' . $data['tinggi'] . ' MDPL)', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 6, 'Pintu Masuk Gerbang', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->Cell(0, 6, 'Pos ' . ($data['pos_masuk'] ?? 'Utama'), 0, 1);
$pdf->Cell(45, 6, 'Rencana Naik', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->Cell(0, 6, date('d M Y', strtotime($data['tanggal_naik'])), 0, 1);
$pdf->Cell(45, 6, 'Rencana Turun', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->Cell(0, 6, ($data['tanggal_turun'] ? date('d M Y', strtotime($data['tanggal_turun'])) : '—'), 0, 1);
$pdf->Cell(45, 6, 'Status Check-In Pos', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->Cell(0, 6, $data['status_checkin'] ?? 'Belum Naik', 0, 1);
$pdf->Ln(4);

// BLOCK 2: INFORMASI KETUA ROMBONGAN
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(13, 59, 34);
$pdf->Cell(0, 7, ' DETAIL PENANGGUNG JAWAB UTAMA (KETUA)', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(30, 30, 30);
$pdf->Cell(45, 6, 'Nama Lengkap Ketua', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 6, ($data['nama_lengkap'] ?? $data['username']), 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(45, 6, 'Kode Booking Tiket', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->SetFont('Arial', 'B', 10); $pdf->SetTextColor(185, 28, 28); $pdf->Cell(0, 6, '#' . $data['kode_booking'], 0, 1);
$pdf->SetFont('Arial', '', 10); $pdf->SetTextColor(30, 30, 30);
$pdf->Cell(45, 6, 'Nomor Handphone', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->Cell(0, 6, ($data['no_hp'] ?? '—'), 0, 1);
$pdf->Cell(45, 6, 'Alamat Surat Elektronik', 0, 0); $pdf->Cell(5, 6, ':', 0, 0); $pdf->Cell(0, 6, $data['email'], 0, 1);
$pdf->Ln(4);

// BLOCK 3: DAFTAR MANIFES ANGGOTA TIM TABLE KABEL
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(13, 59, 34);
$pdf->Cell(0, 7, ' DAFTAR MANIFES FISIK KELOMPOK PENDAKI', 0, 1, 'L', true);
$pdf->Ln(3);

// Lebar kolom tabel
$w_no = 10;
$w_nama = 85;
$w_nik = 50;
$w_hp = 45;

// Header Tabel Manifes
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(13, 59, 34);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell($w_no, 8, 'No', 1, 0, 'C', true);
$pdf->Cell($w_nama, 8, 'Nama Lengkap Pendaki', 1, 0, 'L', true);
$pdf->Cell($w_nik, 8, 'Nomor NIK KTP', 1, 0, 'L', true);
$pdf->Cell($w_hp, 8, 'No. Handphone Aktif', 1, 1, 'L', true);

// Isi Tabel Manifes Anggota Rombongan
$pdf->SetFont('Arial', '', 9.5);
$pdf->SetTextColor(30, 30, 30);
$no_m = 1;

while ($row_m = mysqli_fetch_assoc($query_anggota)) {
    // Zebra stripping background baris tipis biar rapi
    $fill_row = ($no_m % 2 == 0) ? true : false;
    $pdf->SetFillColor(245, 247, 245);
    
    $pdf->Cell($w_no, 8, $no_m++, 1, 0, 'C', $fill_row);
    $pdf->Cell($w_nama, 8, ' ' . $row_m['nama_pendaki'], 1, 0, 'L', $fill_row);
    $pdf->Cell($w_nik, 8, ' ' . $row_m['nik'], 1, 0, 'L', $fill_row);
    $pdf->Cell($w_hp, 8, ' ' . ($row_m['no_hp'] ?? '—'), 1, 1, 'L', $fill_row);
}

// Bersihkan buffer output & eksekusi cetak lembar PDF instan
ob_clean();
$pdf->Output('I', 'EMERGENCY_MANIFEST_' . $data['kode_booking'] . '.pdf');
exit;
?>