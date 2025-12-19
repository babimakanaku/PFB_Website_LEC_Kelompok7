<?php
// Aktifkan logging error untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db_connect.php'; 

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
$requester_id = (int)$_SESSION['user_id']; 

// VALIDASI TAMBAHAN: Pastikan ID requester valid (sebelum digunakan)
if ($requester_id <= 0) {
    // Log error jika user ID invalid
    error_log("Request Error: Invalid requester ID in session: " . $requester_id);
    header("Location: index.php?error=invalid_user_session");
    exit;
}


// --- 1. Cek Kepemilikan dan Status Buku ---
$check_sql = "SELECT user_id, status FROM books WHERE id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("i", $book_id);

if (!$check_stmt->execute()) {
    $conn->close();
    header("Location: index.php?error=db_check_failed");
    exit;
}

$check_result = $check_stmt->get_result();
$book_data = $check_result->fetch_assoc();
$check_stmt->close();

if (!$book_data) {
    $conn->close();
    header("Location: index.php?error=book_not_found");
    exit;
}

if ($book_data['user_id'] == $requester_id) {
    $conn->close();
    header("Location: index.php?error=cannot_request_own_book");
    exit;
}

if ($book_data['status'] != 'Tersedia') {
    $conn->close();
    header("Location: index.php?error=book_not_available");
    exit;
}

// --- 2. Update Status Buku (TERMASUK borrower_id) ---
$update_sql = "UPDATE books SET status = 'Diminta', borrower_id = ? WHERE id = ? AND status = 'Tersedia'";
$update_stmt = $conn->prepare($update_sql);

// Menggunakan $requester_id (tipe integer)
$update_stmt->bind_param("ii", $requester_id, $book_id); 

// Baris 76: Eksekusi statement
if ($update_stmt->execute()) { 
    // Sukses
    $update_stmt->close();
    $conn->close();
    header("Location: index.php?success=requested");
} else {
    // LOGGING: Jika gagal, catat error spesifik database
    error_log("MySQL FOREIGN KEY FAIL. Book ID: " . $book_id . ", Requester ID attempted: " . $requester_id . ". MySQL Error: " . $update_stmt->error);
    
    $update_stmt->close();
    $conn->close();
    header("Location: index.php?error=request_failed_db");
}

exit;
?>