<?php
session_start();
include 'db.php';

// Get user ID before destroying the session
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Update is_logged_in status to FALSE in the database
    $update_sql = "UPDATE users SET is_logged_in = FALSE WHERE id = ?"; // Assuming your user table is 'users' and user ID column is 'id'
    if ($stmt = $conn->prepare($update_sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Close the database connection
$conn->close();

session_destroy();
header("Location: Adminlogin.php");
exit();
?>
