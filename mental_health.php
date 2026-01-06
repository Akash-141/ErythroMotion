<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Mental Health & Well-being - ErythroMotion";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/mental_health.css"> <!-- New CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: 0; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <header class="mental-health-header">
            <div class="header-content">
                <h1>Nurturing Your Mental Well-being</h1>
                <p>A healthy body and a healthy mind go hand-in-hand. Explore resources to help you find balance, manage stress, and thrive.</p>
            </div>
        </header>

        <div class="mh-container">
            <!-- Disclaimer Section -->
            <section class="disclaimer-box">
                <p><strong><i class="fas fa-info-circle"></i> Important Disclaimer:</strong> The information provided here is for educational purposes only and is not a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of a qualified mental health professional or other qualified health provider with any questions you may have regarding a medical condition.</p>
            </section>

            <!-- Content Grid -->
            <section class="mh-content-grid">
                <div class="mh-card">
                    <div class="mh-card-icon"><i class="fas fa-brain"></i></div>
                    <h3>The Mind-Body Connection</h3>
                    <p>Discover how regular physical activity can be a powerful tool for improving your mood, reducing symptoms of anxiety, and combating depression. Exercise releases endorphins, which act as natural mood elevators.</p>
                    <a href="exercise.php" class="button-secondary">Explore Exercises</a>
                </div>

                <div class="mh-card">
                    <div class="mh-card-icon"><i class="fas fa-leaf"></i></div>
                    <h3>Stress Management Techniques</h3>
                    <p>Learn simple yet effective techniques to manage daily stress. Practices like deep-breathing exercises, mindfulness, and journaling can help calm your nervous system and improve your ability to cope with life's challenges.</p>
                </div>

                <div class="mh-card">
                    <div class="mh-card-icon"><i class="fas fa-moon"></i></div>
                    <h3>The Importance of Quality Sleep</h3>
                    <p>Sleep is not a luxury; it's essential for mental and emotional health. Quality sleep helps regulate mood, improve concentration and memory, and allows your body to repair itself, both physically and mentally.</p>
                </div>

                <div class="mh-card">
                    <div class="mh-card-icon"><i class="fas fa-battery-quarter"></i></div>
                    <h3>Recognizing & Preventing Burnout</h3>
                    <p>Pushing yourself is great, but it's vital to recognize the signs of burnout. Chronic fatigue, lack of motivation, and irritability can be signs that you need to prioritize rest and recovery to avoid long-term consequences.</p>
                </div>
            </section>

            <!-- Guided Relaxation Section (Phase 2 Placeholder) -->
            <section class="guided-relaxation-section">
                <h2 class="section-title">Guided Relaxation</h2>
                <p class="section-intro">Take a few moments for yourself. These guided videos can help you de-stress and recenter your mind. More guided sessions coming soon!</p>
                <div class="video-grid">
                    <div class="video-wrapper">
                        <iframe src="https://www.youtube.com/embed/inpok4MKVLM" title="5-Minute Meditation You Can Do Anywhere" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                     <div class="video-wrapper">
                        <iframe src="https://www.youtube.com/embed/869_ciJ1v_c" title="10-Minute Guided Breathing Meditation" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
