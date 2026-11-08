<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "PASSWORD_DATABASE",
    "NAMA_DATABASE"
);

if (!$conn) {
    die("Koneksi database gagal.");
}

?>