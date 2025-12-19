<?php
session_start();
require_once '../includes/db_connect.php'; 

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pastikan ID Buku dikirimkan
if (!isset($_GET['book_id']) || empty($_GET['book_id'])) {
    header("Location: index.php?error=no_id");
    exit;
}

$book_id = (int)$_GET['book_id'];
// Pengguna yang sedang login
$current_user_id = (int)$_SESSION['user_id']; 

// --- 1. Cek Data Buku, Pemilik, dan Peminjam Saat Ini ---
$check_sql = "SELECT user_id, borrower_id, status FROM books WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$book_data = $check_result->fetch_assoc();
$check_stmt->close();

if (!$book_data) {
    $conn->close();
    header("Location: index.php?error=book_not_found");
    exit;
}

$owner_id = (int)$book_data['user_id'];
$requester_id = (int)$book_data['borrower_id'];
$status = $book_data['status'];

// --- 2. Cek Otorisasi Pembatalan ---
$is_authorized = false;
$cancellation_type = '';

// Otorisasi 1: Hanya pemilik buku yang bisa membatalkan/menolak permintaan
if ($current_user_id === $owner_id && $status === 'Diminta') {
    $is_authorized = true;
    $cancellation_type = 'rejected'; // Pemilik menolak permintaan
}

// Otorisasi 2: Hanya peminta buku yang bisa membatalkan permintaan sendiri
if ($current_user_id === $requester_id && $status === 'Diminta') {
    $is_authorized = true;
    $cancellation_type = 'canceled'; // Peminta membatalkan
}

// Jika buku sudah dipinjam ('Dipinjam'), hanya pemilik yang bisa mengakhiri transaksi (misal: mengembalikannya)
if ($current_user_id === $owner_id && $status === 'Dipinjam') {
    // Logika ini bisa diarahkan ke file 'return_book.php' yang terpisah
    // Untuk saat ini, kita akan melarang pembatalan status 'Dipinjam' di sini
    $conn->close();
    header("Location: index.php?error=book_is_already_borrowed");
    exit;
}

if (!$is_authorized) {
    // Pengguna yang tidak berhak mencoba membatalkan
    $conn->close();
    header("Location: index.php?error=unauthorized_cancellation");
    exit;
}

// --- 3. Update Status Buku (Pembatalan/Penolakan) ---
// Kembalikan status ke 'Tersedia' dan borrower_id ke NULL
$update_sql = "UPDATE books SET status = 'Tersedia', borrower_id = NULL WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);

// Menggunakan NULL di SQL akan mengatasi masalah Foreign Key Constraint, 
// asalkan kolom borrower_id di database sudah disetel 'NULLABLE' (seperti yang kita diskusikan sebelumnya).
$update_stmt->bind_param("i", $book_id); 

if ($update_stmt->execute()) {
    // Sukses
    $update_stmt->close();
    $conn->close();
    // Arahkan dengan pesan sukses sesuai jenis pembatalan
    header("Location: index.php?success=" . $cancellation_type);
} else {
    // Gagal
    $update_stmt->close();
    $conn->close();
    header("Location: index.php?error=cancellation_failed_db");
}

exit;
?>