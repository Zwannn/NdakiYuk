<?php
// auth/login_process.php
session_start();
include "../config/koneksi.php";

// JIKA DATA FORM TIDAK MASUK, KEMBALIKAN KE HALAMAN LOGIN
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../login.php");
    exit;
}

// AMBIL DATA FORM DENGAN ESCAPE STRING AMAN
$username = mysqli_real_escape_string($conn, trim($_POST['username']));
$password = $_POST['password'];

// CARI USER PADA DATABASE
$query = mysqli_query($conn, "
    SELECT 
        users.*,
        user_profile.nama_lengkap,
        user_profile.foto_profile
    FROM users
    LEFT JOIN user_profile
        ON users.id_user = user_profile.id_user
    WHERE users.username = '$username'
       OR users.email = '$username'
");

$data = mysqli_fetch_assoc($query);

// CEK APAKAH USER DITEMUKAN
if ($data) {

    // CEK VALIDASI PASSWORD BCRYPT HASH
    if (password_verify($password, $data['password'])) {

        // PERBAIKAN BUG: Bersihkan sisa jejak session dari login sebelumnya demi keamanan lintas tab
        session_unset();

        // SUNTIK DATA BASE SESSION GLOBAL
        $_SESSION['id_user']      = $data['id_user'];
        $_SESSION['role']         = $data['id_role'];
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['foto_profile'] = $data['foto_profile'];

        // REDIRECT DAN INJEKSI TOKEN PROTEKSI BERDASARKAN LEVEL AKSES (ROLE)
        if ($data['id_role'] == 3) {
            // Token Pengunci Khusus Super Admin
            $_SESSION['admin_logged_in'] = true;
            header("Location: ../admin/dashboard.php");
            exit;
            
        } elseif ($data['id_role'] == 2) {
            // Token Pengunci Khusus Mitra Jalur
            $_SESSION['mitra_logged_in'] = true;
            header("Location: ../mitra/dashboard.php");
            exit;
            
        } elseif ($data['id_role'] == 1) {
            // Token Pengunci Khusus Pendaki Nusantara
            $_SESSION['pendaki_logged_in'] = true;
            header("Location: ../index.php");
            exit;
            
        } else {
            // Keamanan Fallback jika Role ID tidak terdefinisi
            header("Location: ../login.php?error=role_unknown");
            exit;
        }

    } else {
        // Password salah
        header("Location: ../login.php?error=password");
        exit;
    }

} else {
    // Akun/Username/Email tidak ditemukan
    header("Location: ../login.php?error=user");
    exit;
}
?>