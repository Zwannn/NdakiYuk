<?php
// review_gunung.php – Halaman Pengisian Ulasan Resmi Pendakian NdakiYuk
session_start();
include "auth/cek_role.php";
hanyaPendakiRoot();
include "config/koneksi.php";

// JIKA BELUM LOGIN, LEMPAR KE LOGIN
if (!isset($_SESSION['id_user'])) {
    header("Location: login.php");
    exit;
}

$id_user = $_SESSION['id_user'];
$kode_booking = mysqli_real_escape_string($conn, $_GET['kode'] ?? '');

if (empty($kode_booking)) {
    header("Location: index.php");
    exit;
}

// AMBIL DATA PEMESANAN & GUNUNG TERKAIT
$query = mysqli_query($conn, "
    SELECT p.*, g.nama_gunung, g.lokasi, g.id_gunung 
    FROM pemesanan p 
    JOIN gunung g ON p.id_gunung = g.id_gunung 
    WHERE p.kode_booking = '$kode_booking' AND p.id_user = '$id_user'
");

if (mysqli_num_rows($query) === 0) {
    header("Location: index.php");
    exit;
}

$data = mysqli_fetch_assoc($query);
$id_gunung = $data['id_gunung'];

// SATU-SATUNYA SYARAT MUTLAK: STATUS WAJIB 'Selesai'
if ($data['status_pemesanan'] !== 'Selesai') {
    die("Akses Ditolak: Anda hanya dapat memberikan ulasan ketika perjalanan pendakian Anda telah berstatus Selesai.");
}

// PROTEKSI DOUBLE REVIEW: BERDASARKAN TAG KODE BOOKING DI KOMENTAR
$check_double = mysqli_query($conn, "SELECT id_review FROM reviews WHERE komentar LIKE '%[$kode_booking]%'");
if (mysqli_num_rows($check_double) > 0) {
    header("Location: konfirmasi.php?kode=" . $kode_booking);
    exit;
}

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_ulasan'])) {
    $rating   = (int)($_POST['rating'] ?? 0);
    $komentar = mysqli_real_escape_string($conn, trim($_POST['komentar'] ?? ''));

    if ($rating < 1 || $rating > 5) {
        $error_msg = "Silakan berikan rating bintang (1-5) terlebih dahulu.";
    } elseif (empty($komentar)) {
        $error_msg = "Kotak ulasan komentar tidak boleh kosong.";
    } else {
        // 🔥 INJEKSI PENANDA KODE BOOKING PADA AWAL STRING KOMENTAR 🔥
        $komentar_tagged = "[$kode_booking] " . $komentar;

        $insert = mysqli_query($conn, "
            INSERT INTO reviews (id_user, id_gunung, rating, komentar, created_at)
            VALUES ('$id_user', '$id_gunung', '$rating', '$komentar_tagged', CURRENT_TIMESTAMP)
        ");
        if ($insert) {
            header("Location: konfirmasi.php?kode=" . $kode_booking . "&review=success");
            exit;
        } else {
            $error_msg = "Gagal memproses ulasan ke database: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
<link rel="stylesheet" href="assets/css/style.css?v=1000">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NdakiYuk – Ulasan Pendakian</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .rev-main {
            background: #f4f6f4;
            min-height: 100vh;
            padding: 120px 20px 60px;
            display: flex;
            justify-content: center;
        }

        .rev-card {
            background: #ffffff;
            border: 1px solid #e0e8e3;
            width: 100%;
            max-width: 550px;
            padding: 32px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(13, 59, 34, 0.04);
        }

        .rev-head {
            text-align: center;
            margin-bottom: 24px;
        }

        .rev-title {
            font-size: 1.3rem;
            font-weight: 800;
            color: #0d3b22;
        }

        .rev-sub {
            font-size: 0.85rem;
            color: #7a9487;
            margin-top: 4px;
        }

        .rev-badge {
            display: inline-block;
            background: #eef7f2;
            color: #1a5c38;
            font-weight: 700;
            font-size: 0.78rem;
            padding: 6px 14px;
            border-radius: 6px;
            margin-top: 10px;
        }

        .stars-group {
            display: flex;
            gap: 10px;
            flex-direction: row-reverse;
            justify-content: center;
            margin: 20px 0;
        }

        .stars-group input {
            display: none;
        }

        .stars-group label {
            font-size: 36px;
            color: #dce8e1;
            cursor: pointer;
            transition: 0.15s;
        }

        .stars-group input:checked~label,
        .stars-group label:hover,
        .stars-group label:hover~label {
            color: #f5c842;
        }

        .rev-textarea {
            width: 100%;
            min-height: 120px;
            padding: 14px 18px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
            outline: none;
            transition: 0.15s;
            color: #0f2419;
        }

        .rev-textarea:focus {
            border-color: #1a5c38;
            box-shadow: 0 0 0 4px rgba(26, 92, 56, 0.06);
        }

        .btn-rev-submit {
            width: 100%;
            background: #1a5c38;
            color: #ffffff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-weight: 800;
            font-size: 0.9rem;
            cursor: pointer;
            margin-top: 16px;
            box-shadow: 0 4px 14px rgba(26, 92, 56, 0.2);
            transition: 0.2s;
        }

        .btn-rev-submit:hover {
            background: #246b43;
        }

        .alert-rev-error {
            background: #fee2e2;
            color: #dc3545;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 16px;
            border: 1px solid rgba(220, 53, 69, 0.15);
        }
    </style>
</head>

<body>

    <?php include 'components/navbar.php'; ?>

    <main class="rev-main">
        <div class="rev-card">
            <div class="rev-head">
                <h2 class="rev-title">Evaluasi & Ulasan Jalur</h2>
                <p class="rev-sub">Bagikan cerita perizinan dan kondisi trek pendakian Anda.</p>
                <span class="rev-badge">📍 Kawasan: <?= htmlspecialchars($data['nama_gunung']) ?> (<?= htmlspecialchars($data['lokasi']) ?>)</span>
            </div>

            <?php if (!empty($error_msg)): ?>
                <div class="alert-rev-error"><?= htmlspecialchars($error_msg) ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="stars-group">
                    <input type="radio" name="rating" id="star5" value="5"><label for="star5">★</label>
                    <input type="radio" name="rating" id="star4" value="4"><label for="star4">★</label>
                    <input type="radio" name="rating" id="star3" value="3"><label for="star3">★</label>
                    <input type="radio" name="rating" id="star2" value="2"><label for="star2">★</label>
                    <input type="radio" name="rating" id="star1" value="1"><label for="star1">★</label>
                </div>

                <textarea name="komentar" class="rev-textarea" placeholder="Ceritakan detail pendakianmu (kondisi shelter, pos air, keasrian vegetasi, pelayanan loket)..." required></textarea>

                <button type="submit" name="kirim_ulasan" class="btn-rev-submit">Kirim Ulasan Resmi Pendaki</button>
            </form>
        </div>
    </main>

</body>

</html>