<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path_prefix . "login.php");
    exit();
}

require __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]

$page_title = "My Profile - ErythroMotion";
$user_id = $_SESSION['user_id'];

$profile_errors = [];
$profile_success_message = "";
$password_errors = [];
$password_success_message = "";
$orders = [];
$order_errors = [];
$user_workout_plan = [];
$workout_plan_errors = [];

// General feedback message from session (used for redirects after POST actions)
$feedback_message_from_session = $_SESSION['feedback_message'] ?? null;
$feedback_type_from_session = $_SESSION['feedback_type'] ?? null;
if (isset($_SESSION['feedback_message'])) unset($_SESSION['feedback_message']);
if (isset($_SESSION['feedback_type'])) unset($_SESSION['feedback_type']);


try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $profile_errors[] = "Database connection failed. Please try again later.";
    // Halt further DB operations if connection fails
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($pdo)) { // Only process POST if DB connection is up
    // --- Profile Update Logic ---
    if (isset($_POST['update_profile'])) {
        $full_name = trim($_POST['full_name'] ?? '');
        $phone_number = trim($_POST['phone_number'] ?? '');
        $date_of_birth_str = trim($_POST['date_of_birth'] ?? '');
        $location = trim($_POST['location'] ?? '');
        $blood_group = trim($_POST['blood_group'] ?? '');
        $gender = trim($_POST['gender'] ?? '');
        $date_of_birth = null;

        if (empty($full_name)) $profile_errors[] = "Full name is required.";
        if (empty($phone_number)) $profile_errors[] = "Phone number is required.";
        elseif (!preg_match('/^[+]?[0-9\s\-()]{7,20}$/', $phone_number)) $profile_errors[] = "Invalid phone number format.";
        if (empty($date_of_birth_str)) $profile_errors[] = "Date of birth is required.";
        else {
            try {
                $d = new DateTime($date_of_birth_str);
                if ($d->format('Y-m-d') !== $date_of_birth_str) throw new Exception();
                if ($d > new DateTime()) $profile_errors[] = "Date of birth cannot be in the future.";
                else $date_of_birth = $date_of_birth_str;
            } catch (Exception $e) {
                $profile_errors[] = "Invalid date of birth format.";
            }
        }
        if (empty($location)) $profile_errors[] = "Location is required.";
        $valid_blood_groups = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
        if (empty($blood_group) || !in_array($blood_group, $valid_blood_groups)) $profile_errors[] = "Valid blood group is required.";
        $valid_genders = ['male', 'female', 'other', 'prefer_not_to_say'];
        if (empty($gender) || !in_array($gender, $valid_genders)) $profile_errors[] = "Valid gender is required.";

        if (empty($profile_errors)) {
            try {
                $sql = "UPDATE users SET full_name = :full_name, phone_number = :phone_number, date_of_birth = :date_of_birth, location = :location, blood_group = :blood_group, gender = :gender, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':phone_number' => $phone_number,
                    ':date_of_birth' => $date_of_birth,
                    ':location' => $location,
                    ':blood_group' => $blood_group,
                    ':gender' => $gender,
                    ':user_id' => $user_id
                ]);
                $profile_success_message = "Profile updated successfully!";
            } catch (PDOException $e) {
                $profile_errors[] = "Profile update failed. Please try again.";
            }
        }
    }
    // --- Password Change Logic ---
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_new_password = $_POST['confirm_new_password'] ?? '';

        if (empty($current_password)) $password_errors[] = "Current password is required.";
        if (empty($new_password)) $password_errors[] = "New password is required.";
        elseif (strlen($new_password) < 6) $password_errors[] = "New password must be at least 6 characters long.";
        if ($new_password !== $confirm_new_password) $password_errors[] = "New passwords do not match.";

        if (empty($password_errors)) {
            try {
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $user_id);
                $stmt->execute();
                $user_data_for_pass = $stmt->fetch();

                if ($user_data_for_pass && password_verify($current_password, $user_data_for_pass['password_hash'])) {
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $pdo->prepare("UPDATE users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id");
                    $update_stmt->execute([':password_hash' => $new_password_hash, ':user_id' => $user_id]);
                    $password_success_message = "Password changed successfully!";
                } else {
                    $password_errors[] = "Incorrect current password.";
                }
            } catch (PDOException $e) {
                $password_errors[] = "Password change failed. Please try again.";
            }
        }
    }
    // --- Workout Plan Actions ---
    elseif (isset($_POST['remove_from_plan_action']) && isset($_POST['plan_entry_id_to_remove'])) {
        $plan_entry_id = filter_var($_POST['plan_entry_id_to_remove'], FILTER_VALIDATE_INT);
        if ($plan_entry_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM user_exercise_plans WHERE plan_entry_id = :plan_entry_id AND user_id = :user_id"); // [cite: DDL.sql]
                $stmt->bindParam(':plan_entry_id', $plan_entry_id, PDO::PARAM_INT);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->rowCount() > 0) {
                    $_SESSION['feedback_message'] = "Exercise removed from your plan.";
                    $_SESSION['feedback_type'] = "success";
                } else {
                    $_SESSION['feedback_message'] = "Could not remove exercise or it was not found in your plan.";
                    $_SESSION['feedback_type'] = "error";
                }
            } catch (PDOException $e) {
                // In production, log $e->getMessage() instead of echoing it directly or storing in session for user.
                $_SESSION['feedback_message'] = "Error removing exercise. Please try again.";
                $_SESSION['feedback_type'] = "error";
            }
        } else {
            $_SESSION['feedback_message'] = "Invalid request to remove exercise.";
            $_SESSION['feedback_type'] = "error";
        }
        header("Location: " . $root_path_prefix . "profile.php#workout-plan-section"); // Redirect to the workout plan section
        exit();
    } elseif (isset($_POST['clear_entire_plan_action'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM user_exercise_plans WHERE user_id = :user_id"); // [cite: DDL.sql]
            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->execute();
            $_SESSION['feedback_message'] = "Your workout plan has been cleared successfully.";
            $_SESSION['feedback_type'] = "success";
        } catch (PDOException $e) {
            $_SESSION['feedback_message'] = "Error clearing your workout plan. Please try again.";
            $_SESSION['feedback_type'] = "error";
        }
        header("Location: " . $root_path_prefix . "profile.php#workout-plan-section"); // Redirect
        exit();
    }
}

$user = null;
if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT full_name, username, phone_number, email, date_of_birth, location, blood_group, gender FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch();
        if (!$user) $profile_errors[] = "User data could not be retrieved.";
    } catch (PDOException $e) {
        $profile_errors[] = "Failed to retrieve user data.";
    }

    try {
        $stmt_orders = $pdo->prepare("SELECT order_id, order_date, total_amount, order_status FROM orders WHERE user_id = :user_id ORDER BY order_date DESC");
        $stmt_orders->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_orders->execute();
        $orders = $stmt_orders->fetchAll();
    } catch (PDOException $e) {
        $order_errors[] = "Could not retrieve your order history.";
    }

    try {
        $stmt_plan = $pdo->prepare("
            SELECT uep.plan_entry_id, e.exercise_id, e.name, e.image_url, e.body_part_targeted 
            FROM user_exercise_plans uep 
            JOIN exercises e ON uep.exercise_id = e.exercise_id 
            WHERE uep.user_id = :user_id 
            ORDER BY uep.date_added DESC
        ");
        $stmt_plan->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_plan->execute();
        $user_workout_plan = $stmt_plan->fetchAll();
    } catch (PDOException $e) {
        $workout_plan_errors[] = "Could not retrieve your workout plan.";
    }

    try {
        $stmt_posts = $pdo->prepare("SELECT post_id, title, status, created_at FROM blog_posts WHERE user_id = :user_id ORDER BY created_at DESC");
        $stmt_posts->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt_posts->execute();
        $user_blog_posts = $stmt_posts->fetchAll();
    } catch (PDOException $e) {
        $blog_post_errors[] = "Could not retrieve your blog posts.";
    }
} else {
    // Fallback if $pdo is not set (DB connection failed)
    $user = [
        'username' => $_SESSION['username'] ?? 'User',
        'email' => 'N/A',
        'full_name' => 'N/A',
        'phone_number' => 'N/A',
        'date_of_birth' => null,
        'location' => 'N/A',
        'blood_group' => 'N/A',
        'gender' => 'N/A'
    ];
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/profile.css"> <!-- [cite: profile_css_workout_plan] -->
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
    <?php include __DIR__ . '/includes/navbar.php'; // [cite: navbar.php] 
    ?>

    <main class="profile-main-content">
        <div class="profile-container">
            <h1 class="profile-title">My Profile</h1>

            <!-- General Feedback Message Area for redirects -->
            <?php if ($feedback_message_from_session): ?>
                <div class="message <?php echo ($feedback_type_from_session === 'error') ? 'error-message' : 'success-message'; ?> global-message">
                    <p><?php echo htmlspecialchars($feedback_message_from_session); ?></p>
                </div>
            <?php endif; ?>

            <!-- Profile Information Section -->
            <div class="profile-section">
                <h2 class="section-title">Profile Information</h2>
                <?php if (!empty($profile_errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($profile_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($profile_success_message)): ?>
                    <div class="message success-message">
                        <p><?php echo htmlspecialchars($profile_success_message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($user): ?>
                    <form action="<?php echo $root_path_prefix; ?>profile.php" method="POST" class="profile-form info-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="username_display">Username:</label>
                                <input type="text" id="username_display" value="<?php echo htmlspecialchars($user['username']); ?>" readonly class="readonly-field-display">
                            </div>
                            <div class="form-group">
                                <label for="email_display">Email:</label>
                                <input type="text" id="email_display" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="readonly-field-display">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="full_name">Full Name:</label>
                                <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="phone_number">Phone Number:</label>
                                <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="date_of_birth">Date of Birth:</label>
                                <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($user['date_of_birth']); ?>" required>
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
                                    <option value="" disabled <?php echo empty($user['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                    <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo ($user['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                                    <option value="prefer_not_to_say" <?php echo ($user['gender'] === 'prefer_not_to_say') ? 'selected' : ''; ?>>Prefer not to say</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="blood_group">Blood Group:</label>
                                <select id="blood_group" name="blood_group" required>
                                    <option value="" disabled <?php echo empty($user['blood_group']) ? 'selected' : ''; ?>>Select Blood Group</option>
                                    <option value="A+" <?php echo ($user['blood_group'] === 'A+') ? 'selected' : ''; ?>>A+</option>
                                    <option value="A-" <?php echo ($user['blood_group'] === 'A-') ? 'selected' : ''; ?>>A-</option>
                                    <option value="B+" <?php echo ($user['blood_group'] === 'B+') ? 'selected' : ''; ?>>B+</option>
                                    <option value="B-" <?php echo ($user['blood_group'] === 'B-') ? 'selected' : ''; ?>>B-</option>
                                    <option value="AB+" <?php echo ($user['blood_group'] === 'AB+') ? 'selected' : ''; ?>>AB+</option>
                                    <option value="AB-" <?php echo ($user['blood_group'] === 'AB-') ? 'selected' : ''; ?>>AB-</option>
                                    <option value="O+" <?php echo ($user['blood_group'] === 'O+') ? 'selected' : ''; ?>>O+</option>
                                    <option value="O-" <?php echo ($user['blood_group'] === 'O-') ? 'selected' : ''; ?>>O-</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group form-group-full-width">
                            <label for="location">Location:</label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($user['location']); ?>" required>
                        </div>
                        <div class="form-group form-group-full-width">
                            <button type="submit" name="update_profile" class="submit-button">Update Profile</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- My Blog Posts Section -->
            <div class="profile-section" id="my-blog-posts-section">
                <div class="section-header">
                    <h2 class="section-title">My Blog Posts</h2>
                    <a href="<?php echo $root_path_prefix; ?>create_post.php" class="button-primary create-post-btn">Create New Post</a>
                </div>
                <?php if (!empty($blog_post_errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($blog_post_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php elseif (empty($user_blog_posts)): ?>
                    <p>You have not created any blog posts yet. <a href="<?php echo $root_path_prefix; ?>create_post.php" class="button-primary create-post-btn">Write your first post!</a></p>
                <?php else: ?>
                    <table class="profile-table posts-table">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date Created</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($user_blog_posts as $post): ?>
                                <tr>
                                    <td data-label="Title"><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td data-label="Date"><?php echo date("M j, Y", strtotime($post['created_at'])); ?></td>
                                    <td data-label="Status"><span class="status-badge status-<?php echo htmlspecialchars($post['status']); ?>"><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $post['status']))); ?></span></td>
                                    <td data-label="Actions" class="actions-cell">
                                        <!-- Note: Will need to create edit_post.php and delete_post.php for users -->
                                        <a href="edit_post.php?id=<?php echo $post['post_id']; ?>" class="button-view-details">Edit</a>
                                        <form action="delete_post.php" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                            <button type="submit" class="button-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Order History Section -->
            <div class="profile-section" id="order-history-section">
                <h2 class="section-title">My Order History</h2>
                <?php if (!empty($order_errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($order_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php elseif (empty($orders)): ?>
                    <p>You have not placed any orders yet.</p>
                <?php else: ?>
                    <table class="profile-table orders-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td data-label="Order ID">#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td data-label="Date"><?php echo htmlspecialchars(date("M j, Y, g:i a", strtotime($order['order_date']))); ?></td>
                                    <td data-label="Total Amount">$<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></td>
                                    <td data-label="Status"><span class="order-status status-<?php echo htmlspecialchars(strtolower(str_replace(' ', '_', $order['order_status']))); ?>"><?php echo htmlspecialchars($order['order_status']); ?></span></td>
                                    <td data-label="Action">
                                        <a href="<?php echo $root_path_prefix; ?>order_details_user.php?id=<?php echo $order['order_id']; ?>" class="button-view-details">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- My Workout Plan Section -->
            <div class="profile-section" id="workout-plan-section">
                <h2 class="section-title">My Workout Plan</h2>
                <?php if (!empty($workout_plan_errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($workout_plan_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php elseif (empty($user_workout_plan)): ?>
                    <p>Your workout plan is currently empty. <a href="<?php echo $root_path_prefix; ?>exercise.php">Find exercises to add!</a></p>
                <?php else: ?>
                    <div class="workout-plan-list">
                        <?php foreach ($user_workout_plan as $plan_item): ?>
                            <div class="workout-plan-item">
                                <div class="workout-plan-item-image">
                                    <img src="<?php echo $root_path_prefix; ?><?php echo !empty($plan_item['image_url']) ? htmlspecialchars($plan_item['image_url']) : 'images/exercises/placeholder_thumb.png'; ?>" alt="<?php echo htmlspecialchars($plan_item['name']); ?>">
                                </div>
                                <div class="workout-plan-item-details">
                                    <h3><?php echo htmlspecialchars($plan_item['name']); ?></h3>
                                    <p>Body Part: <?php echo htmlspecialchars($plan_item['body_part_targeted']); ?></p>
                                    <a href="<?php echo $root_path_prefix; ?>exercise_details.php?id=<?php echo $plan_item['exercise_id']; ?>" class="button-view-details-plan">View Exercise</a>
                                </div>
                                <div class="workout-plan-item-actions">
                                    <form action="profile.php#workout-plan-section" method="POST" class="remove-from-plan-form">
                                        <input type="hidden" name="plan_entry_id_to_remove" value="<?php echo $plan_item['plan_entry_id']; ?>">
                                        <button type="submit" name="remove_from_plan_action" class="button-remove-plan-item" title="Remove from plan">X</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <form action="profile.php#workout-plan-section" method="POST" class="clear-all-plan-form">
                        <button type="submit" name="clear_entire_plan_action" class="button-danger clear-plan-button-profile">Clear Entire Plan</button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Change Password Section -->
            <div class="profile-section">
                <h2 class="section-title">Change Password</h2>
                <?php if (!empty($password_errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($password_errors as $error): ?><p><?php echo htmlspecialchars($error); ?></p><?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($password_success_message)): ?>
                    <div class="message success-message">
                        <p><?php echo htmlspecialchars($password_success_message); ?></p>
                    </div>
                <?php endif; ?>
                <form action="<?php echo $root_path_prefix; ?>profile.php" method="POST" class="profile-form password-form">
                    <div class="form-group password-group">
                        <label for="current_password">Current Password:</label>
                        <input type="password" id="current_password" name="current_password" required>
                        <span class="toggle-password" data-target="current_password">
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
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" required minlength="6">
                        <span class="toggle-password" data-target="new_password">
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
                        <label for="confirm_new_password">Confirm New Password:</label>
                        <input type="password" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                        <span class="toggle-password" data-target="confirm_new_password">
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
                    <div class="form-group form-group-full-width">
                        <button type="submit" name="change_password" class="submit-button">Change Password</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] 
    ?>
    <script src="<?php echo $root_path_prefix; ?>js/profile.js"></script> <!-- [cite: profile.js] -->
</body>

</html>