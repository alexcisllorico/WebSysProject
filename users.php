<?php
require_once 'includes/auth_check.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

require_once 'includes/db_connect.php';

// Initialize variables
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add or Update user
    if (isset($_POST['save_user'])) {
        $username = trim($_POST['username']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $password = $_POST['password']; // Only used for new users or password changes
        $password_confirm = $_POST['password_confirm'];
        
        // Basic validation
        if (empty($username)) {
            $error = "Username is required";
        } elseif ($action === 'add' && empty($password)) {
            $error = "Password is required for new users";
        } elseif (!empty($password) && $password !== $password_confirm) {
            $error = "Passwords do not match";
        } else {
            // Check if updating or adding
            if ($id > 0) {
                // Update existing user
                if (!empty($password)) {
                    // Update with new password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, password = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("sssssi", $username, $full_name, $email, $role, $hashed_password, $id);
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param("ssssi", $username, $full_name, $email, $role, $id);
                }
                
                if ($stmt->execute()) {
                    $success = "User updated successfully";
                    // Redirect after short delay
                    header("Refresh: 1; URL=users.php");
                } else {
                    $error = "Error updating user: " . $conn->error;
                }
            } else {
                // Add new user
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, role, created_at, updated_at) 
                                      VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
                $stmt->bind_param("sssss", $username, $hashed_password, $full_name, $email, $role);
                
                if ($stmt->execute()) {
                    $success = "User added successfully";
                    // Redirect after short delay
                    header("Refresh: 1; URL=users.php");
                } else {
                    $error = "Error adding user: " . $conn->error;
                }
            }
        }
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $delete_id = intval($_POST['delete_id']);
        
        // Prevent deleting your own account
        if ($delete_id === $_SESSION['user_id']) {
            $error = "You cannot delete your own account";
        } else {
            // First check if user exists
            $check = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $check->bind_param("i", $delete_id);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                
                // Delete related transactions first
                $trans_stmt = $conn->prepare("DELETE FROM transactions WHERE user_id = ?");
                $trans_stmt->bind_param("i", $delete_id);
                $trans_stmt->execute();

                // Delete user
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                $stmt->bind_param("i", $delete_id);
                
                if ($stmt->execute()) {
                    $success = "User deleted successfully";
                    // Redirect after short delay
                    header("Refresh: 1; URL=users.php");
                } else {
                    $error = "Error deleting user: " . $conn->error;
                }
            } else {
                $error = "User not found";
            }
        }
    }
}

// Get user data for edit
$user = null;
if ($id > 0 && $action === 'edit') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        $error = "User not found";
        $action = 'list'; // Fallback to list
    }
}

// Get all users for listing
$users = array();
if ($action === 'list') {
    $sql = "SELECT * FROM users ORDER BY username ASC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Mini-Mart Inventory</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            
            <div class="content">
                <div class="content-header">
                    <h1>
                        <?php if ($action === 'add'): ?>
                            Add New User
                        <?php elseif ($action === 'edit'): ?>
                            Edit User
                        <?php else: ?>
                            User Management
                        <?php endif; ?>
                    </h1>
                    
                    <?php if ($action === 'list'): ?>
                    <div class="header-actions">
                        <a href="users.php?action=add" class="btn btn-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-plus"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" x2="19" y1="8" y2="14"/><line x1="22" x2="16" y1="11" y2="11"/></svg>
                            Add User
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($action === 'list'): ?>
                    <!-- Users List View -->
                    <div class="card">
                        <div class="card-header">
                            <input type="text" id="user-search" placeholder="Search users..." class="search-input">
                        </div>
                        <div class="table-responsive">
                            <table id="users-table">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Last Login</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $userItem): ?>
                                            <tr>
                                                <td>
                                                    <?php echo htmlspecialchars($userItem['username']); ?>
                                                    <?php if ($userItem['id'] === $_SESSION['user_id']): ?>
                                                        <span class="badge badge-success" title="Currently logged in">Current</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($userItem['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($userItem['email']); ?></td>
                                                <td>
                                                    <span class="badge <?php 
                                                        if ($userItem['role'] === 'admin') echo 'badge-danger';
                                                        elseif ($userItem['role'] === 'manager') echo 'badge-warning';
                                                        else echo 'badge-info';
                                                    ?>">
                                                        <?php echo ucfirst($userItem['role']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $userItem['last_login'] ? date('M d, Y g:i A', strtotime($userItem['last_login'])) : 'Never'; ?></td>
                                                <td class="actions">
                                                    <a href="users.php?action=edit&id=<?php echo $userItem['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-edit"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    </a>
                                                    <?php if ($userItem['id'] !== $_SESSION['user_id']): // Prevent deleting own account ?>
                                                        <button class="btn btn-sm btn-danger delete-btn" data-id="<?php echo $userItem['id']; ?>" data-name="<?php echo htmlspecialchars($userItem['username']); ?>" title="Delete">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No users found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Delete Confirmation Modal -->
                    <div id="delete-modal" class="modal">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2>Confirm Deletion</h2>
                                <span class="close">&times;</span>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete the user <strong><span id="delete-user-name"></span></strong>?</p>
                                <p class="text-danger">This action cannot be undone!</p>
                            </div>
                            <div class="modal-footer">
                                <form method="POST" action="">
                                    <input type="hidden" name="delete_id" id="delete-id">
                                    <button type="button" class="btn btn-secondary close-btn">Cancel</button>
                                    <button type="submit" name="delete_user" class="btn btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif ($action === 'add' || $action === 'edit'): ?>
                    <!-- Add/Edit User Form -->
                    <div class="card">
                        <form method="POST" action="" class="form">
                            <?php if ($action === 'edit'): ?>
                                <input type="hidden" name="id" value="<?php echo $id; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="username">Username <span class="text-danger">*</span></label>
                                    <input type="text" id="username" name="username" required 
                                           value="<?php echo ($action === 'edit' && $user) ? htmlspecialchars($user['username']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="role">Role <span class="text-danger">*</span></label>
                                    <select id="role" name="role" required class="form-select">
                                        <option value="admin" <?php echo ($action === 'edit' && $user && $user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                        <option value="manager" <?php echo ($action === 'edit' && $user && $user['role'] === 'manager') ? 'selected' : ''; ?>>Manager</option>
                                        <option value="staff" <?php echo ($action === 'edit' && $user && $user['role'] === 'staff') ? 'selected' : ''; ?>>Staff</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="full_name">Full Name</label>
                                    <input type="text" id="full_name" name="full_name"
                                           value="<?php echo ($action === 'edit' && $user) ? htmlspecialchars($user['full_name']) : ''; ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input type="email" id="email" name="email"
                                           value="<?php echo ($action === 'edit' && $user) ? htmlspecialchars($user['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="password">
                                        <?php echo ($action === 'edit') ? 'New Password (leave blank to keep current)' : 'Password <span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" id="password" name="password" 
                                           <?php echo ($action === 'add') ? 'required' : ''; ?>>
                                </div>
                                
                                <div class="form-group">
                                    <label for="password_confirm">
                                        <?php echo ($action === 'edit') ? 'Confirm New Password' : 'Confirm Password <span class="text-danger">*</span>'; ?>
                                    </label>
                                    <input type="password" id="password_confirm" name="password_confirm"
                                           <?php echo ($action === 'add') ? 'required' : ''; ?>>
                                </div>
                            </div>
                            
                            <div class="form-actions">
                                <a href="users.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="save_user" class="btn btn-primary">
                                    <?php echo ($action === 'edit') ? 'Update User' : 'Add User'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Delete confirmation modal
            const deleteModal = document.getElementById('delete-modal');
            const deleteButtons = document.querySelectorAll('.delete-btn');
            const closeButtons = document.querySelectorAll('.close, .close-btn');
            const deleteIdInput = document.getElementById('delete-id');
            const deleteUserName = document.getElementById('delete-user-name');
            
            // Open modal on delete button click
            deleteButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const userId = button.getAttribute('data-id');
                    const userName = button.getAttribute('data-name');
                    
                    if (deleteIdInput) deleteIdInput.value = userId;
                    if (deleteUserName) deleteUserName.textContent = userName;
                    
                    deleteModal.classList.add('show');
                });
            });
            
            // Close modal on close button click
            closeButtons.forEach(button => {
                button.addEventListener('click', () => {
                    deleteModal.classList.remove('show');
                });
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    deleteModal.classList.remove('show');
                }
            });
            
            // User search
            const userSearch = document.getElementById('user-search');
            const usersTable = document.getElementById('users-table');
            
            if (userSearch && usersTable) {
                userSearch.addEventListener('input', () => {
                    const searchTerm = userSearch.value.toLowerCase();
                    const rows = usersTable.querySelectorAll('tbody tr');
                    
                    rows.forEach(row => {
                        const username = row.cells[0].textContent.toLowerCase();
                        const fullName = row.cells[1].textContent.toLowerCase();
                        const email = row.cells[2].textContent.toLowerCase();
                        
                        if (username.includes(searchTerm) || fullName.includes(searchTerm) || email.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
            
            // Password validation
            const passwordForm = document.querySelector('form');
            const password = document.getElementById('password');
            const passwordConfirm = document.getElementById('password_confirm');
            
            if (passwordForm && password && passwordConfirm) {
                passwordForm.addEventListener('submit', (e) => {
                    if (password.value && password.value !== passwordConfirm.value) {
                        e.preventDefault();
                        alert('Passwords do not match!');
                    }
                });
            }
        });
    </script>
</body>
</html>