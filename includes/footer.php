<?php
$root_path_prefix = $root_path_prefix ?? '';
?>
<footer class="site-footer">
    <div class="footer-content-container">
        <div class="footer-column footer-about">
            <h3 class="footer-heading">ErythroMotion</h3>
            <p>Your comprehensive platform for fitness, health management, and community support. Stay active, stay healthy.</p>
        </div>

        <div class="footer-column footer-sitemap">
            <h3 class="footer-heading">Sitemap</h3>
            <ul class="sitemap-links">
                <li><a href="<?php echo $root_path_prefix; ?>index.php">Home</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>fitness_guide.php">Fitness Guide</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>exercise.php">Exercise List</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>bmi.php">BMI Calculator</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>blood_donation.php">Blood Donation</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>motionmart.php">MotionMart</a></li>
            </ul>
        </div>

        <div class="footer-column footer-quicklinks">
            <h3 class="footer-heading">Quick Links</h3>
            <ul class="sitemap-links">
                <li><a href="<?php echo $root_path_prefix; ?>privacy_policy.php">Privacy Policy</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>terms_of_service.php">Terms of Service</a></li>
                <li><a href="<?php echo $root_path_prefix; ?>faq.php">FAQ</a></li>
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                    <li><a href="<?php echo $root_path_prefix; ?>admin/dashboard.php">Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="footer-column footer-contact">
            <h3 class="footer-heading">Contact Us</h3>
            <p>Email: <a href="mailto:info@erythromotion.com">info@erythromotion.com</a></p>
            <p>Phone: +1 (234) 567-8900</p>
            <div class="footer-social-icons">
                <!-- Social Icons Here -->
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?php echo date("Y"); ?> ErythroMotion. All Rights Reserved.</p>
    </div>
</footer>
