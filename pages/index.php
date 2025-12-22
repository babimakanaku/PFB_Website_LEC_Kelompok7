<?php
session_start();
include '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
$current_user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username']);

$sql = "SELECT b.*, u.username AS owner_username 
        FROM books b 
        JOIN users u ON b.user_id = u.id ";

$where_clauses = [];
$params = [];
$types = '';
$sort_order = 'DESC';

if (isset($_GET['condition']) && !empty($_GET['condition'])) {
    $where_clauses[] = "b.condition = ?";
    $params[] = $_GET['condition'];
    $types .= 's';
}

if (isset($_GET['sort']) && $_GET['sort'] == 'oldest') {
    $sort_order = 'ASC';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY b.uploaded_at " . $sort_order;

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $bind_params = array_merge([$types], $params);
    $refs = [];
    foreach($bind_params as $key => $value) {
        $refs[$key] = &$bind_params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
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

    if (isset($_GET['success'])) {
        $msg = '';
        if ($_GET['success'] == 'requested') {
            $msg = 'Permintaan buku berhasil dikirimkan! Status buku kini \'Diminta\'.';
        } elseif ($_GET['success'] == 'book_deleted') {
            $msg = 'Buku berhasil dihapus dari katalog.'; 
        }
        elseif ($_GET['success'] == 'rejected') {
            $msg = 'Permintaan buku berhasil ditolak. Status buku kembali \'Tersedia\'.';
        }
        elseif ($_GET['success'] == 'canceled') {
            $msg = 'Anda berhasil membatalkan permintaan. Status buku kembali \'Tersedia\'.';
        }
        elseif ($_GET['success'] == 'cancellation_success') {
            $msg = 'Pembatalan permintaan berhasil. Status buku kembali \'Tersedia\'.';
        }
        if ($msg) echo '<p class="feedback-success">' . $msg . '</p>';
    }

    if (isset($_GET['error'])) {
        $msg = '';
        if ($_GET['error'] == 'book_not_found') $msg = 'Buku tidak ditemukan.';
        if ($_GET['error'] == 'book_not_available') $msg = 'Buku sedang tidak tersedia atau sudah diminta.';
        if ($_GET['error'] == 'cannot_request_own_book') $msg = 'Anda tidak dapat meminta buku milik Anda sendiri.';
        if ($_GET['error'] == 'request_failed' || $_GET['error'] == 'request_failed_db') $msg = 'Gagal memproses permintaan.';
        if ($_GET['error'] == 'not_owner') $msg = 'Anda tidak memiliki izin untuk melakukan aksi ini.'; 

        if ($_GET['error'] == 'unauthorized_cancellation') $msg = 'Anda tidak berhak membatalkan atau menolak permintaan buku ini.';

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

    if ($result->num_rows > 0) {
        echo '<div class="book-catalog">'; 
        
        while($row = $result->fetch_assoc()) {
            
            $is_owner = ($row['user_id'] == $current_user_id);
            $is_requester = ($row['borrower_id'] == $current_user_id && $row['status'] == 'Diminta'); 

            echo '<div class="book-card">'; 

            $image_src = !empty($row['image_path']) ? '../' . $row['image_path'] : '../assets/placeholder.png';
            echo '<img src="' . $image_src . '" alt="Gambar Buku">';
            
            echo '<h3>' . htmlspecialchars($row['title']) . '</h3>';
            echo '<p><strong>Penulis:</strong> ' . htmlspecialchars($row['author']) . '</p>';
            echo '<p><strong>Kondisi:</strong> ' . htmlspecialchars($row['condition']) . '</p>';

            $status_class = ($row['status'] == 'Tersedia') ? 'status-available' : 'status-requested';
            
            echo '<p><strong>Status:</strong> <span class="' . $status_class . '">' . htmlspecialchars($row['status']) . '</span></p>';
            echo '<p><strong>Pemilik:</strong> ' . htmlspecialchars($row['owner_username']) . '</p>';

            echo '<a href="detail_buku.php?id=' . $row['id'] . '" class="action-link">Lihat Detail</a>';
            
            if ($is_owner) {
                echo ' | <a href="edit_buku.php?id=' . $row['id'] . '" class="action-link">✏️ Edit</a>';
                echo ' | <a href="delete_buku.php?id=' . $row['id'] . '" class="delete-button" onclick="return confirm(\'Yakin hapus?\')">🗑️ Hapus</a>';

                if ($row['status'] == 'Diminta') {
                     echo ' | <a href="cancel_request.php?book_id=' . $row['id'] . '" class="cancel-button">❌ Tolak Permintaan</a>';
                }

            } elseif ($row['status'] == 'Tersedia') {
                echo ' | <a href="request_book.php?book_id=' . $row['id'] . '" class="request-button">✅ Minta Tukar/Pinjam</a>';
                
            } elseif ($is_requester) {
                 echo ' | <a href="cancel_request.php?book_id=' . $row['id'] . '" class="cancel-button" onclick="return confirm(\'Yakin batalkan permintaan buku ini?\')">❌ Batalkan Permintaan</a>';

            } else {
                echo ' | <span class="' . $status_class . ' status-unavailable">' . htmlspecialchars($row['status']) . '</span>';
            }

            echo '</div>';
        }
        
        echo '</div>';

    } else {
        echo "<p>Belum ada buku yang tersedia untuk saat ini. Silakan unggah buku Anda!</p>";
    }
    ?>

    <footer>
        <p>&copy; Pustaka Digital: Tukar & Pinjam Buku - <?php echo date("Y"); ?></p>
    </footer>
</body>
</html>