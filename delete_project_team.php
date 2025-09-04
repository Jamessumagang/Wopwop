<?php
include 'db.php';

// Check if team_id is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    // Get team ID from URL
    $team_id = $_GET['id'];

    // Prepare a delete statement
    $sql = "DELETE FROM project_team WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        $stmt->bind_param("i", $team_id);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect back to the project team list after successful deletion
            header("location: project_team.php");
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
    // If team_id was not provided, redirect with an error or show a message
    echo "Error: Project Team ID not specified.";
}
?> 