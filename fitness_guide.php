<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; // Define path prefix for root files
$page_title = "Fitness Guide - ErythroMotion";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/fitness_guide.css">
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

    <main class="fitness-guide-main">
        <header class="guide-header">
            <div class="guide-header-content">
                <h1>Unlock Your Potential With The ErythroMotion Fitness Guide</h1>
                <p class="subtitle">Discover the transformative power of fitness and take the first step towards a healthier, stronger you.</p>
            </div>
        </header>

        <section class="guide-section benefits-section">
            <div class="section-content">
                <h2>Why Embrace Fitness? The Myriad Benefits Await!</h2>
                <div class="content-columns">
                    <div class="column">
                        <img src="<?php echo $root_path_prefix; ?>images/fitness_benefit1.png" alt="Person feeling energetic" class="guide-image">
                        <h3>Boost Your Energy Levels</h3>
                        <p>Regular physical activity can significantly increase your stamina and reduce feelings of fatigue. Feel more alive and tackle your daily tasks with renewed vigor!</p>
                    </div>
                    <div class="column">
                        <img src="<?php echo $root_path_prefix; ?>images/fitness_benefit2.png" alt="Healthy heart illustration" class="guide-image">
                        <h3>Improve Heart Health</h3>
                        <p>Exercise strengthens your heart and improves circulation. It's a key factor in preventing heart disease and maintaining healthy blood pressure.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="guide-section how-to-section">
            <div class="section-content">
                <h2>How to Stay Fit and Healthy: Simple Steps to Success</h2>
                <ol class="steps-list">
                    <li><strong>Set Realistic Goals:</strong> Start small and gradually increase intensity. Consistency is more important than perfection.</li>
                    <li><strong>Find Activities You Enjoy:</strong> Whether it's dancing, cycling, or team sports, enjoyment makes sticking to a routine easier.</li>
                    <li><strong>Prioritize Nutrition:</strong> Fuel your body with a balanced diet rich in whole foods. Proper nutrition complements your fitness efforts.</li>
                    <li><strong>Stay Hydrated:</strong> Water is crucial for performance and recovery. Drink plenty throughout the day.</li>
                    <li><strong>Get Enough Rest:</strong> Your body repairs and strengthens itself during sleep. Aim for 7-9 hours of quality sleep.</li>
                </ol>
                <p>Combining these habits will create a sustainable and effective path to a healthier lifestyle.</p>
            </div>
        </section>

        <section class="guide-section cta-section">
            <div class="section-content">
                <h2>Ready to Take Action?</h2>
                <p>Your journey to peak fitness is unique. Explore our curated list of exercises tailored for various goals and levels. Find the perfect routines to sculpt your body and enhance your well-being.</p>
                <a href="<?php echo $root_path_prefix; ?>exercise.php" class="cta-button">Explore Exercise List Now!</a>
                <p class="cta-subtext">The right exercises are key, and having the right equipment can amplify your results and make your workouts more effective and enjoyable!</p>
            </div>
        </section>

    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>

</html>