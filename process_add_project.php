<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $project_name = $_POST['project_name'];
    $start_date = $_POST['start_date'];
    $deadline = $_POST['deadline'];
    $location = $_POST['location'];
    $project_cost = $_POST['project_cost'];
    $client_name = $_POST['client_name'] ?? '';
    $client_number = $_POST['client_number'] ?? '';
    $foreman = $_POST['foreman'];
    $project_type = $_POST['project_type'];
    $project_status = $_POST['project_status'];
    $project_divisions = $_POST['project_divisions'];

    // Handle file upload
    $image_path = null;
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] == 0) {
        $allowed_types = array('jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
        $file_name = $_FILES['project_image']['name'];
        $file_type = $_FILES['project_image']['type'];
        $file_size = $_FILES['project_image']['size'];

        // Verify file extension
        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed_types)) die("Error: Please select a valid file format.");

        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($file_size > $maxsize) die("Error: File size is larger than the allowed limit.");

        // Verify MIME type of the file
        if (in_array($file_type, $allowed_types)) {
            // Upload file to server
            // Using a unique name to prevent overwriting files
            $new_file_name = uniqid() . '.' . $ext;
            $upload_directory = 'uploads/'; // Make sure this directory exists and is writable
            $destination = $upload_directory . $new_file_name;

            if (move_uploaded_file($_FILES['project_image']['tmp_name'], $destination)) {
                $image_path = $destination;
            } else {
                echo "Error: There was a problem uploading your file. Please try again.";
            }
        } else {
            echo "Error: Invalid file type.";
        }
    }

    // Ensure columns exist for client_name and client_number (compatible with MySQL/MariaDB)
    $colClientName = $conn->query("SHOW COLUMNS FROM projects LIKE 'client_name'");
    if ($colClientName && $colClientName->num_rows === 0) {
        $conn->query("ALTER TABLE projects ADD COLUMN client_name VARCHAR(255) NULL");
    }
    $colClientNumber = $conn->query("SHOW COLUMNS FROM projects LIKE 'client_number'");
    if ($colClientNumber && $colClientNumber->num_rows === 0) {
        $conn->query("ALTER TABLE projects ADD COLUMN client_number VARCHAR(255) NULL");
    }

    // Prepare and bind SQL statement including client fields
    $sql = "INSERT INTO projects (project_name, start_date, deadline, location, project_cost, client_name, client_number, foreman, project_type, project_status, project_divisions, image_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // Bind as strings to be tolerant of formats (e.g., project_cost with commas)
        $stmt->bind_param("ssssssssssss", $project_name, $start_date, $deadline, $location, $project_cost, $client_name, $client_number, $foreman, $project_type, $project_status, $project_divisions, $image_path);

        if ($stmt->execute()) {
            // Redirect back to project list page after successful insertion with success flag
            header("Location: project_list.php?added=1");
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