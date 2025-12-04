<?php
session_start();
include '../includes/db_connect.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Pastikan ID Buku dikirimkan melalui GET
if (!isset($_GET['book_id']) || empty($_GET['book_id'])) {
    header("Location: index.php?error=no_id");
    exit;
}

$book_id = (int)$_GET['book_id'];
$requester_id = $_SESSION['user_id'];

// --- 1. Cek Kepemilikan dan Status Buku ---
// Penting: Hanya buku yang 'Tersedia' dan bukan milik sendiri yang bisa diminta
$check_sql = "SELECT user_id, status FROM books WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$book_data = $check_result->fetch_assoc();
$check_stmt->close();

if (!$book_data) {
    header("Location: index.php?error=book_not_found");
    exit;
}

if ($book_data['user_id'] == $requester_id) {
    header("Location: index.php?error=cannot_request_own_book");
    exit;
}

if ($book_data['status'] != 'Tersedia') {
    header("Location: index.php?error=book_not_available");
    exit;
}

// --- 2. Update Status Buku ---
// Ganti status buku menjadi 'Diminta'
$update_sql = "UPDATE books SET status = 'Diminta' WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("i", $book_id);

if ($update_stmt->execute()) {
    // Sukses: Redirect kembali ke katalog dengan pesan sukses
    header("Location: index.php?success=requested");
} else {
    // Gagal: Redirect dengan pesan error
    header("Location: index.php?error=request_failed");
}

$update_stmt->close();
$conn->close();
exit;
?>