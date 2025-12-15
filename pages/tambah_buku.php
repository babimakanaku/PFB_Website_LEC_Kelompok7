<?php
session_start();
include '../includes/db_connect.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// --- Proses Form Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data dari form
    $title = $conn->real_escape_string($_POST['title']);
    $author = $conn->real_escape_string($_POST['author']);
    $description = $conn->real_escape_string($_POST['description']);
    $condition = $conn->real_escape_string($_POST['condition']);
    
    // Inisialisasi path gambar
    $image_path = '';

    // --- Penanganan Upload Gambar ---
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/"; // Folder tujuan upload
        $file_name = basename($_FILES["image"]["name"]);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $unique_name = time() . '_' . uniqid() . '.' . $file_ext; // Nama file unik
        $target_file = $target_dir . $unique_name;
        $image_path_db = "uploads/" . $unique_name; // Path yang disimpan di database

        // Cek tipe file yang diperbolehkan
        $allowed_ext = array("jpg", "jpeg", "png", "gif");
        if (!in_array($file_ext, $allowed_ext)) {
            $error = "Hanya file JPG, JPEG, PNG, & GIF yang diizinkan.";
        } 
        
        // Cek ukuran file (Misal: maksimal 5MB)
        elseif ($_FILES["image"]["size"] > 5000000) { 
            $error = "Maaf, ukuran file terlalu besar (maks 5MB).";
        } 
        
        // Lakukan proses upload
        elseif (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $image_path_db; // Simpan path jika upload berhasil
        } else {
            $error = "Terjadi kesalahan saat mengunggah file.";
        }
    }
    
    // --- Simpan Data ke Database jika tidak ada error ---
    if (empty($error)) {
        // Query menggunakan Prepared Statement
        $sql = "INSERT INTO books (user_id, title, author, description, `condition`, image_path) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        // "isssss" -> integer (user_id) dan lima string (title, author, description, condition, image_path)
        $stmt->bind_param("isssss", $user_id, $title, $author, $description, $condition, $image_path);
        
        if ($stmt->execute()) {
            $success = "Buku **$title** berhasil ditambahkan ke katalog!";
            // Reset input (opsional: bisa di-redirect ke index.php juga)
        } else {
            $error = "Gagal menambahkan buku: " . $conn->error;
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Tambah Buku Baru</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Tambah Buku Baru</h1>
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

        <form action="tambah_buku.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="title">Judul Buku:</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div>
                <label for="author">Penulis:</label>
                <input type="text" id="author" name="author" required>
            </div>
            <div>
                <label for="description">Deskripsi Singkat:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>
            <div>
                <label for="condition">Kondisi Buku:</label>
                <select id="condition" name="condition" required>
                    <option value="Baru">Baru</option>
                    <option value="Baik">Baik</option>
                    <option value="Cukup">Cukup</option>
                </select>
            </div>
            <div>
                <label for="image">Gambar Buku (Max 5MB):</label>
                <input type="file" id="image" name="image" accept="image/*">
                <small>Opsional, tapi sangat disarankan.</small>
            </div>
            
            <button type="submit">Unggah Buku</button>
        </form>
    </div>

    <footer>
        <p>&copy; Pustaka Digital: Tukar & Pinjam Buku - <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>