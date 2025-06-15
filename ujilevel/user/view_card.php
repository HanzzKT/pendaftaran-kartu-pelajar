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
        SELECT p.*, u.nama_lengkap, u.nis, u.kelas, k.nomor_kartu, k.tanggal_terbit, k.file_kartu 
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
    
    // Pastikan ada data yang diperlukan
    $nama = !empty($data['nama_lengkap']) ? $data['nama_lengkap'] : 'Nama tidak tersedia';
    $nis = !empty($data['nis']) ? $data['nis'] : 'NIS tidak tersedia';
    $kelas = !empty($data['kelas']) ? $data['kelas'] : 'Kelas tidak tersedia';
    $alamat = !empty($data['alamat']) ? $data['alamat'] : 'Alamat tidak tersedia';
    $tanggal_nis = date('Ymd') . '_' . $nis;
    $nomor_kartu = !empty($data['nomor_kartu']) ? $data['nomor_kartu'] : 'Nomor kartu tidak tersedia';
    $tanggal_terbit = !empty($data['tanggal_terbit']) ? date('d/m/Y', strtotime($data['tanggal_terbit'])) : date('d/m/Y');
    $foto_path = !empty($data['foto']) ? '../uploads/' . $data['foto'] : '';
    
    // Untuk file kartu yang diupload oleh admin
    $file_kartu = !empty($data['file_kartu']) ? '../uploads/kartu/' . $data['file_kartu'] : '';
    $has_uploaded_card = !empty($file_kartu) && file_exists($file_kartu);
    
    // Jika admin telah mengupload file kartu, tampilkan file tersebut
    if ($has_uploaded_card) {
        $file_ext = strtolower(pathinfo($file_kartu, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, ['jpg', 'jpeg', 'png'])) {
            // Jika file adalah gambar, tampilkan langsung
            header('Content-Type: image/' . $file_ext);
            readfile($file_kartu);
            exit;
        } elseif ($file_ext == 'pdf') {
            // Jika file adalah PDF, set header untuk menampilkan PDF
            header('Content-Type: application/pdf');
            readfile($file_kartu);
            exit;
        } else {
            // Jika tidak dikenal, tampilkan halaman dengan link download
            header('Location: download_card.php?id=' . $pengajuan_id);
            exit;
        }
    }
    
    // Jika tidak ada file yang diupload, tampilkan kartu dalam format HTML
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kartu Pelajar - <?php echo $nama; ?></title>
    <style type="text/css">
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f2f5;
            color: #333;
        }
        .card-container {
            width: 350px;
            height: 220px;
            margin: 40px auto;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            position: relative;
            overflow: hidden;
            padding: 0;
            color: white;
        }
        .card-pattern {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.1;
            background-image: repeating-linear-gradient(45deg, #ffffff 0px, #ffffff 2px, transparent 2px, transparent 9px);
            z-index: 1;
        }
        .card-content {
            position: relative;
            z-index: 2;
            padding: 15px;
            display: grid;
            grid-template-columns: 90px 1fr;
            grid-gap: 15px;
            height: 100%;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 8px;
            margin-bottom: 15px;
            grid-column: span 2;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .subtitle {
            font-size: 12px;
            margin: 4px 0;
            opacity: 0.8;
        }
        .photo {
            width: 80px;
            height: 100px;
            border: 2px solid rgba(255,255,255,0.5);
            background-color: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border-radius: 8px;
        }
        .photo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-placeholder {
            font-size: 10px;
            text-align: center;
        }
        .details {
            font-size: 10px;
            line-height: 1.5;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .detail-row {
            margin-bottom: 6px;
            display: flex;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 60px;
            opacity: 0.8;
        }
        .value {
            font-weight: 500;
        }
        .footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            padding: 8px 15px;
            background-color: rgba(0,0,0,0.2);
            display: flex;
            justify-content: space-between;
        }
        .btn-print {
            display: block;
            margin: 20px auto;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            width: 200px;
            font-weight: 600;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.2s ease;
        }
        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
            background-color: #2563eb;
        }
        @media print {
            .btn-print, .back-button {
                display: none;
            }
            body {
                background-color: white;
            }
            .card-container {
                margin: 0 auto;
                box-shadow: none;
                page-break-inside: avoid;
            }
        }
        .back-button {
            display: block;
            margin: 10px auto 30px;
            padding: 8px 16px;
            background-color: #64748b;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            width: 120px;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        .back-button:hover {
            background-color: #475569;
        }
        .school-logo {
            position: absolute;
            top: 10px;
            right: 10px;
            width: 40px;
            height: 40px;
            background-color: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            color: #1d4ed8;
            z-index: 3;
        }
    </style>
    <?php if (isset($_GET['download']) && $_GET['download'] == 1): ?>
    <script>
        // Auto-print when download parameter is set
        window.onload = function() {
            window.print();
        }
    </script>
    <?php endif; ?>
</head>
<body>
    <div class="card-container">
        <div class="card-pattern"></div>
        <div class="school-logo">SMA</div>
        <div class="card-content">
            <div class="header">
                <p class="title">KARTU PELAJAR</p>
                <p class="subtitle">SEKOLAH MENENGAH ATAS</p>
            </div>
            
            <div class="photo">
                <?php if (!empty($foto_path) && file_exists($foto_path)): ?>
                    <img src="<?php echo $foto_path; ?>" alt="Foto Siswa">
                <?php else: ?>
                    <div class="photo-placeholder">FOTO</div>
                <?php endif; ?>
            </div>
            
            <div class="details">
                <div class="detail-row">
                    <span class="label">Nama:</span> 
                    <span class="value"><?php echo $nama; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">NIS:</span> 
                    <span class="value"><?php echo $tanggal_nis; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Kelas:</span> 
                    <span class="value"><?php echo $kelas; ?></span>
                </div>
                <div class="detail-row">
                    <span class="label">Alamat:</span> 
                    <span class="value"><?php echo $alamat; ?></span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <span>Nomor Kartu: <?php echo $nomor_kartu; ?></span>
            <span>Dikeluarkan: <?php echo $tanggal_terbit; ?></span>
        </div>
    </div>
    
    <a href="#" class="btn-print" onclick="window.print(); return false;">Cetak Kartu</a>
    <a href="status.php" class="back-button">Kembali</a>
</body>
</html>
<?php
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 