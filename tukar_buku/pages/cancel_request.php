<?php
session_start();
include '../includes/db_connect.php';

// Guardrail: Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['user_id'];

if (isset($_GET['book_id'])) {
    $book_id = $_GET['book_id'];

    // 1. Ambil data buku untuk verifikasi
    $sql = "SELECT user_id, status FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $book_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $book = $result->fetch_assoc();
    $stmt->close();

    if (!$book) {
        $conn->close(); // <-- Tambahkan penutupan koneksi
        header("Location: index.php?error=book_not_found");
        exit;
    }

    // 2. Verifikasi: Pastikan status buku adalah 'Diminta' 
    if ($book['status'] == 'Diminta') {
        // 3. Update Status Buku menjadi 'Tersedia'
        $update_sql = "UPDATE books SET status = 'Tersedia', borrower_id = NULL WHERE id = ? AND status = 'Diminta'";
        $update_stmt = $conn->prepare($update_sql);
        // Perhatikan tipe param: 'i' (satu integer)
        $update_stmt->bind_param("i", $book_id);

        if ($update_stmt->execute()) {
            if ($update_stmt->affected_rows > 0) {
                // Pembatalan berhasil
                $update_stmt->close();
                $conn->close(); // <-- Tambahkan penutupan koneksi sebelum exit
                header("Location: index.php?success=cancellation_success");
                exit;
            } else {
                // Kasus jarang: status sudah berubah sebelum user mengklik
                $update_stmt->close();
                $conn->close(); // <-- Tambahkan penutupan koneksi
                header("Location: index.php?error=cancellation_failed_status_change");
                exit;
            }
        } else {
            // Gagal Update
            $update_stmt->close();
            $conn->close(); // <-- Tambahkan penutupan koneksi
            $error = "Gagal membatalkan permintaan: " . $conn->error;
            header("Location: index.php?error=cancellation_failed");
            exit;
        }
        
    } else {
        $conn->close(); // <-- Tambahkan penutupan koneksi
        header("Location: index.php?error=cancellation_not_allowed");
        exit;
    }

} else {
    $conn->close(); // <-- Tambahkan penutupan koneksi
    header("Location: index.php?error=invalid_request");
    exit;
}
// Baris ini tidak lagi terjangkau, tetapi memastikan semua alur tertutup jika ada perubahan logika di masa depan.
// Karena semua alur if/else kini memiliki exit dan close, warning seharusnya hilang.
?>