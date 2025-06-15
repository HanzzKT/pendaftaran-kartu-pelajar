<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is logged in and is a user (not admin)
if (!isLoggedIn() || isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle mark as finished or close application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pengajuan_id = $_POST['pengajuan_id'] ?? 0;
    
    if ($_POST['action'] === 'finish') {
        try {
            // Update status to finished
            $stmt = $conn->prepare("UPDATE pengajuan_kartu SET status = 'Finish' WHERE pengajuan_id = :pengajuan_id AND user_id = :user_id AND status = 'disetujui'");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $success_message = 'Status pengajuan berhasil diubah menjadi selesai.';
            } else {
                $error_message = 'Gagal mengubah status pengajuan.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    /* Removed close application functionality */
    } elseif ($_POST['action'] === 'mark_downloaded') {
        try {
            // Update status to finished after download
            $stmt = $conn->prepare("UPDATE pengajuan_kartu SET status = 'Finish' WHERE pengajuan_id = :pengajuan_id AND user_id = :user_id AND status = 'disetujui'");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $success_message = 'Kartu berhasil diunduh dan status pengajuan diubah menjadi selesai.';
            } else {
                $error_message = 'Gagal mengubah status pengajuan setelah unduh.';
            }
        } catch (PDOException $e) {
            $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
        }
    }
}

// Get all applications by user
try {
    $stmt = $conn->prepare("
        SELECT p.*, k.nomor_kartu, k.tanggal_terbit, k.file_kartu
        FROM pengajuan_kartu p
        LEFT JOIN kartu_pelajar k ON p.pengajuan_id = k.pengajuan_id
        WHERE p.user_id = :user_id
        ORDER BY p.tanggal_pengajuan DESC
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    $applications = [];
}

include '../includes/header.php';
?>

<!-- Status Content -->
<div class="mb-6">
    <a href="pengajuan.php" class="btn btn-primary">
        <i class="fas fa-plus mr-2"></i> Buat Pengajuan Baru
    </a>
</div>

<?php if ($success_message): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<!-- Applications List -->
<div class="card">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-800">Status Pengajuan Kartu Pelajar</h2>
    </div>
    
    <?php if (empty($applications)): ?>
        <p class="text-gray-500">Anda belum melakukan pengajuan kartu pelajar.</p>
    <?php else: ?>
        <div class="space-y-6">
            <?php foreach ($applications as $app): ?>
                <div class="border rounded-lg overflow-hidden">
                    <div class="bg-gray-50 p-4 border-b">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-medium">ID Pengajuan: <?php echo $app['pengajuan_id']; ?></p>
                                <p class="text-sm text-gray-500">Tanggal: <?php echo date('d M Y', strtotime($app['tanggal_pengajuan'])); ?></p>
                            </div>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            
                            switch ($app['status']) {
                                case 'menunggu':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    $statusText = 'Menunggu';
                                    break;
                                case 'disetujui':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusText = 'Disetujui';
                                    break;
                                case 'ditolak':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    $statusText = 'Ditolak';
                                    break;
                                case 'Finish':
                                    $statusClass = 'bg-purple-100 text-purple-800';
                                    $statusText = 'Selesai';
                                    break;
                                /* Removed closed status */
                            }
                            ?>
                            <span class="px-3 py-1 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="p-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Alamat</p>
                                <p><?php echo $app['alamat']; ?></p>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Foto</p>
                                <?php if (!empty($app['foto'])): ?>
                                    <img src="../uploads/<?php echo $app['foto']; ?>" alt="Foto" class="w-20 h-20 object-cover rounded-md">
                                <?php else: ?>
                                    <p class="text-gray-500">Tidak ada foto</p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Catatan Admin</p>
                                <p><?php echo !empty($app['catatan_admin']) ? $app['catatan_admin'] : '-'; ?></p>
                            </div>
                        </div>
                        
                        <?php if ($app['status'] === 'disetujui' || $app['status'] === 'Finish'): ?>
                            <div class="mt-4 pt-4 border-t">
                                <p class="font-medium mb-2">Informasi Kartu Pelajar</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">Nomor Kartu</p>
                                        <p><?php echo $app['nomor_kartu'] ?? '-'; ?></p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">Tanggal Terbit</p>
                                        <p><?php echo $app['tanggal_terbit'] ? date('d M Y', strtotime($app['tanggal_terbit'])) : '-'; ?></p>
                                    </div>
                                </div>
                                
                                <?php 
                                    $file_kartu = !empty($app['file_kartu']) ? '../uploads/kartu/' . $app['file_kartu'] : '';
                                    $has_uploaded_card = !empty($file_kartu) && file_exists($file_kartu);
                                    $file_extension = $has_uploaded_card ? strtolower(pathinfo($file_kartu, PATHINFO_EXTENSION)) : '';
                                    
                                    $card_type = '';
                                    $card_icon = '';
                                    
                                    if ($has_uploaded_card) {
                                        if (in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
                                            $card_type = 'Gambar (' . strtoupper($file_extension) . ')';
                                            $card_icon = 'fa-image';
                                        } elseif ($file_extension == 'pdf') {
                                            $card_type = 'Dokumen (PDF)';
                                            $card_icon = 'fa-file-pdf';
                                        } else {
                                            $card_type = 'File (' . strtoupper($file_extension) . ')';
                                            $card_icon = 'fa-file';
                                        }
                                    }
                                ?>
                                
                                <?php if ($has_uploaded_card): ?>
                                <div class="bg-green-50 border border-green-200 rounded p-3 mt-3">
                                    <div class="flex items-center">
                                        <div class="text-green-500">
                                            <i class="fas fa-check-circle text-lg"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-green-800">Kartu pelajar sudah tersedia</p>
                                            <p class="text-sm text-green-600">Format: <i class="fas <?php echo $card_icon; ?> mr-1"></i> <?php echo $card_type; ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="bg-blue-50 border border-blue-200 rounded p-3 mt-3">
                                    <div class="flex items-center">
                                        <div class="text-blue-500">
                                            <i class="fas fa-info-circle text-lg"></i>
                                        </div>
                                        <div class="ml-3">
                                            <p class="text-sm font-medium text-blue-800">Kartu pelajar tersedia dalam format digital</p>
                                            <p class="text-sm text-blue-600">Anda dapat mencetak sendiri kartu dari halaman lihat kartu</p>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="mt-4">
                                    <?php if ($app['status'] === 'disetujui'): ?>
                                    <a href="download_card.php?id=<?php echo $app['pengajuan_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-download mr-2"></i> Unduh Kartu
                                    </a>
                                    <p class="text-xs text-gray-500 mt-1">Kartu hanya dapat diunduh satu kali. Setelah diunduh, status akan berubah menjadi Selesai</p>
                                    <?php elseif ($app['status'] === 'Finish'): ?>
                                    <button disabled class="btn btn-secondary opacity-50 cursor-not-allowed">
                                        <i class="fas fa-download mr-2"></i> Sudah Diunduh
                                    </button>
                                    <p class="text-xs text-gray-500 mt-1">Kartu sudah diunduh dan tidak dapat diunduh kembali</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php /* Removed the separate finish button as we now use mark_downloaded action */ ?>

                        <?php /* Removed close application functionality */ ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
