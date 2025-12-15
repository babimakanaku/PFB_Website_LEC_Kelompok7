<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);
$error = '';
$success = '';

// --- 1. Dapatkan Daftar BUKU YANG SAYA UNGGAH (Owned Books) ---
$sql_owned = "SELECT id, title, author, status FROM books WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt_owned = $conn->prepare($sql_owned);
$stmt_owned->bind_param("i", $current_user_id);
$stmt_owned->execute();
$result_owned = $stmt_owned->get_result();
$stmt_owned->close();

// --- 2. Dapatkan Daftar BUKU YANG SAYA MINTA/PINJAM (Borrowed Books) ---
// Catatan: QUERY INI BERFUNGSI JIKA KOLOM 'borrower_id' SUDAH ADA DI TABEL 'books'
$sql_borrowed = "SELECT b.id, b.title, b.author, b.status, u.username AS owner_username 
                 FROM books b 
                 JOIN users u ON b.user_id = u.id 
                 WHERE b.borrower_id = ? AND b.user_id != ?"; // Filter buku milik user lain
                 
$stmt_borrowed = $conn->prepare($sql_borrowed);
$stmt_borrowed->bind_param("ii", $current_user_id, $current_user_id);
$stmt_borrowed->execute();
$result_borrowed = $stmt_borrowed->get_result();
$stmt_borrowed->close();

// Tambahkan logika feedback jika diperlukan
// if (isset($_GET['success'])) { ... }

$conn->close();
?> 

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Katalog Saya</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Manajemen Buku Saya, <?php echo $username; ?></h1>
        <nav>
            <a href="index.php">← Kembali ke Katalog</a>
            |
            <a href="tambah_buku.php">➕ Tambah Buku Baru</a>
            |
            <a href="logout.php">🚪 Logout</a>
        </nav>
    </header>

    <hr>
    <div class="container">
        
        <h2>Buku Yang Saya Unggah</h2>
        
        <?php if ($result_owned->num_rows > 0): ?>
            <div class="book-list">
            <?php while($book = $result_owned->fetch_assoc()): 
                $status_class = ($book['status'] == 'Tersedia') ? 'status-available' : 'status-requested';
            ?>
                <div class="book-card book-mini">
                    <h4><?php echo htmlspecialchars($book['title']); ?></h4>
                    <p>Status: <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($book['status']); ?></span></p>
                    
                    <a href="detail_buku.php?id=<?php echo $book['id']; ?>">Lihat Detail</a>
                    | <a href="edit_buku.php?id=<?php echo $book['id']; ?>">✏️ Edit</a>
                    | <a href="delete_buku.php?id=<?php echo $book['id']; ?>" onclick="return confirm('Yakin hapus?')">🗑️ Hapus</a>
                    
                    </div>
            <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Anda belum mengunggah buku apapun.</p>
        <?php endif; ?>
        <hr>
        
        <h2>Buku Yang Saya Minta (Diminta/Dipinjam)</h2>
        
        <?php if ($result_borrowed->num_rows > 0): ?>
            <div class="book-list">
            <?php while($book = $result_borrowed->fetch_assoc()): 
                $status_class = ($book['status'] == 'Tersedia') ? 'status-available' : 'status-requested';
            ?>
                <div class="book-card book-mini">
                    <h4><?php echo htmlspecialchars($book['title']); ?> (Pemilik: <?php echo htmlspecialchars($book['owner_username']); ?>)</h4>
                    <p>Status: <span class="<?php echo $status_class; ?>"><?php echo htmlspecialchars($book['status']); ?></span></p>
                    
                    <a href="detail_buku.php?id=<?php echo $book['id']; ?>">Lihat Detail</a>
                    
                    <?php if ($book['status'] == 'Diminta'): ?>
                        | <a href="cancel_request.php?book_id=<?php echo $book['id']; ?>" class="cancel-button" onclick="return confirm('Yakin batalkan permintaan?')">❌ Batalkan</a>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>Anda belum meminta atau meminjam buku dari pengguna lain.</p>
        <?php endif; ?>
        </div>
    <footer>...</footer>
</body>
</html>