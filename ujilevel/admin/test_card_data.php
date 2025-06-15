<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Check if pengajuan_id is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<p>Harap berikan ID pengajuan. <a href='approval.php'>Kembali ke Approval</a></p>";
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
    
    // Foto path
    $foto_path = !empty($data['foto']) ? '../uploads/' . $data['foto'] : '';
    $foto_exists = !empty($foto_path) && file_exists($foto_path);
    
    include '../includes/header.php';
?>

<div class="container mx-auto py-6">
    <h1 class="text-xl font-bold mb-4">Pengujian Data Kartu Pelajar</h1>
    
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h2 class="font-bold text-lg mb-2">Data Siswa</h2>
        <table class="w-full">
            <tr>
                <td class="py-1 font-semibold" width="150">Nama Lengkap</td>
                <td><?php echo !empty($data['nama_lengkap']) ? $data['nama_lengkap'] : 'Tidak tersedia'; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold" width="150">NIS</td>
                <td><?php echo !empty($data['nis']) ? $data['nis'] : 'Tidak tersedia'; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold" width="150">NIS Format Tanggal</td>
                <td><?php echo date('Ymd') . '_' . $data['nis']; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold">Kelas</td>
                <td><?php echo !empty($data['kelas']) ? $data['kelas'] : 'Tidak tersedia'; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold">Alamat</td>
                <td><?php echo !empty($data['alamat']) ? $data['alamat'] : 'Tidak tersedia'; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold">Nomor Kartu</td>
                <td><?php echo !empty($data['nomor_kartu']) ? $data['nomor_kartu'] : 'Tidak tersedia'; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold">Tanggal Terbit</td>
                <td><?php echo !empty($data['tanggal_terbit']) ? date('d/m/Y', strtotime($data['tanggal_terbit'])) : 'Tidak tersedia'; ?></td>
            </tr>
            <tr>
                <td class="py-1 font-semibold">Status</td>
                <td>
                    <?php
                    $statusText = '';
                    switch ($data['status']) {
                        case 'menunggu':
                            $statusText = 'Menunggu';
                            break;
                        case 'disetujui':
                            $statusText = 'Disetujui';
                            break;
                        case 'ditolak':
                            $statusText = 'Ditolak';
                            break;
                        case 'Finish':
                            $statusText = 'Selesai';
                            break;
                        default:
                            $statusText = $data['status'];
                    }
                    echo $statusText;
                    ?>
                </td>
            </tr>
        </table>
    </div>
    
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h2 class="font-bold text-lg mb-2">Foto Siswa</h2>
        <div class="mb-2">
            <p>Path Foto: <?php echo $foto_path; ?></p>
            <p>Status: <?php echo $foto_exists ? 'File Ditemukan' : 'File Tidak Ada'; ?></p>
        </div>
        
        <div class="border p-4 text-center">
            <?php if ($foto_exists): ?>
                <img src="<?php echo $foto_path; ?>" alt="Foto Siswa" class="max-h-64 inline-block">
            <?php else: ?>
                <div class="bg-gray-200 h-64 w-48 flex items-center justify-center mx-auto">
                    <p class="text-gray-500">Foto tidak tersedia</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="flex space-x-2">
        <a href="generate_card.php?id=<?php echo $pengajuan_id; ?>" class="btn btn-primary" target="_blank">
            Cetak Kartu PDF
        </a>
        <a href="approval.php" class="btn btn-secondary">
            Kembali ke Approval
        </a>
    </div>
</div>

<?php
    include '../includes/footer.php';
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?> 