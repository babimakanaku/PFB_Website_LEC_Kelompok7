<?php
session_start();
include '../includes/db_connect.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_or_email = $conn->real_escape_string($_POST['username_or_email']);
    $password = $_POST['password'];

    $sql = "SELECT id, username, password FROM users WHERE username = ? OR email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username_or_email, $username_or_email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            header("Location: index.php");
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
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <header>
        <h1>Pustaka Digital: Tukar & Pinjam Buku</h1>
    </header>

    <hr>
    
    <div class="container"> 
    
        <h2>Form Login</h2> 
        
        <?php 
        if (isset($_GET['success']) && $_GET['success'] == 1): 
            echo '<p class="feedback-success">Registrasi berhasil! Silakan login.</p>';
        endif;
        
        if ($error): ?>
            <p class="feedback-error">ERROR: <?php echo $error; ?></p>
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
        
    </div> 

</body>
</html>