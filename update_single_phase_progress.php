<?php
include 'db.php';

// Ensure all date operations use Philippine time
date_default_timezone_set('Asia/Manila');

$project_id = null;
$division_name = null;
$project_details = null;
$current_progress = 0;
$current_date_updated = '';

// Get project ID and division name from URL
if (isset($_GET['id']) && isset($_GET['division'])) {
    $project_id = $_GET['id'];
    $division_name = $_GET['division'];

    // Fetch project details
    $sql = "SELECT project_id, project_name FROM projects WHERE project_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $project_details = $result->fetch_assoc();
        }
        $stmt->close();
    }

    // Fetch current progress for this specific division
    $sql_progress = "SELECT progress_percentage, date_updated FROM project_progress WHERE project_id = ? AND division_name = ?";
    if ($stmt_progress = $conn->prepare($sql_progress)) {
        $stmt_progress->bind_param("is", $project_id, $division_name);
        $stmt_progress->execute();
        $result_progress = $stmt_progress->get_result();
        if ($result_progress->num_rows > 0) {
            $progress_data = $result_progress->fetch_assoc();
            $current_progress = $progress_data['progress_percentage'];
            $current_date_updated = $progress_data['date_updated'];
        }
        $stmt_progress->close();
    }
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['project_id']) && isset($_POST['division_name'])) {
    $project_id = $_POST['project_id'];
    $division_name = $_POST['division_name'];
    $progress = $_POST['progress'];
    $date_updated_input = $_POST['date_updated'];
    // Normalize date_updated to 'Y-m-d H:i:s' in Asia/Manila
    $manilaTz = new DateTimeZone('Asia/Manila');
    if (!empty($date_updated_input)) {
        if (strpos($date_updated_input, 'T') !== false) {
            // From datetime-local (e.g., 2025-09-04T14:30)
            $dt = new DateTime($date_updated_input, $manilaTz);
        } else {
            // Fallback if only a date is provided
            $dt = new DateTime($date_updated_input . ' 00:00', $manilaTz);
        }
        $date_updated = $dt->format('Y-m-d H:i:s');
    } else {
        $date_updated = (new DateTime('now', $manilaTz))->format('Y-m-d H:i:s');
    }

    // Check if progress for this division already exists
    $sql_check = "SELECT * FROM project_progress WHERE project_id = ? AND division_name = ?";
    if ($stmt_check = $conn->prepare($sql_check)) {
        $stmt_check->bind_param("is", $project_id, $division_name);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($result_check->num_rows > 0) {
            // Update existing record
            $sql_update = "UPDATE project_progress SET progress_percentage = ?, date_updated = ? WHERE project_id = ? AND division_name = ?";
            if ($stmt_update = $conn->prepare($sql_update)) {
                $stmt_update->bind_param("isss", $progress, $date_updated, $project_id, $division_name);
                $stmt_update->execute();
                $stmt_update->close();
            }
        } else {
            // Insert new record
            $sql_insert = "INSERT INTO project_progress (project_id, division_name, progress_percentage, date_updated) VALUES (?, ?, ?, ?)";
            if ($stmt_insert = $conn->prepare($sql_insert)) {
                $stmt_insert->bind_param("isss", $project_id, $division_name, $progress, $date_updated);
                $stmt_insert->execute();
                $stmt_insert->close();
            }
        }
        $stmt_check->close();
    }
    
    header("Location: view_project.php?id=" . $project_id . "&progress_saved=1"); // Redirect back with flag
    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Update <?php echo htmlspecialchars($division_name); ?> Progress</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modern CSS Reset */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --success: #22c55e;
            --danger: #ef4444;
            --warning: #f59e0b;
            --background: #f8fafc;
            --surface: #ffffff;
            --text: #1e293b;
            --text-light: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        /* Base Styles */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
             background: url('./images/background.webp') no-repeat center center fixed;
            
            background-size: cover;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        /* Container */
        .container {
            margin: 150px auto 40px auto;
            max-width: 600px;
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 2rem;
        }

        /* Header */
        .header {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: var(--text);
            border-bottom: 1px solid var(--border);
            padding-bottom: 0.75rem;
        }

        /* Project Info */
        .project-info {
            background: rgba(37, 99, 235, 0.05);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .project-info h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .project-info p {
            color: var(--text-light);
            font-size: 0.875rem;
        }

        .project-info strong {
            color: var(--text);
            font-weight: 600;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text);
            font-size: 0.875rem;
        }

        .form-group input[type="number"],
        .form-group input[type="date"] {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            background: var(--surface);
            color: var(--text);
            transition: all 0.2s ease;
        }

        .form-group input[type="number"]:focus,
        .form-group input[type="date"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Buttons */
        .button-container {
            margin-top: 2rem;
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            color: white;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .save-btn {
            background-color: var(--success);
        }

        .save-btn:hover {
            background-color: #16a34a;
        }

        .cancel-btn {
            background-color: var(--secondary);
        }

        .cancel-btn:hover {
            background-color: #475569;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                margin: 20px auto;
                padding: 1.5rem;
            }

            .button-container {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($project_details): ?>
        <div class="header">Update <?php echo htmlspecialchars($division_name); ?> Progress</div>

        <div class="project-info">
            <h3><?php echo htmlspecialchars($project_details['project_name']); ?></h3>
            <p><strong>Division:</strong> <?php echo htmlspecialchars($division_name); ?></p>
        </div>

        <form method="POST" action="update_single_phase_progress.php">
            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
            <input type="hidden" name="division_name" value="<?php echo htmlspecialchars($division_name); ?>">

            <div class="form-group">
                <label for="progress">Progress Percentage:</label>
                <input type="number" id="progress" name="progress" min="0" max="100" value="<?php echo htmlspecialchars($current_progress); ?>" required>
            </div>

            <div class="form-group">
                <label for="date_updated">Date & Time Updated (Philippine Time):</label>
                <?php
                    $prepopulate = '';
                    if (!empty($current_date_updated)) {
                        try {
                            $dt = new DateTime($current_date_updated, new DateTimeZone('Asia/Manila'));
                            $prepopulate = $dt->format('Y-m-d\\TH:i');
                        } catch (Exception $e) { $prepopulate = ''; }
                    }
                ?>
                <input type="datetime-local" id="date_updated" name="date_updated" value="<?php echo htmlspecialchars($prepopulate); ?>" required>
            </div>

            <div class="button-container">
                <a href="view_project.php?id=<?php echo $project_id; ?>" class="action-btn cancel-btn">
                    <i class="fas fa-times"></i>
                    Cancel
                </a>
                <button type="submit" class="action-btn save-btn">
                    <i class="fas fa-check"></i>
                    Save Progress
                </button>
            </div>
        </form>

        <?php else: ?>
            <p>Project not found.</p>
        <?php endif; ?>
    </div>
    
</body>
</html>
