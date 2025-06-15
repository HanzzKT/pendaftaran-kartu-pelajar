<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Get dashboard statistics
try {
    // Get current month and year
    $currentMonth = date('m');
    $currentYear = date('Y');
    
    // Total users (only siswa)
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'siswa'");
    $totalUsers = $stmt->fetch()['total'];
    
    // Total pending applications for current month
    $stmt = $conn->query("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.status = 'menunggu'
        AND MONTH(p.tanggal_pengajuan) = '$currentMonth'
        AND YEAR(p.tanggal_pengajuan) = '$currentYear'
    ");
    $pendingApplications = $stmt->fetch()['total'];
    
    // Total approved applications for current month
    $stmt = $conn->query("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.status = 'disetujui'
        AND MONTH(p.tanggal_pengajuan) = '$currentMonth'
        AND YEAR(p.tanggal_pengajuan) = '$currentYear'
    ");
    $approvedApplications = $stmt->fetch()['total'];
    
    // Total rejected applications for current month
    $stmt = $conn->query("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.status = 'ditolak'
        AND MONTH(p.tanggal_pengajuan) = '$currentMonth'
        AND YEAR(p.tanggal_pengajuan) = '$currentYear'
    ");
    $rejectedApplications = $stmt->fetch()['total'];
    
    // Total finished applications for current month
    $stmt = $conn->query("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id 
        WHERE p.status = 'Finish'
        AND MONTH(p.tanggal_pengajuan) = '$currentMonth'
        AND YEAR(p.tanggal_pengajuan) = '$currentYear'
    ");
    $finishedApplications = $stmt->fetch()['total'];
    
    // Removed closed applications count
    
    // Recent applications (all statuses)
    $stmt = $conn->query("
        SELECT p.*, u.nama_lengkap, u.nis, u.kelas 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        ORDER BY p.tanggal_pengajuan DESC
        LIMIT 5
    ");
    $recentApplications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

include '../includes/header.php';
?>

<!-- Dashboard Content -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    <!-- Total Users Card -->
    <div class="card bg-white rounded-lg shadow-md p-4 border-l-4 border-blue-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 text-blue-500 mr-4">
                <i class="fas fa-users text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Siswa</p>
                <p class="text-xl font-bold"><?php echo $totalUsers; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Pending Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-4 border-l-4 border-yellow-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500 mr-4">
                <i class="fas fa-clock text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Menunggu</p>
                <p class="text-xl font-bold"><?php echo $pendingApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Approved Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-4 border-l-4 border-green-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 text-green-500 mr-4">
                <i class="fas fa-check-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Disetujui</p>
                <p class="text-xl font-bold"><?php echo $approvedApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Rejected Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-4 border-l-4 border-red-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 text-red-500 mr-4">
                <i class="fas fa-times-circle text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Ditolak</p>
                <p class="text-xl font-bold"><?php echo $rejectedApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Finished Applications Card -->
    <div class="card bg-white rounded-lg shadow-md p-4 border-l-4 border-purple-500">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 text-purple-500 mr-4">
                <i class="fas fa-flag-checkered text-xl"></i>
            </div>
            <div>
                <p class="text-sm text-gray-500 font-medium">Selesai</p>
                <p class="text-xl font-bold"><?php echo $finishedApplications; ?></p>
            </div>
        </div>
    </div>
    
    <!-- Removed Closed Applications Card -->
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Applications -->
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">
                Pengajuan Terbaru
                <span class="text-sm font-normal text-gray-500 ml-2">
                    (Bulan <?php echo date('F Y'); ?>)
                </span>
            </h2>
            <a href="approval.php" class="text-blue-600 hover:text-blue-800 text-sm">Lihat Semua</a>
        </div>
        
        <?php if (empty($recentApplications)): ?>
            <p class="text-gray-500">Belum ada pengajuan kartu pelajar.</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($recentApplications as $app): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3">
                                    <div>
                                        <p class="font-medium"><?php echo $app['nama_lengkap']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo $app['nis'] . ' - ' . $app['kelas']; ?></p>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Summary Chart -->
    <div class="card">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-bold text-gray-800">Statistik</h2>
        </div>
        <div class="p-4">
            <div class="space-y-4">
                <!-- Pending -->
                <div class="flex items-center">
                    <div class="flex items-center w-32">
                        <div class="w-3 h-3 rounded-full bg-yellow-500 mr-2"></div>
                        <span class="text-sm text-gray-600">Menunggu</span>
                    </div>
                    <div class="flex-1 h-4 bg-gray-200 rounded-full overflow-hidden">
                        <?php 
                        $total = $pendingApplications + $approvedApplications + $rejectedApplications + $finishedApplications;
                        $pendingWidth = $total > 0 ? ($pendingApplications / $total) * 100 : 0;
                        ?>
                        <div class="h-full bg-yellow-500" style="width: <?php echo $pendingWidth; ?>%"></div>
                    </div>
                    <div class="w-24 text-right">
                        <span class="text-sm text-gray-600"><?php echo $pendingApplications; ?></span>
                        <span class="text-xs text-gray-400 ml-1">(<?php echo number_format($pendingWidth, 1); ?>%)</span>
                    </div>
                </div>
                
                <!-- Approved -->
                <div class="flex items-center">
                    <div class="flex items-center w-32">
                        <div class="w-3 h-3 rounded-full bg-green-500 mr-2"></div>
                        <span class="text-sm text-gray-600">Disetujui</span>
                    </div>
                    <div class="flex-1 h-4 bg-gray-200 rounded-full overflow-hidden">
                        <?php $approvedWidth = $total > 0 ? ($approvedApplications / $total) * 100 : 0; ?>
                        <div class="h-full bg-green-500" style="width: <?php echo $approvedWidth; ?>%"></div>
                    </div>
                    <div class="w-24 text-right">
                        <span class="text-sm text-gray-600"><?php echo $approvedApplications; ?></span>
                        <span class="text-xs text-gray-400 ml-1">(<?php echo number_format($approvedWidth, 1); ?>%)</span>
                    </div>
                </div>
                
                <!-- Rejected -->
                <div class="flex items-center">
                    <div class="flex items-center w-32">
                        <div class="w-3 h-3 rounded-full bg-red-500 mr-2"></div>
                        <span class="text-sm text-gray-600">Ditolak</span>
                    </div>
                    <div class="flex-1 h-4 bg-gray-200 rounded-full overflow-hidden">
                        <?php $rejectedWidth = $total > 0 ? ($rejectedApplications / $total) * 100 : 0; ?>
                        <div class="h-full bg-red-500" style="width: <?php echo $rejectedWidth; ?>%"></div>
                    </div>
                    <div class="w-24 text-right">
                        <span class="text-sm text-gray-600"><?php echo $rejectedApplications; ?></span>
                        <span class="text-xs text-gray-400 ml-1">(<?php echo number_format($rejectedWidth, 1); ?>%)</span>
                    </div>
                </div>
                
                <!-- Finished -->
                <div class="flex items-center">
                    <div class="flex items-center w-32">
                        <div class="w-3 h-3 rounded-full bg-purple-500 mr-2"></div>
                        <span class="text-sm text-gray-600">Selesai</span>
                    </div>
                    <div class="flex-1 h-4 bg-gray-200 rounded-full overflow-hidden">
                        <?php $finishedWidth = $total > 0 ? ($finishedApplications / $total) * 100 : 0; ?>
                        <div class="h-full bg-purple-500" style="width: <?php echo $finishedWidth; ?>%"></div>
                    </div>
                    <div class="w-24 text-right">
                        <span class="text-sm text-gray-600"><?php echo $finishedApplications; ?></span>
                        <span class="text-xs text-gray-400 ml-1">(<?php echo number_format($finishedWidth, 1); ?>%)</span>
                    </div>
                </div>
                
                <!-- Removed Closed status chart -->

                <!-- Total -->
                <div class="flex items-center pt-2 mt-2 border-t border-gray-200">
                    <div class="flex items-center w-32">
                        <span class="text-sm font-medium text-gray-700">Total</span>
                    </div>
                    <div class="flex-1"></div>
                    <div class="w-24 text-right">
                        <span class="text-sm font-medium text-gray-700"><?php echo $total; ?></span>
                        <span class="text-xs text-gray-400 ml-1">(100%)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
