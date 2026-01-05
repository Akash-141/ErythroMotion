<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../";

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php';
$page_title = "Add New Equipment - Admin";
$errors = [];
$form_data = $_POST;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($form_data['name'] ?? '');
    $description = trim($form_data['description'] ?? '');
    $category = trim($form_data['category'] ?? '');
    $price = trim($form_data['price'] ?? '');
    $stock_quantity = trim($form_data['stock_quantity'] ?? '');
    $image_url = trim($form_data['image_url'] ?? '');
    $brand = trim($form_data['brand'] ?? '');
    $sku = trim($form_data['sku'] ?? '');
    $specifications = trim($form_data['specifications'] ?? '');
    $is_featured = isset($form_data['is_featured']) ? 1 : 0;

    if (empty($name)) {
        $errors[] = "Equipment name is required.";
    }
    if (empty($price)) {
        $errors[] = "Price is required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $errors[] = "Price must be a valid positive number.";
    }
    if ($stock_quantity === '') {
        $errors[] = "Stock quantity is required.";
    } elseif (!is_numeric($stock_quantity) || intval($stock_quantity) < 0 || strval(intval($stock_quantity)) !== $stock_quantity) {
        $errors[] = "Stock quantity must be a valid non-negative integer.";
    } else {
        $stock_quantity = intval($stock_quantity);
    }

    if (!empty($sku)) {
        try {
            $pdo_check = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo_check->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $stmt_check_sku = $pdo_check->prepare("SELECT equipment_id FROM equipments WHERE sku = :sku");
            $stmt_check_sku->bindParam(':sku', $sku);
            $stmt_check_sku->execute();
            if ($stmt_check_sku->fetch()) {
                $errors[] = "SKU already exists. It must be unique.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database check failed: " . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "INSERT INTO equipments (name, description, category, price, stock_quantity, image_url, brand, sku, specifications, is_featured) 
                    VALUES (:name, :description, :category, :price, :stock_quantity, :image_url, :brand, :sku, :specifications, :is_featured)";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':brand', $brand);
            $stmt->bindParam(':sku', $sku);
            $stmt->bindParam(':specifications', $specifications);
            $stmt->bindParam(':is_featured', $is_featured, PDO::PARAM_INT);

            $stmt->execute();
            header("Location: manage_equipments.php?status=added");
            exit();
        } catch (PDOException $e) {
            $errors[] = "Error adding equipment: " . $e->getMessage();
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
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_layout.css">
    <link rel="stylesheet" href="<?php echo $root_path_prefix; ?>css/admin_add_equipment.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            padding-top: var(--navbar-height);
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="admin-area-layout">
        <?php include __DIR__ . '/includes/admin_sidebar.php'; ?>

        <main class="admin-main-content-area">
            <div class="admin-content-container form-container">
                <div class="admin-header">
                    <h1 class="admin-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
                    <a href="manage_equipments.php" class="admin-button plain-button">Back to Equipment List</a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="message error-message">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo htmlspecialchars($error); ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form action="add_equipment.php" method="POST" class="admin-form">
                    <div class="form-group">
                        <label for="name">Equipment Name:</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($form_data['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($form_data['description'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <input type="text" id="category" name="category" value="<?php echo htmlspecialchars($form_data['category'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="brand">Brand:</label>
                            <input type="text" id="brand" name="brand" value="<?php echo htmlspecialchars($form_data['brand'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="price">Price:</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($form_data['price'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity:</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" value="<?php echo htmlspecialchars($form_data['stock_quantity'] ?? '0'); ?>" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="sku">SKU (Unique):</label>
                            <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($form_data['sku'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="image_url">Image URL:</label>
                            <input type="text" id="image_url" name="image_url" placeholder="e.g., images/equipments/item.jpg" value="<?php echo htmlspecialchars($form_data['image_url'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="specifications">Specifications:</label>
                        <textarea id="specifications" name="specifications" rows="3"><?php echo htmlspecialchars($form_data['specifications'] ?? ''); ?></textarea>
                    </div>
                    <div class="form-group checkbox-group">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" <?php echo (isset($form_data['is_featured']) && $form_data['is_featured']) ? 'checked' : ''; ?>>
                        <label for="is_featured">Feature this equipment</label>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="admin-button add-new-button">Add Equipment</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>