<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

if (isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/includes/db_connect.php';

$page_title = "Login - ErythroMotion";
$errors = [];
$form_data = $_POST;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $identifier = trim($form_data['identifier'] ?? '');
    $password = $form_data['password'] ?? '';

    if (empty($identifier)) {
        $errors[] = "Username or Email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $sql = "SELECT user_id, username, password_hash, role FROM users WHERE username = :identifier OR email = :identifier";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':identifier', $identifier);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                header("Location: " . $root_path_prefix . "index.php");
                exit();
            } else {
                $errors[] = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            $errors[] = "Login failed due to a system error. Please try again later.";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/login.css">
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

    <main class="login-main-content">
        <div class="login-container">
            <h1 class="login-title">Login to Your Account</h1>

            <?php if (!empty($errors)): ?>
                <div class="message error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $root_path_prefix; ?>login.php" method="POST" class="login-form" novalidate>
                <div class="form-group">
                    <label for="identifier">Username or Email:</label>
                    <input type="text" id="identifier" name="identifier" value="<?php echo htmlspecialchars($form_data['identifier'] ?? ''); ?>" required>
                </div>
                <div class="form-group password-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" required>
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
                <div class="form-group">
                    <button type="submit" class="submit-button">Login</button>
                </div>
            </form>
            <p class="signup-link">Don't have an account? <a href="<?php echo $root_path_prefix; ?>signup.php">Sign up here</a></p>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script src="<?php echo $root_path_prefix; ?>js/login.js"></script>
</body>

</html>