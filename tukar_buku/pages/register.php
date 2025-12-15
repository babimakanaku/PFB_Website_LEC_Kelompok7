<?php
// Mulai session untuk manajemen user
session_start();

// Jika user sudah login, arahkan ke halaman utama
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Sertakan koneksi database
include '../includes/db_connect.php';

$error = '';

// Proses form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil dan bersihkan data input
    $username = $conn->real_escape_string($_POST['username']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // Password akan di-hash

    // Validasi sederhana
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Semua field harus diisi!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Format email tidak valid!";
    } else {
        // 1. Hash Password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // 2. Query untuk memasukkan data ke database
        $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
        
        // Menggunakan Prepared Statement untuk keamanan (mencegah SQL Injection)
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $username, $email, $hashed_password);

        if ($stmt->execute()) {
            // Registrasi berhasil, arahkan ke halaman login
            header("Location: login.php?success=1");
            exit;
        } else {
            // Registrasi gagal (misal: username/email sudah ada)
            $error = "Registrasi gagal! Username atau Email mungkin sudah terdaftar.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Register - Tukar Buku</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Daftar Akun Baru</h1>
    </header>

    <hr>

    <div class="container"> 
        <h2>Form Registrasi</h2>
        
        <?php 
        // Mengganti inline style dengan class="feedback-error"
        if ($error): ?>
            <p class="feedback-error">ERROR: <?php echo $error; ?></p>
        <?php endif; ?>
        
        <form action="register.php" method="POST">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <button type="submit">Daftar</button>
        </form>
        
        <p>Sudah punya akun? <a href="login.php">Login di sini</a>.</p>
        
    </div> 
</body>
</html>