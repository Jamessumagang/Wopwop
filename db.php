<?php
$host = 'localhost';
$db   = 'capstone_db';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Update last active timestamp for the logged-in user
// Check if session is not already started before calling session_start()
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id']; // Assuming user ID is stored in session
    $sql = "UPDATE employees SET last_active = NOW() WHERE id = ?"; // Assuming your user table is 'employees' and user ID column is 'id'
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->close();
    }
}
?>
