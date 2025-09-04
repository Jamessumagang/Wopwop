<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if (isset($_GET['id'])) {
    $division_id = $_GET['id'];

    $stmt = $conn->prepare("DELETE FROM project_divisions WHERE id = ?");
    $stmt->bind_param("i", $division_id);

    if ($stmt->execute()) {
        $_SESSION['message'] = "<div class=\"alert success\"><i class=\"fa fa-check-circle\"></i> Project division deleted successfully!</div>";
    } else {
        $_SESSION['message'] = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Error deleting project division: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

$conn->close();

header("Location: project_division.php");
exit();
?> 