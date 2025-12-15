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

// --- 1. Ambil Data Buku (dengan Logika Filter) ---

$sql = "SELECT b.*, u.username AS owner_username 
        FROM books b 
        JOIN users u ON b.user_id = u.id ";

$where_clauses = [];
$params = [];
$types = '';
$sort_order = 'DESC'; // Default: Terbaru (DESC)

// --- Filter Kondisi (TIDAK BERUBAH) ---
if (isset($_GET['condition']) && !empty($_GET['condition'])) {
    $where_clauses[] = "b.condition = ?";
    $params[] = $_GET['condition'];
    $types .= 's';
}

// --- Filter/Sort Urutan Waktu Unggah (LOGIKA BARU) ---
if (isset($_GET['sort']) && $_GET['sort'] == 'oldest') {
    $sort_order = 'ASC'; // Paling Lama (ASC)
}
// Jika $_GET['sort'] tidak disetel atau disetel ke 'newest', biarkan $sort_order = 'DESC'

// Menyatukan klausa WHERE
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Menambahkan ORDER BY BARU
$sql .= " ORDER BY b.uploaded_at " . $sort_order;

// --- Eksekusi Query dengan Prepared Statement ---
if (!empty($params)) {
    // Siapkan statement jika ada parameter filter
    $stmt = $conn->prepare($sql);
    
    // Membangun array referensi untuk bind_param secara dinamis
    $bind_params = array_merge([$types], $params);
    $refs = [];
    foreach($bind_params as $key => $value) {
        $refs[$key] = &$bind_params[$key];
    }

    // Menggunakan call_user_func_array untuk memanggil bind_param dengan parameter dinamis
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // Eksekusi query sederhana jika tidak ada filter
    $result = $conn->query($sql);
}

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
            <a href="my_books.php">🗂️ Buku Saya</a> |
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
        elseif ($_GET['success'] == 'cancellation_success') {
            // LOGIKA BARU UNTUK PEMBATALAN PERMINTAAN
            $msg = 'Pembatalan permintaan berhasil. Status buku kembali \'Tersedia\'.';
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

    <div class="container filter-container">
        <h3 style="margin-top: 0;">🔍 Filter Katalog</h3>
        
        <form action="index.php" method="GET" class="filter-form">
            
            <div>
                <label for="condition_filter">Kondisi Buku:</label>
                <select id="condition_filter" name="condition" onchange="this.form.submit()">
                    <option value="">-- Semua Kondisi --</option>
                    <option value="Baru" <?php echo (isset($_GET['condition']) && $_GET['condition'] == 'Baru') ? 'selected' : ''; ?>>Baru</option>
                    <option value="Baik" <?php echo (isset($_GET['condition']) && $_GET['condition'] == 'Baik') ? 'selected' : ''; ?>>Baik</option>
                    <option value="Cukup" <?php echo (isset($_GET['condition']) && $_GET['condition'] == 'Cukup') ? 'selected' : ''; ?>>Cukup</option>
                </select>
            </div>

            <div>
                <label for="sort_filter">Urutan Unggahan:</label>
                <select id="sort_filter" name="sort" onchange="this.form.submit()">
                    <option value="newest" <?php echo (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : ''; ?>>Terbaru</option>
                    <option value="oldest" <?php echo (isset($_GET['sort']) && $_GET['sort'] == 'oldest') ? 'selected' : ''; ?>>Terlama</option>
                </select>
            </div>
            
            <?php if (isset($_GET['condition']) && !empty($_GET['condition']) || isset($_GET['sort']) && !empty($_GET['sort'])): ?>
                <a href="index.php" class="reset-button">❌ Bersihkan Filter</a>
            <?php endif; ?>
            
        </form>
    </div>

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
                // Tombol untuk USER LAIN jika buku tersedia (Permintaan)
                echo ' | <a href="request_book.php?book_id=' . $row['id'] . '" class="request-button">✅ Minta Tukar/Pinjam</a>';
            } elseif ($row['status'] == 'Diminta') {
                // Tombol Batalkan Permintaan (TAMPIL HANYA JIKA STATUS 'DIMINTA')
                echo ' | <a href="cancel_request.php?book_id=' . $row['id'] . '" class="cancel-button" onclick="return confirm(\'Yakin batalkan permintaan buku ini?\')">❌ Batalkan Permintaan</a>';
            } else {
                // Jika statusnya 'Dipinjam' atau status lain yang tidak bisa di-request/cancel
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