<?php
session_start();
include '../includes/db_connect.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

// --- Validasi ID Buku ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid_id_delete");
    exit;
}

$book_id = (int)$_GET['id'];

// --- 1. Cek Kepemilikan Buku ---
// Penting: Pastikan hanya pemilik yang bisa menghapus bukunya
$check_sql = "SELECT user_id, image_path FROM books WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $book_id);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows === 0) {
    header("Location: index.php?error=book_not_found");
    exit;
}

$book_data = $check_result->fetch_assoc();
$check_stmt->close();

if ($book_data['user_id'] != $current_user_id) {
    // Jika bukan pemilik, tolak aksi dan arahkan kembali
    header("Location: index.php?error=not_owner");
    exit;
}

// --- 2. Hapus File Gambar (Opsional tapi Disarankan) ---
if (!empty($book_data['image_path'])) {
    $file_to_delete = '../' . $book_data['image_path'];
    if (file_exists($file_to_delete)) {
        unlink($file_to_delete); // Hapus file dari folder /uploads/
    }
}

// --- 3. Hapus Data dari Database ---
$delete_sql = "DELETE FROM books WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $book_id);

if ($delete_stmt->execute()) {
    header("Location: index.php?success=book_deleted");
} else {
    header("Location: index.php?error=delete_failed");
}

$delete_stmt->close();
$conn->close();
exit;
?>