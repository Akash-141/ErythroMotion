<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

include __DIR__ . '/includes/db_connect.php';
$page_title = "Exercise List - ErythroMotion";
$current_user_id = $_SESSION['user_id'] ?? null;

$pdo = null;
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}

$feedback_message = "";
$feedback_type = "";
$page_errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_query_string = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
    $redirect_to_self = $root_path_prefix . "exercise.php" . $current_query_string;

    if (isset($_POST['add_to_plan_action'])) {
        if (!$current_user_id) {
            header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode(ltrim($redirect_to_self, './')));
            exit();
        }
        $exercise_to_add_id = $_POST['add_to_plan_exercise_id'] ?? null;
        if ($exercise_to_add_id) {
            try {
                $stmt_check = $pdo->prepare("SELECT 1 FROM user_exercise_plans WHERE user_id = :user_id AND exercise_id = :exercise_id");
                $stmt_check->bindParam(':user_id', $current_user_id);
                $stmt_check->bindParam(':exercise_id', $exercise_to_add_id);
                $stmt_check->execute();
                if ($stmt_check->fetch()) {
                    $feedback_message = "This exercise is already in your plan.";
                    $feedback_type = "error";
                } else {
                    $stmt_add = $pdo->prepare("INSERT INTO user_exercise_plans (user_id, exercise_id) VALUES (:user_id, :exercise_id)");
                    $stmt_add->bindParam(':user_id', $current_user_id);
                    $stmt_add->bindParam(':exercise_id', $exercise_to_add_id);
                    $stmt_add->execute();
                    $feedback_message = "Exercise added to your plan successfully!";
                    $feedback_type = "success";
                }
            } catch (PDOException $e) {
                $feedback_message = "Error adding exercise. Please try again.";
                $feedback_type = "error";
            }
        }
    } elseif (isset($_POST['clear_my_plan'])) {
        if (!$current_user_id) {
            header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode(ltrim($redirect_to_self, './')));
            exit();
        }
        try {
            $stmt_clear = $pdo->prepare("DELETE FROM user_exercise_plans WHERE user_id = :user_id");
            $stmt_clear->bindParam(':user_id', $current_user_id);
            $stmt_clear->execute();
            $feedback_message = "Your workout plan has been cleared!";
            $feedback_type = "success";
        } catch (PDOException $e) {
            $feedback_message = "Error clearing your plan. Please try again.";
            $feedback_type = "error";
        }
    } elseif (isset($_POST['remove_from_plan_action']) && isset($_POST['plan_entry_id_to_remove'])) {
        if (!$current_user_id) {
            header("Location: " . $root_path_prefix . "login.php?redirect=" . urlencode(ltrim($redirect_to_self, './')));
            exit();
        }
        $plan_entry_id_to_remove = $_POST['plan_entry_id_to_remove'];
        try {
            $stmt_remove = $pdo->prepare("DELETE FROM user_exercise_plans WHERE plan_entry_id = :plan_entry_id AND user_id = :user_id");
            $stmt_remove->bindParam(':plan_entry_id', $plan_entry_id_to_remove);
            $stmt_remove->bindParam(':user_id', $current_user_id);
            $removed_count = $stmt_remove->execute() ? $stmt_remove->rowCount() : 0;

            if ($removed_count > 0) {
                $feedback_message = "Exercise removed from your plan.";
                $feedback_type = "success";
            } else {
                $feedback_message = "Could not remove exercise or exercise not found in your plan.";
                $feedback_type = "error";
            }
        } catch (PDOException $e) {
            $feedback_message = "Error removing exercise. Please try again.";
            $feedback_type = "error";
        }
    }
    if (!empty($feedback_message) && ($_SERVER["REQUEST_METHOD"] == "POST")) {
        $separator = strpos($redirect_to_self, '?') === false ? '?' : '&';
        header("Location: " . $redirect_to_self . $separator . "feedback_msg=" . urlencode($feedback_message) . "&feedback_type=" . urlencode($feedback_type));
        exit();
    }
}

if (isset($_GET['feedback_msg']) && isset($_GET['feedback_type'])) {
    $feedback_message = htmlspecialchars(urldecode($_GET['feedback_msg']));
    $feedback_type = htmlspecialchars($_GET['feedback_type']);
}

$sql = "SELECT * FROM exercises WHERE 1=1";
$params = [];

$filter_body_part = $_GET['body_part'] ?? '';
$filter_category = $_GET['category_type'] ?? '';
$filter_difficulty = $_GET['difficulty_level'] ?? '';
$filter_gender = $_GET['gender_target'] ?? '';
$filter_equipment_free_only = isset($_GET['equipment_free_only']);

if (!empty($filter_body_part)) {
    $sql .= " AND body_part_targeted = :body_part";
    $params[':body_part'] = $filter_body_part;
}
if (!empty($filter_category)) {
    $sql .= " AND category_type = :category";
    $params[':category'] = $filter_category;
}
if (!empty($filter_difficulty)) {
    $sql .= " AND difficulty_level = :difficulty";
    $params[':difficulty'] = $filter_difficulty;
}
if (!empty($filter_gender)) {
    $sql .= " AND (gender_target = :gender OR gender_target = 'Unisex')";
    $params[':gender'] = $filter_gender;
}
if ($filter_equipment_free_only) {
    $sql .= " AND equipment_needed = 0";
}
$sql .= " ORDER BY name ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exercises = $stmt->fetchAll();
} catch (PDOException $e) {
    $exercises = [];
    $page_errors[] = "Error fetching exercises: " . $e->getMessage();
}

$user_plan_exercises = [];
if ($current_user_id) {
    try {
        $stmt_plan = $pdo->prepare("SELECT uep.plan_entry_id, e.exercise_id, e.name FROM user_exercise_plans uep JOIN exercises e ON uep.exercise_id = e.exercise_id WHERE uep.user_id = :user_id ORDER BY uep.date_added DESC");
        $stmt_plan->bindParam(':user_id', $current_user_id);
        $stmt_plan->execute();
        $user_plan_exercises = $stmt_plan->fetchAll();
    } catch (PDOException $e) {
        $page_errors[] = "Error fetching your plan: " . $e->getMessage();
    }
}

$body_parts = ['Chest', 'Back', 'Legs', 'Shoulders', 'Biceps', 'Triceps', 'Abs', 'Full Body'];
$categories = ['Strength', 'Cardio', 'Flexibility', 'Plyometrics', 'CrossFit'];
$difficulties = ['Beginner', 'Intermediate', 'Advanced'];
$genders = ['Male', 'Female', 'Unisex'];

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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/exercise.css">
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

    <main class="exercise-page-main">
        <?php if (!empty($feedback_message)): ?>
            <div class="message global-message <?php echo $feedback_type === 'success' ? 'success-message' : 'error-message'; ?>">
                <p><?php echo $feedback_message; ?></p>
            </div>
        <?php endif; ?>
        <?php if (!empty($page_errors)): ?>
            <div class="message global-message error-message">
                <?php foreach ($page_errors as $err): ?>
                    <p><?php echo htmlspecialchars($err); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>


        <div class="exercise-page-layout">
            <aside class="filter-section styled-sidebar-box">
                <h2>Filter Exercises</h2>
                <form method="GET" action="<?php echo $root_path_prefix; ?>exercise.php" class="filter-form">
                    <div class="form-group">
                        <label for="body_part">Body Part:</label>
                        <select name="body_part" id="body_part">
                            <option value="">All Body Parts</option>
                            <?php foreach ($body_parts as $part): ?>
                                <option value="<?php echo htmlspecialchars($part); ?>" <?php echo ($filter_body_part == $part) ? 'selected' : ''; ?>><?php echo htmlspecialchars($part); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="category_type">Category:</label>
                        <select name="category_type" id="category_type">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo ($filter_category == $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="difficulty_level">Difficulty:</label>
                        <select name="difficulty_level" id="difficulty_level">
                            <option value="">All Levels</option>
                            <?php foreach ($difficulties as $diff): ?>
                                <option value="<?php echo htmlspecialchars($diff); ?>" <?php echo ($filter_difficulty == $diff) ? 'selected' : ''; ?>><?php echo htmlspecialchars($diff); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="gender_target">Target Gender:</label>
                        <select name="gender_target" id="gender_target">
                            <option value="">All Genders</option>
                            <?php foreach ($genders as $gen): ?>
                                <option value="<?php echo htmlspecialchars($gen); ?>" <?php echo ($filter_gender == $gen) ? 'selected' : ''; ?>><?php echo htmlspecialchars($gen); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" name="equipment_free_only" id="equipment_free_only" value="1" <?php echo $filter_equipment_free_only ? 'checked' : ''; ?>>
                        <label for="equipment_free_only">Show only equipment-free</label>
                    </div>
                    <button type="submit" class="filter-button">Apply Filters</button>
                </form>
            </aside>

            <section class="exercise-list-section">
                <h1>Explore Exercises</h1>
                <?php if (empty($exercises)): ?>
                    <p class="no-exercises">No exercises found matching your criteria. Try adjusting the filters!</p>
                <?php else: ?>
                    <div class="exercise-grid">
                        <?php foreach ($exercises as $exercise): ?>
                            <div class="exercise-card">
                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($exercise['image_url']) ? htmlspecialchars($exercise['image_url']) : 'images/exercises/placeholder.png'; ?>" alt="<?php echo htmlspecialchars($exercise['name']); ?>" class="exercise-image">
                                <div class="exercise-card-content">
                                    <h3><?php echo htmlspecialchars($exercise['name']); ?></h3>
                                    <p class="exercise-meta">
                                        <span>Body Part: <?php echo htmlspecialchars($exercise['body_part_targeted']); ?></span>
                                        <span>Difficulty: <?php echo htmlspecialchars($exercise['difficulty_level']); ?></span>
                                    </p>
                                    <?php if ($exercise['equipment_needed'] && !empty($exercise['equipment_details'])): ?>
                                        <p class="equipment-needed"><strong>Equipment:</strong> <?php echo htmlspecialchars($exercise['equipment_details']); ?></p>
                                    <?php elseif ($exercise['equipment_needed']): ?>
                                        <p class="equipment-needed"><strong>Equipment:</strong> Required</p>
                                    <?php else: ?>
                                        <p class="equipment-free">No equipment needed</p>
                                    <?php endif; ?>
                                    <p class="exercise-description-snippet"><?php echo htmlspecialchars(substr($exercise['description'] ?? '', 0, 100)) . (strlen($exercise['description'] ?? '') > 100 ? '...' : ''); ?></p>
                                    <div class="exercise-actions">
                                        <a href="<?php echo $root_path_prefix; ?>exercise_details.php?id=<?php echo $exercise['exercise_id']; ?>" class="details-button">View Details</a>
                                        <form method="POST" action="<?php echo $root_path_prefix; ?>exercise.php<?php echo htmlspecialchars(empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']); ?>" class="add-to-plan-form">
                                            <input type="hidden" name="add_to_plan_exercise_id" value="<?php echo $exercise['exercise_id']; ?>">
                                            <button type="submit" name="add_to_plan_action" class="add-to-plan-button">Add to Plan</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="right-aside-section">
                <div class="user-plan-content-box styled-sidebar-box">
                    <h2>My Workout Plan</h2>
                    <?php if ($current_user_id): ?>
                        <?php if (!empty($user_plan_exercises)): ?>
                            <ul class="user-plan-list">
                                <?php foreach ($user_plan_exercises as $plan_item): ?>
                                    <li class="user-plan-item">
                                        <span><?php echo htmlspecialchars($plan_item['name']); ?></span>
                                        <form method="POST" action="<?php echo $root_path_prefix; ?>exercise.php<?php echo htmlspecialchars(empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']); ?>" class="remove-item-form">
                                            <input type="hidden" name="plan_entry_id_to_remove" value="<?php echo $plan_item['plan_entry_id']; ?>">
                                            <button type="submit" name="remove_from_plan_action" class="remove-item-button" title="Remove exercise">×</button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <form method="POST" action="<?php echo $root_path_prefix; ?>exercise.php<?php echo htmlspecialchars(empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING']); ?>" class="clear-plan-form">
                                <input type="hidden" name="clear_my_plan" value="1">
                                <button type="submit" class="clear-plan-button">Clear My Plan</button>
                            </form>
                        <?php else: ?>
                            <p>Your workout plan is empty. Add some exercises!</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p><a href="<?php echo $root_path_prefix; ?>login.php?redirect=<?php echo urlencode('exercise.php'); ?>">Log in</a> or <a href="<?php echo $root_path_prefix; ?>signup.php">sign up</a> to create and save your workout plan.</p>
                    <?php endif; ?>
                </div>
                <div class="cta-motionmart-sidebar-box styled-sidebar-box">
                    <h2>Need Equipment?</h2>
                    <p>Elevate your workouts with professional-grade fitness gear!</p>
                    <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="cta-button" id="motionmart-cta-button-sidebar">Visit MotionMart</a>
                </div>
            </aside>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>


</html>
