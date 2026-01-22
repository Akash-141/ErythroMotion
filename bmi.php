<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "BMI Calculator - ErythroMotion";

$bmi_result = null;
$bmi_category = '';
$next_step_message = '';
$next_step_link_text = '';
$next_step_link_url = '';
$errors = [];
$form_data = $_POST;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $unit = $form_data['unit'] ?? 'metric';

    if ($unit === 'metric') {
        $height_cm = filter_input(INPUT_POST, 'height_cm', FILTER_VALIDATE_FLOAT);
        $weight_kg = filter_input(INPUT_POST, 'weight_kg', FILTER_VALIDATE_FLOAT);

        if (!$height_cm || $height_cm <= 0) {
            $errors[] = "Please enter a valid height in centimeters.";
        }
        if (!$weight_kg || $weight_kg <= 0) {
            $errors[] = "Please enter a valid weight in kilograms.";
        }

        if (empty($errors)) {
            $height_m = $height_cm / 100;
            $bmi_result = $weight_kg / ($height_m * $height_m);
        }

    } elseif ($unit === 'imperial') {
        $height_ft = filter_input(INPUT_POST, 'height_ft', FILTER_VALIDATE_FLOAT);
        $height_in = filter_input(INPUT_POST, 'height_in', FILTER_VALIDATE_FLOAT);
        $weight_lbs = filter_input(INPUT_POST, 'weight_lbs', FILTER_VALIDATE_FLOAT);

        if ($height_ft === false || $height_ft < 0) {
            $errors[] = "Please enter a valid height in feet.";
        }
        if ($height_in === false || $height_in < 0) {
             $errors[] = "Please enter a valid height in inches.";
        }
        if ((!$height_ft || $height_ft <= 0) && (!$height_in || $height_in <= 0)) {
            $errors[] = "Please enter a valid height.";
        }
        if (!$weight_lbs || $weight_lbs <= 0) {
            $errors[] = "Please enter a valid weight in pounds.";
        }
        
        if (empty($errors)) {
            $total_height_in = ($height_ft * 12) + $height_in;
            $bmi_result = ($weight_lbs / ($total_height_in * $total_height_in)) * 703;
        }
    }

    if ($bmi_result !== null) {
        $bmi_result = round($bmi_result, 1);
        if ($bmi_result < 18.5) {
            $bmi_category = 'Underweight';
            $next_step_message = 'Based on your result, focusing on nutrient-dense foods is recommended.';
            $next_step_link_text = 'See Our Weight Gain Guide';
            $next_step_link_url = $root_path_prefix . 'diet_nutrition.php?prompt=gain';
        } elseif ($bmi_result >= 18.5 && $bmi_result <= 24.9) {
            $bmi_category = 'Normal weight';
            $next_step_message = 'Great job! Explore our resources to maintain your healthy lifestyle.';
            $next_step_link_text = 'Explore Healthy Eating Tips';
            $next_step_link_url = $root_path_prefix . 'diet_nutrition.php?prompt=normal';
        } else { // BMI >= 25 (Overweight or Obesity)
            if ($bmi_result <= 29.9) {
                $bmi_category = 'Overweight';
            } else {
                $bmi_category = 'Obesity';
            }
            $next_step_message = 'A balanced diet and regular exercise are key for weight management.';
            $next_step_link_text = 'See Our Weight Management Guide';
            $next_step_link_url = $root_path_prefix . 'diet_nutrition.php?prompt=loss';
        }
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/bmi.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
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
        <div class="bmi-container">
            <h1 class="bmi-title">Body Mass Index (BMI) Calculator</h1>
            <p class="bmi-intro">BMI is a widely used measure to determine if your weight is in a healthy range. Enter your details below to calculate your BMI.</p>
            
            <div class="bmi-top-layout">
                <div class="calculator-wrapper">
                    <div class="unit-switcher">
                        <button class="unit-tab active" data-unit="metric">Metric (kg, cm)</button>
                        <button class="unit-tab" data-unit="imperial">Imperial (lbs, ft, in)</button>
                    </div>

                    <!-- Metric Form -->
                    <form action="bmi.php#results" method="POST" id="metric-form" class="bmi-form active">
                        <input type="hidden" name="unit" value="metric">
                        <div class="form-group">
                            <label for="height_cm">Height (cm)</label>
                            <input type="number" id="height_cm" name="height_cm" placeholder="e.g., 175" value="<?php echo htmlspecialchars($form_data['height_cm'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="weight_kg">Weight (kg)</label>
                            <input type="number" id="weight_kg" name="weight_kg" placeholder="e.g., 70" value="<?php echo htmlspecialchars($form_data['weight_kg'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="calculate-btn button-primary">Calculate BMI</button>
                    </form>

                    <!-- Imperial Form -->
                    <form action="bmi.php#results" method="POST" id="imperial-form" class="bmi-form">
                        <input type="hidden" name="unit" value="imperial">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="height_ft">Height (ft)</label>
                                <input type="number" id="height_ft" name="height_ft" placeholder="e.g., 5" value="<?php echo htmlspecialchars($form_data['height_ft'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="height_in">Height (in)</label>
                                <input type="number" id="height_in" name="height_in" placeholder="e.g., 9" value="<?php echo htmlspecialchars($form_data['height_in'] ?? '0'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="weight_lbs">Weight (lbs)</label>
                            <input type="number" id="weight_lbs" name="weight_lbs" placeholder="e.g., 154" value="<?php echo htmlspecialchars($form_data['weight_lbs'] ?? ''); ?>" required>
                        </div>
                        <button type="submit" class="calculate-btn button-primary">Calculate BMI</button>
                    </form>

                     <?php if (!empty($errors)): ?>
                        <div class="bmi-result-box error-box" id="results">
                            <h4>Error</h4>
                            <?php foreach ($errors as $error): ?>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($bmi_result !== null && empty($errors)): ?>
                        <div class="bmi-result-box success-box" id="results">
                            <h4>Your BMI Result</h4>
                            <p class="bmi-score"><?php echo htmlspecialchars($bmi_result); ?></p>
                            <p class="bmi-category-text">This is considered: <strong><?php echo htmlspecialchars($bmi_category); ?></strong></p>
                        </div>
                         <!-- New Next Steps Box -->
                        <div class="bmi-next-steps">
                            <h4>What's Next?</h4>
                            <p><?php echo htmlspecialchars($next_step_message); ?></p>
                            <a href="<?php echo htmlspecialchars($next_step_link_url); ?>" class="button-primary next-steps-btn"><?php echo htmlspecialchars($next_step_link_text); ?></a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="bmi-chart-box">
                    <h2>BMI Categories</h2>
                    <p>The Body Mass Index is a general guide. It doesn't account for factors like muscle mass, so results should be considered as an estimate.</p>
                    <table class="bmi-chart-table">
                        <thead>
                            <tr>
                                <th>BMI Range</th>
                                <th>Weight Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Below 18.5</td>
                                <td>Underweight</td>
                            </tr>
                            <tr>
                                <td>18.5 – 24.9</td>
                                <td>Normal or Healthy Weight</td>
                            </tr>
                            <tr>
                                <td>25.0 – 29.9</td>
                                <td>Overweight</td>
                            </tr>
                            <tr>
                                <td>30.0 and Above</td>
                                <td>Obesity</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bmi-info-section">
                <h3>Understanding Your BMI Result</h3>
                <p>While a helpful starting point, BMI does not tell the whole story of your health. It is a screening tool, not a diagnostic one. Your result is one piece of a larger puzzle that includes lifestyle, diet, genetics, and other health markers.</p>

                <h3>Limitations of BMI</h3>
                <ul>
                    <li><strong>Muscle vs. Fat:</strong> BMI does not distinguish between mass from muscle and mass from fat. A very muscular person, like an athlete, may have a high BMI that classifies them as "overweight" even with very low body fat.</li>
                    <li><strong>Body Frame Size:</strong> It doesn't account for differences in body frame size (small, medium, large).</li>
                    <li><strong>Age and Gender:</strong> The standard BMI calculation is the same for all adults, but ideal body composition can vary with age and between genders.</li>
                    <li><strong>Fat Distribution:</strong> It doesn't tell you where fat is stored on your body. Abdominal fat, for instance, poses a greater health risk than fat stored elsewhere.</li>
                </ul>

                <h3>Next Steps After Getting Your Result</h3>
                <p>Regardless of your result, focus on sustainable, healthy habits rather than just the number on the scale. Consider your BMI a prompt to assess your overall lifestyle.</p>
                <ul>
                    <li><strong>If your BMI is in the Normal range:</strong> Congratulations! Continue to focus on maintaining a balanced diet and regular physical activity. Explore our <a href="<?php echo $root_path_prefix; ?>exercise.php">Exercise List</a> to keep your routine exciting.</li>
                    <li><strong>If your BMI is in the Overweight or Obesity range:</strong> This may indicate a higher risk for certain health conditions like heart disease and type 2 diabetes. Consider incorporating more physical activity and consulting our <a href="<?php echo $root_path_prefix; ?>diet_nutrition.php">Diet & Nutrition</a> guides. It's always best to speak with a healthcare provider for personalized advice.</li>
                    <li><strong>If your BMI is in the Underweight range:</strong> This could also indicate potential health risks. It may be beneficial to consult with a doctor or a registered dietitian to ensure you are getting adequate nutrition for your body's needs.</li>
                </ul>
                <p><strong>Disclaimer:</strong> This tool is for informational purposes only. Consult with a qualified healthcare professional for medical advice, diagnosis, or treatment.</p>
            </div>

        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.unit-tab');
            const forms = document.querySelectorAll('.bmi-form');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    const unit = this.dataset.unit;
                    forms.forEach(form => {
                        if (form.id === unit + '-form') {
                            form.classList.add('active');
                        } else {
                            form.classList.remove('active');
                        }
                    });
                });
            });

            const submittedUnit = "<?php echo isset($form_data['unit']) ? $form_data['unit'] : 'metric'; ?>";
            document.querySelector(`.unit-tab[data-unit="${submittedUnit}"]`).click();
        });
    </script>
</body>
</html>

