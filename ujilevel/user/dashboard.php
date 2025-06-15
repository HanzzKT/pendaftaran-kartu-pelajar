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

// Get dashboard statistics
try {
    // Total applications by user
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengajuan_kartu WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $totalApplications = $stmt->fetchColumn();
    
    // Pending applications
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengajuan_kartu WHERE user_id = :user_id AND status = 'menunggu'");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $pendingApplications = $stmt->fetchColumn();
    
    // Approved applications
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengajuan_kartu WHERE user_id = :user_id AND status = 'disetujui'");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $approvedApplications = $stmt->fetchColumn();
    
    // Rejected applications
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengajuan_kartu WHERE user_id = :user_id AND status = 'ditolak'");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $rejectedApplications = $stmt->fetchColumn();
    
    // Finished applications
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM pengajuan_kartu WHERE user_id = :user_id AND status = 'Finish'");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $finishedApplications = $stmt->fetchColumn();
    
    // Removed closed applications count
    
    // Recent applications
    $stmt = $conn->prepare("
        SELECT p.*, k.nomor_kartu, k.tanggal_terbit, k.file_kartu
        FROM pengajuan_kartu p
        LEFT JOIN kartu_pelajar k ON p.pengajuan_id = k.pengajuan_id
        WHERE p.user_id = :user_id
        ORDER BY p.tanggal_pengajuan DESC
        LIMIT 5
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recentApplications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

// Get user information
try {
    // Query database dengan user_id
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Jika tidak ditemukan dengan user_id, coba gunakan username dari session
    if (!$user && isset($_SESSION['username'])) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $_SESSION['username']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Jika masih tidak ada data dari database, gunakan data dari session
    if (!$user) {
        $user = [
            'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Tidak tersedia',
            'nis' => $_SESSION['nis'] ?? 'Tidak tersedia',
            'kelas' => $_SESSION['kelas'] ?? 'Tidak tersedia',
            'email' => $_SESSION['email'] ?? 'Tidak tersedia',
            'username' => $_SESSION['username'] ?? 'Tidak tersedia'
        ];
    } else {
        // Set default values for any missing fields
        $user['nama_lengkap'] = $user['nama_lengkap'] ?? $_SESSION['nama_lengkap'] ?? 'Tidak tersedia';
        $user['nis'] = $user['nis'] ?? $_SESSION['nis'] ?? 'Tidak tersedia';
        $user['kelas'] = $user['kelas'] ?? $_SESSION['kelas'] ?? 'Tidak tersedia';
        $user['email'] = $user['email'] ?? $_SESSION['email'] ?? 'Tidak tersedia';
        $user['username'] = $user['username'] ?? $_SESSION['username'] ?? 'Tidak tersedia';
    }
} catch (PDOException $e) {
    // Gunakan session data sebagai fallback jika database error
    $user = [
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Tidak tersedia',
        'nis' => $_SESSION['nis'] ?? 'Tidak tersedia',
        'kelas' => $_SESSION['kelas'] ?? 'Tidak tersedia',
        'email' => $_SESSION['email'] ?? 'Tidak tersedia',
        'username' => $_SESSION['username'] ?? 'Tidak tersedia'
    ];
}

include '../includes/header.php';

// Debug info (hanya tampil jika diperlukan)
$show_debug = isset($_GET['debug']) && $_GET['debug'] === 'true';
if ($show_debug):
?>
<div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="fas fa-info-circle text-yellow-500"></i>
        </div>
        <div class="ml-3">
            <p class="text-sm font-medium">Debug Info (hanya terlihat dengan parameter debug=true)</p>
            <div class="mt-2 text-xs">
                <p><strong>Session ID:</strong> <?php echo session_id(); ?></p>
                <p><strong>User ID:</strong> <?php echo $user_id; ?></p>
                <p><strong>Session Data:</strong> <?php echo json_encode($_SESSION); ?></p>
                <p><strong>User Data from DB:</strong> <?php echo json_encode($user); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Dashboard Content -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <!-- Total Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-file-alt text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-gray-500 font-medium truncate">Total Pengajuan</p>
                <p class="text-2xl font-bold"><?php echo $totalApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Pending Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-clock text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-gray-500 font-medium truncate">Menunggu</p>
                <p class="text-2xl font-bold"><?php echo $pendingApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Approved Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                <i class="fas fa-check-circle text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-gray-500 font-medium truncate">Disetujui</p>
                <p class="text-2xl font-bold"><?php echo $approvedApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Rejected Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-6 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                <i class="fas fa-times-circle text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-gray-500 font-medium truncate">Ditolak</p>
                <p class="text-2xl font-bold"><?php echo $rejectedApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Finished Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                <i class="fas fa-flag-checkered text-2xl"></i>
            </div>
            <div class="min-w-0">
                <p class="text-sm text-gray-500 font-medium truncate">Selesai</p>
                <p class="text-2xl font-bold"><?php echo $finishedApplications; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Applications -->
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Pengajuan Terbaru</h2>
            <a href="status.php" class="text-blue-600 hover:text-blue-800 text-sm">Lihat Semua</a>
        </div>
        
        <?php if (empty($recentApplications)): ?>
            <p class="text-gray-500">Anda belum melakukan pengajuan kartu pelajar.</p>
            <div class="mt-4">
                <a href="pengajuan.php" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i> Buat Pengajuan Baru
                </a>
            </div>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($recentApplications as $app): ?>
                    <div class="border rounded-lg p-4 hover:bg-gray-50">
                        <div class="flex justify-between">
                            <div>
                                <p class="text-sm text-gray-500">ID: <?php echo $app['pengajuan_id']; ?></p>
                                <p class="text-sm text-gray-500">Tanggal: <?php echo date('d M Y', strtotime($app['tanggal_pengajuan'])); ?></p>
                                
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
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?> inline-block mt-2">
                                    <?php echo $statusText; ?>
                                </span>
                                
                                <?php if (!empty($app['catatan_admin'])): ?>
                                    <p class="text-sm mt-2"><strong>Catatan Admin:</strong> <?php echo $app['catatan_admin']; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($app['status'] === 'disetujui'): ?>
                                <div>
                                    <form method="POST" action="status.php">
                                        <input type="hidden" name="action" value="finish">
                                        <input type="hidden" name="pengajuan_id" value="<?php echo $app['pengajuan_id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check mr-1"></i> Selesai
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- User Information -->
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Informasi Pengguna</h2>
        </div>
        
        <div class="space-y-4">
            <div class="flex items-center">
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-4">
                    <i class="fas fa-user text-2xl"></i>
                </div>
                <div>
                    <h3 class="text-lg font-medium"><?php echo $user['nama_lengkap']; ?></h3>
                    <p class="text-gray-500">
                        <?php 
                            // Hanya tampilkan NIS dan kelas jika keduanya tersedia dan bukan 'Tidak tersedia'
                            if ($user['nis'] !== 'Tidak tersedia' && $user['kelas'] !== 'Tidak tersedia') {
                                echo date('Ymd') . '_' . $user['nis'] . ' - ' . $user['kelas'];
                            } else {
                                // Jika salah satu tidak tersedia, tampilkan pesan yang lebih baik
                                echo 'NIS/Kelas: Belum tersedia';
                            }
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Username</p>
                        <p class="font-medium">
                            <?php if ($user['username'] !== 'Tidak tersedia'): ?>
                                <?php echo $user['username']; ?>
                            <?php else: ?>
                                <span class="text-red-500">Tidak tersedia</span>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Role</p>
                        <p class="font-medium capitalize">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                <i class="fas fa-user mr-1"></i> Siswa
                            </span>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <p class="text-sm text-gray-500 mb-2">Aksi Cepat</p>
                <div class="flex space-x-2">
                    <a href="pengajuan.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Pengajuan Baru
                    </a>
                    <a href="status.php" class="btn btn-secondary">
                        <i class="fas fa-tasks mr-1"></i> Cek Status
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
