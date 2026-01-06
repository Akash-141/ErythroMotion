<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Terms of Service - ErythroMotion";
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
            <h1>Terms of Service</h1>
            <p class="effective-date">Last updated: June 14, 2025</p>

            <h2 class="section-title">1. Agreement to Terms</h2>
            <p>By using our website, erythromotion.com (the "Site"), you agree to be bound by these Terms of Service. If you do not agree to these terms, you may not access or use the Site.</p>
            
            <h2 class="section-title">2. User Accounts</h2>
            <p>When you create an account with us, you must provide information that is accurate, complete, and current at all times. You are responsible for safeguarding the password that you use to access the Site and for any activities or actions under your password.</p>

            <h2 class="section-title">3. User Content</h2>
            <p>Our Site allows you to post content, including blog posts and comments. You are responsible for the content that you post on or through the Site, including its legality, reliability, and appropriateness. By posting content, you grant us the right and license to use, modify, publicly perform, publicly display, reproduce, and distribute such content on and through the Site.</p>

            <h2 class="section-title">4. Health & Medical Disclaimer</h2>
            <p>The content on ErythroMotion, including text, graphics, images, and other material contained on the Site ("Content"), is for informational purposes only. The Content is not intended to be a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition.</p>
            
             <h2 class="section-title">5. Prohibited Uses</h2>
             <p>You may use the Site only for lawful purposes. You agree not to use the Site in any way that violates any applicable national or international law or regulation.</p>

            <h2 class="section-title">6. Termination</h2>
            <p>We may terminate or suspend your account and bar access to the Site immediately, without prior notice or liability, under our sole discretion, for any reason whatsoever and without limitation, including but not limited to a breach of the Terms.</p>

        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
