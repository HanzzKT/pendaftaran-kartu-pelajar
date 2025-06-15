<?php
session_start();
require_once '../config/database.php';
require_once '../functions/auth.php';

// Check if user is admin
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

// Set default filter values
$status_filter = $_GET['status'] ?? 'all';
$date_from = $_GET['date_from'] ?? date('Y-m-01'); // First day of current month
$date_to = $_GET['date_to'] ?? date('Y-m-t'); // Last day of current month

// Build query based on filters
$query = "
    SELECT p.*, u.nama_lengkap, u.nis, u.kelas, k.nomor_kartu, k.tanggal_terbit
    FROM pengajuan_kartu p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN kartu_pelajar k ON p.pengajuan_id = k.pengajuan_id
    WHERE p.tanggal_pengajuan BETWEEN :date_from AND :date_to
";

if ($status_filter !== 'all') {
    $query .= " AND p.status = :status";
}

$query .= " ORDER BY p.tanggal_pengajuan DESC";

try {
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':date_from', $date_from);
    $stmt->bindParam(':date_to', $date_to);
    
    if ($status_filter !== 'all') {
        $stmt->bindParam(':status', $status_filter);
    }
    
    $stmt->execute();
    $reports = $stmt->fetchAll();
    
    // Get summary statistics
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'menunggu' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'disetujui' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'ditolak' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'Finish' THEN 1 ELSE 0 END) as finished
        FROM pengajuan_kartu
        WHERE tanggal_pengajuan BETWEEN :date_from AND :date_to
    ");
    $stmt->bindParam(':date_from', $date_from);
    $stmt->bindParam(':date_to', $date_to);
    $stmt->execute();
    $summary = $stmt->fetch();
    
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    $reports = [];
    $summary = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'finished' => 0
    ];
}

include '../includes/header.php';
?>

<!-- Reports Content -->
<div class="card mb-6">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Filter Laporan</h2>
    
    <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label for="status" class="form-label">Status</label>
            <select id="status" name="status" class="form-input">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>Semua Status</option>
                <option value="menunggu" <?php echo $status_filter === 'menunggu' ? 'selected' : ''; ?>>Menunggu</option>
                <option value="disetujui" <?php echo $status_filter === 'disetujui' ? 'selected' : ''; ?>>Disetujui</option>
                <option value="ditolak" <?php echo $status_filter === 'ditolak' ? 'selected' : ''; ?>>Ditolak</option>
                <option value="Finish" <?php echo $status_filter === 'Finish' ? 'selected' : ''; ?>>Selesai</option>
            </select>
        </div>
        
        <div>
            <label for="date_from" class="form-label">Dari Tanggal</label>
            <input type="date" id="date_from" name="date_from" class="form-input" value="<?php echo $date_from; ?>">
        </div>
        
        <div>
            <label for="date_to" class="form-label">Sampai Tanggal</label>
            <input type="date" id="date_to" name="date_to" class="form-input" value="<?php echo $date_to; ?>">
        </div>
        
        <div class="md:col-span-3">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter mr-2"></i> Filter
            </button>
            <a href="reports.php" class="btn btn-secondary ml-2">
                <i class="fas fa-sync-alt mr-2"></i> Reset
            </a>
            <button type="button" id="printReportBtn" class="btn btn-success ml-2">
                <i class="fas fa-print mr-2"></i> Cetak Laporan
            </button>
           
        </div>
    </form>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
    <div class="card bg-white p-4 border-l-4 border-blue-500">
        <p class="text-sm text-gray-500 font-medium">Total Pengajuan</p>
        <p class="text-2xl font-bold"><?php echo $summary['total']; ?></p>
    </div>
    
    <div class="card bg-white p-4 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-500 font-medium">Menunggu</p>
        <p class="text-2xl font-bold"><?php echo $summary['pending']; ?></p>
    </div>
    
    <div class="card bg-white p-4 border-l-4 border-green-500">
        <p class="text-sm text-gray-500 font-medium">Disetujui</p>
        <p class="text-2xl font-bold"><?php echo $summary['approved']; ?></p>
    </div>
    
    <div class="card bg-white p-4 border-l-4 border-red-500">
        <p class="text-sm text-gray-500 font-medium">Ditolak</p>
        <p class="text-2xl font-bold"><?php echo $summary['rejected']; ?></p>
    </div>
    
    <div class="card bg-white p-4 border-l-4 border-purple-500">
        <p class="text-sm text-gray-500 font-medium">Selesai</p>
        <p class="text-2xl font-bold"><?php echo $summary['finished']; ?></p>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Hasil Laporan</h2>
    
    <?php if (empty($reports)): ?>
        <p class="text-gray-500">Tidak ada data yang sesuai dengan filter.</p>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full" id="reportTable">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS - Kelas</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Pengajuan</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nomor Kartu</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Terbit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($reports as $report): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo $report['pengajuan_id']; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $report['nama_lengkap']; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('Ymd') . '_' . $report['nis'] . ' - ' . $report['kelas']; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('d M Y', strtotime($report['tanggal_pengajuan'])); ?></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <?php
                                $statusClass = '';
                                $statusText = '';
                                
                                switch ($report['status']) {
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
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo $report['nomor_kartu'] ?? '-'; ?></td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $report['tanggal_terbit'] ? date('d M Y', strtotime($report['tanggal_terbit'])) : '-'; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Print Report Template -->
<div id="printTemplate" class="hidden">
    <div style="padding: 20px;">
        <div style="text-align: center; margin-bottom: 20px;">
            <h1 style="font-size: 18px; font-weight: bold;">LAPORAN PENGAJUAN KARTU PELAJAR</h1>
            <p>Periode: <?php echo date('d M Y', strtotime($date_from)); ?> - <?php echo date('d M Y', strtotime($date_to)); ?></p>
        </div>
        
        <div style="margin-bottom: 20px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 25%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Total Pengajuan</td>
                    <td style="width: 25%; padding: 8px; border: 1px solid #ddd;"><?php echo $summary['total']; ?></td>
                    <td style="width: 25%; padding: 8px; border: 1px solid #ddd; font-weight: bold;">Menunggu</td>
                    <td style="width: 25%; padding: 8px; border: 1px solid #ddd;"><?php echo $summary['pending']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Disetujui</td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $summary['approved']; ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Ditolak</td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $summary['rejected']; ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;">Selesai</td>
                    <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $summary['finished']; ?></td>
                    <td style="padding: 8px; border: 1px solid #ddd; font-weight: bold;"></td>
                    <td style="padding: 8px; border: 1px solid #ddd;"></td>
                </tr>
            </table>
        </div>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f3f4f6;">
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">ID</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nama</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">NIS - Kelas</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tanggal Pengajuan</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Status</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Nomor Kartu</th>
                    <th style="padding: 8px; border: 1px solid #ddd; text-align: left;">Tanggal Terbit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $report['pengajuan_id']; ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $report['nama_lengkap']; ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo date('Ymd') . '_' . $report['nis'] . ' - ' . $report['kelas']; ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo date('d M Y', strtotime($report['tanggal_pengajuan'])); ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <?php
                            switch ($report['status']) {
                                case 'menunggu':
                                    echo 'Menunggu';
                                    break;
                                case 'disetujui':
                                    echo 'Disetujui';
                                    break;
                                case 'ditolak':
                                    echo 'Ditolak';
                                    break;
                                case 'Finish':
                                    echo 'Selesai';
                                    break;
                            }
                            ?>
                        </td>
                        <td style="padding: 8px; border: 1px solid #ddd;"><?php echo $report['nomor_kartu'] ?? '-'; ?></td>
                        <td style="padding: 8px; border: 1px solid #ddd;">
                            <?php echo $report['tanggal_terbit'] ? date('d M Y', strtotime($report['tanggal_terbit'])) : '-'; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div style="margin-top: 30px; text-align: right;">
            <p>Dicetak pada: <?php echo date('d M Y H:i:s'); ?></p>
        </div>
    </div>
</div>

<script>
    // Print Report
    document.getElementById('printReportBtn').addEventListener('click', function() {
        const printContent = document.getElementById('printTemplate').innerHTML;
        const originalContent = document.body.innerHTML;
        
        document.body.innerHTML = printContent;
        window.print();
        document.body.innerHTML = originalContent;
        
        // Reload event listeners
        location.reload();
    });
</script>

<?php include '../includes/footer.php'; ?>
