<?php
// admin/data_user.php
session_start();
include "../config/koneksi.php";

// PROTEKSI OTORITAS SEKURITI ADMIN GATEWAY
if (!isset($_SESSION['id_user']) || $_SESSION['role'] != 3) {
    header("Location: ../login.php");
    exit;
}

$id_user_logged = $_SESSION['id_user'];
$message = "";
$messageType = "";

// Tangkap keyword pencarian jika ada parameter GET 'search'
$search_keyword = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';

// ─────────────────────────────────────────────────────────────────────────
// 1. PROSES LOGIKA CRUD DATABASE MANAGEMENT
// ─────────────────────────────────────────────────────────────────────────

// A. TAMBAH DATA (CREATE) & EDIT DATA (UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_user'])) {
    $id_user      = isset($_POST['id_user']) ? (int)$_POST['id_user'] : 0;
    $id_role      = (int)$_POST['id_role'];
    $username     = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email        = mysqli_real_escape_string($conn, trim($_POST['email']));
    $nama_lengkap = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    
    // Validasi duplikasi username/email milik entitas lain
    $check = mysqli_query($conn, "SELECT id_user FROM users WHERE (username='$username' OR email='$email') AND id_user != '$id_user'");
    
    if (mysqli_num_rows($check) > 0) {
        $message = "Username atau Email sudah terdaftar oleh pengguna lain.";
        $messageType = "danger";
    } else {
        if ($id_user > 0) {
            // OPERASI UPDATE DATA
            $update_user = mysqli_query($conn, "UPDATE users SET id_role='$id_role', username='$username', email='$email' WHERE id_user='$id_user'");
            $update_prof = mysqli_query($conn, "UPDATE user_profile SET nama_lengkap='$nama_lengkap' WHERE id_user='$id_user'");
            
            // Ganti password jika diisi oleh admin
            if (!empty($_POST['password'])) {
                $password_hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
                mysqli_query($conn, "UPDATE users SET password='$password_hashed' WHERE id_user='$id_user'");
            }
            $act_text = "diperbarui";
        } else {
            // OPERASI CREATE DATA BARU
            $password_mentah = !empty($_POST['password']) ? $_POST['password'] : '123456'; // Default password jika kosong
            $password_hashed = password_hash($password_mentah, PASSWORD_DEFAULT);
            
            mysqli_begin_transaction($conn);
            try {
                $q_user = mysqli_query($conn, "INSERT INTO users (id_role, username, email, password) VALUES ('$id_role', '$username', '$email', '$password_hashed')");
                $new_id = mysqli_insert_id($conn);
                
                $dummy_nik = "NEW-USER-" . time();
                $dummy_hp  = "—";
                $q_prof = mysqli_query($conn, "INSERT INTO user_profile (id_user, nama_lengkap, nik, no_hp, bio) VALUES ('$new_id', '$nama_lengkap', '$dummy_nik', '$dummy_hp', 'Anggota terdaftar NdakiYuk.')");
                
                if ($q_user && $q_prof) {
                    mysqli_commit($conn);
                    $update_user = true;
                } else {
                    throw new Exception("Query Gagal");
                }
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $update_user = false;
            }
            $act_text = "ditambahkan";
        }

        if ($update_user) {
            // Pengalihan internal dengan mempertahankan parameter search
            $redirect_url = "data_user.php?msg_success=" . urlencode("Akun kredensial @$username berhasil $act_text!") . (!empty($search_keyword) ? "&search=" . urlencode($search_keyword) : "");
            header("Location: " . $redirect_url);
            exit;
        } else {
            $message = "Gagal memproses manipulasi data akun pengguna.";
            $messageType = "danger";
        }
    }
}

// B. HAPUS DATA (DELETE)
if (isset($_GET['action']) && $_GET['action'] === 'delete') {
    $id_hapus = (int)$_GET['id'];
    
    // Cegah admin menghapus akun miliknya sendiri saat sesi aktif berjalan
    if ($id_hapus === $id_user_logged) {
        $message = "Tindakan ilegal! Anda tidak dapat menghapus akun admin yang sedang Anda gunakan.";
        $messageType = "danger";
    } else {
        mysqli_query($conn, "DELETE FROM user_profile WHERE id_user = '$id_hapus'");
        mysqli_query($conn, "DELETE FROM pemesanan WHERE id_user = '$id_hapus'");
        $query_delete = mysqli_query($conn, "DELETE FROM users WHERE id_user = '$id_hapus'");
        
        if ($query_delete) {
            $redirect_url = "data_user.php?msg_success=" . urlencode("Akun pengguna berhasil dihapus secara permanen dari pangkalan log.") . (!empty($search_keyword) ? "&search=" . urlencode($search_keyword) : "");
            header("Location: " . $redirect_url);
            exit;
        } else {
            $message = "Gagal menghapus data pengguna.";
            $messageType = "danger";
        }
    }
}

// Tangkap flash message hasil redirect POST / GET Delete
if (isset($_GET['msg_success'])) {
    $message = $_GET['msg_success'];
    $messageType = "success";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../assets/img/logo_ndakiyuk.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontrol Data User - NdakiYuk Admin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .main-wrapper { width: 100%; overflow-x: hidden; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(5, 26, 15, 0.7); backdrop-filter: blur(5px); z-index: 2000; align-items: center; justify-content: center; padding: 20px; }
        .modal-overlay.open { display: flex; }
        .modal-box { background-color: var(--bg-card); border: 1px solid var(--border-color); padding: 28px; border-radius: var(--radius-lg); max-width: 520px; width: 100%; box-shadow: 0 15px 40px rgba(0,0,0,0.6); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px; }
        .modal-close-btn { background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: 20px; }
        .modal-close-btn:hover { color: var(--red-text); }
        .form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
        .form-group label { font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; background-color: var(--bg-main); border: 1px solid var(--border-color); padding: 10px 14px; border-radius: var(--radius-md); color: var(--text-title); font-family: inherit; font-size: 13px; outline: none; transition: var(--transition); }
        .form-control:focus { border-color: var(--green-text); }
        
        /* ── CONFIG STYLING SEARCH BAR ELEGAN (VECTOR-BASED) ── */
        .search-wrapper-panel { display: flex; gap: 12px; margin-bottom: 20px; width: 100%; }
        .search-input-container { position: relative; flex: 1; display: flex; }
        .search-input-field { width: 100%; padding: 12px 16px 12px 46px; background-color: var(--bg-card); border: 1px solid var(--border-color); border-radius: var(--radius-md); color: var(--text-title); font-family: inherit; font-size: 13.5px; outline: none; transition: var(--transition); }
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
            
            <div class="header-banner" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                <div>
                    <small class="header-pretitle">OTORITAS MANIPULASI SISTEM HAK AKSES GLOBAL</small>
                    <h1 class="header-welcome">Data Kendali User</h1>
                </div>
                <button type="button" class="btn-action-acc" onclick="openUserModal()" style="padding: 10px 20px;">
                    + Registrasi Akun Baru
                </button>
            </div>

            <?php if (!empty($message)): ?>
                <div class="content-section" style="border-left: 4px solid <?= $messageType === 'success' ? 'var(--green-text)' : 'var(--red-text)' ?>; padding: 16px 20px; margin-bottom: 20px; background-color: <?= $messageType === 'success' ? 'var(--green-soft)' : 'var(--red-soft)' ?>;">
                    <span style="font-size: 13px; font-weight: 600; color: <?= $messageType === 'success' ? 'var(--green-text)' : 'var(--red-text)' ?>;"><?= $message; ?></span>
                </div>
            <?php endif; ?>

            <?php
            // Konstruksi Query Data User Multi-Relational Dinamis
            $sql_base_users = "
                SELECT u.*, up.nama_lengkap, up.nik, up.no_hp, r.nama_role 
                FROM users u
                JOIN user_profile up ON u.id_user = up.id_user
                JOIN roles r         ON u.id_role = r.id_role
            ";

            // Injeksi klausa penapis jika Admin mengetik sesuatu di Search Bar
            if (!empty($search_keyword)) {
                $sql_base_users .= " WHERE up.nama_lengkap LIKE '%$search_keyword%' 
                                      OR u.username LIKE '%$search_keyword%' 
                                      OR u.email LIKE '%$search_keyword%' 
                                      OR r.nama_role LIKE '%$search_keyword%'";
            }

            $sql_base_users .= " ORDER BY u.id_user DESC";
            $query_users = mysqli_query($conn, $sql_base_users);
            $total_rows = mysqli_num_rows($query_users);
            ?>

            <div class="content-section">
                <div class="section-header" style="flex-wrap: wrap; gap: 16px;">
                    <div>
                        <h3 class="section-title">Pangkalan Autentikasi Kredensial Pengguna</h3>
                        <span class="section-badge">Total Terdaftar: <?= $total_rows; ?> Akun</span>
                    </div>
                </div>

                <div class="search-wrapper-panel">
                    <form method="GET" action="" class="search-input-container">
                        <svg class="search-icon-vector" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <input type="text" name="search" class="search-input-field" placeholder="Cari nama lengkap, @username, email, atau tingkatan role akses..." value="<?= htmlspecialchars($search_keyword); ?>">
                    </form>
                    <?php if (!empty($search_keyword)): ?>
                        <a href="data_user.php" class="btn-clear-search">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            Clear
                        </a>
                    <?php endif; ?>
                </div>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Nama Lengkap Pengguna</th>
                                <th>Username</th>
                                <th>Alamat Email</th>
                                <th>NIK / No KTP (Masked)</th>
                                <th>No. Handphone (Masked)</th>
                                <th>Level Akses</th>
                                <th style="text-align: center;">Konfigurasi Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_rows > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($query_users)): ?>
                                    <?php
                                        // PENGAMANAN SENSITIF: Algoritma Masking Data Rahasia Sipil
                                        $raw_nik = $row['nik'];
                                        $masked_nik = (!empty($raw_nik) && !str_contains($raw_nik, 'NEW-USER')) ? substr($raw_nik, 0, 6) . "XXXXXXXX" . substr($raw_nik, -2) : '—';
                                        
                                        $raw_hp = $row['no_hp'];
                                        $masked_hp = (!empty($raw_hp) && $raw_hp != '—') ? substr($raw_hp, 0, 4) . "XXXX" . substr($raw_hp, -3) : '—';

                                        // Konfigurasi Badge Klasifikasi Role
                                        $role_badge = "status-pending";
                                        if ($row['id_role'] == 3) { $role_badge = "status-danger"; } 
                                        elseif ($row['id_role'] == 2) { $role_badge = "status-success"; } 
                                    ?>
                                    <tr>
                                        <td><strong class="hiker-name"><?= htmlspecialchars($row['nama_lengkap']); ?></strong></td>
                                        <td class="date-text">@<?= htmlspecialchars($row['username']); ?></td>
                                        <td class="date-text"><?= htmlspecialchars($row['email']); ?></td>
                                        <td class="bold-code" style="color: var(--text-secondary);"><?= $masked_nik; ?></td>
                                        <td class="count-text"><?= $masked_hp; ?></td>
                                        <td><span class="badge-status <?= $role_badge; ?>"><?= htmlspecialchars($row['nama_role']); ?></span></td>
                                        <td style="text-align: center;">
                                            <div style="display: flex; gap: 6px; justify-content: center;">
                                                <button type="button" class="btn-action-acc" style="padding: 5px 10px; font-size: 11.5px; background-color: var(--accent-hover);"
                                                    onclick='openUserModal(<?= json_encode($row); ?>)'>
                                                    Ubah Role
                                                </button>
                                                <?php if($row['id_user'] != $id_user_logged): ?>
                                                    <a href="data_user.php?action=delete&id=<?= $row['id_user']; ?><?= !empty($search_keyword) ? '&search='.urlencode($search_keyword) : ''; ?>" class="btn-action-reject" style="padding: 5px 10px; font-size: 11.5px;" 
                                                       onclick="return confirm('Apakah Anda yakin ingin menghapus akun @<?= htmlspecialchars($row['username']); ?> secara permanen? Seluruh riwayat transaksi simaksi miliknya akan hangus.');">
                                                        Hapus
                                                    </a>
                                                <?php else: ?>
                                                    <span style="font-size: 11px; color: var(--text-muted); font-style: italic;">Sesi Aktif</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="empty-row-text">Tidak ada data pengguna yang cocok dengan kata kunci pencarian Anda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</div>

<div class="modal-overlay" id="userModal" onclick="closeIfOverlay(event)">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="chart-title" id="modalTitle" style="font-size: 15px;">Registrasi Otentik Akun Baru</h3>
            <button class="modal-close-btn" type="button" onclick="closeUserModal()">&times;</button>
        </div>
        
        <form method="POST" action="data_user.php<?= !empty($search_keyword) ? '?search='.urlencode($search_keyword) : ''; ?>">
            <input type="hidden" name="id_user" id="formIdUser" value="0">

            <div class="form-group">
                <label>Nama Lengkap Identitas</label>
                <input type="text" name="nama_lengkap" id="formNama" class="form-control" placeholder="Contoh: Kusuma Adi Rabbani" required>
            </div>

            <div class="form-group">
                <label>Username Akun</label>
                <input type="text" name="username" id="formUsername" class="form-control" placeholder="Contoh: kusuma_adi" required>
            </div>

            <div class="form-group">
                <label>Alamat Surat Elektronik (Email)</label>
                <input type="email" name="email" id="formEmail" class="form-control" placeholder="Contoh: adi@gmail.com" required>
            </div>

            <div class="form-group">
                <label>Kata Sandi / Password</label>
                <input type="password" name="password" id="formPassword" class="form-control" placeholder="Kosongkan jika tidak ingin mengubah password lama">
                <small id="passwordHint" style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">Default sandi pengguna baru jika kosong: <strong>123456</strong></small>
            </div>

            <div class="form-group">
                <label>Hak Otoritas Kelas Akses (Role)</label>
                <select name="id_role" id="formRole" class="form-control" required>
                    <option value="1">Pengguna (Pendaki Nusantara)</option>
                    <option value="2">Mitra (Otoritas Pintu Basecamp)</option>
                    <option value="3">Admin (Super Administrator Pusat)</option>
                </select>
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end; border-top: 1px solid var(--border-color); padding-top: 16px; margin-top: 20px;">
                <button type="button" class="btn-action-reject" onclick="closeUserModal()">Batalkan</button>
                <button type="submit" name="simpan_user" class="btn-action-acc">Simpan Autentikasi</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal(dataObj = null) {
    const modal     = document.getElementById('userModal');
    const title     = document.getElementById('modalTitle');
    const pwdHint   = document.getElementById('passwordHint');
    
    const inputId       = document.getElementById('formIdUser');
    const inputNama     = document.getElementById('formNama');
    const inputUsername = document.getElementById('formUsername');
    const inputEmail    = document.getElementById('formEmail');
    const inputPassword = document.getElementById('formPassword');
    const inputRole     = document.getElementById('formRole');

    if (dataObj) {
        title.innerText        = "Ubah Otoritas Hak Akses Akun";
        pwdHint.innerHTML      = "Isi kolom jika ingin memaksa reset/mengganti password akun ini.";
        inputId.value          = dataObj.id_user;
        inputNama.value        = dataObj.nama_lengkap;
        inputUsername.value    = dataObj.username;
        inputEmail.value       = dataObj.email;
        inputPassword.value    = "";
        inputRole.value        = dataObj.id_role;
    } else {
        title.innerText        = "Registrasi Otentik Akun Baru";
        pwdHint.innerHTML      = "Default sandi pengguna baru jika kosong: <strong>123456</strong>";
        inputId.value          = "0";
        inputNama.value        = "";
        inputUsername.value    = "";
        inputEmail.value       = "";
        inputPassword.value    = "";
        inputRole.value        = "1";
    }
    modal.classList.add('open');
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('open');
}

function closeIfOverlay(e) {
    if (e.target === document.getElementById('userModal')) closeUserModal();
}
</script>
</body>
</html>