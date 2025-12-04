<?php
session_start();
include '../includes/db_connect.php'; // Koneksi Database

// --- Guardrail: Pastikan User Sudah Login ---
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

// --- 1. Ambil Data Buku ---
// Query untuk mengambil semua buku beserta username pemiliknya
$sql = "SELECT b.*, u.username AS owner_username 
        FROM books b 
        JOIN users u ON b.user_id = u.id 
        ORDER BY b.uploaded_at DESC";

$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Katalog Buku Gratis - Tukar Buku SDG 4</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Selamat Datang, <?php echo $username; ?>!</h1>
        <p>Ayo tukar buku dan tingkatkan kualitas pendidikan!</p>
        <nav>
            <a href="tambah_buku.php">➕ Tambah Buku Baru</a>
            |
            <a href="logout.php">🚪 Logout</a>
        </nav>
    </header>

    <hr>

    <?php 
    // --- 1. Feedback Block (Menggunakan class CSS) ---
    if (isset($_GET['success'])) {
        $msg = '';
        if ($_GET['success'] == 'requested') {
            $msg = 'Permintaan buku berhasil dikirimkan! Status buku kini \'Diminta\'.';
        } elseif ($_GET['success'] == 'book_deleted') {
            // Logika feedback dari delete_buku.php
            $msg = 'Buku berhasil dihapus dari katalog.'; 
        }
        
        // Menampilkan pesan sukses dengan class CSS
        if ($msg) echo '<p class="feedback-success">' . $msg . '</p>';
    }

    if (isset($_GET['error'])) {
        $msg = '';
        if ($_GET['error'] == 'book_not_found') $msg = 'Buku tidak ditemukan.';
        if ($_GET['error'] == 'book_not_available') $msg = 'Buku sedang tidak tersedia atau sudah diminta.';
        if ($_GET['error'] == 'cannot_request_own_book') $msg = 'Anda tidak dapat meminta buku milik Anda sendiri.';
        if ($_GET['error'] == 'request_failed') $msg = 'Gagal memproses permintaan.';
        if ($_GET['error'] == 'not_owner') $msg = 'Anda tidak memiliki izin untuk melakukan aksi ini.'; // Error dari delete/edit

        // Menampilkan pesan error dengan class CSS
        if ($msg) echo '<p class="feedback-error">ERROR: ' . $msg . '</p>';
    }
    ?>

    <h2>Daftar Buku Tersedia</h2>

    <?php 
    // --- 2. Tampilkan Data Buku ---
    if ($result->num_rows > 0) {
        // Mengganti inline style dengan class="book-catalog"
        echo '<div class="book-catalog">'; 
        
        while($row = $result->fetch_assoc()) {
            // Mengganti inline style dengan class="book-card"
            echo '<div class="book-card">'; 
            
            // Gambar Buku
            $image_src = !empty($row['image_path']) ? '../' . $row['image_path'] : '../assets/placeholder.png';
            // Menghapus inline style pada gambar, CSS akan menanganinya
            echo '<img src="' . $image_src . '" alt="Gambar Buku">';
            
            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
            echo '<p><strong>Penulis:</strong> ' . htmlspecialchars($row['author']) . '</p>';
            echo '<p><strong>Kondisi:</strong> ' . htmlspecialchars($row['condition']) . '</p>';
            
            // Menentukan class CSS berdasarkan status
            $status_class = ($row['status'] == 'Tersedia') ? 'status-available' : 'status-requested';
            
            // Menggunakan class CSS untuk status
            echo '<p><strong>Status:</strong> <span class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></p>';
            echo '<p><strong>Pemilik:</strong> ' . htmlspecialchars($row['owner_username']) . '</p>';
            
            // Tombol Detail selalu ada
            echo '<a href="detail_buku.php?id=' . $row['id'] . '">Lihat Detail</a>';
            
            // --- BAGIAN LOGIKA TOMBOL AKSI ---
            
            if ($row['user_id'] == $current_user_id) {
                // Tombol untuk PEMILIK buku (Edit dan Hapus)
                echo ' | <a href="edit_buku.php?id=' . $row['id'] . '">✏️ Edit</a>';
                echo ' | <a href="delete_buku.php?id=' . $row['id'] . '" onclick="return confirm(\'Yakin hapus?\')">🗑️ Hapus</a>';
            } elseif ($row['status'] == 'Tersedia') {
                // Menggunakan class="request-button" untuk tombol permintaan
                echo ' | <a href="request_book.php?book_id=' . $row['id'] . '" class="request-button">✅ Minta Tukar/Pinjam</a>';
            } else {
                // Jika user lain dan status bukan 'Tersedia', tampilkan status dengan class
                echo ' | <span class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</span>';
            }
            // --- AKHIR BAGIAN MODIFIKASI ---

            echo '</div>'; // Tutup div card
        }
        
        echo '</div>'; // Tutup div book-catalog

    } else {
        echo "<p>Belum ada buku yang tersedia untuk saat ini. Silakan unggah buku Anda!</p>";
    }
    ?>

    <footer>
        <p>&copy; Pustaka Digital: Tukar & Pinjam Buku - <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>