<?php
// Fungsi untuk login
function loginUser($conn, $username, $password) {
    $username = sanitize($username);
    
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        $user = $stmt->fetch();
        
        // Debug login
        if (isset($_GET['debug_login']) && $_GET['debug_login'] == 1) {
            echo "<pre>";
            echo "Username: " . $username . "<br>";
            echo "User data: ";
            print_r($user);
            echo "Password match: " . ($user && password_verify($password, $user['password']) ? 'Yes' : 'No');
            echo "</pre>";
            exit;
        }
        
        // Memeriksa password yang tersimpan dengan enkripsi
        if ($user && password_verify($password, $user['password'])) {
            return [
                'success' => true,
                'user_id' => $user['user_id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'nama_lengkap' => $user['nama_lengkap']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Username atau password salah'
            ];
        }
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}

// Fungsi untuk memeriksa apakah user sudah login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fungsi untuk memeriksa role
function isAdmin() {
    return isset($_SESSION['role']) && (
        $_SESSION['role'] === 'admin' || 
        $_SESSION['role'] === 'Admin' ||
        strtolower($_SESSION['role']) === 'admin'
    );
}

// Fungsi untuk logout
function logout() {
    session_unset();
    session_destroy();
}

// Fungsi untuk mendaftarkan user baru (untuk admin)
function registerUser($conn, $username, $password, $role, $nama_lengkap, $nis, $kelas) {
    $username = sanitize($username);
    $nama_lengkap = sanitize($nama_lengkap);
    $nis = sanitize($nis);
    $kelas = sanitize($kelas);
    // Enkripsi password menggunakan password_hash
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Cek apakah username sudah ada
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            return [
                'success' => false,
                'message' => 'Username sudah digunakan'
            ];
        }
        
        // Insert user baru
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, nama_lengkap, nis, kelas) VALUES (:username, :password, :role, :nama_lengkap, :nis, :kelas)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':nama_lengkap', $nama_lengkap);
        $stmt->bindParam(':nis', $nis);
        $stmt->bindParam(':kelas', $kelas);
        $stmt->execute();
        
        return [
            'success' => true,
            'message' => 'User berhasil didaftarkan'
        ];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage()
        ];
    }
}
