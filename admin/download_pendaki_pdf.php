<?php
// admin/download_pendaki_pdf.php
session_start();
require('../assets/fpdf/fpdf.php');
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) {
    die("Akses Otoritas Ditolak.");
}

$id_detail = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$query = mysqli_query($conn, "
    SELECT 
        dp.*, 
        p.kode_booking, p.pos_masuk, p.pos_keluar, p.tanggal_naik, p.tanggal_turun, p.status_checkin,
        g.nama_gunung, g.lokasi
    FROM detail_pendaki dp
    JOIN pemesanan p ON dp.id_pemesanan = p.id_pemesanan
    JOIN gunung g     ON p.id_gunung = g.id_gunung
    WHERE dp.id_detail = '$id_detail'
    LIMIT 1
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data arsip manifes pendaki tidak ditemukan.");
}

class PDF_Emergency extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 14);
        $this->SetTextColor(26, 92, 56); 
        $this->Cell(0, 8, 'NDAKIYUK ADVENTURE HUB', 0, 1, 'C');
        $this->SetFont('Arial', 'B', 10);
        $this->SetTextColor(122, 148, 135);
        $this->Cell(0, 5, 'BERKAS MANIFES DATA EMERGENCY PENDIDIKAN & TIM RESCUE', 0, 1, 'C');
        $this->Ln(4);
        $this->SetDrawColor(26, 92, 56);
        $this->SetLineWidth(0.6);
        $this->Line(10, $this->GetY(), 200, $this->GetY());
        $this->Ln(6);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(150, 150, 150);
        $this->Cell(0, 10, 'Dokumen sah dicetak melalui Pusat Administrasi Administrator NdakiYuk - Halaman ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF_Emergency('P', 'mm', 'A5'); 
$pdf->SetMargins(12, 12, 12);
$pdf->AddPage();

$pdf->SetFillColor(254, 243, 199); 
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(217, 119, 6);
$pdf->Cell(0, 8, ' PERINGATAN: DATA BERSIFAT RAHASIA - GUNAKAN HANYA UNTUK KEBUTUHAN SAR & EVAKUASI', 1, 1, 'L', true);
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 11);
$pdf->SetTextColor(15, 36, 25);
$pdf->Cell(0, 7, 'I. IDENTITAS PERSONAL', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);

// SINKRONISASI DATA PENDUKUNG FIX UMUR & GENDER DI PDF
$umur_pdf   = !empty($data['umur']) ? $data['umur'] . ' Tahun' : '—';
$gender_pdf = !empty($data['jenis_kelamin']) ? $data['jenis_kelamin'] : '—';

$pdf->Cell(40, 7, 'Nama Lengkap', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['nama_pendaki'], 0, 1);
$pdf->Cell(40, 7, 'Nomor NIK / KTP', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 7, $data['nik'], 0, 1); $pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 7, 'No. Handphone', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['no_hp'], 0, 1);
$pdf->Cell(40, 7, 'Estimasi Umur', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $umur_pdf, 0, 1);
$pdf->Cell(40, 7, 'Jenis Kelamin', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $gender_pdf, 0, 1);
$pdf->Ln(4);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 7, 'II. MANIFES LOGISTIK PERJALANAN', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);

$pdf->Cell(40, 7, 'Kode Booking Reg', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->SetFont('Arial', 'B', 10); $pdf->Cell(0, 7, '#' . $data['kode_booking'], 0, 1); $pdf->SetFont('Arial', '', 10);
$pdf->Cell(40, 7, 'Destinasi Wilayah', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['nama_gunung'] . ' (' . $data['lokasi'] . ')', 0, 1);
$pdf->Cell(40, 7, 'Pintu Jalur Masuk', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['pos_masuk'] ?? 'Jalur Utama', 0, 1);
$pdf->Cell(40, 7, 'Rencana Jalur Keluar', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, $data['pos_keluar'] ?? 'Jalur Utama', 0, 1);
$pdf->Cell(40, 7, 'Tanggal Berangkat', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, date('d F Y', strtotime($data['tanggal_naik'])), 0, 1);
$pdf->Cell(40, 7, 'Estimasi Waktu Turun', 0, 0); $pdf->Cell(5, 7, ':', 0, 0); $pdf->Cell(0, 7, date('d F Y', strtotime($data['tanggal_turun'])), 0, 1);
$pdf->Ln(6);

$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 5, 'Dicetak otomatis pada sistem tanggal: ' . date('d-m-Y H:i:s') . ' WIB', 0, 1, 'R');

$pdf->Output('I', 'EMERGENCY_MANIFES_' . strtoupper(str_replace(' ', '_', $data['nama_pendaki'])) . '.pdf');
?>