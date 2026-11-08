<?php
session_start();
include "config/koneksi.php";
include "auth/cek_role.php";
hanyaPendakiRoot();

// JIKA BELUM LOGIN, LEMPAR KE LOGIN (Fitur Terkunci)
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$message = "";
$messageType = "";

// =========================================================================
// PROSES LOGIKA UPDATE DATA & UPLOAD FOTO PROFILE
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
        $message = "Username atau Email sudah terdaftar oleh pengguna lain.";
        $messageType = "error";
    } else {
        // Pembaruan data tabel 'users'
        $updateUsers = mysqli_query($conn, "UPDATE users SET username='$username', email='$email' WHERE id_user='$id_user'");

        // Pembaruan data tabel 'user_profile' termasuk kolom bio baru
        $updateProfile = mysqli_query($conn, "UPDATE user_profile SET nama_lengkap='$nama_lengkap', no_hp='$no_hp', bio='$bio' WHERE id_user='$id_user'");

        // Handle Upload Foto Profile jika ada file baru yang masuk
        if (isset($_FILES['foto_profile']) && $_FILES['foto_profile']['error'] !== UPLOAD_ERR_NO_FILE) {

            // Cek error upload dari PHP/server terlebih dahulu
            if ($_FILES['foto_profile']['error'] !== UPLOAD_ERR_OK) {

                if ($_FILES['foto_profile']['error'] == UPLOAD_ERR_INI_SIZE) {
                    $message = "Gagal upload gambar, ukuran maksimal adalah 2MB.";
                } elseif ($_FILES['foto_profile']['error'] == UPLOAD_ERR_FORM_SIZE) {
                    $message = "Gagal upload gambar, ukuran maksimal adalah 2MB.";
                } elseif ($_FILES['foto_profile']['error'] == UPLOAD_ERR_PARTIAL) {
                    $message = "Gagal upload gambar, file hanya terupload sebagian. Silakan coba lagi.";
                } else {
                    $message = "Gagal upload gambar. Silakan coba lagi.";
                }

                $messageType = "error";
            } else {

                $fileTmpPath = $_FILES['foto_profile']['tmp_name'];
                $fileName    = $_FILES['foto_profile']['name'];
                $fileSize    = $_FILES['foto_profile']['size'];

                $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $allowedExtensions = ['jpg', 'jpeg', 'png'];

                if (in_array($fileExtension, $allowedExtensions)) {

                    if ($fileSize <= 2 * 1024 * 1024) { // Maksimal ukuran 2MB

                        $newFileName = 'profile_' . $id_user . '_' . time() . '.' . $fileExtension;
                        $uploadFileDir = 'assets/img/profile/';
                        $dest_path = $uploadFileDir . $newFileName;

                        if (!is_dir($uploadFileDir)) {
                            mkdir($uploadFileDir, 0755, true);
                        }

                        if (!is_writable($uploadFileDir)) {
                            $message = "Folder upload gambar tidak bisa ditulis.";
                            $messageType = "error";
                        } elseif (move_uploaded_file($fileTmpPath, $dest_path)) {
                            mysqli_query($conn, "UPDATE user_profile SET foto_profile='$dest_path' WHERE id_user='$id_user'");

                            // Update session agar navbar langsung mengenali perubahan
                            $_SESSION['foto_profile'] = $dest_path;
                        } else {
                            $message = "Gagal menyimpan gambar. Silakan coba lagi.";
                            $messageType = "error";
                        }
                    } else {
                        $message = "Gagal upload gambar, ukuran maksimal adalah 2MB.";
                        $messageType = "error";
                    }
                } else {
                    $message = "Format gambar ditolak. Hanya menerima ekstensi JPG, JPEG, dan PNG.";
                    $messageType = "error";
                }
            }
        }

        if ($updateUsers && $updateProfile && empty($message)) {
            $message = "Profil berhasil diperbarui!";
            $messageType = "success";

            // TAMBAHKAN BARIS INI:
            $_SESSION['nama_lengkap'] = $nama_lengkap;
        }
    }
}

// =========================================================================
// AMBIL DATA PENGGUNA TERBARU DARI DATABASE BERDASARKAN SESSION ID
// =========================================================================
$queryUser = mysqli_query($conn, "
    SELECT users.username, users.email, user_profile.* FROM users 
    JOIN user_profile ON users.id_user = user_profile.id_user 
    WHERE users.id_user = '$id_user'
");
$userData = mysqli_fetch_assoc($queryUser);

// =========================================================================
// HITUNG STATISTIK PENDAKIAN USER
// =========================================================================

// Booking selesai = jumlah booking user yang status_pemesanan sudah Selesai
$queryBookingSelesai = mysqli_query($conn, "
    SELECT COUNT(*) AS total_booking_selesai
    FROM pemesanan
    WHERE id_user = '$id_user'
      AND status_pemesanan = 'Selesai'
");

$dataBookingSelesai = mysqli_fetch_assoc($queryBookingSelesai);
$booking_selesai = (int) ($dataBookingSelesai['total_booking_selesai'] ?? 0);


// Gunung ditaklukkan = jumlah gunung berbeda yang pernah selesai didaki user
$queryGunungDitaklukkan = mysqli_query($conn, "
    SELECT COUNT(DISTINCT id_gunung) AS total_gunung_ditaklukkan
    FROM pemesanan
    WHERE id_user = '$id_user'
      AND status_pemesanan = 'Selesai'
");

$dataGunungDitaklukkan = mysqli_fetch_assoc($queryGunungDitaklukkan);
$gunung_ditaklukkan = (int) ($dataGunungDitaklukkan['total_gunung_ditaklukkan'] ?? 0);


// =========================================================================
// DATA USER UNTUK DITAMPILKAN KE HALAMAN PROFIL
// =========================================================================
$user = [
    'nama'     => $userData['nama_lengkap'],
    'level'    => 'Pendaki',
    'bio'      => !empty($userData['bio']) ? $userData['bio'] : 'Menjelajahi puncak-puncak nusantara bersama NdakiYuk!',
    'username' => $userData['username'],
    'email'    => $userData['email'],
    'phone'    => !empty($userData['no_hp']) ? $userData['no_hp'] : '-',
    'avatar'   => !empty($userData['foto_profile']) ? $userData['foto_profile'] : 'assets/img/profile/default.jpg',

    // Statistik dinamis dari database
    'gunung_ditaklukkan' => $gunung_ditaklukkan,
    'booking_selesai'    => $booking_selesai,
];

$peralatan = [];
$peralatan_extra = 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Profil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/profil.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <main class="page-main">
        <div class="profile-container">

            <!-- Notifikasi Status Update -->
            <?php if (!empty($message)): ?>
                <div style="padding: 14px; margin-bottom: 10px; border-radius: 10px; font-size: 0.88rem; font-weight: 600; text-align: center; 
                <?= $messageType === 'success' ? 'background: rgba(40,167,69,0.15); color: #28a745; border: 1px solid #28a745;' : 'background: rgba(220,53,69,0.15); color: #dc3545; border: 1px solid #dc3545;' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- ── PROFIL CARD ─────────────────────────── -->
            <div class="profile-card" style="animation-delay:0s">
                <div class="profile-card-left">
                    <div class="avatar-wrap">
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="Avatar <?= htmlspecialchars($user['nama']) ?>" class="avatar-img">
                        <div class="avatar-badge">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <polyline points="20 6 9 17 4 12" />
                            </svg>
                        </div>
                    </div>

                    <div class="profile-info">
                        <h1 class="profile-nama"><?= htmlspecialchars($user['nama']) ?></h1>
                        <span class="level-badge">
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
                            </svg>
                            <?= htmlspecialchars($user['level']) ?>
                        </span>
                        <!-- Menampilkan data bio dinamis dari variabel array $user -->
                        <p class="profile-bio"><?= htmlspecialchars($user['bio']) ?></p>
                    </div>
                </div>

                <div class="profile-card-actions">
                    <a href="#" class="btn-edit" onclick="openEditModal(event)">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" />
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" />
                        </svg>
                        Edit Profile
                    </a>
                    <a href="auth/logout.php" class="btn-logout">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <polyline points="16 17 21 12 16 7" />
                            <line x1="21" y1="12" x2="9" y2="12" />
                        </svg>
                        Logout
                    </a>
                </div>
            </div>

            <!-- ── BOTTOM SECTION ──────────────────────── -->
            <div class="bottom-grid">
                <div class="info-card" style="animation-delay:0.1s">
                    <div class="card-section-title">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                            <circle cx="12" cy="7" r="4" />
                        </svg>
                        Informasi Akun
                    </div>

                    <div class="info-fields">
                        <div class="info-field">
                            <label class="field-label">USERNAME</label>
                            <div class="field-value-wrap">
                                <span class="field-value">@<?= htmlspecialchars($user['username']) ?></span>
                                <span class="field-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div class="info-field">
                            <label class="field-label">EMAIL ADDRESS</label>
                            <div class="field-value-wrap">
                                <span class="field-value"><?= htmlspecialchars($user['email']) ?></span>
                                <span class="field-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="4" />
                                        <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94" />
                                    </svg>
                                </span>
                            </div>
                        </div>

                        <div class="info-field">
                            <label class="field-label">PHONE NUMBER</label>
                            <div class="field-value-wrap">
                                <span class="field-value"><?= htmlspecialchars($user['phone']) ?></span>
                                <span class="field-icon">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 13a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.59 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z" />
                                    </svg>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="right-column">
                    <div class="stats-card" style="animation-delay:0.2s">
                        <div class="stats-header">Statistik Pendakian</div>
                        <div class="stats-body">
                            <div class="stat-item">
                                <span class="stat-number"><?= $user['gunung_ditaklukkan'] ?></span>
                                <span class="stat-label">Gunung Ditaklukkan</span>
                            </div>
                            <div class="stat-divider"></div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $user['booking_selesai'] ?></span>
                                <span class="stat-label">Booking Selesai</span>
                            </div>
                            <div class="stats-icon-bg">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <polygon points="3 11 22 2 13 21 11 13 3 11" />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div class="rental-card" style="animation-delay:0.3s">
                        <div class="rental-header">HISTORY PENDAKIAN</div>
                        <div class="rental-body">
                            <div class="rental-avatars">
                                <?php if ($peralatan_extra > 0): ?>
                                    <div class="rental-extra">+<?= $peralatan_extra ?></div>
                                <?php endif; ?>
                            </div>
                            <a href="history.php" class="rental-link">
                                Lihat Detail
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                    <line x1="5" y1="12" x2="19" y2="12" />
                                    <polyline points="12 5 19 12 12 19" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ===================================================
     MODAL EDIT PROFILE
==================================================== -->
    <div class="modal-overlay" id="editModal" onclick="closeIfOverlay(event)">
        <div class="modal-box">
            <div class="modal-header">
                <h3 class="modal-title">Edit Profile</h3>
                <button class="modal-close" onclick="closeEditModal()" aria-label="Tutup">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18" />
                        <line x1="6" y1="6" x2="18" y2="18" />
                    </svg>
                </button>
            </div>

            <form class="modal-form" action="profil.php" method="POST" enctype="multipart/form-data">
                <div class="modal-avatar-row">
                    <img src="<?= htmlspecialchars($user['avatar']) ?>" class="modal-avatar" id="previewImg" alt="Preview">
                    <div>
                        <label for="avatarInput" class="btn-change-photo">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z" />
                                <circle cx="12" cy="13" r="4" />
                            </svg>
                            Ganti Foto
                        </label>
                        <input type="file" id="avatarInput" name="foto_profile" accept="image/*" style="display:none" onchange="previewPhoto(event)">
                        <p class="photo-hint">JPG, JPEG, PNG maks. 2MB</p>
                    </div>
                </div>

                <div class="mform-row">
                    <div class="mform-group">
                        <label class="mform-label">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="mform-input" value="<?= htmlspecialchars($user['nama']) ?>" required>
                    </div>
                    <div class="mform-group">
                        <label class="mform-label">Username</label>
                        <input type="text" name="username" class="mform-input" value="<?= htmlspecialchars($user['username']) ?>" required>
                    </div>
                </div>

                <div class="mform-group">
                    <label class="mform-label">Email Address</label>
                    <input type="email" name="email" class="mform-input" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>

                <div class="mform-group">
                    <label class="mform-label">Phone Number</label>
                    <input type="tel" name="no_hp" class="mform-input" value="<?= htmlspecialchars($user['phone'] === '-' ? '' : $user['phone']) ?>">
                </div>

                <div class="mform-group">
                    <label class="mform-label">Bio</label>
                    <textarea name="bio" class="mform-input mform-textarea" rows="3" placeholder="Tulis bio singkat Anda..."><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="update_profile" class="btn-save-modal">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const navbar = document.getElementById('navbar');
        window.addEventListener('scroll', () => {
            navbar.classList.toggle('scrolled', window.scrollY > 30);
        });

        function openEditModal(e) {
            e.preventDefault();
            document.getElementById('editModal').classList.add('open');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('open');
            document.body.style.overflow = '';
        }

        function closeIfOverlay(e) {
            if (e.target === document.getElementById('editModal')) closeEditModal();
        }

        function previewPhoto(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => document.getElementById('previewImg').src = ev.target.result;
            reader.readAsDataURL(file);
        }
    </script>
</body>

</html>