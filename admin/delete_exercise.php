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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['exercise_id'])) {
    $exercise_id_to_delete = filter_var($_POST['exercise_id'], FILTER_VALIDATE_INT);

    if ($exercise_id_to_delete === false || $exercise_id_to_delete <= 0) {
        header("Location: manage_exercises.php?status=delete_error&msg=" . urlencode("Invalid exercise ID."));
        exit();
    }

    $pdo = null;
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->beginTransaction();

        // Because of ON DELETE CASCADE in user_exercise_plans, related entries there will be auto-deleted.
        $sql = "DELETE FROM exercises WHERE exercise_id = :exercise_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':exercise_id', $exercise_id_to_delete, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                $pdo->commit();
                header("Location: manage_exercises.php?status=deleted");
                exit();
            } else {
                $pdo->rollBack();
                header("Location: manage_exercises.php?status=delete_error&msg=" . urlencode("Exercise not found or already deleted."));
                exit();
            }
        } else {
            $pdo->rollBack();
            header("Location: manage_exercises.php?status=delete_error&msg=" . urlencode("Failed to execute deletion."));
            exit();
        }
    } catch (PDOException $e) {
        if ($pdo && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header("Location: manage_exercises.php?status=delete_error&msg=" . urlencode("Database error: " . $e->getMessage()));
        exit();
    }
} else {
    header("Location: manage_exercises.php");
    exit();
}
?>