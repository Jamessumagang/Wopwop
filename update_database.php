<?php
// Database update script to add file_path column to project_phase_steps table
// Run this script once to update your database structure

include 'db.php';

echo "<h2>Database Update Script</h2>";
echo "<p>Adding file_path column to project_phase_steps table...</p>";

try {
    // Check if file_path column already exists
    $check_sql = "SHOW COLUMNS FROM project_phase_steps LIKE 'file_path'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        // Column doesn't exist, so add it
        $alter_sql = "ALTER TABLE `project_phase_steps` ADD COLUMN `file_path` VARCHAR(255) NULL DEFAULT '' AFTER `image_path`";
        
        if ($conn->query($alter_sql) === TRUE) {
            echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
            echo "<strong>✅ SUCCESS:</strong> file_path column has been added to project_phase_steps table successfully!";
            echo "</div>";
            
            echo "<p><strong>What was added:</strong></p>";
            echo "<ul>";
            echo "<li>Column name: <code>file_path</code></li>";
            echo "<li>Data type: <code>VARCHAR(255)</code></li>";
            echo "<li>Default value: Empty string</li>";
            echo "<li>Position: After <code>image_path</code> column</li>";
            echo "</ul>";
            
            echo "<p><strong>Next steps:</strong></p>";
            echo "<ol>";
            echo "<li>Delete this script file (update_database.php) for security</li>";
            echo "<li>Test the file upload functionality in edit_project_phase_steps.php</li>";
            echo "</ol>";
            
        } else {
            echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
            echo "<strong>❌ ERROR:</strong> " . $conn->error;
            echo "</div>";
        }
    } else {
        echo "<div style='color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;'>";
        echo "<strong>ℹ️ INFO:</strong> file_path column already exists in project_phase_steps table.";
        echo "</div>";
        
        echo "<p>No database changes needed. You can proceed to test the file upload functionality.</p>";
    }
    
    // Show current table structure
    echo "<h3>Current project_phase_steps table structure:</h3>";
    $describe_sql = "DESCRIBE project_phase_steps";
    $result = $conn->query($describe_sql);
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background-color: #f2f2f2;'>";
        echo "<th style='padding: 8px;'>Field</th>";
        echo "<th style='padding: 8px;'>Type</th>";
        echo "<th style='padding: 8px;'>Null</th>";
        echo "<th style='padding: 8px;'>Key</th>";
        echo "<th style='padding: 8px;'>Default</th>";
        echo "<th style='padding: 8px;'>Extra</th>";
        echo "</tr>";
        
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . $row["Field"] . "</td>";
            echo "<td style='padding: 8px;'>" . $row["Type"] . "</td>";
            echo "<td style='padding: 8px;'>" . $row["Null"] . "</td>";
            echo "<td style='padding: 8px;'>" . $row["Key"] . "</td>";
            echo "<td style='padding: 8px;'>" . $row["Default"] . "</td>";
            echo "<td style='padding: 8px;'>" . $row["Extra"] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<strong>❌ ERROR:</strong> " . $e->getMessage();
    echo "</div>";
}

$conn->close();

echo "<hr>";
echo "<p><small>⚠️ <strong>Security Note:</strong> Please delete this script after running it to avoid security risks.</small></p>";
?>


