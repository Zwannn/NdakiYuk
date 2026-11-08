<?php
session_start();
include "../config/koneksi.php";

// JIKA BELUM LOGIN ATAU BUKAN MITRA, LEMPAR KE LOGIN
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 2) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$message = "";
$messageType = "";

// =========================================================================
// PROSES LOGIKA UPDATE DATA MITRA (TANPA UPLOAD FOTO)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($conn, $_POST['username']);
    $email        = mysqli_real_escape_string($conn, $_POST['email']);
    $no_hp        = mysqli_real_escape_string($conn, $_POST['no_hp']);
    $bio          = mysqli_real_escape_string($conn, $_POST['bio']); 

    // Cek duplikasi username / email milik orang lain
    $checkQuery = mysqli_query($conn, "SELECT id_user FROM users WHERE (username='$username' OR email='$email') AND id_user != '$id_user'");
    if (mysqli_num_rows($checkQuery) > 0) {
        $message = "Username atau Email sudah terdaftar oleh instansi/mitra lain.";
        $messageType = "error";
    } else {
        // Pembaruan data tabel dasar 'users'
        $updateUsers = mysqli_query($conn, "UPDATE users SET username='$username', email='$email' WHERE id_user='$id_user'");
        
        // Pembaruan data tabel berkas 'user_profile'
        $updateProfile = mysqli_query($conn, "UPDATE user_profile SET nama_lengkap='$nama_lengkap', no_hp='$no_hp', bio='$bio' WHERE id_user='$id_user'");

        if ($updateUsers && $updateProfile) {
            $message = "Profil Otoritas Mitra berhasil diperbarui!";
            $messageType = "success";
            $_SESSION['nama_lengkap'] = $nama_lengkap;
        } else {
            $message = "Gagal memperbarui data profil ke database.";
            $messageType = "error";
        }
    }
}

// =========================================================================
// AMBIL DATA MITRA TERBARU BESERTA STATISTIK KELOLAAN GUNUNG
// =========================================================================
$queryUser = mysqli_query($conn, "
    SELECT users.username, users.email, user_profile.* FROM users 
    JOIN user_profile ON users.id_user = user_profile.id_user 
    WHERE users.id_user = '$id_user'
");
$userData = mysqli_fetch_assoc($queryUser);

// Hitung total kawasan gunung yang dikelola oleh mitra ini
$query_gunung = mysqli_query($conn, "SELECT COUNT(*) as total FROM gunung_mitra WHERE id_user = '$id_user'");
$total_gunung = mysqli_fetch_assoc($query_gunung)['total'] ?? 0;

// Hitung total pendaki yang statusnya sudah Selesai (Selesai Turun) di gunung kelolaan mitra ini
$query_selesai = mysqli_query($conn, "
    SELECT COUNT(*) as total FROM pemesanan p
    JOIN gunung_mitra gm ON p.id_gunung = gm.id_gunung
    WHERE gm.id_user = '$id_user' AND p.status_pemesanan = 'Selesai'
");
$total_selesai = mysqli_fetch_assoc($query_selesai)['total'] ?? 0;

$nama_instansi = !empty($userData['nama_lengkap']) ? $userData['nama_lengkap'] : $userData['username'];
// Ambil inisial huruf pertama untuk template avatar statis
$inisial_mitra = strtoupper(substr(trim($nama_instansi), 0, 1));

$user = [
    'nama'     => $nama_instansi,
    'level'    => 'Otoritas Mitra Pos Basecamp', 
    'bio'      => !empty($userData['bio']) ? $userData['bio'] : 'Pengelola resmi pendaftaran, manifes, dan keselamatan jalur pendakian bersama NdakiYuk!',
    'username' => $userData['username'],
    'email'    => $userData['email'],
    'phone'    => !empty($userData['no_hp']) ? $userData['no_hp'] : '-',
    'total_gunung'  => $total_gunung, 
    'total_selesai' => $total_selesai,
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Otoritas Basecamp - NdakiYuk Mitra</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/mitra.css">
    <style>
        /* CSS Tambahan khusus Halaman Profil agar tata letak card rapi & sinkron */
        .profile-header-card {
            display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;
        }
        .profile-meta-block {
            display: flex; align-items: center; gap: 20px;
        }
        /* Style Template Avatar Statis SVG */
        .avatar-template-statis {
            width: 100px; height: 100px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green-main), #051a0f);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 2.5rem; color: var(--yellow);
            border: 3px solid var(--border); box-shadow: var(--shadow-sm); flex-shrink: 0;
        }
        .avatar-template-preview {
            width: 70px; height: 70px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green-main), #051a0f);
            display: flex; align-items: center; justify-content: center;
            font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 1.8rem; color: var(--yellow);
            border: 2px solid var(--border); margin-right: 15px; flex-shrink: 0;
        }
        .profile-text-block {
            display: flex; flex-direction: column; gap: 4px;
        }
        .profile-action-group {
            display: flex; gap: 10px;
        }
        /* Style Modal Box Override Custom */
        .modal-overlay {
            display: none; position: fixed; inset: 0; background-color: rgba(13, 59, 34, 0.6);
            backdrop-filter: blur(5px); z-index: 2000; align-items: center; justify-content: center; padding: 20px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background-color: var(--white); padding: 30px; border-radius: var(--radius-lg);
            max-width: 550px; width: 100%; box-shadow: var(--shadow-md); animation: fadeUp 0.3s ease;
        }
        .modal-header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid var(--border);
        }
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<div class="layout">

    <?php include 'sidebar.php'; ?>

    <main class="main">

        <!-- FLOATING TOPBAR RESPONSIVE MOBILE -->
        <div class="topbar-mobile">
            <div class="mobile-brand-wrap">
                <span class="mbrand-text">NDAKIYUK</span>
                <span class="mrole-badge">MITRA</span>
            </div>
            <button class="menu-toggle-btn" onclick="toggleSidebarMenu()">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>

        <!-- HEADER BANNER -->
        <div class="header-banner">
            <div class="header-info-text">
                <small class="header-pretitle">MITRA PROFILE AUTHORITY</small>
                <h1 class="header-welcome">Manajemen Profil Instansi</h1>
                <p class="header-sub">Kelola identitas akun pengelola, kredensial email, nomor operasional pos, serta biografi informasi basecamp.</p>
            </div>
        </div>

        <!-- NOTIFIKASI STATUS UPDATE PROFIL -->
        <?php if (!empty($message)): ?>
            <div class="content-section" style="border-left: 4px solid <?= $messageType === 'success' ? 'var(--color-diterima)' : 'var(--color-ditolak)' ?>; margin-bottom: 24px; padding: 16px 24px; background-color: <?= $messageType === 'success' ? 'var(--bg-diterima)' : 'var(--bg-ditolak)' ?>;">
                <strong style="color: <?= $messageType === 'success' ? 'var(--color-diterima)' : 'var(--color-ditolak)' ?>; font-size: 0.92rem;"><?= htmlspecialchars($message) ?></strong>
            </div>
        <?php endif; ?>

        <!-- SEKSI CARD PROFIL UTAMA -->
        <div class="content-section" style="margin-bottom: 24px; padding: 24px 30px;">
            <div class="profile-header-card">
                <div class="profile-meta-block">
                    <!-- TEMPLATE AVATAR STATIS MITRA -->
                    <div class="avatar-template-statis">
                        <?= $inisial_mitra; ?>
                    </div>
                    <div class="profile-text-block">
                        <h2 style="font-size: 1.35rem; font-weight: 700; color: var(--text-dark);"><?= htmlspecialchars($user['nama']) ?></h2>
                        <div style="margin-top: 4px;">
                            <span class="badge-status status-diterima" style="font-size: 0.68rem; padding: 3px 10px; background-color: var(--green-light); color: var(--green-main);">
                                🛡️ <?= htmlspecialchars($user['level']) ?>
                            </span>
                        </div>
                        <p style="font-size: 0.85rem; color: var(--text-mid); margin-top: 8px; max-width: 500px; line-height: 1.5; font-style: italic;">"<?= htmlspecialchars($user['bio']) ?>"</p>
                    </div>
                </div>

                <div class="profile-action-group">
                    <button class="btn-primary" onclick="openEditModal(event)" style="padding: 10px 18px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 8px;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                        Ubah Profil
                    </button>
                    <a href="../logout.php" class="btn-danger" style="padding: 10px 16px; font-size: 0.82rem; display: inline-flex; align-items: center; gap: 8px; text-decoration: none;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Keluar
                    </a>
                </div>
            </div>
        </div>

        <!-- DETAIL DATA DUA KOLOM -->
        <div class="detail-grid">
            
            <!-- BLOK KIRI: INFORMASI UTAMA DATA AKUN -->
            <div class="content-section">
                <div class="section-header" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
                    <div class="section-title-block">
                        <h3 class="section-title-text">Detail Akun & Kredensial</h3>
                        <p class="section-title-sub">Data autentikasi yang terkunci ke dalam gerbang core server.</p>
                    </div>
                </div>

                <div class="detail-row">
                    <span>Username Akun Mitra</span>
                    <strong>@<?= htmlspecialchars($user['username']) ?></strong>
                </div>
                <div class="detail-row">
                    <span>Alamat Email Resmi</span>
                    <strong><?= htmlspecialchars($user['email']) ?></strong>
                </div>
                <div class="detail-row">
                    <span>No. Telp Operasional Pos</span>
                    <strong><?= htmlspecialchars($user['phone']) ?></strong>
                </div>
            </div>

            <!-- BLOK KANAN: METRIK DAN STATISTIK -->
            <div class="content-section">
                <div class="section-header" style="border-bottom: 1px solid var(--border); padding-bottom: 12px; margin-bottom: 16px;">
                    <div class="section-title-block">
                        <h3 class="section-title-text">Metrik Cakupan Kelolaan</h3>
                        <p class="section-title-sub">Arsip log pelaporan data wilayah kelolaan pos Anda.</p>
                    </div>
                </div>

                <div class="detail-row" style="padding: 16px 0;">
                    <span style="font-size: 0.9rem; font-weight: 600;">Total Kawasan Gunung Yang Dikelola</span>
                    <strong style="font-size: 1.4rem; color: var(--green-main);"><?= $user['total_gunung'] ?> <small style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">Wilayah</small></strong>
                </div>
                <div class="detail-row" style="padding: 16px 0; border-bottom: none;">
                    <span style="font-size: 0.9rem; font-weight: 600;">Total Manifes Pendaki Selesai Turun</span>
                    <strong style="font-size: 1.4rem; color: var(--green-main);"><?= $user['total_selesai'] ?> <small style="font-size: 0.85rem; font-weight: 500; color: var(--text-muted);">Anggota</small></strong>
                </div>
            </div>

        </div>

    </main>
</div>

<!-- ===================================================
     MODAL EDIT PROFILE COMPONENT
==================================================== -->
<div class="modal-overlay" id="editModal" onclick="closeIfOverlay(event)">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="section-title-text" style="font-size: 1.05rem;">Edit Profil Otoritas Mitra</h3>
            <button style="background:none; border:none; cursor:pointer; color:var(--text-muted);" onclick="closeEditModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>

        <form action="profil.php" method="POST" style="display: flex; flex-direction: column; gap: 16px;">
            
            <!-- INFORMASI TEMPLATE STATIS PADA MODAL -->
            <div style="display: flex; align-items: center; gap: 16px; background-color: var(--bg-page); padding: 14px; border-radius: 12px; border: 1px solid var(--border);">
                <div class="avatar-template-preview">
                    <?= $inisial_mitra; ?>
                </div>
                <div>
                    <strong style="font-size: 0.88rem; color: var(--text-dark); display: block;">Profil Mitra</strong>
                </div>
            </div>

            <div class="form-grid" style="grid-template-columns: repeat(2, 1fr); gap: 14px;">
                <div class="form-group">
                    <label>Nama Lengkap Instansi</label>
                    <input type="text" name="nama_lengkap" value="<?= htmlspecialchars($user['nama']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Username Otoritas</label>
                    <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Alamat Email Resmi</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Nomor Telepon Operasional Loket</label>
                <input type="tel" name="no_hp" value="<?= htmlspecialchars($user['phone'] === '-' ? '' : $user['phone']) ?>">
            </div>
            
            <div class="form-group">
                <label>Biografi Informasi Pintu Jalur</label>
                <textarea name="bio" rows="3" placeholder="Tulis deskripsi singkat mengenai instansi pengelola atau operasional basecamp jalur..."><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
            </div>
            
            <div class="form-actions" style="border-top: 1px solid var(--border); padding-top: 16px; margin-top: 10px;">
                <button type="button" class="btn-secondary" onclick="closeEditModal()">Batalkan</button>
                <button type="submit" name="update_profile" class="btn-primary">Simpan Profil Baru</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleSidebarMenu() {
    const sidebar = document.querySelector('.sidebar');
    if(sidebar) { sidebar.classList.toggle('mobile-open'); }
}

function openEditModal(e) {
    e.preventDefault();
    document.getElementById('editModal').classList.add('open');
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
}

function closeIfOverlay(e) {
    if (e.target === document.getElementById('editModal')) closeEditModal();
}
</script>
</body>
</html>