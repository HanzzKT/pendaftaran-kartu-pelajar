<?php
session_start();
require_once 'config/database.php';
require_once 'functions/auth.php';

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: user/dashboard.php');
    }
    exit;
}

// Handle login
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Validasi data
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi';
    } else {
        $result = loginUser($conn, $username, $password);
        
        if ($result['success']) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['username'] = $result['username'];
            $_SESSION['role'] = $result['role'];
            $_SESSION['nama_lengkap'] = $result['nama_lengkap'];
            
            // Arahkan ke halaman sesuai role
            if (strtolower($result['role']) === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: user/dashboard.php');
            }
            exit;
        } else {
            $error = $result['message'];
        }
    }
}

// Cek apakah database berhasil terhubung
$db_connected = false;
try {
    $test_query = $conn->query("SELECT 1");
    $db_connected = true;
} catch (Exception $e) {
    $db_error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pengajuan Kartu Pelajar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-blue-600">Sistem Pengajuan Kartu Pelajar</h1>
            <p class="text-gray-600 mt-2">Masuk untuk melanjutkan</p>
        </div>
        
        <?php if (!$db_connected): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <p class="font-bold">Error: Koneksi Database</p>
                <p>Tidak dapat terhubung ke database. Silakan hubungi administrator.</p>
                <?php if (isset($_GET['show_error']) && $_GET['show_error'] == 1): ?>
                <p class="text-xs mt-2"><?php echo $db_error; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Username</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-user text-gray-400"></i>
                    </span>
                    <input type="text" id="username" name="username" class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" placeholder="Masukkan username" required>
                </div>
            </div>
            
            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3">
                        <i class="fas fa-lock text-gray-400"></i>
                    </span>
                    <input type="password" id="password" name="password" class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all" placeholder="Masukkan password" required>
                </div>
            </div>
            
            <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition-all">
                Masuk
            </button>
        </form>
    </div>

    <?php if (isset($_GET['debug'])): ?>
    <div class="fixed bottom-2 right-2 bg-gray-800 text-white p-2 text-xs rounded opacity-70">
        <p>Database: <?php echo $db_connected ? 'Terhubung' : 'Terputus'; ?></p>
        <p>Server: <?php echo $_SERVER['SERVER_SOFTWARE']; ?></p>
        <p>PHP: <?php echo phpversion(); ?></p>
        <a href="debug_login.php?debug_key=dev123" class="text-blue-300 hover:underline" target="_blank">Debug Info</a>
    </div>
    <?php endif; ?>
</body>
</html>
