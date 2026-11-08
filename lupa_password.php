<?php
session_start();
include 'config/koneksi.php';

$error = ''; $success = ''; $info_otp = ''; $step = 1;

// Fungsi standarisasi nomor HP
function formatWhatsAppNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (substr($phone, 0, 1) === '0') return '62' . substr($phone, 1);
    return $phone;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ── STEP 1: VERIFIKASI USERNAME & NIK (MENGAMBIL NO_HP OTOMATIS) ─────
    if (isset($_POST['submit_verify'])) {
        $username = trim($_POST['username'] ?? '');
        $nik      = trim($_POST['nik'] ?? '');

        if (empty($username) || empty($nik)) {
            $error = 'Harap isi Username dan NIK Anda.';
        } else {
            $stmt = $conn->prepare("
                SELECT u.id_user, up.no_hp, up.nama_lengkap 
                FROM users u
                JOIN user_profile up ON u.id_user = up.id_user
                WHERE u.username = ? AND up.nik = ?
            ");
            $stmt->bind_param('ss', $username, $nik);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows > 0) {
                $user = $res->fetch_assoc();
                $target_wa = formatWhatsAppNumber($user['no_hp']);
                
                // Simpan state
                $_SESSION['reset_user_id'] = $user['id_user'];
                $_SESSION['otp_code']      = rand(100000, 999999);
                $_SESSION['otp_expires']   = time() + 300;

                // Kirim OTP via Fonnte
                $token = 'uW1Gvec6AvrtLVguskqC';
                $pesan = "Halo *{$user['nama_lengkap']}*, kode verifikasi OTP akun NdakiYuk Anda adalah: *{$_SESSION['otp_code']}*. Berlaku 5 menit.";

                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.fonnte.com/send',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => ['target' => $target_wa, 'message' => $pesan, 'countryCode' => '62'],
                    CURLOPT_HTTPHEADER => ['Authorization: ' . $token],
                    CURLOPT_SSL_VERIFYPEER => false
                ]);
                curl_exec($curl);
                curl_close($curl);

                $step = 2;
                $info_otp = "Kode OTP dikirim ke WA profil: " . substr($target_wa, 0, 7) . "****";
            } else {
                $error = 'Username atau NIK tidak ditemukan!';
            }
        }
    }

    // ── STEP 2: VERIFIKASI OTP ──────────────────────────────────────────
    if (isset($_POST['submit_otp'])) {
        if ($_POST['otp'] == ($_SESSION['otp_code'] ?? '')) {
            if (time() < ($_SESSION['otp_expires'] ?? 0)) {
                $step = 3; $_SESSION['otp_verified'] = true;
            } else { $error = 'OTP Kadaluarsa.'; $step = 1; }
        } else { $error = 'Kode OTP salah.'; $step = 2; }
    }

    // ── STEP 3: RESET PASSWORD ──────────────────────────────────────────
    if (isset($_POST['submit_reset'])) {
        if ($_POST['new_password'] === $_POST['confirm_password'] && strlen($_POST['new_password']) >= 5) {
            $hashed = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
            $id = $_SESSION['reset_user_id'];
            $conn->query("UPDATE users SET password = '$hashed' WHERE id_user = $id");
            $success = 'Password berhasil diubah!';
            session_destroy();
            header("Refresh: 2; url=login.php");
        } else { $error = 'Password tidak cocok atau terlalu pendek.'; $step = 3; }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <link rel="stylesheet" href="assets/css/style.css">
    <meta charset="UTF-8">
    <title>NdakiYuk – Pemulihan Akun</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>

<?php include 'components/navbar.php'; ?>

<main class="hero">
    <div class="hero-bg"><div class="hero-overlay"></div></div>
    <div class="hero-content">
        <div class="hero-text">
            <h1 class="hero-title">Pemulihan<br><span class="hero-accent">Akun</span> Pendaki</h1>
            <p class="hero-desc">Verifikasi identitas dengan NIK untuk keamanan akun Anda.</p>
        </div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h2>Reset Sandi</h2>
            </div>
            
            <?php if($error) echo "<div class='alert alert-error'>$error</div>"; ?>
            <?php if($success) echo "<div class='alert alert-success'>$success</div>"; ?>
            <?php if($info_otp) echo "<div class='alert alert-success' style='background:rgba(40,167,69,0.15); border:1px solid rgba(40,167,69,0.4); color:#7fff8a;'>$info_otp</div>"; ?>

            <form method="POST" class="login-form">
                <?php if($step == 1): ?>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">NIK KTP</label>
                        <input type="text" name="nik" class="form-input" required>
                    </div>
                    <button type="submit" name="submit_verify" class="btn-login">Verifikasi Data</button>
                <?php elseif($step == 2): ?>
                    <div class="form-group">
                        <label class="form-label">Masukkan 6 Digit OTP</label>
                        <input type="text" name="otp" class="form-input" maxlength="6" required>
                    </div>
                    <button type="submit" name="submit_otp" class="btn-login">Verifikasi OTP</button>
                <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Password Baru</label>
                        <input type="password" name="new_password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" name="submit_reset" class="btn-login">Simpan Password</button>
                <?php endif; ?>
            </form>
            <p class="login-register"><a href="login.php" class="link-register">Kembali ke Login</a></p>
        </div>
    </div>
</main>
</body>
</html>