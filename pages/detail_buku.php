<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid_book_id");
    exit;
}

$book_id = (int)$_GET['id'];
$book = null;

$sql = "SELECT b.*, u.username AS owner_username, u.email AS owner_email 
        FROM books b 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $book_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $book = $result->fetch_assoc();
} else {
    header("Location: index.php?error=book_not_found");
    exit;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail: <?php echo htmlspecialchars($book['title']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Detail Buku</h1>
        <nav>
            <a href="index.php">← Kembali ke Katalog</a>
        </nav>
    </header>

    <hr>
    
    <?php if ($book): ?>
    <div style="display: flex; gap: 40px; border: 1px solid #ccc; padding: 20px;">
        
        <div style="width: 30%;">
            <?php
            $image_src = !empty($book['image_path']) ? '../' . $book['image_path'] : '../assets/placeholder.png';
            ?>
            <img src="<?php echo $image_src; ?>" alt="Gambar <?php echo htmlspecialchars($book['title']); ?>" style="width: 100%; height: auto; border: 1px solid #ddd;">
        </div>

        <div style="width: 70%;">
            <h2><?php echo htmlspecialchars($book['title']); ?></h2>
            <p><strong>Penulis:</strong> <?php echo htmlspecialchars($book['author']); ?></p>
            <p><strong>Kondisi:</strong> <span style="font-weight: bold; color: blue;"><?php echo htmlspecialchars($book['condition']); ?></span></p>
            
            <h3>Deskripsi:</h3>
            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
            
            <hr>
            
            <h3>Informasi Peminjaman/Tukar</h3>
            <p><strong>Pemilik:</strong> <?php echo htmlspecialchars($book['owner_username']); ?></p>
            <p><strong>Status:</strong> 
                <span style="font-weight: bold; color: <?php echo ($book['status'] == 'Tersedia' ? 'green' : 'red'); ?>;">
                    <?php echo htmlspecialchars($book['status']); ?>
                </span>
            </p>
            
            <?php 
            if ($book['user_id'] != $current_user_id && $book['status'] == 'Tersedia') {
                echo '<p><a href="request_book.php?book_id=' . $book['id'] . '" style="background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">✅ Minta Tukar/Pinjam Buku Ini</a></p>';
            } elseif ($book['user_id'] == $current_user_id) {
                echo '<p>
                        <a href="edit_buku.php?id=' . $book['id'] . '">✏️ Edit Informasi Buku</a> | 
                        <a href="delete_buku.php?id=' . $book['id'] . '" onclick="return confirm(\'Yakin ingin menghapus buku ini?\')">🗑️ Hapus Buku</a>
                      </p>';
            }
            ?>
        </div>
    </div>
    <?php endif; ?>

    <footer>
        <p>&copy; Pustaka Digital: Tukar & Pinjam Buku - <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>