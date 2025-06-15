<?php
if (!isset($_SESSION)) {
    session_start();
}

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$nama_lengkap = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User';
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Pengajuan Kartu Pelajar</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        // Tailwind config
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#3b82f6',
                        secondary: '#64748b',
                    }
                }
            }
        }
    </script>
    <style type="text/tailwindcss">
        @layer utilities {
            .nav-link {
                @apply flex items-center px-4 py-2 text-gray-600 rounded-lg hover:bg-blue-100 hover:text-blue-700 transition-all;
            }
            .nav-link.active {
                @apply bg-blue-100 text-blue-700 font-medium;
            }
            .nav-icon {
                @apply mr-3 text-lg;
            }
            .btn {
                @apply px-4 py-2 rounded-md font-medium transition-all focus:outline-none focus:ring-2 focus:ring-opacity-50;
            }
            .btn-primary {
                @apply bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500;
            }
            .btn-success {
                @apply bg-green-600 text-white hover:bg-green-700 focus:ring-green-500;
            }
            .btn-danger {
                @apply bg-red-600 text-white hover:bg-red-700 focus:ring-red-500;
            }
            .btn-warning {
                @apply bg-yellow-500 text-white hover:bg-yellow-600 focus:ring-yellow-400;
            }
            .btn-secondary {
                @apply bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-200;
            }
            .card {
                @apply bg-white rounded-lg shadow-md p-6;
            }
            .form-input {
                @apply w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all;
            }
            .form-label {
                @apply block text-gray-700 text-sm font-medium mb-2;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-white shadow-md fixed h-full">
            <div class="p-4 border-b">
                <h2 class="text-xl font-bold text-blue-600">Kartu Pelajar</h2>
                <p class="text-sm text-gray-600">Sistem Pengajuan</p>
            </div>
            
            <div class="p-4">
                <div class="flex items-center mb-4 pb-4 border-b">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 mr-3">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <p class="font-medium"><?php echo $nama_lengkap; ?></p>
                        <p class="text-xs text-gray-500 capitalize"><?php echo $role; ?></p>
                    </div>
                </div>
                
                <nav class="space-y-1">
                    <?php if ($role === 'admin'): ?>
                        <!-- Admin Navigation -->
                        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt nav-icon"></i> Dashboard
                        </a>
                        <a href="users.php" class="nav-link <?php echo $current_page === 'users.php' ? 'active' : ''; ?>">
                            <i class="fas fa-users nav-icon"></i> Kelola Pengguna
                        </a>
                        <a href="approval.php" class="nav-link <?php echo $current_page === 'approval.php' ? 'active' : ''; ?>">
                            <i class="fas fa-check-circle nav-icon"></i> Approval Kartu
                        </a>
                        <a href="reports.php" class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt nav-icon"></i> Laporan
                        </a>
                    <?php else: ?>
                        <!-- User Navigation -->
                        <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tachometer-alt nav-icon"></i> Dashboard
                        </a>
                        <a href="pengajuan.php" class="nav-link <?php echo $current_page === 'pengajuan.php' ? 'active' : ''; ?>">
                            <i class="fas fa-file-alt nav-icon"></i> Pengajuan Kartu
                        </a>
                        <a href="status.php" class="nav-link <?php echo $current_page === 'status.php' ? 'active' : ''; ?>">
                            <i class="fas fa-tasks nav-icon"></i> Status Pengajuan
                        </a>
                    <?php endif; ?>
                    
                    <a href="../logout.php" class="nav-link text-red-600 hover:bg-red-50 hover:text-red-700">
                        <i class="fas fa-sign-out-alt nav-icon"></i> Logout
                    </a>
                </nav>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="ml-64 flex-1 p-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">
                    <?php
                    switch ($current_page) {
                        case 'dashboard.php':
                            echo 'Dashboard';
                            break;
                        case 'users.php':
                            echo 'Kelola Pengguna';
                            break;
                        case 'approval.php':
                            echo 'Approval Kartu Pelajar';
                            break;
                        case 'reports.php':
                            echo 'Laporan';
                            break;
                        case 'pengajuan.php':
                            echo 'Pengajuan Kartu Pelajar';
                            break;
                        case 'status.php':
                            echo 'Status Pengajuan';
                            break;
                        default:
                            echo 'Sistem Pengajuan Kartu Pelajar';
                    }
                    ?>
                </h1>
                <div class="text-sm text-gray-600">
                    <i class="fas fa-calendar-alt mr-1"></i> <?php echo date('d F Y'); ?>
                </div>
            </div>
