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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Removed close application functionality
    {
        $alamat = $_POST['alamat'] ?? '';
        
        // Handle file upload
        $foto = '';
        if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_name = time() . '_' . basename($_FILES['foto']['name']);
            $target_file = $upload_dir . $file_name;
            
            // Check if file is an image
            $check = getimagesize($_FILES['foto']['tmp_name']);
            if ($check !== false) {
                // Check file size (max 2MB)
                if ($_FILES['foto']['size'] <= 2000000) {
                    // Check file type
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    if (in_array($_FILES['foto']['type'], $allowed_types)) {
                        if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_file)) {
                            $foto = $file_name;
                        } else {
                            $error_message = 'Gagal mengunggah file.';
                        }
                    } else {
                        $error_message = 'Hanya file JPG, JPEG, dan PNG yang diperbolehkan.';
                    }
                } else {
                    $error_message = 'Ukuran file terlalu besar. Maksimal 2MB.';
                }
            } else {
                $error_message = 'File yang diunggah bukan gambar.';
            }
        }
        
        if (empty($error_message)) {
            try {
                // Check if user already has a pending or approved application
                $stmt = $conn->prepare("
                    SELECT pengajuan_id, status, tanggal_pengajuan 
                    FROM pengajuan_kartu 
                    WHERE user_id = :user_id 
                    ORDER BY pengajuan_id DESC 
                    LIMIT 1
                ");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                
                $existingApplication = $stmt->fetch();
                
                // Check if user has submitted an application today
                $today = date('Y-m-d');
                if ($existingApplication && $existingApplication['tanggal_pengajuan'] === $today) {
                    $error_message = 'Anda tidak dapat membuat pengajuan baru di hari yang sama. Silakan coba lagi besok.';
                }
                else if ($existingApplication && in_array($existingApplication['status'], ['menunggu', 'disetujui'])) {
                    $error_message = 'Anda masih memiliki pengajuan yang sedang diproses. Silakan tunggu hingga pengajuan sebelumnya selesai.';
                } else if ($existingApplication && $existingApplication['status'] === 'ditolak') {
                    // Hanya update pengajuan jika statusnya ditolak dan bukan di hari yang sama
                    $status = 'menunggu';
                    $catatan_admin = '';
                    $tanggal_pengajuan = date('Y-m-d');
                    
                    $stmt = $conn->prepare("UPDATE pengajuan_kartu SET tanggal_pengajuan = :tanggal_pengajuan, status = :status, foto = :foto, alamat = :alamat, catatan_admin = :catatan_admin WHERE pengajuan_id = :pengajuan_id");
                    $stmt->bindParam(':pengajuan_id', $existingApplication['pengajuan_id']);
                    $stmt->bindParam(':tanggal_pengajuan', $tanggal_pengajuan);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':foto', $foto);
                    $stmt->bindParam(':alamat', $alamat);
                    $stmt->bindParam(':catatan_admin', $catatan_admin);
                    $stmt->execute();
                    
                    $success_message = 'Pengajuan kartu pelajar berhasil diperbarui. Silakan tunggu untuk diproses oleh admin.';
                    
                    // Update the existingApplication variable to show the success message immediately
                    $existingApplication['status'] = 'menunggu';
                    $existingApplication['tanggal_pengajuan'] = $tanggal_pengajuan;
                } else {
                    // Always insert a new application for first-time users or after Finish status
                    // This will allow multiple applications per user (history is maintained)
                    $tanggal_pengajuan = date('Y-m-d');
                    $status = 'menunggu';
                    $catatan_admin = ''; // Default empty value for catatan_admin
                    
                    // Modify the database structure - remove unique constraint on user_id
                    // This should be run only once by an administrator
                    try {
                        $alter_table = $conn->prepare("ALTER TABLE pengajuan_kartu DROP INDEX user_id");
                        $alter_table->execute();
                    } catch (PDOException $alterErr) {
                        // Ignore if there's an error (constraint might already be gone)
                    }
                    
                    // Insert new application
                    $stmt = $conn->prepare("INSERT INTO pengajuan_kartu (user_id, tanggal_pengajuan, status, foto, alamat, catatan_admin) VALUES (:user_id, :tanggal_pengajuan, :status, :foto, :alamat, :catatan_admin)");
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':tanggal_pengajuan', $tanggal_pengajuan);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':foto', $foto);
                    $stmt->bindParam(':alamat', $alamat);
                    $stmt->bindParam(':catatan_admin', $catatan_admin);
                    $stmt->execute();
                    
                    $success_message = 'Pengajuan kartu pelajar berhasil dibuat. Silakan tunggu untuk diproses oleh admin.';
                    
                    // Set the existingApplication variable to show the success message immediately
                    $existingApplication = [
                        'status' => 'menunggu',
                        'tanggal_pengajuan' => $tanggal_pengajuan
                    ];
                }
            } catch (PDOException $e) {
                // Better error handling with a more descriptive message
                if (strpos($e->getMessage(), 'Integrity constraint violation') !== false) {
                    $error_message = 'Terdapat batasan dalam sistem. Untuk mengirimkan pengajuan baru, kontak admin untuk mengaktifkan fitur riwayat pengajuan.';
                } else {
                    $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<!-- Pengajuan Content -->
<div class="card">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-lg font-bold text-gray-800">Form Pengajuan Kartu Pelajar</h2>
    </div>
    
    <?php /* Success message moved below */ ?>
    
    <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <?php
    // Check if user already has a pending or approved application
    $stmt = $conn->prepare("
        SELECT status, tanggal_pengajuan 
        FROM pengajuan_kartu 
        WHERE user_id = :user_id 
        ORDER BY pengajuan_id DESC 
        LIMIT 1
    ");
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    $existingApplication = $stmt->fetch();
    $today = date('Y-m-d');
    
    // If form was just submitted successfully, force the check to show the appropriate message
    if ($success_message && !$existingApplication) {
        $existingApplication = [
            'status' => 'menunggu',
            'tanggal_pengajuan' => $today
        ];
    }
    
    if ($success_message):
    ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <p class="font-medium">Pengajuan kartu pelajar berhasil dibuat.</p>
            <p class="mt-1">Silakan tunggu untuk diproses oleh admin.</p>
            <div class="mt-2">
                <a href="status.php" class="text-green-700 underline">Lihat status pengajuan</a>
            </div>
        </div>
    <?php elseif ($existingApplication && $existingApplication['tanggal_pengajuan'] === $today):
    ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <p class="font-medium">Pengajuan Tidak Tersedia</p>
            <p class="mt-1">Anda sudah melakukan pengajuan hari ini. Silakan coba lagi besok.</p>
            <div class="mt-2">
                <a href="status.php" class="text-yellow-700 underline">Lihat status pengajuan</a>
            </div>
        </div>
    <?php elseif ($existingApplication && in_array($existingApplication['status'], ['menunggu', 'disetujui'])):
    ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            Anda masih memiliki pengajuan yang sedang diproses. Silakan tunggu hingga pengajuan sebelumnya selesai.
            <div class="mt-2">
                <a href="status.php" class="text-yellow-700 underline">Lihat status pengajuan</a>
            </div>
        </div>
    <?php else: ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <?php if ($existingApplication && $existingApplication['status'] === 'ditolak'): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-4">
                <p>Pengajuan sebelumnya ditolak. Form ini akan memperbarui pengajuan yang ditolak tersebut.</p>
            </div>
            <?php endif; ?>
            
            <div class="mb-4">
                <label for="alamat" class="form-label">Alamat Lengkap</label>
                <textarea id="alamat" name="alamat" rows="3" class="form-input" required></textarea>
                <p class="text-sm text-gray-500 mt-1">Masukkan alamat lengkap Anda untuk pengiriman kartu pelajar.</p>
            </div>
            
            <div class="mb-6">
                <label for="foto" class="form-label">Foto (Ukuran 3x4)</label>
                <div class="flex items-center space-x-4">
                    <div class="w-32 h-40 border-2 border-dashed border-gray-300 rounded-md flex items-center justify-center bg-gray-50" id="preview-container">
                        <div class="text-center p-2" id="upload-prompt">
                            <i class="fas fa-camera text-gray-400 text-2xl mb-2"></i>
                            <p class="text-xs text-gray-500">Klik untuk memilih foto</p>
                        </div>
                        <img id="preview-image" class="hidden w-full h-full object-cover rounded-md" alt="Preview">
                    </div>
                    <div class="flex-1">
                        <input type="file" id="foto" name="foto" class="hidden" accept="image/jpeg, image/png, image/jpg" required>
                        <button type="button" id="upload-btn" class="btn btn-secondary mb-2">
                            <i class="fas fa-upload mr-2"></i> Pilih Foto
                        </button>
                        <p class="text-sm text-gray-500">Format: JPG, JPEG, atau PNG. Maksimal 2MB.</p>
                        <p class="text-sm text-gray-500">Pastikan foto memiliki latar belakang berwarna dan wajah terlihat jelas.</p>
                    </div>
                </div>
            </div>
            
            <div class="border-t pt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane mr-2"></i> Kirim Pengajuan
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script>
    // Image preview
    const uploadBtn = document.getElementById('upload-btn');
    const fileInput = document.getElementById('foto');
    const previewImage = document.getElementById('preview-image');
    const uploadPrompt = document.getElementById('upload-prompt');
    const previewContainer = document.getElementById('preview-container');
    
    if (uploadBtn && fileInput && previewImage && uploadPrompt && previewContainer) {
        uploadBtn.addEventListener('click', () => {
            fileInput.click();
        });
        
        previewContainer.addEventListener('click', () => {
            fileInput.click();
        });
        
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = (e) => {
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('hidden');
                    uploadPrompt.classList.add('hidden');
                };
                
                reader.readAsDataURL(file);
            }
        });
    }
</script>

<?php include '../includes/footer.php'; ?>
