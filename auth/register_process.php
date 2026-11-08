<?php
session_start();
include "../config/koneksi.php";

// 1. AMBIL DATA FORM
$nama       = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
$username   = mysqli_real_escape_string($conn, $_POST['username']);
$email      = mysqli_real_escape_string($conn, $_POST['email']);
$nik        = mysqli_real_escape_string($conn, trim($_POST['nik']));
$no_hp      = mysqli_real_escape_string($conn, $_POST['no_hp']);
$password   = $_POST['password'];
$konfirmasi = $_POST['konfirmasi'];

// 🔥 2. VALIDASI BACKEND LAYER MUTLAK: WAJIB ANGKA MURNI & HARUS TEPAT 16 DIGIT
if(empty($nik) || strlen($nik) !== 16 || !ctype_digit($nik)){
    header("Location: ../register.php?error=nik_invalid");
    exit;
}

// 3. VALIDASI PASSWORD
if($password !== $konfirmasi){
    header("Location: ../register.php?error=password");
    exit;
}

// 4. FORMAT NOMOR HP KE 628...
$no_hp = preg_replace('/[^0-9]/', '', $no_hp);
if (substr($no_hp, 0, 1) === '0') {
    $no_hp = '62' . substr($no_hp, 1);
}

// 5. HASH PASSWORD
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// 6. CEK USERNAME / EMAIL DUPLIKASI
$cek = mysqli_query($conn, "SELECT id_user FROM users WHERE username='$username' OR email='$email'");
if(mysqli_num_rows($cek) > 0){
    header("Location: ../register.php?error=userexists");
    exit;
}

// 7. INSERT USERS (Gunakan transaction)
$conn->begin_transaction();

try {
    $queryUsers = mysqli_query($conn, "INSERT INTO users (id_role, username, email, password) VALUES (1, '$username', '$email', '$passwordHash')");
    $id_user = mysqli_insert_id($conn);

    // 8. INSERT PROFILE DENGAN GAMBAR DEFAULT & BIO KOSONG
    $foto_default = 'assets/img/profile/profile_template.png';
    $bio_default  = ''; 
    
    $queryProfile = mysqli_query($conn, "INSERT INTO user_profile (id_user, nama_lengkap, nik, no_hp, foto_profile, bio) 
                                         VALUES ('$id_user', '$nama', '$nik', '$no_hp', '$foto_default', '$bio_default')");

    if($queryUsers && $queryProfile){
        $conn->commit();
        header("Location: ../login.php?success=register");
    } else {
        throw new Exception("Gagal insert ke database");
    }
} catch (Exception $e) {
    $conn->rollback();
    header("Location: ../register.php?error=gagal");
}
exit;
?>