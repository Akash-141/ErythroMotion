<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Blood Donation - Save a Life";
$current_user_id = $_SESSION['user_id'] ?? null;

$user_donor_status = null;
$feedback_message = '';
$feedback_type = '';

include __DIR__ . '/includes/db_connect.php';

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // If a user is logged in, fetch their current donor status
    if ($current_user_id) {
        $stmt = $pdo->prepare("SELECT is_donor, donor_status, blood_group FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $current_user_id]);
        $user_donor_status = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Handle the 'Register as Donor' form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_as_donor'])) {
        if (!$current_user_id) {
            header("Location: login.php?redirect=blood_donation.php");
            exit();
        }

        // Check if user has a blood group set, which is required
        if (empty($user_donor_status['blood_group'])) {
            $feedback_message = "Please set your blood group in your profile before registering as a donor.";
            $feedback_type = "error";
        } else {
            // Update the user's record to mark them as a donor
            $update_stmt = $pdo->prepare("UPDATE users SET is_donor = 1, donor_status = 'unverified' WHERE user_id = :user_id");
            $update_stmt->execute([':user_id' => $current_user_id]);
            
            $_SESSION['feedback_message'] = "Thank you! You have been successfully registered as a donor. Your status is pending verification.";
            header("Location: blood_donation.php");
            exit();
        }
    }
    
    // Handle session feedback messages
    if (isset($_SESSION['feedback_message'])) {
        $feedback_message = $_SESSION['feedback_message'];
        unset($_SESSION['feedback_message']);
    }

} catch (PDOException $e) {
    // In a real application, you would log this error and show a generic message
    $page_errors[] = "A database error occurred. Please try again later.";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/blood_donation.css"> <!-- New CSS file -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style> 
        body { display: flex; flex-direction: column; min-height: 100vh; }
        main { flex-grow: 1; padding: 0; background-color: #f4f7f6; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <main>
        <header class="donation-header">
            <div class="header-content">
                <h1>Give the Gift of Life</h1>
                <p>One blood donation can save up to three lives. Join our community of heroes today.</p>
            </div>
        </header>

        <div class="donation-container">
            <?php if ($feedback_message): ?>
                <div class="message <?php echo $feedback_type === 'error' ? 'error-message' : 'success-message'; ?> global-message">
                    <p><?php echo htmlspecialchars($feedback_message); ?></p>
                </div>
            <?php endif; ?>

            <!-- Donor Registration Call to Action -->
            <section class="cta-section register-cta">
                <?php if ($current_user_id): ?>
                    <?php if ($user_donor_status && $user_donor_status['is_donor']): ?>
                        <div class="already-donor-message">
                            <h3><i class="fas fa-check-circle"></i> You are a Registered Donor!</h3>
                            <p>Thank you for your commitment. Your current status is: <strong><?php echo htmlspecialchars(ucfirst($user_donor_status['donor_status'])); ?></strong>.</p>
                            <p>You can find donors or manage your profile from the links above.</p>
                        </div>
                    <?php elseif ($user_donor_status && empty($user_donor_status['blood_group'])): ?>
                        <div class="action-box">
                             <h3>Want to Become a Donor?</h3>
                            <p>Your blood group is not set in your profile. Please update it to register as a donor.</p>
                            <a href="profile.php" class="button-primary">Update Profile</a>
                        </div>
                    <?php else: ?>
                        <div class="action-box">
                            <h3>Ready to Make a Difference?</h3>
                            <p>By clicking the button below, you agree to be listed as a potential blood donor on our platform. Your contact information will be made available to verified requests.</p>
                            <form action="blood_donation.php" method="POST">
                                <button type="submit" name="register_as_donor" class="button-primary register-btn">Register as a Donor</button>
                            </form>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="action-box">
                        <h3>Join Our Lifesaving Community</h3>
                        <p>Please log in or create an account to register as a blood donor.</p>
                        <a href="login.php?redirect=blood_donation.php" class="button-primary">Login to Register</a>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Informational Sections -->
            <section class="info-section">
                <h2 class="section-title">Why Your Donation Matters</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <i class="fas fa-heartbeat"></i>
                        <h3>Saves Lives</h3>
                        <p>Blood is essential for surgeries, cancer treatment, chronic illnesses, and traumatic injuries. Your single donation can help multiple people in need.</p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-medkit"></i>
                        <h3>Supports a Healthy Community</h3>
                        <p>A ready and available blood supply is crucial for emergency preparedness. By donating, you strengthen the health infrastructure of your community.</p>
                    </div>
                    <div class="info-card">
                        <i class="fas fa-hand-holding-heart"></i>
                        <h3>A Generous Act</h3>
                        <p>Donating blood costs nothing but your time and a little bit of courage. It's one of the most selfless and impactful gifts you can give.</p>
                    </div>
                </div>
            </section>

            <section class="info-section eligibility">
                 <h2 class="section-title">Are You Eligible to Donate?</h2>
                 <p class="section-intro">While requirements can vary slightly by location, here are some general guidelines to see if you are eligible to donate blood.</p>
                 <ul>
                    <li><strong>Age:</strong> Typically between 18 and 65 years old.</li>
                    <li><strong>Weight:</strong> Must weigh at least 50 kg (approx. 110 lbs).</li>
                    <li><strong>Health:</strong> You must be in good general health and feeling well on the day of donation.</li>
                    <li><strong>Travel & Medical History:</strong> Certain travel histories or medical conditions may temporarily or permanently defer you from donating.</li>
                 </ul>
                 <p class="disclaimer">This is a general guide. Please consult with a local blood bank or healthcare professional for specific eligibility criteria in your area.</p>
            </section>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
