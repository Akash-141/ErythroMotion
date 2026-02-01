<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Contact Us - ErythroMotion";

$errors = [];
$success_message = "";
$form_data = $_POST;
$is_user_logged_in = isset($_SESSION['user_id']);
$user_email = '';
$user_name = '';

// If user is logged in, pre-fill their name and email
if ($is_user_logged_in) {
    $user_email = $_SESSION['email'] ?? ''; 
    $user_name = $_SESSION['username'] ?? '';
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($form_data['name'] ?? '');
    $email = trim($form_data['email'] ?? '');
    $subject = trim($form_data['subject'] ?? '');
    $message = trim($form_data['message'] ?? '');

    if (empty($name)) {
        $errors[] = "Your name is required.";
    }
    if (empty($email)) {
        $errors[] = "Your email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($subject)) {
        $errors[] = "A subject for your message is required.";
    }
    if (empty($message)) {
        $errors[] = "The message body cannot be empty.";
    }

    if (empty($errors)) {
        // --- SAVE MESSAGE TO DATABASE ---
        try {
            include __DIR__ . '/includes/db_connect.php'; // [cite: db_connect.php]
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (:user_id, :name, :email, :subject, :message)";
            $stmt = $pdo->prepare($sql);

            $current_user_id = $_SESSION['user_id'] ?? null; // Get logged-in user ID, or null if not logged in

            $stmt->bindParam(':user_id', $current_user_id, PDO::PARAM_INT); // This will bind NULL if $current_user_id is null
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':subject', $subject);
            $stmt->bindParam(':message', $message);
            
            $stmt->execute();

            $success_message = "Thank you for contacting us, " . htmlspecialchars($name) . "! Your message has been sent to our team and we will get back to you shortly.";
            $form_data = []; // Clear form data after successful submission

        } catch (PDOException $e) {
            $errors[] = "We're sorry, there was a problem sending your message. Please try again later.";
            // In a real application, you would log the error: error_log($e->getMessage());
        }
    }
} else {
    // Pre-fill form data for logged-in user on initial GET request
    if ($is_user_logged_in) {
        $form_data['name'] = $user_name;
        $form_data['email'] = $user_email;
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/contact.css"> <!-- [cite: contact_css_page] -->
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
            padding: var(--spacing-lg) var(--spacing-md);
            background-color: #f4f7f6;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <div class="contact-container">
            <div class="contact-header">
                <h1>Get In Touch</h1>
                <p>We'd love to hear from you! Whether you have a question, feedback, or need support, please don't hesitate to reach out.</p>
            </div>

            <div class="contact-layout">
                <div class="contact-form-wrapper">
                    <?php if ($success_message): ?>
                        <div class="message success-message">
                            <p><?php echo $success_message; ?></p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($errors)): ?>
                        <div class="message error-message">
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <form action="contact.php" method="POST" class="contact-form" novalidate>
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="name">Your Name</label>
                                    <input type="text" id="name" name="name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label for="email">Your Email</label>
                                    <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?php echo htmlspecialchars($form_data['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="subject">Subject</label>
                                <input type="text" id="subject" name="subject" placeholder="What is your message about?" value="<?php echo htmlspecialchars($form_data['subject'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="message">Message</label>
                                <textarea id="message" name="message" rows="6" placeholder="Write your message here..." required><?php echo htmlspecialchars($form_data['message'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="button-primary submit-btn">Send Message</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="contact-info-wrapper">
                    <h3>Contact Information</h3>
                    <p>You can also reach us through the following channels. We're available during standard business hours.</p>
                    <ul class="contact-details-list">
                        <li><i class="fas fa-envelope"></i> <span>Email:</span> <a href="mailto:info@erythromotion.com">info@erythromotion.com</a></li>
                        <li><i class="fas fa-phone-alt"></i> <span>Phone:</span> +1 (234) 567-8900</li>
                        <li><i class="fas fa-map-marker-alt"></i> <span>Address:</span> 123 Fitness Lane, Health City, HC 54321</li>
                    </ul>
                    <div class="contact-socials">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

