<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_GET['id'])) {
    $position_id = $_GET['id'];

    // Prepare and execute the DELETE statement
    $stmt = $conn->prepare("DELETE FROM positions WHERE id = ?");
    $stmt->bind_param("i", $position_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "<div class=\"alert success\"><i class=\"fa fa-check-circle\"></i> Position deleted successfully!</div>";
    } else {
        $_SESSION['message'] = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Error deleting position: " . $stmt->error . "</div>";
    }

    $stmt->close();
}

$conn->close();

header("Location: position.php");
exit();
?> 