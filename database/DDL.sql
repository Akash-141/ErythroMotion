CREATE DATABASE IF NOT EXISTS erythromotion CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE erythromotion;

--
-- Table structure for table `users`
--
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    date_of_birth DATE NULL,
    location VARCHAR(255) NULL,
    blood_group VARCHAR(5) NULL,
    gender ENUM('male', 'female', 'other', 'prefer_not_to_say') NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'suspended', 'pending_verification', 'banned') NOT NULL DEFAULT 'active',
    is_donor BOOLEAN NOT NULL DEFAULT 0,
    donor_status ENUM('unverified', 'verified', 'unavailable') NOT NULL DEFAULT 'unverified',
    last_donation_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Table structure for table `exercises`
--
CREATE TABLE IF NOT EXISTS exercises (
    exercise_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    body_part_targeted VARCHAR(100) NULL,
    category_type VARCHAR(100) NULL,
    difficulty_level ENUM('Beginner', 'Intermediate', 'Advanced') NOT NULL,
    equipment_needed BOOLEAN NOT NULL DEFAULT 0,
    equipment_details VARCHAR(255) NULL,
    image_url VARCHAR(255) NULL,
    video_url VARCHAR(255) NULL,
    gender_target ENUM('Unisex', 'Male', 'Female') DEFAULT 'Unisex',
    notes TEXT NULL,
    is_featured BOOLEAN DEFAULT 0
);

--
-- Table structure for table `user_exercise_plans`
--
CREATE TABLE IF NOT EXISTS user_exercise_plans (
    plan_entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exercise_id INT NOT NULL,
    date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (exercise_id) REFERENCES exercises(exercise_id) ON DELETE CASCADE,
    UNIQUE KEY `user_exercise_unique` (`user_id`, `exercise_id`)
);

--
-- Table structure for table `equipments`
--
CREATE TABLE IF NOT EXISTS equipments (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT NOT NULL DEFAULT 0,
    image_url VARCHAR(255) NULL,
    brand VARCHAR(100) NULL,
    sku VARCHAR(100) NULL,
    specifications TEXT NULL,
    is_featured BOOLEAN NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

--
-- Table structure for table `equipment_reviews`
--
CREATE TABLE IF NOT EXISTS equipment_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL,
    review_title VARCHAR(255) NULL,
    review_text TEXT NULL,
    is_approved BOOLEAN NOT NULL DEFAULT 1,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_id) REFERENCES equipments(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

--
-- Table structure for table `orders`
--
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    order_status VARCHAR(50) NOT NULL DEFAULT 'Pending',
    payment_method VARCHAR(50) NULL,
    shipping_name VARCHAR(100) NOT NULL,
    shipping_address_line1 VARCHAR(255) NOT NULL,
    shipping_address_line2 VARCHAR(255) NULL,
    shipping_city VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20) NOT NULL,
    shipping_phone VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

--
-- Table structure for table `order_items`
--
CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    equipment_id INT NULL,
    quantity INT NOT NULL,
    price_at_purchase DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipments(equipment_id) ON DELETE SET NULL
);

--
-- Table structure for table `user_wishlist_items`
--
CREATE TABLE IF NOT EXISTS user_wishlist_items (
    wishlist_item_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    equipment_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipments(equipment_id) ON DELETE CASCADE,
    UNIQUE KEY `user_equipment_wishlist_unique` (`user_id`, `equipment_id`)
);

--
-- Table structure for table `blog_categories`
--
CREATE TABLE IF NOT EXISTS blog_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL
);

--
-- Table structure for table `blog_posts`
--
CREATE TABLE IF NOT EXISTS blog_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NULL,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT NOT NULL,
    featured_image_url VARCHAR(255) NULL,
    status ENUM('published', 'draft', 'pending_review', 'archived') NOT NULL DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES blog_categories(category_id) ON DELETE SET NULL
);

--
-- Table structure for table `blog_comments`
--
CREATE TABLE IF NOT EXISTS blog_comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    parent_comment_id INT NULL,
    comment_text TEXT NOT NULL,
    is_approved BOOLEAN NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(post_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (parent_comment_id) REFERENCES blog_comments(comment_id) ON DELETE CASCADE
);

--
-- Table structure for table `contact_messages`
--
CREATE TABLE IF NOT EXISTS contact_messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT 0,
    received_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

--
-- Table structure for table `blood_requests`
--
CREATE TABLE IF NOT EXISTS blood_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    requester_user_id INT NOT NULL,
    donor_user_id INT NOT NULL,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    patient_blood_group VARCHAR(5) NOT NULL,
    reason TEXT NULL,
    contact_phone VARCHAR(20) NOT NULL,
    hospital_name VARCHAR(255) NOT NULL,
    required_date DATE NOT NULL,
    request_status ENUM('pending', 'viewed_by_admin', 'donor_contacted', 'fulfilled', 'closed', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (donor_user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
