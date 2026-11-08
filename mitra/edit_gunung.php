<?php
session_start();
include "../config/koneksi.php";

if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$id_gunung = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Ambil data gunung, pastikan gunung ini memang milik mitra yang login
$query = mysqli_query($conn, "
    SELECT 
        g.*,
        kg.nama_kategori,
        up.nama_lengkap
    FROM gunung_mitra gm
    JOIN gunung g ON gm.id_gunung = g.id_gunung
    LEFT JOIN kategori_gunung kg ON g.id_kategori = kg.id_kategori
    LEFT JOIN user_profile up ON gm.id_user = up.id_user
    WHERE gm.id_user = '$id_user'
    AND g.id_gunung = '$id_gunung'
");

if (!$query) {
    die("Query gunung error: " . mysqli_error($conn));
}

$gunung = mysqli_fetch_assoc($query);

if (!$gunung) {
    die("Data gunung tidak ditemukan atau bukan gunung yang Anda kelola.");
}

// Ambil kategori gunung
$query_kategori = mysqli_query($conn, "
    SELECT * FROM kategori_gunung
    ORDER BY id_kategori ASC
");

if (!$query_kategori) {
    die("Query kategori error: " . mysqli_error($conn));
}

// Ambil semua logistik dari database
$query_logistik = mysqli_query($conn, "
    SELECT 
        l.id_logistik,
        l.nama_logistik,
        l.deskripsi,
        kl.nama_kategori_logistik
    FROM logistik l
    LEFT JOIN kategori_logistik kl 
        ON l.id_kategori_logistik = kl.id_kategori_logistik
    ORDER BY kl.id_kategori_logistik ASC, l.nama_logistik ASC
");

if (!$query_logistik) {
    die("Query logistik error: " . mysqli_error($conn));
}

// Ambil logistik yang sudah dipakai oleh gunung ini
$query_logistik_gunung = mysqli_query($conn, "
    SELECT 
        id_logistik,
        status_wajib
    FROM gunung_logistik
    WHERE id_gunung = '$id_gunung'
");

if (!$query_logistik_gunung) {
    die("Query logistik gunung error: " . mysqli_error($conn));
}

$logistik_terpilih = [];
while ($row = mysqli_fetch_assoc($query_logistik_gunung)) {
    $logistik_terpilih[$row['id_logistik']] = $row['status_wajib'];
}

// Cek apakah ada pengajuan yang masih pending
$query_pending = mysqli_query($conn, "
    SELECT *
    FROM pengajuan_update_gunung
    WHERE id_gunung = '$id_gunung'
    AND id_mitra = '$id_user'
    AND status_pengajuan = 'Menunggu Verifikasi'
    ORDER BY id_pengajuan DESC
    LIMIT 1
");

if (!$query_pending) {
    die("Query pengajuan pending error: " . mysqli_error($conn));
}

$pengajuan_pending = mysqli_fetch_assoc($query_pending);

function e($value)
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Parameter Wilayah - NdakiYuk Mitra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mitra.css">
</head>

<body>

    <div class="layout">

        <?php include 'sidebar.php'; ?>

        <main class="main">

            <div class="topbar-mobile">
                <div class="mobile-brand-wrap">
                    <span class="mbrand-text">NDAKIYUK</span>
                    <span class="mrole-badge">MITRA</span>
                </div>
                <button class="menu-toggle-btn" onclick="toggleSidebarMenu()">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="3" y1="12" x2="21" y2="12" />
                        <line x1="3" y1="6" x2="21" y2="6" />
                        <line x1="3" y1="18" x2="21" y2="18" />
                    </svg>
                </button>
            </div>

            <div class="header-banner">
                <div class="header-info-text">
                    <small class="header-pretitle">PENGAJUAN PERUBAHAN PARAMETER STRUKTUR</small>
                    <h1 class="header-welcome">Edit Data Jalur <?= htmlspecialchars($gunung['nama_gunung']); ?></h1>
                    <p class="header-sub">Formulir usulan revisi berkas kuota harian dan kelayakan jalur. Data baru akan di-commit setelah disetujui Admin.</p>
                </div>
            </div>

            <?php if ($pengajuan_pending): ?>
                <div class="content-section" style="border-left: 4px solid var(--yellow); margin-bottom: 24px; background-color: var(--yellow-light);">
                    <div class="section-header" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
                        <div class="section-title-block">
                            <h3 class="section-title-text" style="color: var(--color-pending);">Antrean Usulan Masih Berjalan</h3>
                            <p class="section-title-sub" style="color: var(--text-mid);">Anda memiliki satu draf pengajuan pembaruan yang masih diperiksa oleh tim Admin pusat. Harap tunggu hingga proses sinkronisasi kelar.</p>
                        </div>
                        <span class="badge-status status-pending"><?= htmlspecialchars($pengajuan_pending['status_pengajuan']); ?></span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="content-section">
                <div class="section-header" style="margin-bottom: 30px;">
                    <div class="section-title-block">
                        <h3 class="section-title-text">Form Parameter Registrasi Jalur</h3>
                        <p class="section-title-sub">Gunakan data valid operasional lapangan saat mengajukan revisi.</p>
                    </div>
                    <span class="section-badge-info">Target: <?= htmlspecialchars($gunung['nama_gunung']); ?></span>
                </div>

                <form action="proses_pengajuan_gunung.php" method="POST" class="form-edit">
                    <input type="hidden" name="id_gunung" value="<?= $gunung['id_gunung']; ?>">

                    <div style="margin-bottom: 32px;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--green-dark); margin-bottom: 16px; border-left: 3px solid var(--green-main); padding-left: 10px;">1. Parameter Tarif & Otoritas Jalur</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Harga Tiket Masuk (Rp)</label>
                                <input type="number" name="harga_tiket" value="<?= htmlspecialchars($gunung['harga_tiket']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Kategori / Difficulty Rute</label>
                                <select name="id_kategori" required>
                                    <?php while ($kategori = mysqli_fetch_assoc($query_kategori)): ?>
                                        <option value="<?= $kategori['id_kategori']; ?>" <?= $kategori['id_kategori'] == $gunung['id_kategori'] ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($kategori['nama_kategori']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>Ketinggian Wilayah (MDPL)</label>
                                <input type="number" name="tinggi" value="<?= htmlspecialchars($gunung['tinggi']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Status Operasional Gerbang</label>
                                <select name="status_gunung" required>
                                    <option value="Buka" <?= $gunung['status_gunung'] == 'Buka' ? 'selected' : ''; ?>>Buka Jalur</option>
                                    <option value="Tutup" <?= $gunung['status_gunung'] == 'Tutup' ? 'selected' : ''; ?>>Tutup Jalur</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 32px; border-top: 1px solid var(--border); padding-top: 24px;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--green-dark); margin-bottom: 16px; border-left: 3px solid var(--green-main); padding-left: 10px;">2. Regulasi Logistik & Logistik Rombongan</h4>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Estimasi Durasi Pendakian</label>
                                <input type="text" name="estimasi_pendakian" value="<?= htmlspecialchars($gunung['estimasi_pendakian']); ?>" placeholder="Contoh: 12-16 Jam atau 2 Hari 1 Malam" required>
                            </div>

                            <div class="form-group">
                                <label>Kuota Harian Maksimal</label>
                                <input type="number" name="kuota_harian" value="<?= htmlspecialchars($gunung['kuota_harian']); ?>" required>
                            </div>

                            <div class="form-group full">
                                <label>Pos Pintu Perizinan Utama Basecamp</label>
                                <input type="text" name="pos_perizinan" value="<?= e($gunung['pos_perizinan']); ?>" required>
                            </div>

                            <div class="form-group full">
                                <label>Fasilitas Umum & Fasilitas Jalur</label>
                                <textarea name="fasilitas" rows="3" required><?= htmlspecialchars($gunung['fasilitas_gunung'] ?? ''); ?></textarea>
                                <small style="color: var(--text-muted); font-size: 0.78rem; margin-top: 4px; display: block;">⚠️ Pisahkan setiap item fasilitas menggunakan tanda baca koma (,). Contoh: <em>Pos Registrasi, Shelter Istirahat, Toilet Basecamp, Musholla</em></small>
                            </div>
                        </div>
                    </div>

                    <div style="margin-bottom: 32px; border-top: 1px solid var(--border); padding-top: 24px;">
                        <h4 style="font-size: 0.95rem; font-weight: 700; color: var(--green-dark); margin-bottom: 4px; border-left: 3px solid var(--green-main); padding-left: 10px;">3. Regulasi Kelayakan Logistik Kelompok</h4>
                        <p style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 18px; padding-left: 13px;">Tandai material perlengkapan kelompok yang wajib ditunjukkan fisiknya di loket registrasi.</p>

                        <div class="logistik-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 14px;">
                            <?php
                            mysqli_data_seek($query_logistik, 0);
                            while ($logistik = mysqli_fetch_assoc($query_logistik)):
                                $id_logistik = $logistik['id_logistik'];
                                $checked = isset($logistik_terpilih[$id_logistik]) ? 'checked' : '';
                                $status_lama = $logistik_terpilih[$id_logistik] ?? 'Wajib';
                            ?>
                                <div class="logistik-item" style="display: flex; align-items: center; justify-content: space-between; gap: 14px; background: var(--bg-page); border: 1px solid var(--border); border-radius: 12px; padding: 14px; transition: var(--transition);">
                                    <div class="logistik-left" style="display: flex; align-items: flex-start; gap: 12px; flex: 1;">
                                        <input type="checkbox" name="logistik[]" value="<?= $id_logistik; ?>" id="logistik_<?= $id_logistik; ?>" <?= $checked; ?> style="width: 17px; height: 17px; accent-color: var(--green-main); margin-top: 2px; cursor: pointer;">
                                        <label for="logistik_<?= $id_logistik; ?>" style="cursor: pointer; display: block;">
                                            <strong style="font-size: 0.9rem; color: var(--text-dark); display: block; line-height: 1.2;"><?= htmlspecialchars($logistik['nama_logistik']); ?></strong>
                                            <span class="section-badge-info" style="display: inline-block; font-size: 0.68rem; padding: 2px 8px; border-radius: 4px; background-color: var(--white); margin-top: 6px; font-weight: 700;">
                                                📦 <?= htmlspecialchars($logistik['nama_kategori_logistik'] ?? 'Saran Dasar'); ?>
                                            </span>
                                        </label>
                                    </div>
                                    <select name="status_logistik[<?= $id_logistik; ?>]" style="width: 110px; padding: 8px; font-size: 0.8rem; border-radius: 8px; border: 1px solid var(--border); background-color: var(--white); font-weight: 600; color: var(--text-dark); cursor: pointer;">
                                        <option value="Wajib" <?= $status_lama == 'Wajib' ? 'selected' : ''; ?>>Wajib</option>
                                        <option value="Disarankan" <?= $status_lama == 'Disarankan' ? 'selected' : ''; ?>>Disarankan</option>
                                    </select>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <div style="border-top: 1px solid var(--border); padding-top: 24px;">
                        <div class="form-group full">
                            <label>Warta Kondisi Trek Terkini (Info Kondisi Lapangan)</label>
                            <textarea name="info_terkini" rows="4" placeholder="Contoh: Terjadi longsoran vegetasi kecil di antara pos 2 dan pos 3. Pendaki diimbau tidak mendirikan camp di area lembahan pohon lapuk."><?= htmlspecialchars($gunung['info_terkini'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="form-actions" style="margin-top: 32px; padding-top: 20px; border-top: 1px solid var(--border);">
                        <a href="gunung_saya.php" class="btn-secondary">Batalkan Pengisian</a>

                        <?php if (!$pengajuan_pending): ?>
                            <button type="submit" name="ajukan" class="btn-primary">
                                Kirim Usulan Perubahan
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-disabled" disabled>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <polyline points="12 6 12 12 16 14"></polyline>
                                </svg>
                                Menunggu Verifikasi Admin
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

        </main>
    </div>

    <script>
        function toggleSidebarMenu() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                sidebar.classList.toggle('mobile-open');
            }
        }
    </script>
</body>

</html>