<?php
session_start();
include '../includes/db_connect.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- 1. Validasi ID dan Ambil Data Buku Awal ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php?error=invalid_id_edit");
    exit;
}

$book_id = (int)$_GET['id'];

// Query untuk mengambil data buku yang akan diedit
$sql_select = "SELECT * FROM books WHERE id = ?";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->bind_param("i", $book_id);
$stmt_select->execute();
$result_select = $stmt_select->get_result();

if ($result_select->num_rows === 0) {
    header("Location: index.php?error=book_not_found");
    exit;
}

$book_data = $result_select->fetch_assoc();
$stmt_select->close();

// --- Guardrail: Pastikan Hanya Pemilik yang Bisa Edit ---
if ($book_data['user_id'] != $current_user_id) {
    header("Location: index.php?error=not_owner");
    exit;
}

// --- 2. Proses Form Submission (UPDATE) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $description = $conn->real_escape_string($_POST['description']);
    $condition = $conn->real_escape_string($_POST['condition']);
    $image_path = $book_data['image_path']; // Pertahankan path lama

    // --- Penanganan Upload Gambar Baru ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/"; 
        $file_name = basename($_FILES["image"]["name"]);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_name = time() . '_' . uniqid() . '.' . $file_ext;
        $target_file = $target_dir . $unique_name;
        $new_image_path = "uploads/" . $unique_name;

        // Cek tipe file dan ukuran, lalu pindahkan
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Hapus gambar lama (jika ada)
            if (!empty($book_data['image_path'])) {
                $old_file = '../' . $book_data['image_path'];
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $image_path = $new_image_path; // Update path baru
        } else {
            $error = "Terjadi kesalahan saat mengunggah gambar baru.";
        }
    }
    
    // --- Lakukan Update ke Database ---
    if (empty($error)) {
        $sql_update = "UPDATE books SET title = ?, author = ?, description = ?, `condition` = ?, image_path = ? WHERE id = ?";
        
        $stmt_update = $conn->prepare($sql_update);
        // "sssssi" -> 5 string, 1 integer
        $stmt_update->bind_param("sssssi", $title, $author, $description, $condition, $image_path, $book_id);
        
        if ($stmt_update->execute()) {
            $success = "Data buku **$title** berhasil diperbarui!";
            // Ambil ulang data terbaru setelah update agar form menampilkan nilai yang benar
            $book_data['title'] = $title;
            $book_data['author'] = $author;
            $book_data['description'] = $description;
            $book_data['condition'] = $condition;
            $book_data['image_path'] = $image_path;
        } else {
            $error = "Gagal memperbarui buku: " . $conn->error;
        }
        $stmt_update->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Edit Buku: <?php echo htmlspecialchars($book_data['title']); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Edit Buku</h1>
        <nav>
            <a href="index.php">← Kembali ke Katalog</a>
        </nav>
    </header>

    <hr>
    
    <div class="container">
        <?php if ($success): ?>
            <p style="color: green; font-weight: bold;"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <form action="edit_buku.php?id=<?php echo $book_id; ?>" method="POST" enctype="multipart/form-data">
            
            <div>
                <label for="title">Judul Buku:</label>
                <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($book_data['title']); ?>" required>
            </div>
            
            <div>
                <label for="author">Penulis:</label>
                <input type="text" id="author" name="author" value="<?php echo htmlspecialchars($book_data['author']); ?>" required>
            </div>
            
            <div>
                <label for="description">Deskripsi Singkat:</label>
                <textarea id="description" name="description" rows="4" required><?php echo htmlspecialchars($book_data['description']); ?></textarea>
            </div>
            
            <div>
                <label for="condition">Kondisi Buku:</label>
                <select id="condition" name="condition" required>
                    <option value="Baru" <?php echo ($book_data['condition'] == 'Baru' ? 'selected' : ''); ?>>Baru</option>
                    <option value="Baik" <?php echo ($book_data['condition'] == 'Baik' ? 'selected' : ''); ?>>Baik</option>
                    <option value="Cukup" <?php echo ($book_data['condition'] == 'Cukup' ? 'selected' : ''); ?>>Cukup</option>
                </select>
            </div>

            <div>
                <label>Gambar Saat Ini:</label>
                <?php if (!empty($book_data['image_path'])): ?>
                    <img src="../<?php echo htmlspecialchars($book_data['image_path']); ?>" alt="Gambar Saat Ini" style="max-width: 150px; display: block;">
                <?php else: ?>
                    <p>Tidak ada gambar terunggah.</p>
                <?php endif; ?>
            </div>
            
            <div>
                <label for="image">Ganti Gambar (Biarkan kosong jika tidak diubah):</label>
                <input type="file" id="image" name="image" accept="image/*">
            </div>
            
            <button type="submit">Simpan Perubahan</button>
        </form>
    </div>

    <footer>
        <p>&copy; Pustaka Digital: Tukar & Pinjam Buku - <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>