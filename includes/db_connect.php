<?php
// Konfigurasi Database
$servername = "localhost";
$username = "root"; // Ganti dengan username DB Anda
$password = "";     // Ganti dengan password DB Anda
$dbname = "db_tukar_buku"; // Ganti dengan nama DB Anda

// Membuat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi Gagal: " . $conn->connect_error);
}
// echo "Koneksi Berhasil"; // Hapus baris ini setelah berhasil
?>