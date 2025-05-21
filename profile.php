<?php
require_once 'includes/auth_check.php';
require_once 'includes/db_connect.php';

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$user = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $civil_status = trim($_POST['civil_status']);
    $sex = trim($_POST['sex']);
    $birthday = trim($_POST['birthday']);

    $update_stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, address = ?, civil_status = ?, sex = ?, birthday = ? WHERE id = ?");
    $update_stmt->bind_param("sssssssi", $fullname, $email, $phone, $address, $civil_status, $sex, $birthday, $user_id);

    if ($update_stmt->execute()) {
        $success = "Profile updated successfully.";
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

$stmt = $conn->prepare("SELECT username, fullname, role, email, phone, address, civil_status, sex, birthday FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
} else {
    $error = "User not found.";
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>User Profile - Mini-Mart Inventory</title>
    <link rel="stylesheet" href="assets/css/styles.css" />
    <link rel="stylesheet" href="assets/css/users.css" />
    <style>
        .profile-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 1rem 2rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.4);
            color: #f7fafc;
        }
        .profile-form input[disabled],
        .profile-form select[disabled],
        .profile-form textarea[disabled] {
            background-color: rgba(255, 255, 255, 0.15);
            color: #ccc;
            cursor: not-allowed;
        }
        .btn-edit {
            background-color: #4a90e2;
            color: white;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .btn-edit:hover {
            background-color: #357ABD;
        }
        .btn-save {
            display: none;
        }
    </style>
</head>
<body>
    <div class="app-container">
        <?php include 'includes/sidebar.php'; ?>
        <main class="main-content">
            <?php include 'includes/header.php'; ?>
            <div class="content">
                <h1>User Profile</h1>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($user): ?>
                    <div class="profile-container">
                        <button id="editProfileBtn" class="btn btn-edit" style="float: right; margin-bottom: 1rem;">Edit Profile</button>
                        <form method="POST" action="profile.php" class="form profile-form">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="fullname">Full Name</label>
                                <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['fullname']); ?>" disabled required>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" value="<?php echo htmlspecialchars($user['role']); ?>" disabled>
                            </div>
                            <?php if (!empty($user['email'])): ?>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled required>
                            </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea id="address" name="address" rows="3" disabled><?php echo htmlspecialchars($user['address']); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="civil_status">Civil Status</label>
                                <select id="civil_status" name="civil_status" disabled>
                                    <option value="">Select Civil Status</option>
                                    <option value="Single" <?php if ($user['civil_status'] === 'Single') echo 'selected'; ?>>Single</option>
                                    <option value="Married" <?php if ($user['civil_status'] === 'Married') echo 'selected'; ?>>Married</option>
                                    <option value="Widowed" <?php if ($user['civil_status'] === 'Widowed') echo 'selected'; ?>>Widowed</option>
                                    <option value="Separated" <?php if ($user['civil_status'] === 'Separated') echo 'selected'; ?>>Separated</option>
                                    <option value="Annulled" <?php if ($user['civil_status'] === 'Annulled') echo 'selected'; ?>>Annulled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="sex">Sex</label>
                                <select id="sex" name="sex" disabled>
                                    <option value="">Select Sex</option>
                                    <option value="Male" <?php if ($user['sex'] === 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if ($user['sex'] === 'Female') echo 'selected'; ?>>Female</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="birthday">Birthday</label>
                                <input type="date" id="birthday" name="birthday" value="<?php echo htmlspecialchars($user['birthday']); ?>" disabled>
                            </div>
                            <div class="form-actions" style="text-align: right; margin-top: 1rem;">
                                <button type="submit" name="update_profile" class="btn btn-primary btn-save" style="display:none;">Save Changes</button>
                            </div>
                        </form>
                    </div>
                    <script>
                        const editBtn = document.getElementById('editProfileBtn');
                        const form = document.querySelector('.profile-form');
                        const inputs = form.querySelectorAll('input, select, textarea');
                        const saveBtn = form.querySelector('.btn-save');

                        editBtn.addEventListener('click', () => {
                            inputs.forEach(input => {
                                if (!input.hasAttribute('disabled')) return;
                                input.removeAttribute('disabled');
                            });
                            saveBtn.style.display = 'inline-block';
                            editBtn.style.display = 'none';
                        });
                    </script>
                <?php else: ?>
                    <p>No user data available.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
