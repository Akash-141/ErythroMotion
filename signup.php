<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

include __DIR__ . '/includes/db_connect.php';

$page_title = "Sign Up - ErythroMotion";
$errors = [];
$success_message = "";
$form_data = $_POST;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($form_data['full_name'] ?? '');
    $username = trim($form_data['username'] ?? '');
    $phone_number = trim($form_data['phone_number'] ?? '');
    $email = trim($form_data['email'] ?? '');
    $date_of_birth_str = trim($form_data['date_of_birth'] ?? '');
    $location = trim($form_data['location'] ?? '');
    $blood_group = trim($form_data['blood_group'] ?? '');
    $gender = trim($form_data['gender'] ?? '');
    $password = $form_data['password'] ?? '';
    $confirm_password = $form_data['confirm_password'] ?? '';

    $date_of_birth = null;

    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    if (empty($phone_number)) {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $phone_number)) {
        $errors[] = "Invalid phone number format.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($date_of_birth_str)) {
        $errors[] = "Date of birth is required.";
    } else {
        try {
            $d = new DateTime($date_of_birth_str);
            if ($d->format('Y-m-d') !== $date_of_birth_str) {
                throw new Exception("Invalid date format part.");
            }
            if ($d > new DateTime()) {
                $errors[] = "Date of birth cannot be in the future.";
            } else {
                $date_of_birth = $date_of_birth_str;
            }
        } catch (Exception $e) {
            $errors[] = "Invalid date of birth format. Please use YYYY-MM-DD.";
        }
    }

    if (empty($location)) {
        $errors[] = "Location is required.";
    }

    $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
    if (empty($blood_group)) {
        $errors[] = "Blood group is required.";
    } elseif (!in_array($blood_group, $valid_blood_groups)) {
        $errors[] = "Invalid blood group selected.";
    }

    $valid_genders = ['male', 'female', 'other', 'prefer_not_to_say'];
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    } elseif (!in_array($gender, $valid_genders)) {
        $errors[] = "Invalid gender selected.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = "Username already taken.";
            }

            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            if ($stmt->fetch()) {
                $errors[] = "Email already registered.";
            }

            if (empty($errors)) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (full_name, username, phone_number, email, date_of_birth, location, blood_group, gender, password_hash)
                        VALUES (:full_name, :username, :phone_number, :email, :date_of_birth, :location, :blood_group, :gender, :password_hash)";
                $stmt = $pdo->prepare($sql);

                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':phone_number', $phone_number);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':date_of_birth', $date_of_birth);
                $stmt->bindParam(':location', $location);
                $stmt->bindParam(':blood_group', $blood_group);
                $stmt->bindParam(':gender', $gender);
                $stmt->bindParam(':password_hash', $password_hash);

                $stmt->execute();

                $success_message = "Registration successful! You can now log in.";
                $form_data = [];
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex-grow: 1;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main class="signup-main-content">
        <div class="signup-container">
            <h1 class="signup-title">Create Your Account</h1>

            <?php if (!empty($errors)): ?>
                <div class="message error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="message success-message">
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                    <p><a href="<?php echo $root_path_prefix; ?>login.php">Go to Login Page</a></p>
                </div>
            <?php endif; ?>

            <?php if (empty($success_message)): ?>
                <form action="<?php echo $root_path_prefix; ?>signup.php" method="POST" class="signup-form" novalidate>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name:</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($form_data['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($form_data['username'] ?? ''); ?>" required minlength="3" maxlength="50" pattern="^[a-zA-Z0-9_]+$">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Phone Number:</label>
                            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($form_data['phone_number'] ?? ''); ?>" placeholder="+8801XX-XXXXXX" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_of_birth">Date of Birth:</label>
                            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($form_data['date_of_birth'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="age">Age:</label>
                            <input type="text" id="age" name="age" readonly class="readonly-field">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="gender">Gender:</label>
                            <select id="gender" name="gender" required>
                                <option value="" <?php echo !isset($form_data['gender']) || $form_data['gender'] === '' ? 'selected disabled' : ''; ?>>Select Gender</option>
                                <option value="male" <?php echo ($form_data['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($form_data['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($form_data['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                                <option value="prefer_not_to_say" <?php echo ($form_data['gender'] ?? '') === 'prefer_not_to_say' ? 'selected' : ''; ?>>Prefer not to say</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="blood_group">Blood Group:</label>
                            <select id="blood_group" name="blood_group" required>
                                <option value="" <?php echo !isset($form_data['blood_group']) || $form_data['blood_group'] === '' ? 'selected disabled' : ''; ?>>Select Blood Group</option>
                                <option value="A+" <?php echo ($form_data['blood_group'] ?? '') === 'A+' ? 'selected' : ''; ?>>A+</option>
                                <option value="A-" <?php echo ($form_data['blood_group'] ?? '') === 'A-' ? 'selected' : ''; ?>>A-</option>
                                <option value="B+" <?php echo ($form_data['blood_group'] ?? '') === 'B+' ? 'selected' : ''; ?>>B+</option>
                                <option value="B-" <?php echo ($form_data['blood_group'] ?? '') === 'B-' ? 'selected' : ''; ?>>B-</option>
                                <option value="AB+" <?php echo ($form_data['blood_group'] ?? '') === 'AB+' ? 'selected' : ''; ?>>AB+</option>
                                <option value="AB-" <?php echo ($form_data['blood_group'] ?? '') === 'AB-' ? 'selected' : ''; ?>>AB-</option>
                                <option value="O+" <?php echo ($form_data['blood_group'] ?? '') === 'O+' ? 'selected' : ''; ?>>O+</option>
                                <option value="O-" <?php echo ($form_data['blood_group'] ?? '') === 'O-' ? 'selected' : ''; ?>>O-</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group form-group-full-width">
                        <label for="location">Location:</label>
                        <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($form_data['location'] ?? ''); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group password-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required minlength="6">
                            <span class="toggle-password" data-target="password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-open">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-closed" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </span>
                        </div>
                        <div class="form-group password-group">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                            <span class="toggle-password" data-target="confirm_password">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-open">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon eye-closed" style="display: none;">
                                    <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                    <line x1="1" y1="1" x2="23" y2="23"></line>
                                </svg>
                            </span>
                        </div>
                    </div>
                    <div class="form-group form-group-full-width">
                        <button type="submit" class="submit-button">Sign Up</button>
                    </div>
                </form>
                <p class="login-link">Already have an account? <a href="<?php echo $root_path_prefix; ?>login.php">Login here</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo $root_path_prefix; ?>js/signup.js"></script>
</body>

</html>