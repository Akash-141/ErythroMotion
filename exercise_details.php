<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files

include __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]
$page_title = "Exercise Details - ErythroMotion"; // Default title
$exercise = null;
$errors = [];

$exercise_id_from_get = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$exercise_id_from_get) {
    $errors[] = "No exercise ID specified or invalid ID format.";
} else {
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Fetch the exercise details
        $stmt = $pdo->prepare("SELECT * FROM exercises WHERE exercise_id = :exercise_id"); // [cite: DDL.sql]
        $stmt->bindParam(':exercise_id', $exercise_id_from_get, PDO::PARAM_INT);
        $stmt->execute();
        $exercise = $stmt->fetch();

        if (!$exercise) {
            $errors[] = "Exercise not found.";
            $page_title = "Exercise Not Found - ErythroMotion";
        } else {
            $page_title = htmlspecialchars($exercise['name']) . " - Exercise Details";
        }

    } catch (PDOException $e) {
        $errors[] = "Database error: Could not retrieve exercise details. " . $e->getMessage();
        // In a production environment, log $e->getMessage() and show a generic error.
        $page_title = "Error - ErythroMotion";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/exercise_details.css"> <!-- UPDATED CSS FILENAME -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh;
        }
        main { 
            flex-grow: 1; 
            padding: var(--spacing-lg) var(--spacing-md); /* [cite: variables.css] */
            background-color: var(--white); /* [cite: variables.css] */
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; // [cite: navbar.php] ?>

    <main>
        <div class="exercise-details-container">
            <?php if (!empty($errors)): ?>
                <div class="message error-message global-message">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                    <p><a href="<?php echo $root_path_prefix; ?>exercise.php">Back to Exercise List</a></p>
                </div>
            <?php elseif ($exercise): ?>
                <!-- Apply the two-column layout structure -->
                <div class="exercise-layout">
                    <div class="exercise-media-column">
                        <?php if (!empty($exercise['image_url'])): ?>
                            <img src="<?php echo $root_path_prefix . htmlspecialchars($exercise['image_url']); ?>" alt="<?php echo htmlspecialchars($exercise['name']); ?>" class="exercise-main-image">
                        <?php else: ?>
                            <img src="<?php echo $root_path_prefix; ?>images/exercises/placeholder_large.png" alt="Placeholder Exercise Image" class="exercise-main-image placeholder-image">
                        <?php endif; ?>

                        <?php 
                        if (!empty($exercise['video_url'])):
                            $video_id = '';
                            if (strpos($exercise['video_url'], 'youtube.com/watch?v=') !== false) {
                                parse_str(parse_url($exercise['video_url'], PHP_URL_QUERY), $query_params);
                                $video_id = $query_params['v'] ?? '';
                            } elseif (strpos($exercise['video_url'], 'youtu.be/') !== false) {
                                $video_id = substr(parse_url($exercise['video_url'], PHP_URL_PATH), 1);
                            }

                            if ($video_id): ?>
                                <div class="exercise-video-wrapper">
                                    <iframe 
                                        src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_id); ?>" 
                                        frameborder="0" 
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                                        allowfullscreen
                                        title="Exercise Video: <?php echo htmlspecialchars($exercise['name']); ?>">
                                    </iframe>
                                </div>
                            <?php else: ?>
                                <p class="video-link-info">Video available at: <a href="<?php echo htmlspecialchars($exercise['video_url']); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($exercise['video_url']); ?></a></p>
                            <?php endif; 
                        endif; ?>
                    </div>

                    <div class="exercise-info-column">
                        <h1 class="exercise-main-title"><?php echo htmlspecialchars($exercise['name']); ?></h1>
                        
                        <div class="exercise-info-grid">
                            <div class="info-item">
                                <strong>Body Part Targeted:</strong>
                                <p><?php echo htmlspecialchars($exercise['body_part_targeted'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-item">
                                <strong>Category:</strong>
                                <p><?php echo htmlspecialchars($exercise['category_type'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="info-item">
                                <strong>Difficulty Level:</strong>
                                <p><?php echo htmlspecialchars($exercise['difficulty_level']); ?></p>
                            </div>
                            <div class="info-item">
                                <strong>Gender Target:</strong>
                                <p><?php echo htmlspecialchars($exercise['gender_target']); ?></p>
                            </div>
                            <div class="info-item full-width-info">
                                <strong>Equipment Needed:</strong>
                                <p>
                                    <?php 
                                    if ($exercise['equipment_needed']) {
                                        echo htmlspecialchars($exercise['equipment_details'] ?: 'Specific equipment required.');
                                    } else {
                                        echo 'None (Bodyweight Exercise)';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>

                        <div class="exercise-details-section exercise-description-section">
                            <h2>Description</h2>
                            <p><?php echo nl2br(htmlspecialchars($exercise['description'] ?? 'No description available.')); ?></p>
                        </div>

                        <?php if (!empty($exercise['notes'])): ?>
                        <div class="exercise-details-section exercise-notes-section">
                            <h2>Important Notes / Tips</h2>
                            <p><?php echo nl2br(htmlspecialchars($exercise['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div> <!-- End .exercise-layout -->
                
                <div class="exercise-page-actions">
                    <a href="<?php echo $root_path_prefix; ?>exercise.php" class="button-secondary">Back to Exercise List</a>
                </div>

            <?php else: ?>
                 <p>The exercise you are looking for could not be found.</p>
                 <p><a href="<?php echo $root_path_prefix; ?>exercise.php">Back to Exercise List</a></p>
            <?php endif; ?>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] ?>
</body>
</html>
