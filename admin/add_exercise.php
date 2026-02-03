<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php'; // [cite: db_connect.php]
$page_title = "Add New Exercise - Admin";
$errors = [];
$form_data = $_POST; // To repopulate form in case of errors

$difficulty_levels = ['Beginner', 'Intermediate', 'Advanced']; // [cite: add_exercise.php]
$gender_targets = ['Unisex', 'Male', 'Female']; // [cite: add_exercise.php]

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($form_data['name'] ?? '');
    $description = trim($form_data['description'] ?? '');
    $body_part_targeted = trim($form_data['body_part_targeted'] ?? '');
    $category_type = trim($form_data['category_type'] ?? '');
    $difficulty_level = $form_data['difficulty_level'] ?? '';
    $equipment_needed = isset($form_data['equipment_needed']) ? 1 : 0;
    $equipment_details = trim($form_data['equipment_details'] ?? '');
    $image_url = trim($form_data['image_url'] ?? '');
    $video_url = trim($form_data['video_url'] ?? '');
    $gender_target = $form_data['gender_target'] ?? '';
    $notes = trim($form_data['notes'] ?? '');
    $is_featured = isset($form_data['is_featured_exercise']) ? 1 : 0; // Get the new 'is_featured' value

    // Validation
    if (empty($name)) {
        $errors[] = "Exercise name is required.";
    }
    if (empty($difficulty_level) || !in_array($difficulty_level, $difficulty_levels)) {
        $errors[] = "Please select a valid difficulty level.";
    }
    if (empty($gender_target) || !in_array($gender_target, $gender_targets)) {
        $errors[] = "Please select a valid gender target.";
    }
    if ($equipment_needed && empty($equipment_details)) {
        $errors[] = "Equipment details are required if 'Equipment Needed' is checked.";
    }
    if (!$equipment_needed) {
        $equipment_details = null; // Set to NULL if not needed, consistent with DB
    }


    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Updated SQL query to include is_featured
            $sql = "INSERT INTO exercises (name, description, body_part_targeted, category_type, difficulty_level, equipment_needed, equipment_details, image_url, video_url, gender_target, notes, is_featured) 
                    VALUES (:name, :description, :body_part_targeted, :category_type, :difficulty_level, :equipment_needed, :equipment_details, :image_url, :video_url, :gender_target, :notes, :is_featured)"; // [cite: DDL.sql]
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':body_part_targeted', $body_part_targeted);
            $stmt->bindParam(':category_type', $category_type);
            $stmt->bindParam(':difficulty_level', $difficulty_level);
            $stmt->bindParam(':equipment_needed', $equipment_needed, PDO::PARAM_INT);
            $stmt->bindParam(':equipment_details', $equipment_details); // PDO will handle null if $equipment_details is null
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':video_url', $video_url);
            $stmt->bindParam(':gender_target', $gender_target);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':is_featured', $is_featured, PDO::PARAM_INT); // Bind the new parameter

            $stmt->execute();
            header("Location: manage_exercises.php?status=added"); // [cite: manage_exercises.php]
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error adding exercise: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_add_equipment.css"> <!-- Reusing styles [cite: admin_add_equipment.css] -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height); /* [cite: variables.css] */
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; // [cite: navbar_php_wishlist_link] ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; // [cite: admin_sidebar.php] ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container form-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="manage_exercises.php" class="admin-button plain-button">Back to Exercises List</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message global-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="add_exercise.php" method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="name">Exercise Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="body_part_targeted">Body Part Targeted:</label>
                            <input type="text" id="body_part_targeted" name="body_part_targeted" value="<?php echo htmlspecialchars($form_data['body_part_targeted'] ?? ''); ?>" placeholder="e.g., Chest, Legs">
                        </div>
                        <div class="form-group">
                            <label for="category_type">Category Type:</label>
                            <input type="text" id="category_type" name="category_type" value="<?php echo htmlspecialchars($form_data['category_type'] ?? ''); ?>" placeholder="e.g., Strength, Cardio">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="difficulty_level">Difficulty Level:</label>
                            <select id="difficulty_level" name="difficulty_level" required>
                                <option value="" disabled <?php echo empty($form_data['difficulty_level']) ? 'selected' : ''; ?>>Select Level</option>
                                <?php foreach ($difficulty_levels as $level): ?>
                                    <option value="<?php echo $level; ?>" <?php echo ($form_data['difficulty_level'] ?? '') === $level ? 'selected' : ''; ?>><?php echo $level; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="gender_target">Gender Target:</label>
                            <select id="gender_target" name="gender_target" required>
                                <option value="" disabled <?php echo empty($form_data['gender_target']) ? 'selected' : ''; ?>>Select Target</option>
                                <?php foreach ($gender_targets as $gender): ?>
                                    <option value="<?php echo $gender; ?>" <?php echo ($form_data['gender_target'] ?? 'Unisex') === $gender ? 'selected' : ''; ?>><?php echo $gender; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="equipment_needed" name="equipment_needed" value="1" <?php echo (isset($form_data['equipment_needed']) && $form_data['equipment_needed']) ? 'checked' : ''; ?>>
                        <label for="equipment_needed">Equipment Needed?</label>
                    </div>
                    <div class="form-group">
                        <label for="equipment_details">Equipment Details (if needed):</label>
                        <input type="text" id="equipment_details" name="equipment_details" value="<?php echo htmlspecialchars($form_data['equipment_details'] ?? ''); ?>" placeholder="e.g., Dumbbells, Bench">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="image_url">Image URL:</label>
                            <input type="text" id="image_url" name="image_url" placeholder="e.g., images/exercises/pushup.jpg" value="<?php echo htmlspecialchars($form_data['image_url'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="video_url">Video URL (Optional):</label>
                            <input type="url" id="video_url" name="video_url" placeholder="e.g., https://youtube.com/watch?v=..." value="<?php echo htmlspecialchars($form_data['video_url'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="notes">Additional Notes:</label>
                        <textarea id="notes" name="notes" rows="3"><?php echo htmlspecialchars($form_data['notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- New Checkbox for 'is_featured' -->
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_featured_exercise" name="is_featured_exercise" value="1" <?php echo (isset($form_data['is_featured_exercise']) && $form_data['is_featured_exercise']) ? 'checked' : ''; ?>>
                        <label for="is_featured_exercise">Feature this exercise on homepage</label>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="admin-button add-new-button">Add Exercise</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>

