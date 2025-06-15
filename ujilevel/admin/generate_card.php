<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Allow both admin and regular users to access this page
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

// Check if pengajuan_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: approval.php');
    exit;
}

$pengajuan_id = $_GET['id'];

try {
    // Get pengajuan and user data
    $stmt = $conn->prepare("
        SELECT p.*, u.nama_lengkap, u.nis, u.kelas, k.nomor_kartu, k.tanggal_terbit, k.file_kartu 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN kartu_pelajar k ON p.pengajuan_id = k.pengajuan_id
        WHERE p.pengajuan_id = :pengajuan_id
    ");
    $stmt->bindParam(':pengajuan_id', $pengajuan_id);
    $stmt->execute();
    
    $data = $stmt->fetch();
    
    if (!$data) {
        die("Pengajuan tidak ditemukan");
    }

    // Only approved or finished cards can be generated
    if (!in_array($data['status'], ['disetujui', 'Finish'])) {
        die("Kartu pelajar belum disetujui");
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

    // Generate PDF-style page
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>PDF Kartu Pelajar - <?php echo $nama; ?></title>
    <style>
        @page {
            size: 85.6mm 53.98mm landscape;
            margin: 0;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .print-area {
            width: 85.6mm;
            height: 53.98mm;
            position: relative;
            margin: auto;
        }
        .pdf-container {
            width: 100%;
            height: 100%;
            position: relative;
            background: #0047AB; /* Warna biru untuk SMK Telesandi */
            color: white;
            overflow: hidden;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        /* Diagonal stripes background */
        .pdf-container::before {
            content: '';
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
            padding: 12px 15px 30px 15px;
            display: grid;
            grid-template-columns: 90px 1fr;
            grid-gap: 15px;
            height: calc(100% - 42px);
        }
        .card-header {
            grid-column: 1 / -1;
            text-align: center;
            border-bottom: 2px solid rgba(255,255,255,0.3);
            padding-bottom: 4px;
            margin-bottom: 8px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-container {
            position: absolute;
            left: 0;
            top: -5px;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-container img {
            max-width: 100%;
            max-height: 100%;
        }
        .card-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .card-subtitle {
            font-size: 11px;
            margin: 2px 0;
            opacity: 0.8;
        }
        .school-badge {
            position: absolute;
            top: -8px;
            right: 0;
            background: white;
            color: #0047AB; /* Warna biru untuk SMK Telesandi */
            font-weight: bold;
            font-size: 14px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 3;
        }
        .photo-area {
            width: 80px;
            height: 100px;
            border: 2px solid rgba(255,255,255,0.5);
            overflow: hidden;
            border-radius: 8px;
            background: rgba(255,255,255,0.2);
            margin-top: -4px;
        }
        .photo-area img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .details-area {
            font-size: 10px;
            line-height: 1.4;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding-top: 5px;
        }
        .detail-row {
            margin-bottom: 4px;
            display: flex;
        }
        .detail-label {
            font-weight: bold;
            display: inline-block;
            width: 60px;
            opacity: 0.8;
        }
        .detail-value {
            font-weight: 500;
        }
        .card-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 8px;
            padding: 6px 15px;
            display: flex;
            justify-content: space-between;
            z-index: 5;
            color: rgba(255, 255, 255, 0.9);
        }
        .card-footer::before {
            content: '';
            position: absolute;
            top: -1px;
            left: 15px;
            right: 15px;
            height: 1px;
            background: rgba(255, 255, 255, 0.3);
        }
        .card-footer span {
            position: relative;
            z-index: 2;
        }
        .action-buttons {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            background: white;
            padding: 10px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            margin: 0 5px;
            background-color: #4287f5;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn-print {
            background-color: #10b981;
        }
        .btn-back {
            background-color: #6b7280;
        }
        @media print {
            @page {
                size: 85.6mm 53.98mm landscape;
                margin: 0;
            }
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                background: none;
            }
            .print-area {
                margin: 0;
                position: absolute;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
            }
            .action-buttons {
                display: none;
            }
            .pdf-container {
                box-shadow: none;
            }
        }
    </style>
    <script>
        window.onload = function() {
            window.print();
        }
    </script>
</head>
<body>
    <div class="print-area">
        <div class="pdf-container">
            <div class="card-content">
                <div class="card-header">
                    <div class="logo-container">
                        <img src="../uploads/logo/smk_telesandi_logo.png" alt="SMK Telesandi Logo">
                    </div>
                    <div>
                        <h1 class="card-title">Kartu Pelajar</h1>
                        <p class="card-subtitle">SMK TELESANDI BEKASI</p>
                    </div>
                    <div class="school-badge">SMK</div>
                </div>
                
                <div class="photo-area">
                    <?php if (!empty($foto_path) && file_exists($foto_path)): ?>
                        <img src="<?php echo $foto_path; ?>" alt="Foto Siswa">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: rgba(255,255,255,0.7); font-size: 10px;">FOTO</div>
                    <?php endif; ?>
                </div>
                
                <div class="details-area">
                    <div class="detail-row">
                        <span class="detail-label">Nama:</span> 
                        <span class="detail-value"><?php echo $nama; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">NIS:</span> 
                        <span class="detail-value"><?php echo $tanggal_nis; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Kelas:</span> 
                        <span class="detail-value"><?php echo $kelas; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Alamat:</span> 
                        <span class="detail-value"><?php echo substr($alamat, 0, 25); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card-footer">
                <span>Nomor Kartu: KP-<?php echo date('y', strtotime($tanggal_terbit)) . date('m', strtotime($tanggal_terbit)) . '-' . sprintf('%02d', $pengajuan_id); ?></span>
                <span>Dikeluarkan: <?php echo $tanggal_terbit; ?></span>
            </div>
        </div>
    </div>
    
    <?php if (isset($_GET['format']) && $_GET['format'] === 'pdf'): ?>
    <!-- Sembunyikan tombol jika format PDF -->
    <?php else: ?>
    <div class="flex justify-center mt-4">
        <button id="printBtn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded mr-2">
            Cetak
        </button>
        <a href="approval.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            Kembali
        </a>
    </div>
    <?php endif; ?>
</body>
</html>
<?php
    exit;
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 