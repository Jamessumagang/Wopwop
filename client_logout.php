<?php
session_start();
include 'db.php';

// If a client user is logged in, flip their DB flag off
if (isset($_SESSION['client_logged_in'], $_SESSION['client_user_id']) && $_SESSION['client_logged_in'] === true) {
    $clientId = (int)$_SESSION['client_user_id'];
    $colCheck = $conn->query("SHOW COLUMNS FROM client_users LIKE 'is_logged_in'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE client_users ADD COLUMN is_logged_in TINYINT(1) NOT NULL DEFAULT 0");
    }
    if ($update = $conn->prepare('UPDATE client_users SET is_logged_in = 0 WHERE id = ?')) {
        $update->bind_param('i', $clientId);
        $update->execute();
        $update->close();
    }
}

session_unset();
session_destroy();
header('Location: client_login.php');
exit(); 