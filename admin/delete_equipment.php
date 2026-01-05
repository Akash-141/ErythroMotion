<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$root_path_prefix = "../"; // Define path to root

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: " . $root_path_prefix . "index.php");
    exit();
}

include __DIR__ . '/../includes/db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['equipment_id'])) {
    $equipment_id_to_delete = filter_var($_POST['equipment_id'], FILTER_VALIDATE_INT);

    if ($equipment_id_to_delete === false || $equipment_id_to_delete <= 0) {
        header("Location: manage_equipments.php?status=delete_error&msg=" . urlencode("Invalid equipment ID."));
        exit();
    }

    $pdo = null;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sql = "DELETE FROM equipments WHERE equipment_id = :equipment_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':equipment_id', $equipment_id_to_delete, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                header("Location: manage_equipments.php?status=deleted");
                exit();
            } else {
                header("Location: manage_equipments.php?status=delete_error&msg=" . urlencode("Equipment not found or already deleted."));
                exit();
            }
        } else {
            header("Location: manage_equipments.php?status=delete_error&msg=" . urlencode("Failed to execute deletion."));
            exit();
        }
    } catch (PDOException $e) {
        header("Location: manage_equipments.php?status=delete_error&msg=" . urlencode("Database error: " . $e->getMessage()));
        exit();
    }
} else {
    // If not a POST request or equipment_id is not set, redirect to manage page
    header("Location: manage_equipments.php");
    exit();
}
?>