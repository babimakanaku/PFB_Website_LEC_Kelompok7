<?php
session_start();
include '../includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = $conn->real_escape_string($_POST['username_or_email']);
    $password = $_POST['password'];

    // Cari user berdasarkan username atau email
    $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verifikasi password yang dimasukkan dengan hash di database
        if (password_verify($password, $user['password'])) {
            // Login Berhasil!
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: index.php"); // Arahkan ke halaman utama
            exit;
        } else {
            $error = "Password salah.";
        }
    } else {
        $error = "Username atau Email tidak ditemukan.";
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Login - Tukar Buku</title>
</head>
<body>
    <h2>Form Login</h2>
    <?php 
    if (isset($_GET['success']) && $_GET['success'] == 1): 
        echo '<p style="color: green;">Registrasi berhasil! Silakan login.</p>';
    endif;
    if ($error): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <div>
            <label for="username_or_email">Username atau Email:</label>
            <input type="text" id="username_or_email" name="username_or_email" required>
        </div>
        <div>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <button type="submit">Login</button>
    </form>
    <p>Belum punya akun? <a href="register.php">Daftar di sini</a>.</p>
</body>
</html>