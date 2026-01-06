<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = ""; 
$page_title = "Weather & Health Outlook - ErythroMotion";
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/weather_health.css"> <!-- New CSS file -->
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
        <div class="weather-container">
            <h1 class="weather-page-title">Weather & Health Outlook</h1>
            
            <!-- Weather Dashboard -->
            <div class="weather-dashboard" id="weather-dashboard">
                <div class="dashboard-header">
                    <h2>Current Conditions in <span id="location-name">Loading...</span></h2>
                    <div id="search-container">
                        <input type="text" id="location-input" placeholder="Enter City or Zip Code">
                        <button id="search-btn">Search</button>
                    </div>
                </div>
                
                <div id="dashboard-content" class="loading">
                    <!-- Loading Spinner -->
                    <div class="spinner"></div>

                    <!-- Weather Data (populated by JS) -->
                    <div class="weather-grid">
                        <div class="weather-card main-temp">
                            <i class="fas fa-temperature-high"></i>
                            <div class="card-content">
                                <h4>Temperature</h4>
                                <p><span id="temp-c">--</span>°C / <span id="temp-f">--</span>°F</p>
                                <small>Feels like <span id="feels-like-c">--</span>°C</small>
                            </div>
                        </div>
                        <div class="weather-card condition">
                             <img id="condition-icon" src="" alt="Weather condition icon" style="width: 50px; height: 50px;">
                            <div class="card-content">
                                <h4>Condition</h4>
                                <p id="condition-text">--</p>
                            </div>
                        </div>
                        <div class="weather-card">
                            <i class="fas fa-tint"></i>
                            <div class="card-content">
                                <h4>Humidity</h4>
                                <p id="humidity">--%</p>
                            </div>
                        </div>
                        <div class="weather-card">
                            <i class="fas fa-wind"></i>
                            <div class="card-content">
                                <h4>Wind</h4>
                                <p><span id="wind-kph">--</span> kph</p>
                            </div>
                        </div>
                         <div class="weather-card">
                            <i class="fas fa-smog"></i>
                            <div class="card-content">
                                <h4>Air Quality (AQI)</h4>
                                <p id="aqi-us-epa">--</p>
                            </div>
                        </div>
                        <div class="weather-card">
                            <i class="fas fa-sun"></i>
                            <div class="card-content">
                                <h4>UV Index</h4>
                                <p id="uv-index">--</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Today's Outlook Section (populated by JS) -->
                <div id="outlook-section" class="outlook-box" style="display: none;">
                    <h3><i class="fas fa-lightbulb"></i> Today's Health Outlook</h3>
                    <p id="outlook-text"></p>
                    <div id="outlook-tips"></div>
                </div>
            </div>

            <!-- Static Informational Content -->
            <div class="info-section">
                <h2>How Weather Impacts Your Health & Exercise</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h3><i class="fas fa-thermometer-three-quarters"></i> Exercising in the Heat</h3>
                        <p>High temperatures force your heart to work harder to cool your body down. This increases the risk of heat cramps, heat exhaustion, and heatstroke. Always prioritize hydration and consider exercising during cooler parts of the day, like early morning or late evening.</p>
                    </div>
                     <div class="info-card">
                        <h3><i class="fas fa-snowflake"></i> Exercising in the Cold</h3>
                        <p>Cold weather makes your body work harder to stay warm, which can be a great cardiovascular workout. However, it also constricts airways, which can be risky for those with asthma. Remember to dress in layers, warm up thoroughly, and protect your extremities (hands, ears, and face).</p>
                    </div>
                     <div class="info-card">
                        <h3><i class="fas fa-lungs-virus"></i> Understanding Air Quality</h3>
                        <p>The Air Quality Index (AQI) measures air pollution. When AQI is high (above 100), outdoor exercise can irritate your lungs and may be harmful, especially for sensitive groups. On high AQI days, it's best to choose an indoor workout from our exercise list.</p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const apiKey = '370dda270d2c4b189fc115650250606'; // <-- IMPORTANT: REPLACE WITH YOUR KEY
            let defaultLocation = 'Jessore, Bangladesh';

            const locationNameEl = document.getElementById('location-name');
            const dashboardContentEl = document.getElementById('dashboard-content');
            const searchInput = document.getElementById('location-input');
            const searchBtn = document.getElementById('search-btn');
            
            const tempCEl = document.getElementById('temp-c');
            const tempFEl = document.getElementById('temp-f');
            const feelsLikeCEl = document.getElementById('feels-like-c');
            const conditionIconEl = document.getElementById('condition-icon');
            const conditionTextEl = document.getElementById('condition-text');
            const humidityEl = document.getElementById('humidity');
            const windKphEl = document.getElementById('wind-kph');
            const aqiUsEpaEl = document.getElementById('aqi-us-epa');
            const uvIndexEl = document.getElementById('uv-index');

            const outlookSectionEl = document.getElementById('outlook-section');
            const outlookTextEl = document.getElementById('outlook-text');
            const outlookTipsEl = document.getElementById('outlook-tips');

            function getOutlook(data) {
                const tempC = data.current.temp_c;
                const aqi = data.current.air_quality['us-epa-index'];
                const uv = data.current.uv;

                let safetyRating = 'good'; // good, caution, danger
                let mainMessage = '';
                let tips = [];

                // Evaluate conditions - simple example logic
                if (tempC > 32 || uv > 8 || aqi > 3) {
                    safetyRating = 'danger';
                    mainMessage = 'Conditions are challenging for outdoor activities today.';
                    if (tempC > 32) tips.push('<strong>High Heat Risk:</strong> Avoid strenuous activity outdoors. Hydrate frequently.');
                    if (uv > 8) tips.push('<strong>High UV Index:</strong> Use sunscreen and wear protective clothing.');
                    if (aqi > 3) tips.push('<strong>Poor Air Quality:</strong> It is strongly recommended to exercise indoors.');
                } else if (tempC > 28 || uv > 6 || aqi > 2) {
                    safetyRating = 'caution';
                    mainMessage = 'Conditions require some caution for outdoor exercise.';
                    if (tempC > 28) tips.push('<strong>Warm Weather:</strong> Remember to stay hydrated and take breaks.');
                    if (uv > 6) tips.push('<strong>High UV Index:</strong> Sun protection is recommended.');
                     if (aqi > 2) tips.push('<strong>Moderate Air Quality:</strong> Sensitive groups should consider reducing outdoor exertion.');
                } else if (tempC < 5) {
                    safetyRating = 'caution';
                    mainMessage = 'It\'s cold outside. Remember to prepare properly.';
                    tips.push('<strong>Cold Weather:</strong> Dress in layers and warm up thoroughly before your workout.');
                } else {
                    mainMessage = 'Conditions are excellent for outdoor activities!';
                    tips.push('It\'s a great day to get outside and be active.');
                }

                // Update the DOM
                outlookSectionEl.className = 'outlook-box ' + safetyRating;
                outlookTextEl.textContent = mainMessage;
                outlookTipsEl.innerHTML = '<ul>' + tips.map(tip => `<li>${tip}</li>`).join('') + '</ul>';
                outlookSectionEl.style.display = 'block';
            }

            function fetchWeather(location) {
                dashboardContentEl.classList.add('loading');
                outlookSectionEl.style.display = 'none';

                fetch(`https://api.weatherapi.com/v1/current.json?key=${apiKey}&q=${encodeURIComponent(location)}&aqi=yes`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('City not found or network response was not ok.');
                        }
                        return response.json();
                    })
                    .then(data => {
                        // Populate dashboard
                        locationNameEl.textContent = `${data.location.name}, ${data.location.country}`;
                        tempCEl.textContent = data.current.temp_c;
                        tempFEl.textContent = data.current.temp_f;
                        feelsLikeCEl.textContent = data.current.feelslike_c;
                        conditionIconEl.src = data.current.condition.icon;
                        conditionTextEl.textContent = data.current.condition.text;
                        humidityEl.textContent = data.current.humidity;
                        windKphEl.textContent = data.current.wind_kph;
                        aqiUsEpaEl.textContent = `${data.current.air_quality['us-epa-index']} (${getAqiCategory(data.current.air_quality['us-epa-index'])})`;
                        uvIndexEl.textContent = data.current.uv;
                        
                        // Generate and display outlook
                        getOutlook(data);

                        dashboardContentEl.classList.remove('loading');
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        dashboardContentEl.classList.remove('loading');
                        locationNameEl.textContent = 'Error';
                        outlookTextEl.textContent = `Could not fetch weather data. Please check the city name or your API key.`;
                        outlookTipsEl.innerHTML = '';
                        outlookSectionEl.className = 'outlook-box danger';
                        outlookSectionEl.style.display = 'block';
                    });
            }

            function getAqiCategory(index) {
                if (index <= 1) return 'Good';
                if (index <= 2) return 'Moderate';
                if (index <= 3) return 'Unhealthy for Sensitive Groups';
                if (index <= 4) return 'Unhealthy';
                if (index <= 5) return 'Very Unhealthy';
                return 'Hazardous';
            }

            searchBtn.addEventListener('click', () => {
                const newLocation = searchInput.value.trim();
                if (newLocation) {
                    fetchWeather(newLocation);
                }
            });

            searchInput.addEventListener('keyup', (event) => {
                if (event.key === 'Enter') {
                    searchBtn.click();
                }
            });

            // Initial load
            fetchWeather(defaultLocation);
        });
    </script>
</body>
</html>
