
--
-- File: admin/add_equipment.php
--
INSERT INTO equipments (name, description, category, price, stock_quantity, image_url, brand, sku, specifications, is_featured) 
VALUES (:name, :description, :category, :price, :stock_quantity, :image_url, :brand, :sku, :specifications, :is_featured);

--
-- File: admin/add_exercise.php
--
INSERT INTO exercises (name, description, body_part_targeted, category_type, difficulty_level, equipment_needed, equipment_details, image_url, video_url, gender_target, notes, is_featured) 
VALUES (:name, :description, :body_part_targeted, :category_type, :difficulty_level, :equipment_needed, :equipment_details, :image_url, :video_url, :gender_target, :notes, :is_featured);

--
-- File: admin/dashboard.php
--
SELECT COUNT(*) FROM users;
SELECT COUNT(*) FROM exercises;
SELECT COUNT(*) FROM equipments;
SELECT COUNT(*) FROM orders;
SELECT COUNT(*) FROM orders WHERE order_status = :status;

--
-- File: admin/delete_equipment.php
--
DELETE FROM equipments WHERE equipment_id = :equipment_id;

--
-- File: admin/delete_exercise.php
--
DELETE FROM exercises WHERE exercise_id = :exercise_id;

--
-- File: admin/edit_blog_category.php
--
SELECT category_id FROM blog_categories WHERE (name = :name OR slug = :slug) AND category_id != :category_id;
UPDATE blog_categories SET name = :name, slug = :slug, description = :description WHERE category_id = :category_id;
SELECT * FROM blog_categories WHERE category_id = :category_id;

--
-- File: admin/edit_blog_post.php
--
SELECT * FROM blog_categories ORDER BY name ASC;
SELECT post_id FROM blog_posts WHERE slug = :slug AND post_id != :post_id;
UPDATE blog_posts SET category_id = :category_id, title = :title, slug = :slug, content = :content, featured_image_url = :featured_image_url, status = :status WHERE post_id = :post_id;
SELECT * FROM blog_posts WHERE post_id = :post_id;

--
-- File: admin/edit_equipment.php
--
UPDATE equipments SET name = :name, description = :description, category = :category, price = :price, stock_quantity = :stock_quantity, image_url = :image_url, brand = :brand, sku = :sku, specifications = :specifications, is_featured = :is_featured WHERE equipment_id = :equipment_id;
SELECT * FROM equipments WHERE equipment_id = :equipment_id;

--
-- File: admin/edit_exercise.php
--
UPDATE exercises SET name = :name, description = :description, body_part_targeted = :body_part_targeted, category_type = :category_type, difficulty_level = :difficulty_level, equipment_needed = :equipment_needed, equipment_details = :equipment_details, image_url = :image_url, video_url = :video_url, gender_target = :gender_target, notes = :notes, is_featured = :is_featured WHERE exercise_id = :exercise_id;
SELECT * FROM exercises WHERE exercise_id = :exercise_id;

--
-- File: admin/edit_user.php
--
SELECT user_id FROM users WHERE email = :email AND user_id != :user_id_to_edit;
UPDATE users SET full_name = :full_name, email = :email, phone_number = :phone_number, date_of_birth = :date_of_birth, location = :location, blood_group = :blood_group, gender = :gender, role = :role, status = :status, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id_to_edit;
UPDATE users SET full_name = :full_name, phone_number = :phone_number, date_of_birth = :date_of_birth, location = :location, gender = :gender, role = :role, status = :status, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id_to_edit; -- (Version with read-only email/blood group)
SELECT username FROM users WHERE user_id = :user_id_to_edit;
SELECT * FROM users WHERE user_id = :user_id_to_edit;

--
-- File: admin/manage_blog_categories.php
--
SELECT * FROM blog_categories WHERE name = :name OR slug = :slug;
INSERT INTO blog_categories (name, slug, description) VALUES (:name, :slug, :description);
SELECT * FROM blog_categories ORDER BY name ASC;

--
-- File: admin/manage_blog_posts.php
--
SELECT COUNT(*) FROM blog_posts;
SELECT bp.post_id, bp.title, bp.status, bp.created_at, u.username AS author_name, bc.name AS category_name FROM blog_posts bp JOIN users u ON bp.user_id = u.user_id LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id ORDER BY bp.created_at DESC LIMIT :limit OFFSET :offset;

--
-- File: admin/manage_donors.php
--
UPDATE users SET donor_status = :donor_status WHERE user_id = :user_id AND is_donor = 1;
SELECT COUNT(*) FROM users WHERE is_donor = 1;
SELECT user_id, full_name, username, email, phone_number, blood_group, donor_status FROM users WHERE is_donor = 1 ORDER BY created_at DESC LIMIT :limit OFFSET :offset;

--
-- File: admin/manage_equipments.php
--
SELECT COUNT(*) FROM equipments;
SELECT equipment_id, name, category, price, stock_quantity, is_featured FROM equipments ORDER BY name ASC, equipment_id ASC LIMIT :limit OFFSET :offset;

--
-- File: admin/manage_exercises.php
--
SELECT COUNT(*) FROM exercises;
SELECT exercise_id, name, category_type, body_part_targeted, difficulty_level, equipment_needed FROM exercises ORDER BY name ASC, exercise_id ASC LIMIT :limit OFFSET :offset;

--
-- File: admin/manage_orders.php
--
SELECT COUNT(*) FROM orders;
SELECT o.order_id, o.order_date, o.total_amount, o.order_status, o.payment_method, u.username AS customer_username, o.shipping_name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT :limit OFFSET :offset;

--
-- File: admin/manage_users.php
--
SELECT COUNT(*) FROM users;
SELECT user_id, full_name, username, email, role, status, created_at FROM users ORDER BY user_id ASC LIMIT :limit OFFSET :offset;

--
-- File: admin/order_details.php
--
UPDATE orders SET order_status = :order_status WHERE order_id = :order_id;
SELECT o.*, u.username FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = :order_id;
SELECT oi.*, eq.name AS equipment_name FROM order_items oi LEFT JOIN equipments eq ON oi.equipment_id = eq.equipment_id WHERE oi.order_id = :order_id;

--
-- File: blood_donation.php
--
SELECT is_donor, donor_status, blood_group FROM users WHERE user_id = :user_id;
UPDATE users SET is_donor = 1, donor_status = 'unverified' WHERE user_id = :user_id;

--
-- File: cart.php
--
SELECT name, price, image_url, stock_quantity FROM equipments WHERE equipment_id = :equipment_id;

--
-- File: checkout.php
--
SELECT phone_number, location, full_name FROM users WHERE user_id = :user_id;
UPDATE equipments SET stock_quantity = stock_quantity - :quantity WHERE equipment_id = :equipment_id;
INSERT INTO orders (user_id, total_amount, payment_method, shipping_name, shipping_address_line1, shipping_address_line2, shipping_city, shipping_postal_code, shipping_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?);
INSERT INTO order_items (order_id, equipment_id, quantity, price_at_purchase) VALUES (?, ?, ?, ?);
SELECT equipment_id, stock_quantity FROM equipments WHERE equipment_id IN (...) FOR UPDATE;

--
-- File: contact.php
--
INSERT INTO contact_messages (user_id, name, email, subject, message) VALUES (:user_id, :name, :email, :subject, :message);

--
-- File: donor_list.php
--
SELECT COUNT(*) FROM users WHERE is_donor = 1 AND donor_status = 'verified';
SELECT user_id, full_name, blood_group, location FROM users WHERE is_donor = 1 AND donor_status = 'verified' ORDER BY created_at DESC LIMIT :limit OFFSET :offset;

--
-- File: equipment_details.php
--
SELECT * FROM equipments WHERE equipment_id = :equipment_id;
SELECT er.*, u.username FROM equipment_reviews er JOIN users u ON er.user_id = u.user_id WHERE er.equipment_id = :equipment_id AND er.is_approved = 1 ORDER BY er.review_date DESC;
SELECT wishlist_item_id FROM user_wishlist_items WHERE user_id = :user_id AND equipment_id = :equipment_id;
SELECT oi.order_item_id FROM order_items oi JOIN orders o ON oi.order_id = o.order_id WHERE o.user_id = :user_id AND oi.equipment_id = :equipment_id LIMIT 1;
SELECT review_id FROM equipment_reviews WHERE equipment_id = :equipment_id AND user_id = :user_id;
INSERT INTO equipment_reviews (equipment_id, user_id, rating, review_title, review_text) VALUES (:equipment_id, :user_id, :rating, :review_title, :review_text);

--
-- File: exercise.php
--
DELETE FROM user_exercise_plans WHERE user_id = :user_id;
DELETE FROM user_exercise_plans WHERE user_id = :user_id AND exercise_id = :exercise_id;
INSERT INTO user_exercise_plans (user_id, exercise_id) VALUES (:user_id, :exercise_id);
SELECT exercise_id FROM user_exercise_plans WHERE user_id = :user_id;
SELECT * FROM exercises ...; -- (Dynamic WHERE/AND clauses)
SELECT e.exercise_id, e.name, e.body_part_targeted FROM user_exercise_plans uep JOIN exercises e ON uep.exercise_id = e.exercise_id WHERE uep.user_id = :user_id ORDER BY uep.date_added DESC;

--
-- File: exercise_details.php
--
SELECT * FROM exercises WHERE exercise_id = :exercise_id;

--
-- File: index.php
--
SELECT exercise_id, name, description, image_url, body_part_targeted FROM exercises WHERE is_featured = 1 ORDER BY RAND() LIMIT 4;
SELECT equipment_id, name, price, image_url, description FROM equipments WHERE is_featured = 1 LIMIT 4;

--
-- File: login.php
--
SELECT user_id, username, password_hash, role, email FROM users WHERE username = :identifier OR email = :identifier;

--
-- File: motionmart.php
--
SELECT equipment_id, name, description, category, price, image_url, brand, is_featured, stock_quantity FROM equipments ORDER BY is_featured DESC, name ASC;
SELECT equipment_id FROM user_wishlist_items WHERE user_id = :user_id;

--
-- File: profile.php
--
SELECT full_name, username, phone_number, email, date_of_birth, location, blood_group, gender FROM users WHERE user_id = :user_id;
UPDATE users SET full_name = :full_name, phone_number = :phone_number, date_of_birth = :date_of_birth, location = :location, blood_group = :blood_group, gender = :gender, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id;
SELECT password_hash FROM users WHERE user_id = :user_id;
UPDATE users SET password_hash = :password_hash, updated_at = CURRENT_TIMESTAMP WHERE user_id = :user_id;
SELECT order_id, order_date, total_amount, order_status FROM orders WHERE user_id = :user_id ORDER BY order_date DESC;
SELECT uep.plan_entry_id, e.exercise_id, e.name, e.image_url, e.body_part_targeted FROM user_exercise_plans uep JOIN exercises e ON uep.exercise_id = e.exercise_id WHERE uep.user_id = :user_id ORDER BY uep.date_added DESC;
DELETE FROM user_exercise_plans WHERE plan_entry_id = :plan_entry_id AND user_id = :user_id;
DELETE FROM user_exercise_plans WHERE user_id = :user_id;
SELECT post_id, title, status, created_at FROM blog_posts WHERE user_id = :user_id ORDER BY created_at DESC;

--
-- File: request_blood.php
--
SELECT user_id, full_name, blood_group, location FROM users WHERE user_id = :user_id AND is_donor = 1 AND donor_status = 'verified';
INSERT INTO blood_requests (requester_user_id, donor_user_id, patient_name, patient_age, patient_blood_group, reason, contact_phone, hospital_name, required_date) VALUES (:requester_user_id, :donor_user_id, :patient_name, :patient_age, :patient_blood_group, :reason, :contact_phone, :hospital_name, :required_date);

--
-- File: request_received.php
--
UPDATE blood_requests SET request_status = :request_status WHERE request_id = :request_id AND donor_user_id = :donor_user_id;
SELECT br.*, u.username AS requester_username, u.full_name AS requester_full_name FROM blood_requests br JOIN users u ON br.requester_user_id = u.user_id WHERE br.donor_user_id = :donor_user_id ORDER BY br.created_at DESC;

--
-- File: search_donor.php
--
SELECT user_id, full_name, blood_group, location FROM users WHERE is_donor = 1 AND donor_status = 'verified' AND blood_group = :blood_group AND location LIKE :location ORDER BY created_at DESC; -- (Example with all params)

--
-- File: signup.php
--
SELECT user_id FROM users WHERE username = :username;
SELECT user_id FROM users WHERE email = :email;
INSERT INTO users (full_name, username, phone_number, email, date_of_birth, location, blood_group, gender, password_hash) VALUES (:full_name, :username, :phone_number, :email, :date_of_birth, :location, :blood_group, :gender, :password_hash);

--
-- File: single_post.php
--
SELECT bp.*, u.username AS author_name, bc.name AS category_name, bc.slug AS category_slug FROM blog_posts bp JOIN users u ON bp.user_id = u.user_id LEFT JOIN blog_categories bc ON bp.category_id = bc.category_id WHERE bp.slug = :slug AND bp.status = 'published';
INSERT INTO blog_comments (post_id, user_id, parent_comment_id, comment_text) VALUES (:post_id, :user_id, :parent_comment_id, :comment_text);
SELECT c.*, u.username FROM blog_comments c JOIN users u ON c.user_id = u.user_id WHERE c.post_id = :post_id AND c.is_approved = 1 ORDER BY c.created_at ASC;
SELECT * FROM blog_categories ORDER BY name ASC;
SELECT title, slug FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 5;

--
-- File: wishlist_handler.php
--
SELECT wishlist_item_id FROM user_wishlist_items WHERE user_id = :user_id AND equipment_id = :equipment_id;
INSERT INTO user_wishlist_items (user_id, equipment_id) VALUES (:user_id, :equipment_id);
DELETE FROM user_wishlist_items WHERE wishlist_item_id = :wishlist_item_id AND user_id = :user_id;
DELETE FROM user_wishlist_items WHERE user_id = :user_id AND equipment_id = :equipment_id;

