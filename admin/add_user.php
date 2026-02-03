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

include __DIR__ . '/../includes/db_connect.php'; // Database connection [cite: db_connect.php]
$page_title = "Add New User - Admin";
$errors = [];
$form_data = $_POST; // To repopulate form in case of errors

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize form data
    $full_name = trim($form_data['full_name'] ?? '');
    $username = trim($form_data['username'] ?? '');
    $email = trim($form_data['email'] ?? '');
    $phone_number = trim($form_data['phone_number'] ?? '');
    $date_of_birth_str = trim($form_data['date_of_birth'] ?? '');
    $location = trim($form_data['location'] ?? '');
    $blood_group = trim($form_data['blood_group'] ?? '');
    $gender = trim($form_data['gender'] ?? '');
    $password = $form_data['password'] ?? '';
    $confirm_password = $form_data['confirm_password'] ?? '';
    $role = $form_data['role'] ?? 'user'; // Default to 'user'
    $status = $form_data['status'] ?? 'active'; // Default to 'active'
    $date_of_birth = null;

    // --- Validation (similar to signup.php) ---
    if (empty($full_name)) $errors[] = "Full name is required.";
    if (empty($username)) $errors[] = "Username is required.";
    elseif (strlen($username) < 3 || strlen($username) > 50) $errors[] = "Username must be between 3 and 50 characters.";
    elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) $errors[] = "Username can only contain letters, numbers, and underscores.";

    if (empty($email)) $errors[] = "Email is required.";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format.";
    
    if (empty($phone_number)) $errors[] = "Phone number is required.";
    elseif (!preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $phone_number)) $errors[] = "Invalid phone number format."; // [cite: profile.php]

    if (empty($date_of_birth_str)) {
        $errors[] = "Date of birth is required.";
    } else {
        try {
            $d = new DateTime($date_of_birth_str);
            if ($d->format('Y-m-d') !== $date_of_birth_str) throw new Exception("Invalid date format part.");
            // Optional: Allow admin to create users with future DOB if necessary, or add validation:
            // if ($d > new DateTime()) $errors[] = "Date of birth cannot be in the future.";
            $date_of_birth = $date_of_birth_str;
        } catch (Exception $e) {
            $errors[] = "Invalid date of birth format (YYYY-MM-DD).";
        }
    }

    if (empty($location)) $errors[] = "Location is required.";
    
    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; // [cite: signup.php]
    if (empty($blood_group) || !in_array($blood_group, $valid_blood_groups)) $errors[] = "Valid blood group is required.";
    
    $valid_genders = ['male', 'female', 'other', 'prefer_not_to_say']; // [cite: DDL.sql]
    if (empty($gender) || !in_array($gender, $valid_genders)) $errors[] = "Valid gender is required.";

    if (empty($password)) $errors[] = "Password is required.";
    elseif (strlen($password) < 6) $errors[] = "Password must be at least 6 characters long.";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match.";

    $valid_roles = ['user', 'admin']; // [cite: DDL.sql]
    if (empty($role) || !in_array($role, $valid_roles)) $errors[] = "Invalid role selected.";
    
    $valid_statuses = ['active', 'suspended', 'pending_verification', 'banned']; // Based on your DDL update
    if (empty($status) || !in_array($status, $valid_statuses)) $errors[] = "Invalid status selected.";

    // Database checks if basic validation passes
    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Check for existing username
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username"); // [cite: DDL.sql]
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = "Username already taken.";
            }

            // Check for existing email
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email"); // [cite: DDL.sql]
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = "Email already registered.";
            }

            // If still no errors, proceed to insert
            if (empty($errors)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (full_name, username, email, phone_number, date_of_birth, location, blood_group, gender, password_hash, role, status) 
                        VALUES (:full_name, :username, :email, :phone_number, :date_of_birth, :location, :blood_group, :gender, :password_hash, :role, :status)"; // [cite: DDL.sql]
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':date_of_birth', $date_of_birth);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':blood_group', $blood_group);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':password_hash', $password_hash);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':status', $status);
                
                $stmt->execute();
                header("Location: manage_users.php?message=" . urlencode("User created successfully!") . "&type=success");
                exit();
            }
        } catch (PDOException $e) {
            $errors[] = "Database operation failed: " . $e->getMessage();
        }
    }
}
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_add_equipment.css"> <!-- Reusing form styles [cite: admin_add_equipment.css] -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height); /* [cite: variables.css] */
        }
        /* Additional styles for password toggle if needed, or ensure they are in global admin CSS */
        .password-group { position: relative; }
        .toggle-password { 
            position: absolute; 
            top: 70%; /* Adjust based on your label and input height */
            right: 10px; 
            transform: translateY(-50%); 
            cursor: pointer; 
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; // [cite: navbar.php] ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; // [cite: admin_sidebar.php] ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container form-container"> <!-- Reusing form-container for max-width -->
                 <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="manage_users.php" class="admin-button plain-button">Back to User List</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="add_user.php" method="POST" class="admin-form" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Phone Number:</label>
                            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth:</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="age">Age (auto-calculated):</label>
                            <input type="text" id="age" name="age_display" readonly class="readonly-field" style="background-color: #e9ecef; cursor:not-allowed;">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($form_data['location'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" required>
                                <option value="" disabled <?php echo empty($form_data['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                <option value="male" <?php echo ($form_data['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($form_data['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($form_data['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer_not_to_say" <?php echo ($form_data['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="blood_group">Blood Group:</label>
                            <select id="blood_group" name="blood_group" required>
                                <option value="" disabled <?php echo empty($form_data['blood_group']) ? 'selected' : ''; ?>>Select Blood Group</option>
                                <?php $blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']; ?>
                                <?php foreach ($blood_groups as $bg): ?>
                                    <option value="<?php echo $bg; ?>" <?php echo ($form_data['blood_group'] ?? '') === $bg ? 'selected' : ''; ?>><?php echo $bg; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group password-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                            <span class="toggle-password" data-target="password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-open">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-closed" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </span>
                        </div>
                        <div class="form-group password-group">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                             <span class="toggle-password" data-target="confirm_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-open">
                                   <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-closed" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="user" <?php echo ($form_data['role'] ?? 'user') === 'user' ? 'selected' : ''; ?>>User</option>
                                <option value="admin" <?php echo ($form_data['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" required>
                                <option value="active" <?php echo ($form_data['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo ($form_data['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                <option value="pending_verification" <?php echo ($form_data['status'] ?? '') === 'pending_verification' ? 'selected' : ''; ?>>Pending Verification</option>
                                <option value="banned" <?php echo ($form_data['status'] ?? '') === 'banned' ? 'selected' : ''; ?>>Banned</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="admin-button add-new-button">Add User</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // [cite: footer.php] ?>
    <!-- Link to a JS file similar to signup.js for age calculation and password toggle -->
    <script src="<?php echo $root_path_prefix; ?>js/signup.js"></script> 
</body>
</html>

