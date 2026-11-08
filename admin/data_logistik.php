<?php
// admin/data_logistik.php
session_start();
include "../config/koneksi.php";

// Validasi Proteksi Token Hak Akses Admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true || $_SESSION['role'] != 3) {
    session_unset();
    session_destroy();
    header("Location: ../login.php?error=unauthorized_admin");
    exit;
}

$message = "";
$messageType = "";

// Tangkap keyword pencarian jika ada
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// ── 1. OPERASIONAL CRUD: PROSES SIMPAN / TAMBAH DATA BARU ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_logistik'])) {
    $id_kategori_logistik = (int)$_POST['id_kategori_logistik'];
    $nama_logistik = mysqli_real_escape_string($conn, trim($_POST['nama_logistik']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));

    if (!empty($nama_logistik) && $id_kategori_logistik > 0) {
        $insert = mysqli_query($conn, "INSERT INTO logistik (id_kategori_logistik, nama_logistik, deskripsi) VALUES ('$id_kategori_logistik', '$nama_logistik', '$deskripsi')");
        if ($insert) {
            $message = "Berhasil menambahkan perlengkapan logistik baru ke database.";
            $messageType = "success";
        } else {
            $message = "Gagal menyimpan data: " . mysqli_error($conn);
            $messageType = "danger";
        }
    } else {
        $message = "Mohon isi seluruh parameter kolom formulir secara lengkap.";
        $messageType = "danger";
    }
}

// ── 2. OPERASIONAL CRUD: PROSES UPDATE / UBAH DATA ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_logistik'])) {
    $id_logistik = (int)$_POST['id_logistik'];
    $id_kategori_logistik = (int)$_POST['id_kategori_logistik'];
    $nama_logistik = mysqli_real_escape_string($conn, trim($_POST['nama_logistik']));
    $deskripsi = mysqli_real_escape_string($conn, trim($_POST['deskripsi']));

    if ($id_logistik > 0 && !empty($nama_logistik) && $id_kategori_logistik > 0) {
        $update = mysqli_query($conn, "UPDATE logistik SET id_kategori_logistik = '$id_kategori_logistik', nama_logistik = '$nama_logistik', deskripsi = '$deskripsi' WHERE id_logistik = '$id_logistik'");
        if ($update) {
            // Tetap pertahankan parameter pencarian saat redirect setelah update jika ada
            $redirect_url = "data_logistik.php?status=updated" . (!empty($search_keyword) ? "&search=" . urlencode($search_keyword) : "");
            header("Location: " . $redirect_url);
            exit;
        } else {
            $message = "Gagal meremajakan data: " . mysqli_error($conn);
            $messageType = "danger";
        }
    }
}

// ── 3. OPERASIONAL CRUD: PROSES DELETE / HAPUS DATA ──
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_del = (int)$_GET['id'];
    
    mysqli_query($conn, "DELETE FROM gunung_logistik WHERE id_logistik = '$id_del'");
    mysqli_query($conn, "DELETE FROM pengajuan_update_logistik WHERE id_logistik = '$id_del'");
    
    $delete = mysqli_query($conn, "DELETE FROM logistik WHERE id_logistik = '$id_del'");
    if ($delete) {
        $redirect_url = "data_logistik.php?status=deleted" . (!empty($search_keyword) ? "&search=" . urlencode($search_keyword) : "");
        header("Location: " . $redirect_url);
        exit;
    } else {
        $message = "Gagal menghapus berkas data: " . mysqli_error($conn);
        $messageType = "danger";
    }
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'updated') {
        $message = "Berhasil memperbarui berkas parameter komponen logistik.";
        $messageType = "success";
    } elseif ($_GET['status'] === 'deleted') {
        $message = "Berhasil menghapus item perlengkapan logistik dari sistem.";
        $messageType = "success";
    }
}

// ── Ambil Seluruh Opsi Kategori Logistik Untuk Kebutuhan Form Dropdown ──
$query_kat_list = mysqli_query($conn, "SELECT * FROM kategori_logistik ORDER BY id_kategori_logistik ASC");
$kategori_options = [];
while ($kat = mysqli_fetch_assoc($query_kat_list)) {
    $kategori_options[] = $kat;
}

// ── Ambil Data Logistik yang Ingin Diedit (Jika parameter GET edit terpenuhi) ──
$edit_data = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id_edit = (int)$_GET['id'];
    $q_edit = mysqli_query($conn, "SELECT * FROM logistik WHERE id_logistik = '$id_edit'");
    $edit_data = mysqli_fetch_assoc($q_edit);
}

// ── 🔥 MODIFIKASI QUERY: AMBIL DATA LOGISTIK BERDASARKAN FILTER KEYWORD SEARCH 🔥 ──
$sql_master = "
    SELECT l.*, kl.nama_kategori_logistik 
    FROM logistik l
    LEFT JOIN kategori_logistik kl ON l.id_kategori_logistik = kl.id_kategori_logistik
";

if (!empty($search_keyword)) {
    $sql_master .= " WHERE l.nama_logistik LIKE '%$search_keyword%' OR kl.nama_kategori_logistik LIKE '%$search_keyword%'";
}

$sql_master .= " ORDER BY l.id_logistik DESC";
$query_master_logistik = mysqli_query($conn, $sql_master);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Logistik - NdakiYuk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        /* Penyelarasan Sistem Warna Global Konten */
        body { background-color: #121b15 !important; color: #f8fafc; }
        .main-wrapper { width: 100%; overflow-x: hidden; padding: 24px; }
        
        /* Layout Grid Pembagian */
        .crud-box-layout { display: grid; grid-template-columns: 380px 1fr; gap: 28px; margin-top: 24px; align-items: start; }
        @media (max-width: 1100px) { .crud-box-layout { grid-template-columns: 1fr; } }
        
        /* Desain Blok Form Kiri (Sticky Mode) */
        .form-crud-container { background: #1a241e; border: 1px solid rgba(255, 255, 255, 0.06); padding: 26px; border-radius: 16px; box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2); position: sticky; top: 24px; }
        .form-crud-title { font-size: 1.05rem; font-weight: 800; color: #ffffff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px; }
        .input-group-crud { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
        .input-group-crud label { font-size: 0.78rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; }
        
        /* Elemen Input Sisi Gelap */
        .input-group-crud input, .input-group-crud select, .input-group-crud textarea { width: 100%; padding: 11px 14px; font-family: inherit; font-size: 0.85rem; border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; outline: none; background: #222f27; color: #f1f5f9; transition: all 0.2s; }
        .input-group-crud input:focus, .input-group-crud select:focus, .input-group-crud textarea:focus { border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.15); background: #26352c; }
        
        /* Tombol Form Operasional */
        .btn-crud-submit { width: 100%; padding: 12px; background: #2ecc71; color: #0d1510; border: none; border-radius: 10px; font-weight: 800; font-size: 0.88rem; cursor: pointer; transition: 0.2s; box-shadow: 0 4px 14px rgba(46,204,113,0.2); margin-top: 8px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .btn-crud-submit:hover { background: #27ae60; transform: translateY(-1px); }
        .btn-crud-cancel { display: flex; align-items: center; justify-content: center; gap: 6px; width: 100%; padding: 11px; background: rgba(255,255,255,0.05); color: #cbd5e1; text-decoration: none; border-radius: 10px; font-size: 0.85rem; font-weight: 700; margin-top: 10px; transition: 0.15s; border: 1px solid rgba(255,255,255,0.05); }
        .btn-crud-cancel:hover { background: rgba(255,255,255,0.1); color: #ffffff; }

        /* Struktur Tampilan Grid Card Kanan */
        .cards-list-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.08); }
        .cards-list-title { font-size: 1.1rem; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 8px; }
        
        /* 🔥 DESAIN BARU: SEARCH BAR WRAPPER STYLE 🔥 */
        .search-container-box { display: flex; gap: 10px; margin-bottom: 20px; width: 100%; }
        .search-form-wrapper { display: flex; flex: 1; position: relative; }
        .search-input-field { width: 100%; padding: 12px 16px 12px 46px; background: #1a241e; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; color: #ffffff; font-size: 0.88rem; font-family: inherit; outline: none; transition: 0.2s; }
        .search-input-field:focus { border-color: #2ecc71; box-shadow: 0 0 0 3px rgba(46,204,113,0.12); }
        .search-icon-svg { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #64748b; pointer-events: none; }
        .btn-search-clear { display: flex; align-items: center; justify-content: center; padding: 0 16px; background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.06); border-radius: 12px; color: #94a3b8; text-decoration: none; font-size: 0.82rem; font-weight: 700; transition: 0.15s; }
        .btn-search-clear:hover { background: rgba(231,76,60,0.1); color: #ec7063; border-color: rgba(231,76,60,0.15); }
        
        .cards-grid-logistik { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 20px; }
        
        /* Desain Blok Komponen Card */
        .logistik-card { background: #1a241e; border: 1px solid rgba(255, 255, 255, 0.06); border-radius: 16px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; position: relative; box-shadow: 0 4px 20px rgba(0,0,0,0.15); transition: all 0.2s ease-in-out; }
        .logistik-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.3); border-color: rgba(46, 204, 113, 0.3); }
        
        .card-top { margin-bottom: 16px; }
        .card-id { font-size: 0.7rem; font-weight: 700; color: #64748b; font-family: 'Space Grotesk', sans-serif; margin-bottom: 8px; letter-spacing: 0.5px; }
        .card-name { font-size: 1.05rem; font-weight: 800; color: #ffffff; line-height: 1.3; margin-bottom: 10px; }
        .badge-cat-logistik { display: inline-flex; align-items: center; gap: 5px; background: rgba(46, 204, 113, 0.1); color: #2ecc71; font-weight: 800; font-size: 0.68rem; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; border: 1px solid rgba(46, 204, 113, 0.15); }
        .card-desc { font-size: 0.78rem; color: #94a3b8; line-height: 1.5; text-align: justify; margin-top: 12px; word-wrap: break-word; border-top: 1px dashed rgba(255,255,255,0.04); padding-top: 10px; }
        
        /* Tombol Navigasi di Sisi Bawah Card */
        .card-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 14px; margin-top: auto; }
        .btn-card-edit { display: flex; align-items: center; justify-content: center; gap: 4px; background: rgba(52, 152, 219, 0.12); color: #54b4eb; padding: 9px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none; transition: 0.15s; border: 1px solid rgba(52, 152, 219, 0.15); }
        .btn-card-edit:hover { background: rgba(52, 152, 219, 0.25); color: #ffffff; }
        .btn-card-delete { display: flex; align-items: center; justify-content: center; gap: 4px; background: rgba(231, 76, 60, 0.12); color: #ec7063; padding: 9px; border-radius: 8px; font-size: 0.78rem; font-weight: 700; text-decoration: none; transition: 0.15s; border: 1px solid rgba(231, 76, 60, 0.15); cursor: pointer; }
        .btn-card-delete:hover { background: rgba(231, 76, 60, 0.25); color: #ffffff; }
        
        .empty-card-state { grid-column: 1 / -1; text-align: center; background: #1a241e; padding: 50px; border-radius: 16px; border: 2px dashed rgba(255,255,255,0.1); color: #64748b; font-style: italic; font-size: 0.85rem; }
    </style>
</head>
<body>

<div class="layout">
    <?php include 'sidebar.php'; ?>

    <main class="main">
        <div class="main-wrapper">
            
            <div class="header-banner">
                <small class="header-pretitle">SISTEM INTEGRASI REGULASI OPERASIONAL MANIFES</small>
                <h1 class="header-welcome">Manajemen Komponen Kelayakan Logistik</h1>
            </div>

            <?php if (!empty($message)): ?>
                <div style="padding: 14px; margin-top: 20px; border-radius: 12px; font-size: 0.85rem; font-weight: 600; text-align: center; 
                    <?= $messageType === 'success' ? 'background: rgba(46,204,113,0.1); color: #2ecc71; border: 1px solid rgba(46,204,113,0.15);' : 'background: rgba(231,76,60,0.1); color: #e74c3c; border: 1px solid rgba(231,76,60,0.15);' ?>">
                    <?= htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <div class="crud-box-layout">
                
                <div>
                    <?php if ($edit_data): ?>
                        <div class="form-crud-container">
                            <h3 class="form-crud-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                Perbarui Alat
                            </h3>
                            <form method="POST" action="">
                                <input type="hidden" name="id_logistik" value="<?= $edit_data['id_logistik']; ?>">
                                
                                <div class="input-group-crud">
                                    <label>Nama Perlengkapan</label>
                                    <input type="text" name="nama_logistik" value="<?= htmlspecialchars($edit_data['nama_logistik']); ?>" required placeholder="Contoh: Tenda Dome 4P">
                                </div>

                                <div class="input-group-crud">
                                    <label>Klasifikasi Kategori</label>
                                    <select name="id_kategori_logistik" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($kategori_options as $kat): ?>
                                            <option value="<?= $kat['id_kategori_logistik']; ?>" <?= $kat['id_kategori_logistik'] == $edit_data['id_kategori_logistik'] ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($kat['nama_kategori_logistik']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="input-group-crud">
                                    <label>Deskripsi Standar Kelayakan</label>
                                    <textarea name="deskripsi" rows="5" placeholder="Tuliskan petunjuk minimal kelayakan alat saat pengecekan fisik..."><?= htmlspecialchars($edit_data['deskripsi']); ?></textarea>
                                </div>

                                <button type="submit" name="update_logistik" class="btn-crud-submit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                                    Simpan Perubahan Berkas
                                </button>
                                <a href="data_logistik.php<?= !empty($search_keyword) ? '?search='.urlencode($search_keyword) : ''; ?>" class="btn-crud-cancel">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                    Batalkan Perubahan
                                </a>
                            </form>
                        </div>
                    <?php else: ?>
                        <div class="form-crud-container">
                            <h3 class="form-crud-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2.5"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/><line x1="12" y1="11" x2="12" y2="17"/><line x1="9" y1="14" x2="15" y2="14"/></svg>
                                Registrasi Logistik Baru
                            </h3>
                            <form method="POST" action="">
                                <div class="input-group-crud">
                                    <label>Nama Perlengkapan</label>
                                    <input type="text" name="nama_logistik" required placeholder="Contoh: Kompor Portable Outdoor">
                                </div>

                                <div class="input-group-crud">
                                    <label>Klasifikasi Kategori</label>
                                    <select name="id_kategori_logistik" required>
                                        <option value="">-- Pilih Kategori --</option>
                                        <?php foreach ($kategori_options as $kat): ?>
                                            <option value="<?= $kat['id_kategori_logistik']; ?>">
                                                <?= htmlspecialchars($kat['nama_kategori_logistik']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="input-group-crud">
                                    <label>Deskripsi Standar Kelayakan</label>
                                    <textarea name="deskripsi" rows="5" placeholder="Tuliskan petunjuk standar fisik di pintu loket perizinan resmi..."></textarea>
                                </div>

                                <button type="submit" name="tambah_logistik" class="btn-crud-submit">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="14" x2="16" y2="14"/></svg>
                                    Masukkan Ke Database
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>

                <div>
                    <div class="cards-list-header">
                        <h3 class="cards-list-title">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Database Logistik Aktif
                        </h3>
                        <span class="section-badge" style="margin:0; background: rgba(255,255,255,0.06); color: #cbd5e1; border: 1px solid rgba(255,255,255,0.05);">Total: <?= mysqli_num_rows($query_master_logistik); ?> Item</span>
                    </div>

                    <div class="search-container-box">
                        <form method="GET" action="" class="search-form-wrapper">
                            <svg class="search-icon-svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            <input type="text" name="search" class="search-input-field" placeholder="Cari berdasarkan nama perlengkapan atau kategori logistik..." value="<?= htmlspecialchars($search_keyword); ?>">
                        </form>
                        <?php if (!empty($search_keyword)): ?>
                            <a href="data_logistik.php" class="btn-search-clear">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                Bersihkan
                            </a>
                        <?php endif; ?>
                    </div>

                    <div class="cards-grid-logistik">
                        <?php if (mysqli_num_rows($query_master_logistik) > 0): ?>
                            <?php while ($row = mysqli_fetch_assoc($query_master_logistik)): ?>
                                <div class="logistik-card">
                                    <div class="card-top">
                                        <div class="card-id">REG ID: #<?= $row['id_logistik']; ?></div>
                                        <h4 class="card-name"><?= htmlspecialchars($row['nama_logistik']); ?></h4>
                                        <span class="badge-cat-logistik">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                            <?= htmlspecialchars($row['nama_kategori_logistik'] ?? 'Saran Umum'); ?>
                                        </span>
                                        <div class="card-desc">
                                            <?= !empty($row['deskripsi']) ? htmlspecialchars($row['deskripsi']) : 'Belum ada deskripsi spesifikasi aturan kelayakan objek.'; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="card-actions">
                                        <a href="data_logistik.php?action=edit&id=<?= $row['id_logistik']; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-card-edit">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                            Edit
                                        </a>
                                        <a href="data_logistik.php?action=delete&id=<?= $row['id_logistik']; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-card-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus item ini? Aturan wajib logistik pada manajemen mitra gunung terkait akan ikut terhapus.');">
                                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                            Hapus
                                        </a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="empty-card-state">
                                Tidak ada item logistik yang cocok dengan kata kunci pencarian Anda.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

</body>
</html>