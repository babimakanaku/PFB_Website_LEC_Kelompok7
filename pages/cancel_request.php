<?php
session_start();
require_once '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($_GET['book_id']) || empty($_GET['book_id'])) {
    header("Location: index.php?error=no_id");
    exit;
}

$book_id = (int)$_GET['book_id'];

$current_user_id = (int)$_SESSION['user_id']; 

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

$is_authorized = false;
$cancellation_type = '';

if ($current_user_id === $owner_id && $status === 'Diminta') {
    $is_authorized = true;
    $cancellation_type = 'rejected'; 
}

if ($current_user_id === $requester_id && $status === 'Diminta') {
    $is_authorized = true;
    $cancellation_type = 'canceled'; // Peminta membatalkan
}

if ($current_user_id === $owner_id && $status === 'Dipinjam') {
    $conn->close();
    header("Location: index.php?error=book_is_already_borrowed");
    exit;
}

if (!$is_authorized) {
    $conn->close();
    header("Location: index.php?error=unauthorized_cancellation");
    exit;
}

$update_sql = "UPDATE books SET status = 'Tersedia', borrower_id = NULL WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);

$update_stmt->bind_param("i", $book_id); 

if ($update_stmt->execute()) {
    // Sukses
    $update_stmt->close();
    $conn->close();
    header("Location: index.php?success=" . $cancellation_type);
} else {
    $update_stmt->close();
    $conn->close();
    header("Location: index.php?error=cancellation_failed_db");
}

exit;
?>