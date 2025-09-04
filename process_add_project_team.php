<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $foreman_id = $_POST['foreman_id'];
    $members = $_POST['members']; // This will be a comma-separated string of IDs
    $status = $_POST['status'];

    // Prepare and bind SQL statement
    // Make sure the column names match your database table
    $sql = "INSERT INTO project_team (foreman_id, status) VALUES (?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $foreman_id, $status); // 'i' for integer (foreman_id), 's' for string (status)

        if ($stmt->execute()) {
            // Get the inserted team ID
            $team_id = $stmt->insert_id;

            // Insert members if needed (assuming you have a project_team_members table)
            if (!empty($members)) {
                $member_ids = explode(',', $members);
                foreach ($member_ids as $member_id) {
                    $member_id = intval($member_id);
                    $conn->query("INSERT INTO project_team_members (team_id, employee_id) VALUES ($team_id, $member_id)");
                }
            }

            header("Location: project_team.php");
            exit();
        } else {
            echo "Error: Could not execute query: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Error: Could not prepare statement: " . $conn->error;
    }

    $conn->close();
}
?> 