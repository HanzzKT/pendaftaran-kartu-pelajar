<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Get application counts
try {
    // Total applications count (excluding admin accounts)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE u.role = 'siswa'
    ");
    $stmt->execute();
    $totalApplications = $stmt->fetchColumn();
    
    // Pending applications count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'menunggu' AND u.role = 'siswa'
    ");
    $stmt->execute();
    $pendingApplications = $stmt->fetchColumn();
    
    // Approved applications count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'disetujui' AND u.role = 'siswa'
    ");
    $stmt->execute();
    $approvedApplications = $stmt->fetchColumn();
    
    // Rejected applications count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'ditolak' AND u.role = 'siswa'
    ");
    $stmt->execute();
    $rejectedApplications = $stmt->fetchColumn();

    // Finished applications count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE p.status = 'Finish' AND u.role = 'siswa'
    ");
    $stmt->execute();
    $finishedApplications = $stmt->fetchColumn();
    
    // Applications with cards count (for report matching)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        JOIN kartu_pelajar k ON p.pengajuan_id = k.pengajuan_id
        WHERE p.status = 'Finish' AND u.role = 'siswa'
    ");
    $stmt->execute();
    $withCardsApplications = $stmt->fetchColumn();
    
    // Recent applications
    $stmt = $conn->prepare("
        SELECT p.*, u.nama_lengkap, u.nis, u.kelas 
        FROM pengajuan_kartu p
        JOIN users u ON p.user_id = u.user_id
        WHERE u.role = 'siswa'
        ORDER BY p.tanggal_pengajuan DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentApplications = $stmt->fetchAll();
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

include '../includes/header.php';
?>

<div class="flex flex-wrap -mx-3">
    <!-- Total Applications Card -->
    <div class="w-full md:w-1/5 px-3 mb-6">
        <div class="card">
            <div class="text-gray-700 text-sm font-semibold mb-1">Total Pengajuan</div>
            <div class="text-3xl font-bold"><?php echo $totalApplications; ?></div>
        </div>
    </div>
    
    <!-- Pending Applications Card -->
    <div class="w-full md:w-1/5 px-3 mb-6">
        <div class="card">
            <div class="text-yellow-600 text-sm font-semibold mb-1 ">Menunggu</div>
            <div class="text-3xl font-bold"><?php echo $pendingApplications; ?></div>
        </div>
    </div>
    
    <!-- Approved Applications Card -->
    <div class="w-full md:w-1/5 px-3 mb-6">
        <div class="card">
            <div class="text-green-600 text-sm font-semibold mb-1">Disetujui</div>
            <div class="text-3xl font-bold"><?php echo $approvedApplications; ?></div>
        </div>
    </div>
    
    <!-- Rejected Applications Card -->
    <div class="w-full md:w-1/5 px-3 mb-6">
        <div class="card">
            <div class="text-red-600 text-sm font-semibold mb-1">Ditolak</div>
            <div class="text-3xl font-bold"><?php echo $rejectedApplications; ?></div>
        </div>
    </div>
    
    <!-- Finished Applications Card -->
    <div class="w-full md:w-1/5 px-3 mb-6">
        <div class="card">
            <div class="text-purple-600 text-sm font-semibold mb-1">Selesai</div>
            <div class="text-3xl font-bold"><?php echo $finishedApplications; ?></div>
            <div class="text-xs text-gray-500 mt-1">Dengan kartu: <?php echo $withCardsApplications; ?></div>
        </div>
    </div>
</div>

<!-- Recent Applications -->
<div class="card mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Pengajuan Terbaru</h2>
    
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
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($recentApplications as $app): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?php echo $app['nama_lengkap']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('Ymd') . '_' . $app['nis'] . ' - ' . $app['kelas']; ?></p>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-500">
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
                                }
                                ?>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="approval.php" class="text-blue-600 hover:text-blue-900">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <div class="mt-4 text-right">
        <a href="approval.php" class="text-blue-600 hover:text-blue-900">Lihat Semua</a>
    </div>
</div>

<!-- Panduan Kartu Pelajar -->
<div class="card">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Panduan Pencetakan Kartu Pelajar</h2>
    
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-yellow-700">
                    <strong>Penting:</strong> Pastikan data siswa lengkap sebelum mencetak kartu pelajar, terutama foto dan NIS.
                </p>
            </div>
        </div>
    </div>
    
    <div class="space-y-4">
        <div>
            <h3 class="font-medium text-gray-800 mb-1">Langkah-langkah Pencetakan Kartu:</h3>
            <ol class="list-decimal list-inside pl-4 text-sm text-gray-600">
                <li class="mb-2">Setujui pengajuan kartu pelajar di halaman <a href="approval.php" class="text-blue-600 hover:underline">Approval</a></li>
                <li class="mb-2">Klik tombol <span class="px-2 py-1 bg-gray-100 text-gray-800 text-xs rounded">Cek Data</span> untuk memastikan data dan foto siswa sudah lengkap dan benar</li>
                <li class="mb-2">Klik tombol <span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded">Cetak Kartu</span> untuk mencetak kartu dalam bentuk PDF</li>
                <li class="mb-2">Setelah kartu dicetak, klik tombol <span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs rounded">Selesaikan Kartu</span> untuk menandai proses sudah selesai</li>
            </ol>
        </div>
        
        <div>
            <h3 class="font-medium text-gray-800 mb-1">Catatan:</h3>
            <ul class="list-disc list-inside pl-4 text-sm text-gray-600">
                <li class="mb-2">Pastikan foto siswa sudah diupload dan terlihat jelas</li>
                <li class="mb-2">Format NIS yang digunakan adalah: YYYYMMDD_NIS (contoh: 20230501_1234)</li>
                <li class="mb-2">Kartu akan ditampilkan dalam format HTML yang dapat dicetak langsung dari browser</li>
                <li class="mb-2">Klik tombol "Cetak Kartu" pada halaman kartu untuk mencetak kartu pelajar</li>
                <li class="mb-2">Jika ada masalah dengan pencetakan kartu, gunakan halaman "Cek Data" untuk memverifikasi data</li>
            </ul>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?> 