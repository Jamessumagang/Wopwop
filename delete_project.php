<?php
include 'db.php';

// Check if project_id is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Get project ID from URL
    $project_id = $_GET['id'];

    // Delete related phase steps first
    $stmt_steps = $conn->prepare("DELETE FROM project_phase_steps WHERE project_id = ?");
    $stmt_steps->bind_param("i", $project_id);
    $stmt_steps->execute();
    $stmt_steps->close();

    // Delete related project progress entries
    $stmt_progress = $conn->prepare("DELETE FROM project_progress WHERE project_id = ?");
    $stmt_progress->bind_param("i", $project_id);
    $stmt_progress->execute();
    $stmt_progress->close();

    // Prepare a delete statement
    $sql = "DELETE FROM projects WHERE project_id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("i", $project_id);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect back to the admin dashboard after successful deletion
            header("location: Admindashboard.php");
            exit();
        } else {
            echo "Error deleting record: " . $conn->error;
        }
    } else {
        echo "Error preparing statement: " . $conn->error;
    }

    // Close statement
    $stmt->close();

    // Close connection
    $conn->close();
} else {
    // If project_id was not provided, redirect with an error or show a message
    echo "Error: Project ID not specified.";
    // header("location: Admindashboard.php"); // Optional: redirect back
    // exit();
}
?> 