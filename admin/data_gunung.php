<?php
// admin/data_gunung.php
session_start();
include "../config/koneksi.php";

// PROTEKSI OTORITAS SEKURITI ADMIN GATEWAY
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) {
    header("Location: ../login.php");
    exit;
}

$message = "";
$messageType = "";

// Tangkap keyword pencarian jika ada parameter GET 'search'
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// ── 1. PROSES LOGIKA CRUD DATABASE + UPLOAD GAMBAR ──

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_gunung'])) {
    $id_gunung          = isset($_POST['id_gunung']) ? (int)$_POST['id_gunung'] : 0;
    $id_kategori        = (int)$_POST['id_kategori'];
    $nama_gunung        = mysqli_real_escape_string($conn, $_POST['nama_gunung']);
    $lokasi             = mysqli_real_escape_string($conn, $_POST['lokasi']);
    $tinggi             = (int)$_POST['tinggi'];
    $estimasi_pendakian = mysqli_real_escape_string($conn, $_POST['estimasi_pendakian']);
    $kuota_harian       = (int)$_POST['kuota_harian'];
    $harga_tiket        = (int)$_POST['harga_tiket'];
    $pos_perizinan      = mysqli_real_escape_string($conn, $_POST['pos_perizinan']);
    $status_gunung      = mysqli_real_escape_string($conn, $_POST['status_gunung']);
    $fasilitas_gunung   = mysqli_real_escape_string($conn, $_POST['fasilitas_gunung']);
    $deskripsi          = mysqli_real_escape_string($conn, $_POST['deskripsi']);

    // Handler Manajemen Validasi Gambar Gunung
    $nama_gambar_db = "";

    if (isset($_FILES['gambar_gunung']) && $_FILES['gambar_gunung']['error'] !== UPLOAD_ERR_NO_FILE) {

        // Cek error upload dari PHP/server terlebih dahulu
        if ($_FILES['gambar_gunung']['error'] !== UPLOAD_ERR_OK) {

            if ($_FILES['gambar_gunung']['error'] == UPLOAD_ERR_INI_SIZE) {
                $message = "Gagal upload, ukuran gambar maksimal adalah 2MB.";
            } elseif ($_FILES['gambar_gunung']['error'] == UPLOAD_ERR_FORM_SIZE) {
                $message = "Gagal upload, ukuran gambar maksimal adalah 2MB.";
            } elseif ($_FILES['gambar_gunung']['error'] == UPLOAD_ERR_PARTIAL) {
                $message = "Gagal upload, gambar hanya terupload sebagian. Silakan coba lagi.";
            } else {
                $message = "Gagal upload gambar. Silakan coba lagi.";
            }
            
            $messageType = "danger";
        } else {

            $fileTmpPath   = $_FILES['gambar_gunung']['tmp_name'];
            $fileName      = $_FILES['gambar_gunung']['name'];
            $fileSize      = $_FILES['gambar_gunung']['size'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            $allowedExtensions = ['jpg', 'jpeg', 'png'];

            if (in_array($fileExtension, $allowedExtensions)) {

                if ($fileSize <= 2 * 1024 * 1024) { // Batasan maksimal 2MB

                    $clean_name = strtolower(str_replace(' ', '_', $nama_gunung));
                    $clean_name = preg_replace('/[^a-z0-9_]/', '', $clean_name);

                    $newFileName = $clean_name . '_' . time() . '.' . $fileExtension;

                    $uploadFileDir = '../assets/img/gunung/';
                    $dest_path = $uploadFileDir . $newFileName;

                    if (!is_dir($uploadFileDir)) {
                        $message = "Folder upload gambar tidak ditemukan.";
                        $messageType = "danger";
                    } elseif (!is_writable($uploadFileDir)) {
                        $message = "Folder upload gambar tidak bisa ditulis. Cek permission folder assets/img/gunung.";
                        $messageType = "danger";
                    } elseif (move_uploaded_file($fileTmpPath, $dest_path)) {
                        $nama_gambar_db = $newFileName;

                        // Hapus gambar lama dari server jika update data
                        if ($id_gunung > 0) {
                            $old_img_query = mysqli_query($conn, "SELECT gambar FROM gunung WHERE id_gunung = '$id_gunung'");
                            $old_img = mysqli_fetch_assoc($old_img_query)['gambar'] ?? '';

                            if (!empty($old_img) && $old_img != 'default.jpg' && file_exists($uploadFileDir . $old_img)) {
                                unlink($uploadFileDir . $old_img);
                            }
                        }
                    } else {
                        $message = "Gagal menyimpan gambar ke server.";
                        $messageType = "danger";
                    }
                } else {
                    $message = "Gagal upload, ukuran gambar maksimal adalah 2MB.";
                    $messageType = "danger";
                }
            } else {
                $message = "Format gambar ditolak. Hanya menerima ekstensi JPG, JPEG, dan PNG.";
                $messageType = "danger";
            }
        }
    }


    // Eksekusi SQL Query jika lolos pengecekan berkas gambar
    if (empty($message)) {
        if ($id_gunung > 0) {
            // Operasi UPDATE DATA
            $sql_update = "
                UPDATE gunung SET 
                    id_kategori = '$id_kategori', nama_gunung = '$nama_gunung', lokasi = '$lokasi', 
                    tinggi = '$tinggi', estimasi_pendakian = '$estimasi_pendakian', deskripsi = '$deskripsi',
                    kuota_harian = '$kuota_harian', harga_tiket = '$harga_tiket', pos_perizinan = '$pos_perizinan', 
                    status_gunung = '$status_gunung', fasilitas_gunung = '$fasilitas_gunung'
            ";
            if (!empty($nama_gambar_db)) {
                $sql_update .= ", gambar = '$nama_gambar_db' ";
            }
            $sql_update .= " WHERE id_gunung = '$id_gunung' ";

            $query_save = mysqli_query($conn, $sql_update);
            $act_text = "diperbarui";
        } else {
            // Operasi CREATE DATA BARU
            $final_gambar = !empty($nama_gambar_db) ? $nama_gambar_db : 'default.jpg';
            $query_save = mysqli_query($conn, "
                INSERT INTO gunung (id_kategori, nama_gunung, lokasi, tinggi, estimasi_pendakian, deskripsi, kuota_harian, harga_tiket, pos_perizinan, status_gunung, fasilitas_gunung, gambar, peta_gunung)
                VALUES ('$id_kategori', '$nama_gunung', '$lokasi', '$tinggi', '$estimasi_pendakian', '$deskripsi', '$kuota_harian', '$harga_tiket', '$pos_perizinan', '$status_gunung', '$fasilitas_gunung', '$final_gambar', 'default.pdf')
            ");
            $act_text = "ditambahkan";
        }

        if ($query_save) {
            // Pengalihan internal dengan mempertahankan parameter search
            $redirect_url = "data_gunung.php?msg_success=" . urlencode("Data destinasi wilayah $nama_gunung berhasil $act_text!") . (!empty($search_keyword) ? "&search=" . urlencode($search_keyword) : "");
            header("Location: " . $redirect_url);
            exit;
        } else {
            $message = "Gagal memproses manipulasi data ke database: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

// B. HAPUS DATA (DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id_hapus = (int)$_GET['id'];

    $img_query = mysqli_query($conn, "SELECT gambar FROM gunung WHERE id_gunung = '$id_hapus'");
    $img_name = mysqli_fetch_assoc($img_query)['gambar'] ?? '';
    if (!empty($img_name) && $img_name != 'default.jpg' && file_exists('../assets/img/gunung/' . $img_name)) {
        unlink('../assets/img/gunung/' . $img_name);
    }

    mysqli_query($conn, "DELETE FROM gunung_logistik WHERE id_gunung = '$id_hapus'");
    mysqli_query($conn, "DELETE FROM gunung_mitra WHERE id_gunung = '$id_hapus'");

    $query_delete = mysqli_query($conn, "DELETE FROM gunung WHERE id_gunung = '$id_hapus'");
    if ($query_delete) {
        $redirect_url = "data_gunung.php?msg_success=" . urlencode("Data wilayah destinasi berhasil dihapus secara permanen.") . (!empty($search_keyword) ? "&search=" . urlencode($search_keyword) : "");
        header("Location: " . $redirect_url);
        exit;
    } else {
        $message = "Gagal menghapus data: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

// Tangkap flash message hasil redirect
if (isset($_GET['msg_success'])) {
    $message = $_GET['msg_success'];
    $messageType = "success";
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Data Gunung - NdakiYuk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body {
            background-color: #121b15 !important;
            color: #f8fafc;
        }

        .main-wrapper {
            width: 100%;
            overflow-x: hidden;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(5, 26, 15, 0.7);
            backdrop-filter: blur(5px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow-y: auto;
        }

        .modal-overlay.open {
            display: flex;
        }

        .modal-box {
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            padding: 28px;
            border-radius: var(--radius-lg);
            max-width: 650px;
            width: 100%;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            margin: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 12px;
        }

        .modal-close-btn {
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 20px;
        }

        .modal-close-btn:hover {
            color: var(--red-text);
        }

        .form-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 14px;
        }

        .form-group label {
            font-size: 11px;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control {
            width: 100%;
            background-color: var(--bg-main);
            border: 1px solid var(--border-color);
            padding: 10px 14px;
            border-radius: var(--radius-md);
            color: var(--text-title);
            font-family: inherit;
            font-size: 13px;
            outline: none;
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--green-text);
        }

        textarea.form-control {
            resize: vertical;
            min-height: 70px;
        }

        .table-thumb {
            width: 44px;
            height: 32px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid var(--border-color);
            vertical-align: middle;
            margin-right: 8px;
        }

        .modal-preview-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(0, 0, 0, 0.15);
            padding: 12px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .modal-img-preview {
            width: 70px;
            height: 50px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid var(--border-color);
        }

        /* ── CONFIG STYLING SEARCH BAR VECTOR PANEL ── */
        .search-wrapper-panel {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            width: 100%;
        }

        .search-input-container {
            position: relative;
            flex: 1;
            display: flex;
        }

        .search-input-field {
            width: 100%;
            padding: 12px 16px 12px 46px;
            background-color: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-title);
            font-family: inherit;
            font-size: 13.5px;
            outline: none;
            transition: var(--transition);
        }

        .search-input-field:focus {
            border-color: var(--green-text);
        }

        .search-icon-vector {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            pointer-events: none;
        }

        .btn-clear-search {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 16px;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-muted);
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            transition: var(--transition);
        }

        .btn-clear-search:hover {
            background: var(--red-soft);
            color: var(--red-text);
            border-color: rgba(255, 107, 107, 0.15);
        }

        @media(max-width: 576px) {
            .form-grid-2 {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>

    <div class="layout">
        <?php include 'sidebar.php'; ?>

        <main class="main">
            <div class="main-wrapper">

                <div class="header-banner" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <div>
                        <small class="header-pretitle">SISTEM MANIPULASI GEOGRAFIS REGULASI WILAYAH</small>
                        <h1 class="header-welcome">Data Wilayah Destinasi</h1>
                    </div>
                    <button type="button" class="btn-action-acc" onclick="openCrudModal()" style="padding: 10px 20px;">
                        + Tambah Gunung Baru
                    </button>
                </div>

                <?php if (!empty($message)): ?>
                    <div class="content-section" style="border-left: 4px solid <?= $messageType === 'success' ? 'var(--green-text)' : 'var(--red-text)' ?>; padding: 16px 20px; margin-bottom: 20px; background-color: <?= $messageType === 'success' ? 'var(--green-soft)' : 'var(--red-soft)' ?>;">
                        <span style="font-size: 13px; font-weight: 600; color: <?= $messageType === 'success' ? 'var(--green-text)' : 'var(--red-text)' ?>;"><?= $message; ?></span>
                    </div>
                <?php endif; ?>

                <?php
                $sql_base_gunung = "
                SELECT g.*, kg.nama_kategori 
                FROM gunung g
                JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori
            ";

                if (!empty($search_keyword)) {
                    $sql_base_gunung .= " WHERE g.nama_gunung LIKE '%$search_keyword%' 
                                      OR g.lokasi LIKE '%$search_keyword%' 
                                      OR g.pos_perizinan LIKE '%$search_keyword%'
                                      OR kg.nama_kategori LIKE '%$search_keyword%'";
                }

                $sql_base_gunung .= " ORDER BY g.id_gunung DESC";
                $query_gunung = mysqli_query($conn, $sql_base_gunung);
                $total_rows = mysqli_num_rows($query_gunung);
                ?>

                <div class="content-section">
                    <div class="section-header">
                        <div>
                            <h3 class="section-title">Database Pangkalan Jalur Gunung Registrasi</h3>
                            <span class="section-badge">Total: <?= $total_rows; ?> Kawasan</span>
                        </div>
                    </div>

                    <div class="search-wrapper-panel">
                        <form method="GET" action="" class="search-input-container">
                            <svg class="search-icon-vector" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                            <input type="text" name="search" class="search-input-field" placeholder="Cari wilayah destinasi berdasarkan nama gunung, kabupaten lokasi, basecamp, tingkat medan..." value="<?= htmlspecialchars($search_keyword); ?>">
                        </form>
                        <?php if (!empty($search_keyword)): ?>
                            <a href="data_gunung.php" class="btn-clear-search">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                                Clear
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nama Destinasi</th>
                                    <th>Lokasi / Kabupaten</th>
                                    <th>Ketinggian</th>
                                    <th>Estimasi Jalur</th>
                                    <th>Harga Tiket Masuk</th>
                                    <th>Kuota Harian</th>
                                    <th>Status Pintu</th>
                                    <th style="text-align: center;">Konfigurasi Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($total_rows > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($query_gunung)): ?>
                                        <tr>
                                            <td>
                                                <img src="../assets/img/gunung/<?= !empty($row['gambar']) ? htmlspecialchars($row['gambar']) : 'default.jpg'; ?>" class="table-thumb" alt="Cover">
                                                <strong class="hiker-name"><?= htmlspecialchars($row['nama_gunung']); ?></strong>
                                            </td>
                                            <td class="date-text"><?= htmlspecialchars($row['lokasi']); ?></td>
                                            <td class="bold-code" style="color: var(--info-text);"><?= number_format($row['tinggi'], 0, ',', '.'); ?> MDPL</td>
                                            <td class="count-text"><?= htmlspecialchars($row['estimasi_pendakian']); ?></td>
                                            <td class="price-table">Rp <?= number_format($row['harga_tiket'], 0, ',', '.'); ?></td>
                                            <td class="count-text"><strong><?= $row['kuota_harian']; ?></strong> / Hari</td>
                                            <td><span class="badge-status <?= $row['status_gunung'] == 'Buka' ? 'status-success' : 'status-danger'; ?>">Gerbang <?= $row['status_gunung']; ?></span></td>
                                            <td style="text-align: center;">
                                                <div style="display: flex; gap: 6px; justify-content: center;">
                                                    <button type="button" class="btn-action-acc" style="padding: 5px 10px; font-size: 11.5px; background-color: var(--accent-hover);"
                                                        onclick='openCrudModal(<?= json_encode($row); ?>)'>
                                                        Edit
                                                    </button>
                                                    <a href="data_gunung.php?action=delete&id=<?= $row['id_gunung']; ?><?= !empty($search_keyword) ? '&search=' . urlencode($search_keyword) : ''; ?>" class="btn-action-reject" style="padding: 5px 10px; font-size: 11.5px;"
                                                        onclick="return confirm('Apakah Anda yakin ingin menghapus kawasan <?= htmlspecialchars($row['nama_gunung']); ?>? Gambar fisik dan seluruh logistik terkait di server akan dibersihkan.');">
                                                        Hapus
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="empty-row-text">Tidak ada data wilayah destinasi gunung yang cocok dengan kata kunci pencarian Anda.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <div class="modal-overlay" id="crudModal" onclick="closeIfOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="chart-title" id="modalTitle" style="font-size: 15px;">Tambah Kawasan Destinasi Baru</h3>
                <button class="modal-close-btn" type="button" onclick="closeCrudModal()">&times;</button>
            </div>

            <form method="POST" action="data_gunung.php<?= !empty($search_keyword) ? '?search=' . urlencode($search_keyword) : ''; ?>" enctype="multipart/form-data">
                <input type="hidden" name="id_gunung" id="formIdGunung" value="0">

                <div class="form-group">
                    <label>Gambar Lanskap Wilayah</label>
                    <div class="modal-preview-wrap">
                        <img src="../assets/img/gunung/default.jpg" class="modal-img-preview" id="previewCover" alt="Preview">
                        <div>
                            <input type="file" name="gambar_gunung" id="fileGambar" accept="image/*" onchange="previewMediaFile(event)" style="font-size: 12px; color: var(--text-body);">
                            <p style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">Format: JPG, JPEG, PNG (Maksimal 2MB)</p>
                        </div>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Nama Wilayah Gunung</label>
                        <input type="text" name="nama_gunung" id="formNama" class="form-control" placeholder="Contoh: Gunung Arjuno" required>
                    </div>
                    <div class="form-group">
                        <label>Tingkat Kategori Medan</label>
                        <select name="id_kategori" id="formKategori" class="form-control" required>
                            <option value="1">Easy (Pemula / Santai)</option>
                            <option value="2">Medium (Menengah / Treking)</option>
                            <option value="3">Hard (Ekspedisi Trek Panjang)</option>
                            <option value="4">Extreme (Punggung Naga / Safety Gear)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Lokasi / Kabupaten Wilayah</label>
                        <input type="text" name="lokasi" id="formLokasi" class="form-control" placeholder="Contoh: Malang" required>
                    </div>
                    <div class="form-group">
                        <label>Ketinggian Puncak (MDPL)</label>
                        <input type="number" name="tinggi" id="formTinggi" class="form-control" placeholder="Contoh: 3339" required>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Estimasi Waktu Tempuh Jalur</label>
                        <input type="text" name="estimasi_pendakian" id="formEstimasi" class="form-control" placeholder="Contoh: 10-12 Jam" required>
                    </div>
                    <div class="form-group">
                        <label>Status Akses Pintu Loket</label>
                        <select name="status_gunung" id="formStatus" class="form-control" required>
                            <option value="Buka">Buka (Operasional Lancar)</option>
                            <option value="Tutup">Tutup (Sterilisasi / Cuaca Ekstrem)</option>
                        </select>
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Kuota Batas Harian Pendaki</label>
                        <input type="number" name="kuota_harian" id="formKuota" class="form-control" placeholder="Contoh: 300" required>
                    </div>
                    <div class="form-group">
                        <label>Harga Tiket Masuk SIMAKSI (Rp)</label>
                        <input type="number" name="harga_tiket" id="formHarga" class="form-control" placeholder="Contoh: 25000" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Nama Aliansi Pos Perizinan / Basecamp Terkait</label>
                    <input type="text" name="pos_perizinan" id="formPos" class="form-control" placeholder="Contoh: Via Tamiajeng" required>
                </div>

                <div class="form-group">
                    <label>Fasilitas Penunjang Pos Loket</label>
                    <textarea name="fasilitas_gunung" id="formFasilitas" class="form-control" placeholder="Contoh: Camping ground..."></textarea>
                </div>

                <div class="form-group">
                    <label>Deskripsi Karakteristik Geografis & Hutan</label>
                    <textarea name="deskripsi" id="formDeskripsi" class="form-control" placeholder="Tulis catatan ringkas mengenai karakteristik trek..."></textarea>
                </div>

                <div style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 10px;">
                    <button type="button" class="btn-action-reject" onclick="closeCrudModal()">Batalkan</button>
                    <button type="submit" name="simpan_gunung" class="btn-action-acc">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openCrudModal(dataObj = null) {
            const modal = document.getElementById('crudModal');
            const title = document.getElementById('modalTitle');
            const imgPreview = document.getElementById('previewCover');

            const inputId = document.getElementById('formIdGunung');
            const inputNama = document.getElementById('formNama');
            const inputKategori = document.getElementById('formKategori');
            const inputLokasi = document.getElementById('formLokasi');
            const inputTinggi = document.getElementById('formTinggi');
            const inputEstimasi = document.getElementById('formEstimasi');
            const inputStatus = document.getElementById('formStatus');
            const inputKuota = document.getElementById('formKuota');
            const inputHarga = document.getElementById('formHarga');
            const inputPos = document.getElementById('formPos');
            const inputFasilitas = document.getElementById('formFasilitas');
            const inputDeskripsi = document.getElementById('formDeskripsi');

            document.getElementById('fileGambar').value = "";

            if (dataObj) {
                title.innerText = "Update Parameter Wilayah Destinasi";
                inputId.value = dataObj.id_gunung;
                inputNama.value = dataObj.nama_gunung;
                inputKategori.value = dataObj.id_kategori;
                inputLokasi.value = dataObj.lokasi;
                inputTinggi.value = dataObj.tinggi;
                inputEstimasi.value = dataObj.estimasi_pendakian;
                inputStatus.value = dataObj.status_gunung;
                inputKuota.value = dataObj.kuota_harian;
                inputHarga.value = dataObj.harga_tiket;
                inputPos.value = dataObj.pos_perizinan ? dataObj.pos_perizinan : '';
                inputFasilitas.value = dataObj.fasilitas_gunung ? dataObj.fasilitas_gunung : '';
                inputDeskripsi.value = dataObj.deskripsi ? dataObj.deskripsi : '';
                imgPreview.src = "../assets/img/gunung/" + (dataObj.gambar ? dataObj.gambar : 'default.jpg');
            } else {
                title.innerText = "Tambah Kawasan Destinasi Baru";
                inputId.value = "0";
                inputNama.value = "";
                inputKategori.value = "1";
                inputLokasi.value = "";
                inputTinggi.value = "";
                inputEstimasi.value = "";
                inputStatus.value = "Buka";
                inputKuota.value = "";
                inputHarga.value = "";
                inputPos.value = "";
                inputFasilitas.value = "";
                inputDeskripsi.value = "";
                imgPreview.src = "../assets/img/gunung/default.jpg";
            }
            modal.classList.add('open');
        }

        function closeCrudModal() {
            document.getElementById('crudModal').classList.remove('open');
        }

        function closeIfOverlay(e) {
            if (e.target === document.getElementById('crudModal')) closeCrudModal();
        }

        function previewMediaFile(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => document.getElementById('previewCover').src = ev.target.result;
            reader.readAsDataURL(file);
        }
    </script>
</body>

</html>