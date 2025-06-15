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

// Handle form submission for adding new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'siswa';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $nis = $_POST['nis'] ?? '';
    $kelas = $_POST['kelas'] ?? '';
    
    // Set default values for admin
    if ($role === 'admin') {
        $nis = '-';
        $kelas = '-';
    }
    
    $result = registerUser($conn, $username, $password, $role, $nama_lengkap, $nis, $kelas);
    
    if ($result['success']) {
        $success_message = $result['message'];
    } else {
        $error_message = $result['message'];
    }
}

// Handle user deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $user_id = $_POST['user_id'] ?? 0;
    
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $success_message = 'User berhasil dihapus';
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $user_id = $_POST['user_id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $role = $_POST['role'] ?? 'siswa';
    $nama_lengkap = $_POST['nama_lengkap'] ?? '';
    $nis = $_POST['nis'] ?? '';
    $kelas = $_POST['kelas'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Set default values for admin
    if ($role === 'admin') {
        $nis = '-';
        $kelas = '-';
    }
    
    try {
        if (!empty($password)) {
            // Update with new password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = :username, password = :password, role = :role, nama_lengkap = :nama_lengkap, nis = :nis, kelas = :kelas WHERE user_id = :user_id");
            $stmt->bindParam(':password', $hashed_password);
        } else {
            // Update without changing password
            $stmt = $conn->prepare("UPDATE users SET username = :username, role = :role, nama_lengkap = :nama_lengkap, nis = :nis, kelas = :kelas WHERE user_id = :user_id");
        }
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':nama_lengkap', $nama_lengkap);
        $stmt->bindParam(':nis', $nis);
        $stmt->bindParam(':kelas', $kelas);
        $stmt->execute();
        
        $success_message = 'User berhasil diperbarui';
    } catch (PDOException $e) {
        $error_message = 'Terjadi kesalahan: ' . $e->getMessage();
    }
}

// Get all users with pagination
try {
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 10;
    $offset = ($page - 1) * $limit;
    
    // Count total users for pagination
    if (!empty($search)) {
        $countStmt = $conn->prepare("
            SELECT COUNT(*) FROM users 
            WHERE username LIKE :search 
            OR nama_lengkap LIKE :search 
            OR nis LIKE :search 
            OR kelas LIKE :search
        ");
        $searchTerm = "%{$search}%";
        $countStmt->bindParam(':search', $searchTerm);
        $countStmt->execute();
    } else {
        $countStmt = $conn->query("SELECT COUNT(*) FROM users");
    }
    $totalUsers = $countStmt->fetchColumn();
    $totalPages = ceil($totalUsers / $limit);
    
    // Get users for current page
    if (!empty($search)) {
        $stmt = $conn->prepare("
            SELECT * FROM users 
            WHERE username LIKE :search 
            OR nama_lengkap LIKE :search 
            OR nis LIKE :search 
            OR kelas LIKE :search 
            ORDER BY user_id DESC
            LIMIT :limit OFFSET :offset
        ");
        $searchTerm = "%{$search}%";
        $stmt->bindParam(':search', $searchTerm);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT * FROM users ORDER BY user_id DESC LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    }
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = 'Database error: ' . $e->getMessage();
    $users = [];
    $totalPages = 0;
}

include '../includes/header.php';
?>

<style>
    .search-container {
        position: relative;
        transition: all 0.3s ease;
        max-width: 600px;
        margin: 0 auto;
    }
    
    .search-input {
        width: 300px;
        padding: 8px 40px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 0.9rem;
        transition: all 0.3s ease;
        background-color: #f9fafb;
        color: #374151;
    }
    
    .search-input::placeholder {
        color: #9ca3af;
        font-size: 0.9rem;
    }
    
    .search-input:focus {
        width: 320px;
        background-color: #fff;
        border-color: #e5e7eb;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        outline: none;
    }
    
    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        transition: all 0.3s ease;
        font-size: 0.9rem;
        pointer-events: none;
    }
    
    .search-input:focus + .search-icon {
        color: #3b82f6;
    }
    
    .clear-search {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        cursor: pointer;
        opacity: 0;
        transition: all 0.2s ease;
        font-size: 0.8rem;
        width: 16px;
        height: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background-color: #f3f4f6;
    }
    
    .clear-search:hover {
        color: #4b5563;
        background-color: #e5e7eb;
    }
    
    .clear-search.visible {
        opacity: 1;
    }
    
    .search-button {
        background-color: #3b82f6;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .search-button:hover {
        background-color: #2563eb;
        transform: translateY(-1px);
    }
    
    .search-button:active {
        transform: translateY(0);
    }
    
    .search-button i {
        font-size: 0.8rem;
    }

    .search-result-text {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.5rem;
        opacity: 0;
        animation: fadeIn 0.3s ease forwards;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @media (max-width: 640px) {
        .search-input, .search-input:focus {
            width: 100%;
        }
        .search-container {
            width: 100%;
        }
    }
</style>

<!-- Users Management Content -->
<div class="flex flex-col sm:flex-row justify-between items-center gap-4 mb-6">
    <button id="addUserBtn" class="btn btn-primary w-full sm:w-auto">
        <i class="fas fa-plus mr-2"></i> Tambah Pengguna Baru
    </button>
    
    <div class="relative w-full sm:w-auto">
        <form method="GET" action="" class="flex flex-col sm:flex-row items-center gap-2">
            <div class="search-container w-full sm:w-auto">
                <input 
                    type="text" 
                    name="search" 
                    id="searchInput"
                    value="<?php echo htmlspecialchars($search); ?>"
                    placeholder="Cari username, nama, NIS, atau kelas..." 
                    class="search-input w-full sm:w-auto"
                    autocomplete="off"
                >
                <i class="fas fa-search search-icon"></i>
                <?php if (!empty($search)): ?>
                    <i class="fas fa-times clear-search visible" id="clearSearch"></i>
                <?php else: ?>
                    <i class="fas fa-times clear-search" id="clearSearch"></i>
                <?php endif; ?>
            </div>
            <button type="submit" class="search-button w-full sm:w-auto">
                <i class="fas fa-search"></i>
                <span>Cari</span>
            </button>
        </form>
        <?php if (!empty($search)): ?>
            <div class="search-result-text">
                Menampilkan hasil pencarian untuk: "<?php echo htmlspecialchars($search); ?>"
            </div>
        <?php endif; ?>
    </div>
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

<!-- Users Table -->
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Username</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nama Lengkap</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIS</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kelas</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['user_id']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $user['username']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['nama_lengkap']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['nis']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $user['kelas']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $user['role'] === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="text-blue-600 hover:text-blue-900 mr-3 edit-user-btn" data-user='<?php echo json_encode($user); ?>'>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <form method="POST" action="" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">Tidak ada data pengguna.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Tambah Pengguna Baru</h3>
            <button id="closeAddModal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="add">
            
            <div class="mb-4">
                <label for="username" class="form-label">Username</label>
                <input type="text" id="username" name="username" class="form-input" required>
            </div>
            
            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-input" required>
            </div>
            
            <div class="mb-4">
                <label for="role" class="form-label">Role</label>
                <select id="role" name="role" class="form-input" onchange="toggleStudentFields()">
                    <option value="siswa">Siswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="mb-4 student-field">
                <label for="nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-input" required>
            </div>
            
            <div class="mb-4 student-field">
                <label for="nis" class="form-label">NIS</label>
                <input type="text" id="nis" name="nis" class="form-input student-required">
            </div>
            
            <div class="mb-4 student-field">
                <label for="kelas" class="form-label">Kelas</label>
                <input type="text" id="kelas" name="kelas" class="form-input student-required">
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="cancelAddBtn" class="btn btn-secondary mr-2">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold text-gray-900">Edit Pengguna</h3>
            <button id="closeEditModal" class="text-gray-400 hover:text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="update">
            <input type="hidden" id="edit_user_id" name="user_id" value="">
            
            <div class="mb-4">
                <label for="edit_username" class="form-label">Username</label>
                <input type="text" id="edit_username" name="username" class="form-input" required>
            </div>
            
            <div class="mb-4">
                <label for="edit_password" class="form-label">Password (Kosongkan jika tidak ingin mengubah)</label>
                <input type="password" id="edit_password" name="password" class="form-input">
            </div>
            
            <div class="mb-4">
                <label for="edit_role" class="form-label">Role</label>
                <select id="edit_role" name="role" class="form-input" onchange="toggleEditStudentFields()">
                    <option value="siswa">Siswa</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="mb-4 edit-student-field">
                <label for="edit_nama_lengkap" class="form-label">Nama Lengkap</label>
                <input type="text" id="edit_nama_lengkap" name="nama_lengkap" class="form-input" required>
            </div>
            
            <div class="mb-4 edit-student-field">
                <label for="edit_nis" class="form-label">NIS</label>
                <input type="text" id="edit_nis" name="nis" class="form-input edit-student-required">
            </div>
            
            <div class="mb-4 edit-student-field">
                <label for="edit_kelas" class="form-label">Kelas</label>
                <input type="text" id="edit_kelas" name="kelas" class="form-input edit-student-required">
            </div>
            
            <div class="flex justify-end">
                <button type="button" id="cancelEditBtn" class="btn btn-secondary mr-2">Batal</button>
                <button type="submit" class="btn btn-primary">Perbarui</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Add User Modal
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserModal = document.getElementById('addUserModal');
    const closeAddModal = document.getElementById('closeAddModal');
    const cancelAddBtn = document.getElementById('cancelAddBtn');
    
    addUserBtn.addEventListener('click', () => {
        addUserModal.classList.remove('hidden');
        // Reset fields and set default visibility
        toggleStudentFields();
    });
    
    closeAddModal.addEventListener('click', () => {
        addUserModal.classList.add('hidden');
    });
    
    cancelAddBtn.addEventListener('click', () => {
        addUserModal.classList.add('hidden');
    });
    
    // Edit User Modal
    const editUserBtns = document.querySelectorAll('.edit-user-btn');
    const editUserModal = document.getElementById('editUserModal');
    const closeEditModal = document.getElementById('closeEditModal');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    
    editUserBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const userData = JSON.parse(btn.getAttribute('data-user'));
            
            document.getElementById('edit_user_id').value = userData.user_id;
            document.getElementById('edit_username').value = userData.username;
            document.getElementById('edit_role').value = userData.role;
            document.getElementById('edit_nama_lengkap').value = userData.nama_lengkap;
            document.getElementById('edit_nis').value = userData.nis;
            document.getElementById('edit_kelas').value = userData.kelas;
            
            editUserModal.classList.remove('hidden');
            // Set field visibility based on role
            toggleEditStudentFields();
        });
    });
    
    closeEditModal.addEventListener('click', () => {
        editUserModal.classList.add('hidden');
    });
    
    cancelEditBtn.addEventListener('click', () => {
        editUserModal.classList.add('hidden');
    });
    
    // Toggle student fields visibility based on role selection
    function toggleStudentFields() {
        const role = document.getElementById('role').value;
        const studentFields = document.querySelectorAll('.student-field');
        const studentRequired = document.querySelectorAll('.student-required');
        
        if (role === 'admin') {
            // Hide student fields and remove required attribute for admin
            studentFields.forEach(field => {
                field.style.display = 'none';
            });
            studentRequired.forEach(field => {
                field.removeAttribute('required');
                field.value = '-'; // Set default value for admin
            });
        } else {
            // Show student fields and add required attribute for students
            studentFields.forEach(field => {
                field.style.display = 'block';
            });
            studentRequired.forEach(field => {
                field.setAttribute('required', '');
                field.value = ''; // Clear default value
            });
        }
    }
    
    // Toggle edit student fields visibility based on role selection
    function toggleEditStudentFields() {
        const role = document.getElementById('edit_role').value;
        const studentFields = document.querySelectorAll('.edit-student-field');
        const studentRequired = document.querySelectorAll('.edit-student-required');
        
        if (role === 'admin') {
            // Hide student fields and remove required attribute for admin
            studentFields.forEach(field => {
                field.style.display = 'none';
            });
            studentRequired.forEach(field => {
                field.removeAttribute('required');
                if (!field.value) {
                    field.value = '-'; // Set default value for admin if empty
                }
            });
        } else {
            // Show student fields and add required attribute for students
            studentFields.forEach(field => {
                field.style.display = 'block';
            });
            studentRequired.forEach(field => {
                field.setAttribute('required', '');
                if (field.value === '-') {
                    field.value = ''; // Clear default value
                }
            });
        }
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
        if (e.target === addUserModal) {
            addUserModal.classList.add('hidden');
        }
        if (e.target === editUserModal) {
            editUserModal.classList.add('hidden');
        }
    });
    
    // Initialize fields visibility on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Setup for search functionality
        const searchInput = document.getElementById('searchInput');
        const clearSearch = document.getElementById('clearSearch');
        
        // Show/hide clear button based on input
        searchInput.addEventListener('input', function() {
            if (this.value.length > 0) {
                clearSearch.classList.add('visible');
            } else {
                clearSearch.classList.remove('visible');
            }
        });
        
        // Clear search input
        clearSearch.addEventListener('click', function() {
            searchInput.value = '';
            clearSearch.classList.remove('visible');
            window.location.href = window.location.pathname;
        });
        
        // Add focus animation
        searchInput.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.01)';
        });
        
        searchInput.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
        
        // Initialize form fields visibility
        toggleStudentFields();
    });
</script>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div class="mt-6 flex justify-center">
    <nav class="inline-flex rounded-md shadow">
        <?php if ($page > 1): ?>
            <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
            </a>
        <?php else: ?>
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-500 cursor-not-allowed">
                <i class="fas fa-chevron-left mr-1"></i> Sebelumnya
            </span>
        <?php endif; ?>

        <div class="hidden md:flex">
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            
            if ($startPage > 1) {
                echo '<a href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                if ($startPage > 2) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
            }
            
            for ($i = $startPage; $i <= $endPage; $i++) {
                if ($i == $page) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600">'.$i.'</span>';
                } else {
                    echo '<a href="?page='.$i.(!empty($search) ? '&search='.urlencode($search) : '').'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$i.'</a>';
                }
            }
            
            if ($endPage < $totalPages) {
                if ($endPage < $totalPages - 1) {
                    echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                }
                echo '<a href="?page='.$totalPages.(!empty($search) ? '&search='.urlencode($search) : '').'" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">'.$totalPages.'</a>';
            }
            ?>
        </div>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
            </a>
        <?php else: ?>
            <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-500 cursor-not-allowed">
                Selanjutnya <i class="fas fa-chevron-right ml-1"></i>
            </span>
        <?php endif; ?>
    </nav>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
