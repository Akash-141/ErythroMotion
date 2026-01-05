<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../"; // Path to access root-level files

// Restrict access to admins only
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "login.php");
    exit();
}

require __DIR__ . '/../includes/db_connect.php'; // Database connection
$page_title = "Edit User - Admin";
$errors = [];
$user_data = []; // To store fetched user data or submitted form data

$user_id_to_edit = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$user_id_to_edit && isset($_POST['user_id'])) {
    $user_id_to_edit = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
}

if (!$user_id_to_edit) {
    header("Location: manage_users.php?message=" . urlencode("Invalid User ID specified.") . "&type=error");
    exit();
}

// PDO connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: manage_users.php?message=" . urlencode("Database connection error.") . "&type=error");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    // Fetch original user data again to ensure read-only fields are not tampered with
    // or simply don't include them in the update query if they are not meant to be changed.
    // For this version, we'll rely on not including them in the SET clause.
    $form_submission_data = $_POST;

    $full_name = trim($form_submission_data['full_name'] ?? '');
    // Email and Blood Group will be taken from fetched data, not form, for the update itself.
    $phone_number = trim($form_submission_data['phone_number'] ?? '');
    $date_of_birth_str = trim($form_submission_data['date_of_birth'] ?? '');
    $location = trim($form_submission_data['location'] ?? '');
    $gender = trim($form_submission_data['gender'] ?? '');
    $role = trim($form_submission_data['role'] ?? '');
    $status = trim($form_submission_data['status'] ?? '');
    $date_of_birth = null;

    // --- Validation for editable fields ---
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($phone_number)) $errors[] = "Phone number is required.";
    elseif (!preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $phone_number)) { //
        $errors[] = "Invalid phone number format.";
    }

    if (empty($date_of_birth_str)) {
        $errors[] = "Date of birth is required.";
    } else {
        try {
            $d = new DateTime($date_of_birth_str);
            if ($d->format('Y-m-d') !== $date_of_birth_str) throw new Exception("Invalid date format part.");
            $date_of_birth = $date_of_birth_str;
        } catch (Exception $e) {
            $errors[] = "Invalid date of birth format (YYYY-MM-DD).";
        }
    }

    if (empty($location)) $errors[] = "Location is required.";

    $valid_genders = ['male', 'female', 'other', 'prefer_not_to_say']; //
    if (empty($gender) || !in_array($gender, $valid_genders)) $errors[] = "Valid gender is required.";

    $valid_roles = ['user', 'admin']; //
    if (empty($role) || !in_array($role, $valid_roles)) $errors[] = "Invalid role selected.";

    $valid_statuses = ['active', 'suspended', 'pending_verification', 'banned']; // As per user confirmation
    if (empty($status) || !in_array($status, $valid_statuses)) $errors[] = "Invalid status selected.";

    if (empty($errors)) {
        try {
            // Email and Blood Group are NOT updated here as they are read-only for admin
            $sql = "UPDATE users SET 
                        full_name = :full_name, 
                        phone_number = :phone_number, 
                        date_of_birth = :date_of_birth,
                        location = :location,
                        gender = :gender,
                        role = :role, 
                        status = :status, 
                        updated_at = CURRENT_TIMESTAMP 
                    WHERE user_id = :user_id_to_edit";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone_number', $phone_number);
            $stmt->bindParam(':date_of_birth', $date_of_birth);
            $stmt->bindParam(':location', $location);
            $stmt->bindParam(':gender', $gender);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':user_id_to_edit', $user_id_to_edit, PDO::PARAM_INT);

            $stmt->execute();
            header("Location: manage_users.php?message=" . urlencode("User details updated successfully!") . "&type=success");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error updating user: " . $e->getMessage();
        }
    }
    // If errors, repopulate $user_data with submitted values for the form
    // but ensure read-only fields (username, email, blood_group) still show original DB values if not part of $form_submission_data or to prevent tampering.
    // For simplicity here, we'll refetch original for display if POST fails and some values are missing.
    // Or, better, always rely on $user_data fetched initially for read-only display parts.
    // $user_data for the form will be a merge of initial fetch and POST data for editable fields.
    $stmt_refetch = $pdo->prepare("SELECT * FROM users WHERE user_id = :user_id_to_edit");
    $stmt_refetch->bindParam(':user_id_to_edit', $user_id_to_edit, PDO::PARAM_INT);
    $stmt_refetch->execute();
    $original_user_data_for_display = $stmt_refetch->fetch();

    $user_data = array_merge((array)$original_user_data_for_display, $form_submission_data);
} else { // GET request or no POST submission - Fetch user data for the form
    try {
        $stmt = $pdo->prepare("SELECT user_id, full_name, username, email, phone_number, date_of_birth, location, blood_group, gender, role, status FROM users WHERE user_id = :user_id_to_edit");
        $stmt->bindParam(':user_id_to_edit', $user_id_to_edit, PDO::PARAM_INT);
        $stmt->execute();
        $user_data = $stmt->fetch();

        if (!$user_data) {
            header("Location: manage_users.php?message=" . urlencode("User not found.") . "&type=error");
            exit();
        }
    } catch (PDOException $e) {
        $errors[] = "Error fetching user details: " . $e->getMessage();
        $user_data['username'] = 'Error'; // Prevent undefined index if fetch fails partially
    }
}

$page_title = "Edit User: " . htmlspecialchars($user_data['username'] ?? 'N/A') . " - Admin";

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/variables.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/navbar.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/footer.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_layout.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_add_equipment.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height);
            /* */
        }

        .readonly-field-display {
            /* Ensure this class exists or is styled in your CSS */
            background-color: #e9ecef;
            color: #495057;
            cursor: not-allowed;
            border: 1px solid #ced4da;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; // 
    ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; // 
        ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container form-container">
                <div class="admin-header">
                    <h1 class="admin-page-title">Edit User: <?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></h1>
                    <a href="manage_users.php" class="admin-button plain-button">Back to User List</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="edit_user.php?id=<?php echo htmlspecialchars($user_id_to_edit); ?>" method="POST" class="admin-form">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user_data['user_id'] ?? $user_id_to_edit); ?>">

                    <div class="form-group">
                        <label for="username_display">Username (Read-only):</label>
                        <input type="text" id="username_display" value="<?php echo htmlspecialchars($user_data['username'] ?? ''); ?>" readonly class="readonly-field-display">
                    </div>

                    <div class="form-group">
                        <label for="email_display">Email (Read-only):</label>
                        <input type="text" id="email_display" name="email_display_field" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" readonly class="readonly-field-display">
                    </div>

                    <div class="form-group">
                        <label for="full_name">Full Name:</label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_data['phone_number'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="date_of_birth">Date of Birth:</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user_data['date_of_birth'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled>Select Gender</option>
                                <option value="male" <?php echo (isset($user_data['gender']) && $user_data['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo (isset($user_data['gender']) && $user_data['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo (isset($user_data['gender']) && $user_data['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer_not_to_say" <?php echo (isset($user_data['gender']) && $user_data['gender'] === 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="blood_group_display">Blood Group (Read-only):</label>
                            <input type="text" id="blood_group_display" name="blood_group_display_field" value="<?php echo htmlspecialchars($user_data['blood_group'] ?? ''); ?>" readonly class="readonly-field-display">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="user" <?php echo (isset($user_data['role']) && $user_data['role'] === 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo (isset($user_data['role']) && $user_data['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo (isset($user_data['status']) && $user_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo (isset($user_data['status']) && $user_data['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                                <option value="pending_verification" <?php echo (isset($user_data['status']) && $user_data['status'] === 'pending_verification') ? 'selected' : ''; ?>>Pending Verification</option>
                                <option value="banned" <?php echo (isset($user_data['status']) && $user_data['status'] === 'banned') ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_user" class="admin-button add-new-button">Update User</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // 
    ?>
</body>

</html>