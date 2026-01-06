<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Privacy Policy - ErythroMotion";
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
            <h1>Privacy Policy for ErythroMotion</h1>
            <p class="effective-date">Last updated: June 14, 2025</p>

            <p>Welcome to ErythroMotion. We are committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website erythromotion.com, including any other media form, media channel, mobile website, or mobile application related or connected thereto (collectively, the “Site”). Please read this privacy policy carefully. If you do not agree with the terms of this privacy policy, please do not access the site.</p>

            <h2 class="section-title">Collection of Your Information</h2>
            <p>We may collect information about you in a variety of ways. The information we may collect on the Site includes:</p>
            <ul>
                <li><strong>Personal Data:</strong> Personally identifiable information, such as your name, shipping address, email address, and telephone number, and demographic information, such as your age, gender, hometown, and interests, that you voluntarily give to us when you register with the Site or when you choose to participate in various activities related to the Site, such as online chat and message boards.</li>
                <li><strong>Health Information:</strong> Information you voluntarily provide related to your health and fitness, such as blood group, BMI, and workout plans. This information is treated with the highest level of confidentiality.</li>
                <li><strong>Derivative Data:</strong> Information our servers automatically collect when you access the Site, such as your IP address, your browser type, your operating system, your access times, and the pages you have viewed directly before and after accessing the Site.</li>
            </ul>

            <h2 class="section-title">Use of Your Information</h2>
            <p>Having accurate information about you permits us to provide you with a smooth, efficient, and customized experience. Specifically, we may use information collected about you via the Site to:</p>
            <ul>
                <li>Create and manage your account.</li>
                <li>Process your orders and manage payments.</li>
                <li>Enable user-to-user communications (e.g., blood requests, blog comments).</li>
                <li>Generate a personal profile about you to make future visits to the Site more personalized.</li>
                <li>Monitor and analyze usage and trends to improve your experience with the Site.</li>
                <li>Notify you of updates to the Site.</li>
            </ul>

            <h2 class="section-title">Security of Your Information</h2>
            <p>We use administrative, technical, and physical security measures to help protect your personal information. While we have taken reasonable steps to secure the personal information you provide to us, please be aware that despite our efforts, no security measures are perfect or impenetrable, and no method of data transmission can be guaranteed against any interception or other type of misuse.</p>

            <h2 class="section-title">Contact Us</h2>
            <p>If you have questions or comments about this Privacy Policy, please contact us at: <a href="contact.php">Contact Page</a></p>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
