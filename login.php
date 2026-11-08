<?php
// login.php
session_start();

// JIKA USER SUDAH LOGIN, OTOMATIS REDIRECT KE HALAMAN MASING-MASING AGAR TIDAK DOUBLE LOGIN
if (isset($_SESSION['id_user'])) {
    if (isset($_SESSION['admin_logged_in'])) {
        header("Location: admin/dashboard.php");
        exit;
    } elseif (isset($_SESSION['mitra_logged_in'])) {
        header("Location: mitra/dashboard.php");
        exit;
    } elseif (isset($_SESSION['pendaki_logged_in'])) {
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Merriweather:ital,wght@0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <main class="hero">
        <div class="hero-bg">
            <div class="hero-overlay"></div>
        </div>

        <div class="hero-content">
            <div class="hero-text">
                <h1 class="hero-title">
                    Jelajahi Puncak<br>
                    <span class="hero-accent">Tertinggi</span><br>
                    Nusantara
                </h1>
                <p class="hero-desc">
                    Masuk untuk melanjutkan rencana pendakian Anda dan temukan komunitas pendaki profesional dari seluruh penjuru negeri.
                </p>
                <div class="hero-stats">
                    <div class="stat">
                        <span class="stat-number">150+</span>
                        <span class="stat-label">Gunung</span>
                    </div>
                    <div class="stat-divider"></div>
                    <div class="stat">
                        <span class="stat-number">12k+</span>
                        <span class="stat-label">Pendaki</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-wrapper">
            <div class="login-card">
                <div class="login-header">
                    <h2 class="login-title">Selamat Datang<br>Kembali</h2>
                    <p class="login-subtitle">Siapkan perlengkapanmu, puncak menunggumu hari ini.</p>
                </div>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-error" style="background-color: rgba(255, 107, 107, 0.12); color: #ff6b6b; padding: 12px; border-radius: 8px; border: 1px solid rgba(255, 107, 107, 0.2); font-size: 13px; margin-bottom: 16px; font-weight: 500;">
                        <?php
                        if ($_GET['error'] == 'password') {
                            echo "Password yang Anda masukkan salah!";
                        } elseif ($_GET['error'] == 'user') {
                            echo "Akun tidak ditemukan di database!";
                        } elseif (str_contains($_GET['error'], 'unauthorized')) {
                            echo "Sesi login tumpang tindih! Otoritas ditolak, silakan masuk kembali.";
                        } elseif ($_GET['error'] == 'role_unknown') {
                            echo "Hak akses akun tidak terdefinisi.";
                        } else {
                            echo "Gagal masuk, terjadi kesalahan otentikasi.";
                        }
                        ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="auth/login_process.php" class="login-form" novalidate>
                    <div class="form-group">
                        <label for="username" class="form-label">Username atau Email</label>
                        <div class="input-wrap">
                            <span class="input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-input"
                                placeholder="Masukkan username atau email Anda"
                                required
                                autocomplete="username">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label for="password" class="form-label">Password</label>
                            <a href="lupa_password.php" class="link-forgot">Lupa password?</a>
                        </div>
                        <div class="input-wrap">
                            <span class="input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="••••••••"
                                required
                                autocomplete="current-password">
                            <button type="button" class="toggle-pw" onclick="togglePassword()" aria-label="Tampilkan password" style="background: none; border: none; cursor: pointer; color: inherit;">
                                <svg id="eye-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path id="eye-path" d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-login">
                        Masuk Sekarang
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12" />
                            <polyline points="12 5 19 12 12 19" />
                        </svg>
                    </button>
                </form>

                <p class="login-register">
                    Belum punya akun? <a href="register.php" class="link-register">Daftar Sekarang</a>
                </p>
            </div>
        </div>
    </main>

    <?php include 'components/footer.php'; ?>

    <script src="assets/js/navbar.js"></script>
    <script>
        // CONTROLLER INTERAKTIF TOGGLE REVEAL PASSWORD FIELD
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyePath = document.getElementById('eye-path');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                // Ganti ikon mata menjadi coret jika dalam mode teks transparan (opsional, bawaan tetap render)
            } else {
                passwordInput.type = 'password';
            }
        }
    </script>
</body>

</html>