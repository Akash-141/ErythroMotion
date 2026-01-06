<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files
$page_title = "ErythroMotion - Your Path to Fitness & Health";

include __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]

$featured_exercises = [];
$featured_equipments = [];
$home_errors = [];

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    $stmt_ex = $pdo->query("SELECT exercise_id, name, description, image_url, body_part_targeted FROM exercises WHERE is_featured = 1 ORDER BY RAND() LIMIT 6"); // [cite: DDL.sql]
    $featured_exercises = $stmt_ex->fetchAll();

    $stmt_eq = $pdo->query("SELECT equipment_id, name, price, image_url, description FROM equipments WHERE is_featured = 1 LIMIT 6"); // [cite: DDL.sql]
    $featured_equipments = $stmt_eq->fetchAll();

} catch (PDOException $e) {
    $home_errors[] = "Database error: Could not fetch featured content. " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/variables.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/navbar.css"> <!-- [cite: navbar_php_wishlist_link] -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/footer.css"> <!-- [cite: footer.php] -->
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/index.css"> <!-- [cite: index_css_homepage] -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

    <?php include __DIR__ . '/includes/navbar.php'; // [cite: navbar_php_wishlist_link] ?>

    <main class="homepage-main">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Welcome to ErythroMotion!</h1>
                <p class="hero-tagline">Your ultimate partner in achieving peak fitness, managing health, and fostering a supportive community. Start your journey with us today!</p>
                <a href="<?php echo $root_path_prefix; ?>exercise.php" class="button-primary hero-cta">Explore Exercises</a>
            </div>
        </section>

        <!-- Carousel Section (Replaces About Section) -->
        <section class="carousel-section section-padding">
            <div class="container">
                <!-- <h2 class="section-title">Discover ErythroMotion</h2> You can add a title if desired -->
                <div class="carousel-wrapper">
                    <div class="carousel-slides">
                        <!-- Slide 1: Exercises -->
                        <div class="carousel-slide active">
                            <img src="<?php echo $root_path_prefix; ?>images/carousel/exercises_showcase.png" alt="Diverse range of fitness exercises" class="carousel-image">
                            <div class="carousel-caption">
                                <h3>Unlock Your Potential</h3>
                                <p>Explore a wide variety of exercises tailored for all fitness levels. Find your perfect routine and start transforming your body and mind.</p>
                                <a href="<?php echo $root_path_prefix; ?>exercise.php" class="button-primary carousel-cta">View Exercises</a>
                            </div>
                        </div>
                        <!-- Slide 2: MotionMart -->
                        <div class="carousel-slide">
                            <img src="<?php echo $root_path_prefix; ?>images/carousel/motionmart_showcase.png" alt="High-quality fitness equipment from MotionMart" class="carousel-image">
                            <div class="carousel-caption">
                                <h3>Gear Up at MotionMart</h3>
                                <p>Discover top-quality fitness equipment and accessories to elevate your workouts. Everything you need, all in one place.</p>
                                <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="button-primary carousel-cta">Shop MotionMart</a>
                            </div>
                        </div>
                        <!-- Add more slides as needed -->
                        <!-- Slide 3: Example (e.g., Blood Donation)
                        <div class="carousel-slide">
                            <img src="<?php echo $root_path_prefix; ?>images/carousel/blood_donation_showcase.jpg" alt="Community blood donation drive" class="carousel-image">
                            <div class="carousel-caption">
                                <h3>Make a Difference</h3>
                                <p>Join our community efforts. Learn about blood donation and how you can contribute to saving lives.</p>
                                <a href="<?php echo $root_path_prefix; ?>blood_donation.php" class="button-primary carousel-cta">Learn More</a>
                            </div>
                        </div>
                        -->
                    </div>
                    <button class="carousel-control prev" aria-label="Previous slide">&#10094;</button>
                    <button class="carousel-control next" aria-label="Next slide">&#10095;</button>
                    <div class="carousel-dots">
                        <!-- Dots will be generated by JavaScript -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Exercises Section -->
        <?php if (!empty($featured_exercises)): ?>
        <section class="featured-exercises-section section-padding colored-section">
            <div class="container">
                <h2 class="section-title">Featured Exercises</h2>
                <div class="featured-grid">
                    <?php foreach ($featured_exercises as $exercise): ?>
                        <div class="featured-card exercise-card">
                            <a href="<?php echo $root_path_prefix; ?>exercise_details.php?id=<?php echo $exercise['exercise_id']; ?>">
                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($exercise['image_url']) ? htmlspecialchars($exercise['image_url']) : 'images/exercises/placeholder.png'; ?>" alt="<?php echo htmlspecialchars($exercise['name']); ?>" class="featured-card-image">
                                <div class="featured-card-content">
                                    <h3><?php echo htmlspecialchars($exercise['name']); ?></h3>
                                    <p class="exercise-target">Targets: <?php echo htmlspecialchars($exercise['body_part_targeted']); ?></p>
                                    <p class="card-description"><?php echo htmlspecialchars(substr($exercise['description'] ?? '', 0, 80)) . (strlen($exercise['description'] ?? '') > 80 ? '...' : ''); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="section-cta">
                    <a href="<?php echo $root_path_prefix; ?>exercise.php" class="button-secondary">View All Exercises</a>
                </div>
            </div>
        </section>
        <?php elseif (empty($home_errors)): ?>
        <section class="featured-exercises-section section-padding colored-section">
            <div class="container">
                <h2 class="section-title">Featured Exercises</h2>
                <p class="text-center">No featured exercises available at the moment. Check back soon!</p>
                 <div class="section-cta">
                    <a href="<?php echo $root_path_prefix; ?>exercise.php" class="button-secondary">View All Exercises</a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Featured Equipment Section -->
        <?php if (!empty($featured_equipments)): ?>
        <section class="featured-equipment-section section-padding">
            <div class="container">
                <h2 class="section-title">Hot Deals from MotionMart</h2>
                <div class="featured-grid">
                    <?php foreach ($featured_equipments as $equipment): ?>
                        <div class="featured-card equipment-card">
                             <a href="<?php echo $root_path_prefix; ?>equipment_details.php?id=<?php echo $equipment['equipment_id']; ?>">
                                <img src="<?php echo $root_path_prefix; ?><?php echo !empty($equipment['image_url']) ? htmlspecialchars($equipment['image_url']) : 'images/equipments/placeholder.jpg'; ?>" alt="<?php echo htmlspecialchars($equipment['name']); ?>" class="featured-card-image">
                                <div class="featured-card-content">
                                    <h3><?php echo htmlspecialchars($equipment['name']); ?></h3>
                                    <p class="equipment-price">$<?php echo htmlspecialchars(number_format($equipment['price'], 2)); ?></p>
                                     <p class="card-description"><?php echo htmlspecialchars(substr($equipment['description'] ?? '', 0, 70)) . (strlen($equipment['description'] ?? '') > 70 ? '...' : ''); ?></p>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="section-cta">
                    <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="button-primary">Shop All Equipment</a>
                </div>
            </div>
        </section>
        <?php elseif (empty($home_errors)): ?>
        <section class="featured-equipment-section section-padding">
            <div class="container">
                <h2 class="section-title">Hot Deals from MotionMart</h2>
                <p class="text-center">No featured equipment available at the moment. Visit our shop for all items!</p>
                 <div class="section-cta">
                    <a href="<?php echo $root_path_prefix; ?>motionmart.php" class="button-primary">Shop All Equipment</a>
                </div>
            </div>
        </section>
        <?php endif; ?>
        
        <?php if (!empty($home_errors)): ?>
            <div class="container section-padding">
                <div class="message error-message global-message">
                    <?php foreach ($home_errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Key Features / CTA Section -->
        <section class="key-features-cta-section section-padding colored-section">
            <div class="container">
                <h2 class="section-title">Explore More</h2>
                <div class="cta-grid">
                    <a href="<?php echo $root_path_prefix; ?>fitness_guide.php" class="cta-card">
                        <i class="fas fa-book-reader"></i>
                        <h3>Fitness Guide</h3>
                        <p>Unlock your potential with our expert fitness guidance.</p>
                    </a>
                    <a href="<?php echo $root_path_prefix; ?>bmi.php" class="cta-card">
                        <i class="fas fa-calculator"></i>
                        <h3>BMI Calculator</h3>
                        <p>Check your Body Mass Index instantly.</p>
                    </a>
                    <a href="<?php echo $root_path_prefix; ?>blood_donation.php" class="cta-card">
                        <i class="fas fa-tint"></i>
                        <h3>Blood Donation</h3>
                        <p>Learn how you can save lives by donating blood.</p>
                    </a>
                     <a href="<?php echo $root_path_prefix; ?>contact.php" class="cta-card">
                        <i class="fas fa-envelope"></i>
                        <h3>Contact Us</h3>
                        <p>Get in touch with our team for any queries.</p>
                    </a>
                </div>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/includes/footer.php'; // [cite: footer.php] ?>
    <script src="<?php echo $root_path_prefix; ?>js/homepage_carousel.js"></script> <!-- Link to new JS file -->
</body>
</html>
