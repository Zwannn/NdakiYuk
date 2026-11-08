<?php
// admin/data_pendaki.php
session_start();
include "../config/koneksi.php";

// PROTEKSI OTORITAS SEKURITI ADMIN GATEWAY
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) {
    header("Location: ../login.php");
    exit;
}

// Tangkap parameter filter pencarian keyword text jika ada
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
// Handler Fitur Filter Berdasarkan Gunung
$filter_gunung = isset($_GET['gunung']) ? mysqli_real_escape_string($conn, $_GET['gunung']) : '';

// ── 🔥 LOGIKA BARU: INTERKONEKSI DINAMIS CLAUSE WHERE FILTER & SEARCH 🔥 ──
$conditions = [];

if (!empty($filter_gunung)) {
    $conditions[] = "p.id_gunung = '$filter_gunung'";
}

if (!empty($search_keyword)) {
    $conditions[] = "(dp.nama_pendaki LIKE '%$search_keyword%' 
                    OR dp.nik LIKE '%$search_keyword%' 
                    OR p.kode_booking LIKE '%$search_keyword%')";
}

$where_clause = "";
if (count($conditions) > 0) {
    $where_clause = " WHERE " . implode(" AND ", $conditions);
}

// ── KONFIGURASI ENGINE PAGINATION ──
$limit_data = 20; 
$halaman_aktif = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($halaman_aktif <= 0) { $halaman_aktif = 1; }
$offset_data = ($halaman_aktif - 1) * $limit_data;

// HITUNG TOTAL DATA BERDASARKAN FILTER & SEARCH
$sql_total = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM detail_pendaki dp
    JOIN pemesanan p ON dp.id_pemesanan = p.id_pemesanan
    $where_clause
");
$total_data = mysqli_fetch_assoc($sql_total)['total'] ?? 0;
$total_halaman = ceil($total_data / $limit_data);

// AGREGASI METRIK EMERGENCY MONITORING
$q_aktif = mysqli_query($conn, "
    SELECT SUM(p.jumlah_pendaki) AS total 
    FROM pemesanan p 
    $where_clause " . (empty($where_clause) ? "WHERE" : "AND") . " p.status_checkin = 'Sudah Check-in' AND p.status_checkout = 'Belum Check-out'
");
$hiker_di_gunung = mysqli_fetch_assoc($q_aktif)['total'] ?? 0;

$q_overdue = mysqli_query($conn, "
    SELECT COUNT(*) AS total 
    FROM pemesanan p 
    $where_clause " . (empty($where_clause) ? "WHERE" : "AND") . " p.status_checkin = 'Sudah Check-in' AND p.status_checkout = 'Belum Check-out' AND p.tanggal_turun < CURDATE()
");
$grup_overdue = mysqli_fetch_assoc($q_overdue)['total'] ?? 0;

// TARIK DATA MANIFES BERDASARKAN HASIL AKUMULASI FILTER & SEARCH JALUR
$query_pendaki = mysqli_query($conn, "
    SELECT 
        dp.*, 
        p.kode_booking, p.status_checkin, p.status_checkout, p.tanggal_naik, p.tanggal_turun,
        g.nama_gunung, g.lokasi
    FROM detail_pendaki dp
    JOIN pemesanan p ON dp.id_pemesanan = p.id_pemesanan
    JOIN gunung g     ON p.id_gunung = g.id_gunung
    $where_clause
    ORDER BY dp.id_detail DESC
    LIMIT $offset_data, $limit_data
");
$total_rows = mysqli_num_rows($query_pendaki);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Data Pendaki - NdakiYuk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        body { background-color: #121b15 !important; color: #f8fafc; }
        .main-wrapper { width: 100%; overflow-x: hidden; }
        .filter-panel { display: flex; gap: 12px; margin-bottom: 0; align-items: center; flex-wrap: wrap; }
        .select-custom { background: var(--bg-sidebar); border: 1px solid var(--border-color); color: var(--text-primary); padding: 8px 14px; border-radius: var(--radius-md); font-family: inherit; font-size: 13px; outline: none; }
        .btn-filter { padding: 8px 16px; background-color: var(--accent-color); color: #fff; border: none; border-radius: var(--radius-md); cursor: pointer; font-size: 13px; font-weight: 600; text-decoration: none; }
        .btn-filter:hover { background-color: var(--accent-hover); }
        .emergency-pulse { width: 10px; height: 10px; background-color: #ff6b6b; border-radius: 50%; display: inline-block; position: relative; margin-right: 6px; }
        .emergency-pulse::after { content: ''; position: absolute; inset: 0; background-color: #ff6b6b; border-radius: 50%; animation: pulseSAR 1.8s infinite ease-in-out; }
        @keyframes pulseSAR { 0% { transform: scale(1); opacity: 1; } 100% { transform: scale(3); opacity: 0; } }

        .pagination-wrapper { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; gap: 16px; flex-wrap: wrap; }
        .pagination-info { font-size: 13px; color: var(--text-muted); }
        .pagination-nav { display: flex; gap: 8px; }
        .btn-page { display: inline-flex; align-items: center; justify-content: center; padding: 8px 16px; background-color: var(--bg-sidebar); border: 1px solid var(--border-color); color: var(--text-body); border-radius: var(--radius-md); text-decoration: none; font-size: 13px; font-weight: 600; transition: var(--transition); }
        .btn-page:hover:not(.disabled) { background-color: rgba(255,255,255,0.04); color: var(--text-title); border-color: rgba(255,255,255,0.15); }
        .btn-page.active { background-color: var(--accent-color); border-color: var(--accent-color); color: #ffffff; }
        .btn-page.disabled { opacity: 0.3; cursor: not-allowed; pointer-events: none; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(5, 26, 15, 0.6); backdrop-filter: blur(4px); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background-color: var(--bg-card); border: 1px solid var(--border-color); padding: 28px; border-radius: var(--radius-lg); max-width: 480px; width: 100%; box-shadow: 0 10px 30px rgba(0,0,0,0.5); animation: modalFade 0.25s ease; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .modal-close-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; transition: var(--transition); }
        .modal-close-btn:hover { color: var(--red-text); }
        .detail-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid rgba(255,255,255,0.02); font-size: 13.5px; }
        .detail-row span { color: var(--text-muted); }
        .detail-row strong { color: var(--text-title); font-weight: 600; }
        @keyframes modalFade { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* ── CONFIG STYLING INTERNAL ENGINE PANEL SEARCH BAR VECTOR ── */
        .search-wrapper-panel { display: flex; gap: 12px; margin-bottom: 16px; width: 100%; }
        .search-input-container { position: relative; flex: 1; display: flex; }
        .search-input-field { width: 100%; padding: 11px 16px 11px 46px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: var(--text-title); font-family: inherit; font-size: 13.5px; outline: none; transition: var(--transition); }
        .search-input-field:focus { border-color: var(--green-text); }
        .search-icon-vector { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted); pointer-events: none; }
        .btn-clear-search { display: flex; align-items: center; justify-content: center; padding: 0 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: var(--text-muted); text-decoration: none; font-size: 13px; font-weight: 600; transition: var(--transition); }
        .btn-clear-search:hover { background: var(--red-soft); color: var(--red-text); border-color: rgba(255,107,107,0.15); }
    </style>
</head>
<body>

<div class="layout">
    <?php include 'sidebar.php'; ?>

    <main class="main">
        <div class="main-wrapper">
            
            <div class="header-banner">
                <small class="header-pretitle">MANAJEMEN DATABASE OPERASIONAL KEAMANAN</small>
                <h1 class="header-welcome">Data Kendali Pendaki</h1>
                <p class="header-sub">Otoritas pusat pemantauan manifes fisik pendaki, log inventarisasi rombongan, dan proteksi penelusuran tim SAR.</p>
            </div>

            <div class="cards-grid">
                <div class="stat-card">
                    <div class="scard-icon-wrap" style="color: var(--blue-text);">👤</div>
                    <div class="scard-data">
                        <span class="scard-label">Total Anggota Manifes Filtered</span>
                        <h2 class="scard-value"><?= number_format($total_data, 0, ',', '.'); ?> <small>Jiwa</small></h2>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="scard-icon-wrap" style="color: var(--green-text); display: flex; align-items: center;">
                        <span class="emergency-pulse" style="background-color: #2ecc71;"></span> Di Gunung
                    </div>
                    <div class="scard-data">
                        <span class="scard-label">Personel Masih di Atas</span>
                        <h2 class="scard-value" style="color: var(--green-text);"><?= number_format($hiker_di_gunung, 0, ',', '.'); ?> <small>Orang</small></h2>
                    </div>
                </div>

                <div class="stat-card" style="border-color: <?= $grup_overdue > 0 ? 'rgba(255,107,107,0.4)' : 'var(--border-color)'; ?>">
                    <div class="scard-icon-wrap" style="color: var(--red-text); display: flex; align-items: center;">
                        <?php if($grup_overdue > 0): ?><span class="emergency-pulse"></span><?php endif; ?> Alert SAR
                    </div>
                    <div class="scard-data">
                        <span class="scard-label">Grup Terlambat Turun</span>
                        <h2 class="scard-value" style="color: <?= $grup_overdue > 0 ? 'var(--red-text)' : 'var(--text-title)'; ?>"><?= $grup_overdue; ?> <small>Kelompok</small></h2>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="scard-icon-wrap" style="color: var(--yellow-text); font-family: monospace;">PAGE</div>
                    <div class="scard-data">
                        <span class="scard-label">Halaman Berjalan</span>
                        <h2 class="scard-value" style="font-size: 1.6rem; margin-top: 4px;"><?= $halaman_aktif; ?> <small>dari <?= max(1, $total_halaman); ?></small></h2>
                    </div>
                </div>
            </div>

            <div class="content-section" style="padding: 16px 20px; margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                    <form method="GET" action="data_pendaki.php" class="filter-panel">
                        <span style="font-size: 13px; font-weight: 600; color: var(--text-title);">Saring Jalur Gunung:</span>
                        
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search_keyword); ?>">
                        
                        <select name="gunung" class="select-custom">
                            <option value="">-- Tampilkan Semua Gunung --</option>
                            <?php
                            $list_g = mysqli_query($conn, "SELECT id_gunung, nama_gunung FROM gunung ORDER BY nama_gunung ASC");
                            while($lg = mysqli_fetch_assoc($list_g)) {
                                $selected = ($filter_gunung == $lg['id_gunung']) ? 'selected' : '';
                                echo "<option value='{$lg['id_gunung']}' $selected>{$lg['nama_gunung']}</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn-filter">Terapkan Sinkronisasi</button>
                        <?php if(!empty($filter_gunung) || !empty($search_keyword)): ?>
                            <a href="data_pendaki.php" class="btn-action-reject" style="padding: 8px 14px; display: inline-flex; align-items: center; border-radius: var(--radius-md); text-decoration:none; font-size:13px; font-weight:600;">Reset</a>
                        <?php endif; ?>
                    </form>

                    <a href="export_pendaki_excel.php?gunung=<?= $filter_gunung; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-filter" style="background-color: #107c41; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                        <span>📊</span> Export Semua ke Excel
                    </a>
                </div>
            </div>

            <div class="content-section">
                <div class="section-header" style="flex-wrap: wrap; gap: 16px; margin-bottom:14px;">
                    <div>
                        <h3 class="section-title">Log Manifes Fisik Seluruh Anggota Rombongan</h3>
                        <span class="section-badge">Menampilkan: <?= $total_rows; ?> Baris</span>
                    </div>
                </div>

                <div class="search-wrapper-panel">
                    <form method="GET" action="" class="search-input-container">
                        <input type="hidden" name="gunung" value="<?= htmlspecialchars($filter_gunung); ?>">
                        
                        <svg class="search-icon-vector" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" class="search-input-field" placeholder="Cari berdasarkan nama anggota personil, nomor identitas NIK KTP, atau kode booking tiket..." value="<?= htmlspecialchars($search_keyword); ?>">
                    </form>
                    <?php if (!empty($search_keyword)): ?>
                        <a href="data_pendaki.php?gunung=<?= urlencode($filter_gunung); ?>" class="btn-clear-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Clear
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Anggota</th>
                                <th>Kode Booking</th>
                                <th>Wilayah Destinasi</th>
                                <th>Umur / Gender</th> 
                                <th>Nomor KTP (NIK)</th>
                                <th>No. Handphone</th>
                                <th>Estimasi Jadwal</th>
                                <th>Status Pos Log</th>
                                <th style="text-align: center;">Otoritas</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_rows > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_pendaki)): ?>
                                    <?php
                                        $nik = $row['nik'];
                                        $masked_nik = (!empty($nik) && !str_contains($nik, 'NEW-USER')) ? substr($nik, 0, 6) . "XXXXXXXX" . substr($nik, -2) : '—';
                                        
                                        $hp = $row['no_hp'];
                                        $masked_hp = (!empty($hp) && $hp != '—') ? substr($hp, 0, 4) . "XXXX" . substr($hp, -3) : '—';

                                        $tampil_umur   = !empty($row['umur']) ? $row['umur'] . ' Thn' : '—';
                                        $tampil_gender = !empty($row['jenis_kelamin']) ? $row['jenis_kelamin'] : '—';

                                        $status_pos = ""; $badge_class = "";
                                        if ($row['status_checkin'] == 'Belum Check-in') {
                                            $status_pos = "Belum Naik"; $badge_class = "status-pending";
                                        } elseif ($row['status_checkin'] == 'Sudah Check-in' && $row['status_checkout'] == 'Belum Check-out') {
                                            if (strtotime($row['tanggal_turun']) < strtotime(date('Y-m-d'))) {
                                                $status_pos = "Terlambat Checkout"; $badge_class = "status-danger";
                                            } else {
                                                $status_pos = "Di Gunung"; $badge_class = "status-success";
                                            }
                                        } elseif ($row['status_checkout'] == 'Sudah Check-out') {
                                            $status_pos = "Sudah Kembali"; $badge_class = "status-success";
                                        }
                                    ?>
                                    <tr>
                                        <td><strong class="hiker-name"><?= htmlspecialchars($row['nama_pendaki']); ?></strong></td>
                                        <td><span class="bold-code">#<?= htmlspecialchars($row['kode_booking']); ?></span></td>
                                        <td class="date-text"><?= htmlspecialchars($row['nama_gunung']); ?></td>
                                        <td class="date-text" style="font-size: 12.5px;"><?= $tampil_umur; ?> / <?= $tampil_gender; ?></td>
                                        <td class="bold-code" style="color: var(--text-secondary);"><?= $masked_nik; ?></td>
                                        <td class="count-text"><?= $masked_hp; ?></td>
                                        <td class="date-text" style="font-size: 12px; line-height: 1.4;">
                                            Naik: <?= date('d M Y', strtotime($row['tanggal_naik'])); ?><br>
                                            Turun: <?= date('d M Y', strtotime($row['tanggal_turun'])); ?>
                                        </td>
                                        <td><span class="badge-status <?= $badge_class; ?>"><?= $status_pos; ?></span></td>
                                        <td style="text-align: center;">
                                            <button type="button" class="btn-action-acc" style="padding: 5px 10px; font-size: 11.5px;" 
                                                onclick="viewPendakiDetail('<?= htmlspecialchars($row['nama_pendaki']); ?>', '<?= htmlspecialchars($row['nik']); ?>', '<?= htmlspecialchars($row['no_hp']); ?>', '<?= htmlspecialchars($row['nama_gunung']); ?>', '#<?= htmlspecialchars($row['kode_booking']); ?>', '<?= $row['id_detail']; ?>', '<?= $tampil_umur; ?>', '<?= $tampil_gender; ?>')">
                                                View
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" class="empty-row-text">Tidak ada data anggota pendaki yang cocok dengan filter kata kunci Anda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($total_halaman > 1): ?>
                    <div class="pagination-wrapper">
                        <div class="pagination-info">
                            Menampilkan data ke-<strong><?= ($offset_data + 1); ?></strong> sampai <strong><?= min($offset_data + $limit_data, $total_data); ?></strong> dari total <strong><?= $total_data; ?></strong> pendaki.
                        </div>
                        <div class="pagination-nav">
                            <a href="?page=<?= ($halaman_aktif - 1); ?><?= !empty($filter_gunung) ? '&gunung='.$filter_gunung : ''; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-page <?= ($halaman_aktif <= 1) ? 'disabled' : ''; ?>">
                                &laquo; Previous
                            </a>
                            <?php for ($i = 1; $i <= $total_halaman; $i++): ?>
                                <a href="?page=<?= $i; ?><?= !empty($filter_gunung) ? '&gunung='.$filter_gunung : ''; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-page <?= ($halaman_aktif == $i) ? 'active' : ''; ?>">
                                    <?= $i; ?>
                                </a>
                            <?php endfor; ?>
                            <a href="?page=<?= ($halaman_aktif + 1); ?><?= !empty($filter_gunung) ? '&gunung='.$filter_gunung : ''; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-page <?= ($halaman_aktif >= $total_halaman) ? 'disabled' : ''; ?>">
                                Next &raquo;
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="detailModal" onclick="closeIfOverlay(event)">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="chart-title" style="font-size: 15px;">Berkas Otentik Manifes Pendaki</h3>
            <button class="modal-close-btn" onclick="closeDetailModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 4px; margin-bottom: 24px;">
            <div class="detail-row"><span>Nama Anggota</span><strong id="detNama"></strong></div>
            <div class="detail-row"><span>Umur / Gender</span><strong id="detUmurGender"></strong></div> 
            <div class="detail-row"><span>Kode Booking</span><strong id="detBooking" class="bold-code" style="color: var(--yellow-text);"></strong></div>
            <div class="detail-row"><span>Nomor KTP / NIK</span><strong id="detNik" style="font-family: monospace; letter-spacing: 0.5px;"></strong></div>
            <div class="detail-row"><span>No. Handphone Aktif</span><strong id="detHp"></strong></div>
            <div class="detail-row"><span>Tujuan Kawasan</span><strong id="detGunung"></strong></div>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button type="button" class="btn-action-reject" style="padding: 10px 16px; font-size: 13px;" onclick="closeDetailModal()">Tutup</button>
            <a href="" id="detPdfLink" class="btn-action-acc" style="padding: 10px 18px; font-size: 13px; text-decoration: none;" target="_blank">Download PDF Darurat</a>
        </div>
    </div>
</div>

<script>
function toggleSidebarMenu() {
    const sidebar = document.querySelector('.sidebar');
    if(sidebar) { sidebar.classList.toggle('mobile-open'); }
}

function viewPendakiDetail(nama, nik, hp, gunung, booking, idDetail, umur, gender) {
    document.getElementById('detNama').innerText = nama;
    document.getElementById('detUmurGender').innerText = umur + ' / ' + gender;
    document.getElementById('detBooking').innerText = booking;
    document.getElementById('detNik').innerText = (nik !== '') ? nik : '—';
    document.getElementById('detHp').innerText = (hp !== '') ? hp : '—';
    document.getElementById('detGunung').innerText = gunung;
    
    document.getElementById('detPdfLink').href = 'download_pendaki_pdf.php?id=' + idDetail;
    document.getElementById('detailModal').classList.add('open');
}

function closeDetailModal() {
    document.getElementById('detailModal').classList.remove('open');
}

function closeIfOverlay(e) {
    if (e.target === document.getElementById('detailModal')) closeDetailModal();
}
</script>
</body>
</html>