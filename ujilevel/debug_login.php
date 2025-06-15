<?php
// Pengaturan header untuk keamanan debug - jangan pernah digunakan di production
header('Content-Type: text/plain');

// Muat koneksi database
require_once 'config/database.php';

// Cek akses debug
if (!isset($_GET['debug_key']) || $_GET['debug_key'] !== 'dev123') {
    die("Akses ditolak");
}

echo "========== DEBUG LOGIN ==========\n\n";

try {
    // Cek struktur tabel users
    echo "STRUKTUR TABEL USERS:\n";
    $result = $conn->query("DESCRIBE users");
    $fields = [];
    foreach ($result as $row) {
        $fields[] = $row;
        echo $row['Field'] . " - " . $row['Type'] . " - " . ($row['Null'] === 'NO' ? 'Required' : 'Optional') . "\n";
    }
    echo "\n";

    // Cek contoh data pengguna (tanpa password)
    echo "CONTOH DATA PENGGUNA:\n";
    $stmt = $conn->query("SELECT user_id, username, role, nama_lengkap FROM users LIMIT 3");
    $users = $stmt->fetchAll();
    
    if (count($users) === 0) {
        echo "Tidak ada data pengguna!\n";
    } else {
        foreach ($users as $user) {
            echo "ID: " . $user['user_id'] . "\n";
            echo "Username: " . $user['username'] . "\n";
            echo "Role: " . $user['role'] . "\n";
            echo "Nama: " . $user['nama_lengkap'] . "\n";
            echo "------------------------\n";
        }
    }
    
    // Cek jumlah pengguna per role
    echo "\nJUMLAH PENGGUNA PER ROLE:\n";
    $stmt = $conn->query("SELECT role, COUNT(*) as jumlah FROM users GROUP BY role");
    $roleCounts = $stmt->fetchAll();
    
    foreach ($roleCounts as $role) {
        echo $role['role'] . ": " . $role['jumlah'] . " pengguna\n";
    }
    
    // Periksa apakah ada pengguna yang tidak memiliki password
    echo "\nPENGGUNA TANPA PASSWORD:\n";
    $stmt = $conn->query("SELECT COUNT(*) as jumlah FROM users WHERE password IS NULL OR password = ''");
    $noPassword = $stmt->fetchColumn();
    echo "Jumlah: " . $noPassword . " pengguna\n";
    
} catch (PDOException $e) {
    echo "ERROR DATABASE: " . $e->getMessage();
} 