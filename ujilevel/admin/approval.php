<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle approval/rejection/completion/close
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $pengajuan_id = $_POST['pengajuan_id'] ?? 0;
    $action = $_POST['action'];
    $catatan_admin = $_POST['catatan_admin'] ?? '';
    
    try {
        if ($action === 'approve') {
            // Update status to approved
            $stmt = $conn->prepare("UPDATE pengajuan_kartu SET status = 'disetujui', catatan_admin = :catatan_admin WHERE pengajuan_id = :pengajuan_id");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->bindParam(':catatan_admin', $catatan_admin);
            $stmt->execute();
            
            // Check if kartu_pelajar entry already exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM kartu_pelajar WHERE pengajuan_id = :pengajuan_id");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->execute();
            $kartExists = $stmt->fetchColumn() > 0;
            
            $nomor_kartu = 'KP-' . date('Ym') . '-' . $pengajuan_id;
            $tanggal_terbit = date('Y-m-d');
            $file_kartu = ''; // Default empty value for file_kartu
            
            if ($kartExists) {
                // Update existing kartu_pelajar entry
                $stmt = $conn->prepare("UPDATE kartu_pelajar SET nomor_kartu = :nomor_kartu, tanggal_terbit = :tanggal_terbit, status = 'active', file_kartu = :file_kartu WHERE pengajuan_id = :pengajuan_id");
                $stmt->bindParam(':pengajuan_id', $pengajuan_id);
                $stmt->bindParam(':nomor_kartu', $nomor_kartu);
                $stmt->bindParam(':tanggal_terbit', $tanggal_terbit);
                $stmt->bindParam(':file_kartu', $file_kartu);
                $stmt->execute();
            } else {
                // Create new kartu_pelajar entry
                $stmt = $conn->prepare("INSERT INTO kartu_pelajar (pengajuan_id, nomor_kartu, tanggal_terbit, status, file_kartu) VALUES (:pengajuan_id, :nomor_kartu, :tanggal_terbit, 'active', :file_kartu)");
                $stmt->bindParam(':pengajuan_id', $pengajuan_id);
                $stmt->bindParam(':nomor_kartu', $nomor_kartu);
                $stmt->bindParam(':tanggal_terbit', $tanggal_terbit);
                $stmt->bindParam(':file_kartu', $file_kartu);
                $stmt->execute();
            }
            
            // Tampilkan pesan sukses
            $success_message = 'Pengajuan berhasil disetujui';
        } elseif ($action === 'reject') {
            // Update status to rejected
            $stmt = $conn->prepare("UPDATE pengajuan_kartu SET status = 'ditolak', catatan_admin = :catatan_admin WHERE pengajuan_id = :pengajuan_id");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->bindParam(':catatan_admin', $catatan_admin);
            $stmt->execute();
            
            $success_message = 'Pengajuan berhasil ditolak';
        } elseif ($action === 'finish') {
            // Update status to finished
            $stmt = $conn->prepare("UPDATE pengajuan_kartu SET status = 'Finish' WHERE pengajuan_id = :pengajuan_id");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->execute();
            
            // Update kartu_pelajar status if needed
            $stmt = $conn->prepare("UPDATE kartu_pelajar SET status = 'Finish' WHERE pengajuan_id = :pengajuan_id");
            $stmt->bindParam(':pengajuan_id', $pengajuan_id);
            $stmt->execute();
            
            $success_message = 'Pengajuan berhasil ditandai selesai';
        /* Removed close functionality */
        }
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Get all pending applications
try {
    $stmt = $conn->query("
        SELECT p.*, u.nama_lengkap, u.nis, u.kelas 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'menunggu'
        ORDER BY p.tanggal_pengajuan DESC
    ");
    $pendingApplications = $stmt->fetchAll();
    
    // Get all processed applications (approved, rejected, finished)
    $stmt = $conn->query("
        SELECT p.*, u.nama_lengkap, u.nis, u.kelas 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status IN ('disetujui', 'ditolak', 'Finish')
        ORDER BY p.tanggal_pengajuan DESC
        LIMIT 10
    ");
    $processedApplications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    $pendingApplications = [];
    $processedApplications = [];
}

include '../includes/header.php';
?>

<!-- Approval Content -->
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

<!-- Pending Applications -->
<div class="card mb-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-800">Pengajuan Menunggu Approval</h2>
        <span class="px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">
            <?php echo count($pendingApplications); ?> Pengajuan
        </span>
    </div>
    
    <?php if (empty($pendingApplications)): ?>
        <p class="text-gray-500">Tidak ada pengajuan yang menunggu approval.</p>
    <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($pendingApplications as $app): ?>
                <div class="border rounded-lg p-4 hover:bg-gray-50">
                    <div class="flex justify-between">
                        <div>
                            <h3 class="font-medium"><?php echo $app['nama_lengkap']; ?></h3>
                            <p class="text-sm text-gray-500"><?php echo date('Ymd') . '_' . $app['nis'] . ' - ' . $app['kelas']; ?></p>
                            <p class="text-sm text-gray-500">Tanggal Pengajuan: <?php echo date('d M Y', strtotime($app['tanggal_pengajuan'])); ?></p>
                            <p class="text-sm mt-2"><?php echo $app['alamat']; ?></p>
                        </div>
                        <div class="flex items-start">
                            <?php if (!empty($app['foto'])): ?>
                                <img src="../uploads/<?php echo $app['foto']; ?>" alt="Foto" class="w-20 h-20 object-cover rounded-md">
                            <?php else: ?>
                                <div class="w-20 h-20 bg-gray-200 rounded-md flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex justify-end">
                        <button class="btn btn-secondary mr-2 approve-btn" data-id="<?php echo $app['pengajuan_id']; ?>">
                            <i class="fas fa-check mr-1"></i> Setujui
                        </button>
                        <button class="btn btn-danger reject-btn" data-id="<?php echo $app['pengajuan_id']; ?>">
                            <i class="fas fa-times mr-1"></i> Tolak
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Processed Applications -->
<div class="card">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-800">Pengajuan Terproses</h2>
    </div>
    
    <?php if (empty($processedApplications)): ?>
        <p class="text-gray-500">Belum ada pengajuan yang diproses.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Catatan Admin</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($processedApplications as $app): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium"><?php echo $app['nama_lengkap']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('Ymd') . '_' . $app['nis'] . ' - ' . $app['kelas']; ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo date('d M Y', strtotime($app['tanggal_pengajuan'])); ?>
                            </td>
                            <td class="px-4 py-3">
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
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                                <?php /* Removed "Kirim Kartu" button as users now download directly */ ?>
                                <?php /* Removed close button */ ?>
                                <?php if (in_array($app['status'], ['disetujui', 'Finish'])): ?>
                                    <div class="flex space-x-1 mt-1">
                                        <a href="test_card_data.php?id=<?php echo $app['pengajuan_id']; ?>" class="text-xs px-2 py-1 bg-gray-100 text-gray-800 rounded hover:bg-gray-200">
                                            <i class="fas fa-search mr-1"></i> Cek Data
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php echo !empty($app['catatan_admin']) ? $app['catatan_admin'] : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Approval Modal -->
<div id="approvalModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Setujui Pengajuan</h3>
            <button id="closeApprovalModal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="approve">
            <input type="hidden" id="approval_pengajuan_id" name="pengajuan_id" value="">
            
            <div class="mb-4">
                <label for="catatan_admin" class="form-label">Catatan Admin (Opsional)</label>
                <textarea id="catatan_admin" name="catatan_admin" class="form-input" rows="3"></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="cancelApprovalBtn" class="btn btn-secondary mr-2">Batal</button>
                <button type="submit" class="btn btn-success">Setujui Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejectionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Tolak Pengajuan</h3>
            <button id="closeRejectionModal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" id="rejection_pengajuan_id" name="pengajuan_id" value="">
            
            <div class="mb-4">
                <label for="rejection_catatan_admin" class="form-label">Alasan Penolakan</label>
                <textarea id="rejection_catatan_admin" name="catatan_admin" class="form-input" rows="3" required></textarea>
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="cancelRejectionBtn" class="btn btn-secondary mr-2">Batal</button>
                <button type="submit" class="btn btn-danger">Tolak Pengajuan</button>
            </div>
        </form>
    </div>
</div>

<!-- Card Completion Modal Removed -->

<!-- Removed Close Application Modal -->

<!-- Print Card Modal Removed -->

<script>
    // Approval Modal
    const approveBtns = document.querySelectorAll('.approve-btn');
    const approvalModal = document.getElementById('approvalModal');
    const closeApprovalModal = document.getElementById('closeApprovalModal');
    const cancelApprovalBtn = document.getElementById('cancelApprovalBtn');
    
    approveBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const pengajuanId = btn.getAttribute('data-id');
            document.getElementById('approval_pengajuan_id').value = pengajuanId;
            approvalModal.classList.remove('hidden');
        });
    });
    
    closeApprovalModal.addEventListener('click', () => {
        approvalModal.classList.add('hidden');
    });
    
    cancelApprovalBtn.addEventListener('click', () => {
        approvalModal.classList.add('hidden');
    });
    
    // Rejection Modal
    const rejectBtns = document.querySelectorAll('.reject-btn');
    const rejectionModal = document.getElementById('rejectionModal');
    const closeRejectionModal = document.getElementById('closeRejectionModal');
    const cancelRejectionBtn = document.getElementById('cancelRejectionBtn');
    
    rejectBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const pengajuanId = btn.getAttribute('data-id');
            document.getElementById('rejection_pengajuan_id').value = pengajuanId;
            rejectionModal.classList.remove('hidden');
        });
    });
    
    closeRejectionModal.addEventListener('click', () => {
        rejectionModal.classList.add('hidden');
    });
    
    cancelRejectionBtn.addEventListener('click', () => {
        rejectionModal.classList.add('hidden');
    });
    
    // Finish Modal - Removed
    
    // Removed Close Application Modal JS
    
    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === approvalModal) {
            approvalModal.classList.add('hidden');
        }
        if (e.target === rejectionModal) {
            rejectionModal.classList.add('hidden');
        }
        /* Removed finish and close modal checks */
    });

    // Print Card Modal - Removed

    // Close modal when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === approvalModal) {
            approvalModal.classList.add('hidden');
        }
        if (e.target === rejectionModal) {
            rejectionModal.classList.add('hidden');
        }
        /* Removed close modal check */
    });
</script>

<?php include '../includes/footer.php'; ?>
