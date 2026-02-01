<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Diet & Nutrition Guide - ErythroMotion";

// Recommendation system based on prompt from BMI page
$prompt = $_GET['prompt'] ?? '';
$recommendation_html = '';

if ($prompt === 'gain') {
    $recommendation_html = '<div class="recommendation-box"><h3><i class="fas fa-star"></i> Recommended for You: Healthy Weight Gain</h3><p>Based on your BMI result, focusing on nutrient-dense foods can help you achieve a healthy weight. Start with our guide below!</p></div>';
} elseif ($prompt === 'loss') {
    $recommendation_html = '<div class="recommendation-box"><h3><i class="fas fa-star"></i> Recommended for You: Sustainable Weight Loss</h3><p>Based on your BMI result, combining a balanced diet with exercise is key. Explore our tips for sustainable weight management.</p></div>';
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/diet_nutrition.css"> <!-- [cite: diet_nutrition_css] -->
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
        <div class="nutrition-container">
            <div class="nutrition-header">
                <h1>Diet & Nutrition Hub</h1>
                <p>Fuel your body, optimize your performance, and achieve your health goals with our expert nutritional guidance.</p>
            </div>

            <?php echo $recommendation_html; // Display recommendation box if prompt exists ?>

            <!-- Tab Navigation -->
            <div class="tabs-container">
                <button class="tab-button" data-tab="normal-weight">Healthy Eating</button>
                <button class="tab-button" data-tab="underweight">Weight Gain Plan</button>
                <button class="tab-button" data-tab="overweight">Weight Management</button>
            </div>

            <!-- Tab Content -->
            <div class="tab-content-container">
                <!-- Healthy Eating / Normal Weight Content -->
                <div id="normal-weight" class="tab-content">
                    <h2>Foundations of Healthy Eating</h2>
                    <p>Maintaining a healthy weight is about balance. Focus on whole foods, mindful eating, and creating a sustainable lifestyle. Here are the core principles:</p>
                    <ul>
                        <li><strong>Balanced Macronutrients:</strong> Ensure each meal contains a good source of lean protein (chicken, fish, beans), complex carbohydrates (oats, brown rice, quinoa), and healthy fats (avocado, nuts, olive oil).</li>
                        <li><strong>Eat the Rainbow:</strong> Consume a wide variety of fruits and vegetables to get a broad spectrum of vitamins, minerals, and antioxidants.</li>
                        <li><strong>Stay Hydrated:</strong> Water is essential for metabolism, energy levels, and overall bodily functions. Aim for 8-10 glasses per day.</li>
                        <li><strong>Mindful Eating:</strong> Pay attention to your body's hunger and fullness cues. Eat slowly and savor your food to prevent overeating.</li>
                        <li><strong>Consistent Exercise:</strong> Pair your healthy diet with regular physical activity. Explore our <a href="exercise.php">Exercise List</a> to keep your routine exciting.</li>
                    </ul>
                </div>

                <!-- Weight Gain / Underweight Content -->
                <div id="underweight" class="tab-content">
                    <h2>Nutrition for Healthy Weight Gain</h2>
                    <p>For those with a low BMI, the goal is to gain weight healthily by focusing on nutrient-dense foods, not just empty calories. This supports muscle growth and overall well-being.</p>
                    <ul>
                        <li><strong>Calorie Surplus:</strong> You need to consume more calories than your body burns. Aim for an extra 300-500 quality calories per day to start.</li>
                        <li><strong>Focus on Protein:</strong> Protein is the building block of muscle. Include sources like eggs, dairy, lean meats, and legumes in every meal. A protein shake can also be a convenient supplement.</li>
                        <li><strong>Choose Healthy Fats:</strong> Healthy fats are calorie-dense and provide many health benefits. Add nuts, seeds, avocado, and olive oil to your meals.</li>
                        <li><strong>Eat Frequent Meals:</strong> Instead of three large meals, try eating 5-6 smaller, nutrient-rich meals and snacks throughout the day to increase your overall intake without feeling overly full.</li>
                        <li><strong>Smart Snacking:</strong> Opt for snacks like Greek yogurt with granola, trail mix, fruit smoothies with protein powder, or whole-grain crackers with nut butter.</li>
                        <li><strong>Combine with Strength Training:</strong> To ensure the extra calories are used to build muscle, not just fat, incorporate regular strength training. This signals your body to repair and build muscle tissue.</li>
                    </ul>
                </div>

                <!-- Weight Management / Overweight Content -->
                <div id="overweight" class="tab-content">
                    <h2>A Guide to Sustainable Weight Management</h2>
                    <p>For those with a high BMI, successful weight management is about creating long-term healthy habits, not short-term diets. A combination of balanced nutrition and consistent exercise is key to motivate you.</p>
                    <ul>
                        <li><strong>Moderate Calorie Deficit:</strong> Aim to consume slightly fewer calories than your body needs. Avoid crash diets, as they are often unsustainable and can slow your metabolism. A deficit of 300-500 calories is a good starting point.</li>
                        <li><strong>Prioritize Protein & Fiber:</strong> Foods high in protein and fiber (like vegetables, legumes, and whole grains) help you feel full and satisfied, reducing the likelihood of overeating.</li>
                        <li><strong>Limit Processed Foods & Sugars:</strong> Reduce your intake of sugary drinks, processed snacks, and fast food, which are often high in calories but low in nutrients and don't keep you full for long.</li>
                        <li><strong>Control Portion Sizes:</strong> Be mindful of how much you are eating. Using smaller plates and reading food labels can help you manage portion control effectively.</li>
                        <li><strong>Incorporate Regular Exercise:</strong> Combine both cardiovascular exercise (like running or cycling) to burn calories and strength training to build muscle, which boosts your metabolism. Find a plan on our <a href="exercise.php">Exercise List</a> and get moving!</li>
                    </ul>
                </div>
            </div>
             <!-- Disclaimer Section -->
            <div class="disclaimer-box">
                <p><strong><i class="fas fa-exclamation-triangle"></i> Disclaimer:</strong> The information provided on this page is for educational purposes only. It is not intended as a substitute for professional medical advice, diagnosis, or treatment. Always seek the advice of your physician or other qualified health provider with any questions you may have regarding a medical condition or dietary changes.</p>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabsContainer = document.querySelector('.tabs-container');
            const contentContainer = document.querySelector('.tab-content-container');
            const tabs = tabsContainer.querySelectorAll('.tab-button');
            const contents = contentContainer.querySelectorAll('.tab-content');
            
            function switchTab(targetId) {
                tabs.forEach(tab => tab.classList.remove('active'));
                contents.forEach(content => content.classList.remove('active'));

                const targetTab = tabsContainer.querySelector(`[data-tab="${targetId}"]`);
                const targetContent = contentContainer.querySelector(`#${targetId}`);

                if (targetTab && targetContent) {
                    targetTab.classList.add('active');
                    targetContent.classList.add('active');
                }
            }

            tabsContainer.addEventListener('click', function(e) {
                if (e.target.matches('.tab-button')) {
                    const targetId = e.target.dataset.tab;
                    switchTab(targetId);
                }
            });

            // Handle prompt from URL to set the initial active tab
            const prompt = "<?php echo $prompt; ?>";
            let initialTab = 'normal-weight'; // Default tab
            if (prompt === 'gain') {
                initialTab = 'underweight';
            } else if (prompt === 'loss') {
                initialTab = 'overweight';
            }
            
            switchTab(initialTab);
        });
    </script>
</body>
</html>

