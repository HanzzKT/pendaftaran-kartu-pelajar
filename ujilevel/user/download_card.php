<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is logged in and is a user (not admin)
if (!isLoggedIn() || isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: status.php');
    exit;
}

$pengajuan_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

try {
    // Get pengajuan and user data
    $stmt = $conn->prepare("
        SELECT p.*, u.nama_lengkap, u.nis, k.file_kartu 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN kartu_pelajar k ON p.pengajuan_id = k.pengajuan_id
        WHERE p.pengajuan_id = :pengajuan_id AND p.user_id = :user_id AND (p.status = 'Finish' OR p.status = 'disetujui')
    ");
    $stmt->bindParam(':pengajuan_id', $pengajuan_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $data = $stmt->fetch();
    
    if (!$data) {
        die("Kartu pelajar tidak ditemukan atau belum selesai diproses.");
    }
    
    // Update status to Finish if it's currently 'disetujui'
    if ($data['status'] === 'disetujui') {
        // Update status to finished
        $updateStmt = $conn->prepare("UPDATE pengajuan_kartu SET status = 'Finish' WHERE pengajuan_id = :pengajuan_id AND user_id = :user_id");
        $updateStmt->bindParam(':pengajuan_id', $pengajuan_id);
        $updateStmt->bindParam(':user_id', $user_id);
        $updateStmt->execute();
        
        // Update kartu_pelajar status if needed
        $updateCardStmt = $conn->prepare("UPDATE kartu_pelajar SET status = 'Finish' WHERE pengajuan_id = :pengajuan_id");
        $updateCardStmt->bindParam(':pengajuan_id', $pengajuan_id);
        $updateCardStmt->execute();
    }
    
    // Untuk file kartu yang diupload oleh admin
    $file_kartu = !empty($data['file_kartu']) ? '../uploads/kartu/' . $data['file_kartu'] : '';
    $has_uploaded_card = !empty($file_kartu) && file_exists($file_kartu);
    
    if ($has_uploaded_card) {
        // Buat nama file yang akan diunduh
        $nama_file = 'kartu_pelajar_' . $data['nis'] . '_' . date('Ymd') . '.' . pathinfo($file_kartu, PATHINFO_EXTENSION);
        
        // Set header untuk download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $nama_file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_kartu));
        readfile($file_kartu);
        exit;
    } else {
        // Jika tidak ada file upload, gunakan generate_card.php untuk membuat kartu
        header("Location: ../admin/generate_card.php?id=$pengajuan_id&format=pdf");
        exit;
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} 