<?php
// auth/search_gunung_ajax.php
session_start();
include "../config/koneksi.php";

$keyword = isset($_GET['keyword']) ? mysqli_real_escape_string($conn, trim($_GET['keyword'])) : '';

if (empty($keyword)) {
    echo '<div class="search-empty-state">Mulai ketikkan destinasi puncak yang ingin kamu tuju.</div>';
    exit;
}

// Query mencari kesamaan nama gunung atau lokasi kabupaten
$query = mysqli_query($conn, "
    SELECT id_gunung, nama_gunung, lokasi, tinggi, harga_tiket, gambar 
    FROM gunung 
    WHERE nama_gunung LIKE '%$keyword%' 
       OR lokasi LIKE '%$keyword%'
    ORDER BY nama_gunung ASC 
    LIMIT 5
");

if (mysqli_num_rows($query) > 0) {
    while ($row = mysqli_fetch_assoc($query)) {
        $cover = !empty($row['gambar']) ? $row['gambar'] : 'default.jpg';
        ?>
        <a href="detail_gunung.php?id=<?= $row['id_gunung'] ?>" class="search-item-link">
            <img src="assets/img/gunung/<?= htmlspecialchars($cover) ?>" class="search-item-thumb" alt="Cover">
            <div>
                <span class="search-item-name"><?= htmlspecialchars($row['nama_gunung']) ?></span>
                <span class="search-item-meta"><?= htmlspecialchars($row['lokasi']) ?> • <?= number_format($row['tinggi'], 0, ',', '.') ?> MDPL</span>
            </div>
            <div style="margin-left: auto; font-size: 12.5px; font-weight: 700; color: #1a5c38;">
                Rp <?= number_format($row['harga_tiket'], 0, ',', '.') ?>
            </div>
        </a>
        <?php
    }
} else {
    echo '<div class="search-empty-state">Gunung "' . htmlspecialchars($keyword) . '" tidak ditemukan. Coba kata kunci lain.</div>';
}
?>