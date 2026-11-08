<?php
// register.php
$errors  = [];
$success = '';
$old     = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'nama'     => trim($_POST['nama_lengkap'] ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'email'    => trim($_POST['email']    ?? ''),
        'nik'      => trim($_POST['nik']      ?? ''),
        'phone'    => trim($_POST['no_hp']    ?? ''),
    ];
    $password   = $_POST['password']   ?? '';
    $konfirmasi = $_POST['konfirmasi'] ?? '';
    $setuju     = isset($_POST['setuju']);

    if (empty($old['nama']))     $errors['nama']     = 'Nama lengkap wajib diisi.';
    if (empty($old['username'])) $errors['username'] = 'Username wajib diisi.';
    if (empty($old['email']) || !filter_var($old['email'], FILTER_VALIDATE_EMAIL))
        $errors['email']    = 'Email tidak valid.';
    if (empty($old['nik']) || strlen($old['nik']) !== 16 || !ctype_digit($old['nik']))
        $errors['nik']      = 'NIK harus 16 digit angka.';
    if (empty($old['phone']))    $errors['phone']    = 'Nomor HP wajib diisi.';
    if (strlen($password) < 8)   $errors['password'] = 'Password minimal 8 karakter.';
    if ($password !== $konfirmasi) $errors['konfirmasi'] = 'Konfirmasi password tidak cocok.';
    if (!$setuju)                $errors['setuju']   = 'Anda harus menyetujui syarat & ketentuan.';

    if (empty($errors)) {
        $success = 'Akun berhasil dibuat! Silakan masuk ke akun Anda.';
        $old = [];
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Buat Akun</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/register.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <main class="hero">
        <div class="hero-bg">
            <div class="hero-overlay"></div>
        </div>

        <div class="hero-content">
            <div class="hero-badge">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polygon points="3 11 22 2 13 21 11 13 3 11" />
                </svg>
                Ndakiyuk
            </div>
            <h1 class="hero-title">Puncak impian<br>Anda menanti.</h1>
            <p class="hero-desc">
                Daftar akun Ndakiyuk hari ini dan mulai rencanakan penaklukan gunung impian Anda.
                Bagikan ulasan perjalanan, pesan tiket pendakian dengan mudah, dan bergabunglah dengan
                komunitas pendaki profesional.
            </p>
            <div class="hero-avatars">
                <div class="avatar-group">
                    <img src="https://i.pravatar.cc/36?img=11" alt="Pendaki" class="avatar">
                    <img src="https://i.pravatar.cc/36?img=22" alt="Pendaki" class="avatar">
                    <img src="https://i.pravatar.cc/36?img=33" alt="Pendaki" class="avatar">
                </div>
                <span class="avatar-label">+10 Pendaki Aktif</span>
            </div>
        </div>

        <div class="register-wrapper">
            <div class="register-card">
                <div class="register-header">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-error">
                            <?php
                            if ($_GET['error'] == 'password') {
                                echo "Konfirmasi password tidak cocok";
                            } elseif ($_GET['error'] == 'userexists') {
                                echo "Username atau Email sudah digunakan";
                            } elseif ($_GET['error'] == 'nik_invalid') {
                                echo "Format NIK salah! NIK harus tepat berisikan 16 digit angka.";
                            } else {
                                echo "Registrasi gagal";
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                    <h2 class="register-title">Buat Akun</h2>
                    <p class="register-subtitle">Siapkan perlengkapanmu, petualangan baru akan segera dimulai.</p>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <polyline points="20 6 9 17 4 12" />
                        </svg>
                        <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                        Mohon periksa kembali isian form Anda.
                    </div>
                <?php endif; ?>

                <form method="POST" action="auth/register_process.php" class="register-form">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="nama">Nama Lengkap</label>
                            <input type="text" id="nama" name="nama_lengkap" required
                                class="form-input" placeholder="Contoh: John Doe"
                                value="<?= htmlspecialchars($old['nama'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="username">Username</label>
                            <input type="text" id="username" name="username" required
                                class="form-input" placeholder="ndakiyuk_id"
                                value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <div class="input-wrap">
                            <span class="input-icon">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="4" />
                                    <path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-3.92 7.94" />
                                </svg>
                            </span>
                            <input type="email" id="email" name="email" required
                                class="form-input has-icon" placeholder="contoh@email.com"
                                value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="nik">NIK (Sesuai KTP)</label>
                        <input type="text" id="nik" name="nik" required
                            class="form-input <?= isset($errors['nik']) ? 'is-error' : '' ?>"
                            placeholder="Masukkan 16 digit Nomor NIK"
                            inputmode="numeric"
                            minlength="16"
                            maxlength="16"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '').substring(0, 16);"
                            value="<?= htmlspecialchars($old['nik'] ?? '') ?>">
                        <?php if (isset($errors['nik'])): ?>
                            <span class="field-error"><?= htmlspecialchars($errors['nik']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="phone">Nomor HP / WhatsApp</label>
                        <input type="tel" id="phone" name="no_hp" required
                            class="form-input" placeholder="+628123456789"
                            value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-wrap">
                                <input type="password" id="password" name="password" required
                                    class="form-input has-toggle" placeholder="••••••••">
                                <button type="button" class="toggle-pw" onclick="togglePw('password','eye1')">
                                    <svg id="eye1" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="konfirmasi">Konfirmasi Password</label>
                            <div class="input-wrap">
                                <input type="password" id="konfirmasi" name="konfirmasi" required
                                    class="form-input has-toggle" placeholder="••••••••">
                                <button type="button" class="toggle-pw" onclick="togglePw('konfirmasi','eye2')">
                                    <svg id="eye2" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                        <circle cx="12" cy="12" r="3" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="form-check">
                        <label class="check-label">
                            <input type="checkbox" name="setuju" class="check-input" required>
                            <span class="check-box"></span>
                            <span class="check-text">
                                Saya menyetujui <a href="#" class="link-terms">Syarat &amp; Ketentuan</a> serta <a href="#" class="link-terms">Kebijakan Privasi</a> Ndakiyuk.
                            </span>
                        </label>
                    </div>

                    <button type="submit" class="btn-register">
                        Daftar Sekarang
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </form>

                <p class="register-login">
                    Sudah punya akun? <a href="login.php" class="link-login">Masuk</a>
                </p>
            </div>
        </div>
    </main>

    <script>
        function togglePw(inputId, eyeId) {
            const inputField = document.getElementById(inputId);
            const eyeIcon = document.getElementById(eyeId);
            if (inputField && eyeIcon) {
                if (inputField.type === "password") {
                    inputField.type = "text";
                    eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
                } else {
                    inputField.type = "password";
                    eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>';
                }
            }
        }

        const navbar = document.querySelector('.navbar');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 30) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
    <?php include 'components/footer.php'; ?>
</body>

</html>