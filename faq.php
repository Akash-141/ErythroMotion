<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Frequently Asked Questions (FAQ) - ErythroMotion";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/static_pages.css"> <!-- New CSS file -->
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: var(--spacing-lg) 0; background-color: #f8f9fa; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <div class="static-container">
            <h1>Frequently Asked Questions (FAQ)</h1>
            <p class="section-intro">Find answers to common questions about ErythroMotion's features and services.</p>

            <div class="faq-accordion">
                <div class="faq-item">
                    <button class="faq-question">What is ErythroMotion?</button>
                    <div class="faq-answer">
                        <p>ErythroMotion is a comprehensive health and fitness platform designed to help you track your fitness, access exercise guides, use health tools like a BMI calculator, buy equipment from our MotionMart, and engage with a community through our blog and blood donation features.</p>
                    </div>
                </div>

                 <div class="faq-item">
                    <button class="faq-question">How does the blood donation feature work?</button>
                    <div class="faq-answer">
                        <p>Our blood donation system is a community-driven feature. Users can voluntarily register as donors. Other users in need can then search the list of verified donors and send a request. For privacy and safety, all communication is initially moderated by our admin team, who will facilitate contact between the requester and the potential donor.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">Is the information on this site medical advice?</button>
                    <div class="faq-answer">
                        <p>No. All content on ErythroMotion, including articles on our blog, fitness guides, and health tools, is for informational and educational purposes only. It is not a substitute for professional medical advice, diagnosis, or treatment. Always consult with a qualified healthcare provider for any health concerns.</p>
                    </div>
                </div>

                <div class="faq-item">
                    <button class="faq-question">How do I track my orders from MotionMart?</button>
                    <div class="faq-answer">
                        <p>You can view your complete order history by visiting your "My Profile" page and navigating to the "My Order History" section. There, you will find a list of all your past orders and can view the details for each one.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqItems = document.querySelectorAll('.faq-item');
            faqItems.forEach(item => {
                const questionButton = item.querySelector('.faq-question');
                const answerDiv = item.querySelector('.faq-answer');

                questionButton.addEventListener('click', () => {
                    const is_open = item.classList.contains('active');
                    // Optional: close all others when one is opened
                    // faqItems.forEach(i => i.classList.remove('active')); 

                    if (!is_open) {
                        item.classList.add('active');
                    } else {
                        item.classList.remove('active');
                    }
                });
            });
        });
    </script>
</body>
</html>
