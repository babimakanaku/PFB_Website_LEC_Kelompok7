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

    <h2>Daftar Buku Tersedia</h2>

    <?php 
    // --- 2. Tampilkan Data Buku ---
    if ($result->num_rows > 0) {
        // Mulai tampilan grid atau list
        echo '<div style="display: flex; flex-wrap: wrap; gap: 20px;">';
        
        while($row = $result->fetch_assoc()) {
            // Tampilan per kartu buku (Book Card)
            echo '<div style="border: 1px solid #ccc; padding: 15px; width: 300px;">';
            
            // Gambar Buku (Saat ini masih placeholder)
            $image_src = !empty($row['image_path']) ? '../' . $row['image_path'] : '../assets/placeholder.png';
            echo '<img src="' . $image_src . '" alt="Gambar Buku" style="width: 100%; height: auto;">';
            
            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
            echo '<p><strong>Penulis:</strong> ' . htmlspecialchars($row['author']) . '</p>';
            echo '<p><strong>Kondisi:</strong> ' . htmlspecialchars($row['condition']) . '</p>';
            echo '<p><strong>Status:</strong> <span style="color: green;">' . htmlspecialchars($row['status']) . '</span></p>';
            echo '<p><strong>Pemilik:</strong> ' . htmlspecialchars($row['owner_username']) . '</p>';
            
            // Tombol Permintaan (Fitur Minggu 4) dan Detail (Fitur Minggu 3)
            echo '<a href="detail_buku.php?id=' . $row['id'] . '">Lihat Detail</a>';
            
            // Tambahkan tombol Edit/Hapus hanya jika user_id buku sama dengan user yang sedang login
            if ($row['user_id'] == $current_user_id) {
                echo ' | <a href="edit_buku.php?id=' . $row['id'] . '">✏️ Edit</a>';
                echo ' | <a href="delete_buku.php?id=' . $row['id'] . '" onclick="return confirm(\'Yakin hapus?\')">🗑️ Hapus</a>';
            }

            echo '</div>'; // Tutup div card
        }
        
        echo '</div>'; // Tutup div flex container

    } else {
        echo "<p>Belum ada buku yang tersedia untuk saat ini. Silakan unggah buku Anda!</p>";
    }
    ?>

    <footer>
        <p>&copy; Tukar Buku SDG 4 - <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>