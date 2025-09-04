<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $division_name = $_POST['division_name'];
    $project_type = $_POST['project_type'];

    if (!empty($division_name) && !empty($project_type)) {
        $stmt = $conn->prepare("INSERT INTO project_divisions (division_name, project_type) VALUES (?, ?)");
        $stmt->bind_param("ss", $division_name, $project_type);

        if ($stmt->execute()) {
            $message = "<div class=\"alert success\"><i class=\"fa fa-check-circle\"></i> New project division added successfully!</div>";
        } else {
            $message = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class=\"alert warning\"><i class=\"fa fa-exclamation-triangle\"></i> Please fill in all fields.</div>";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Project Division</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
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
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--surface);
            padding: 1rem 2rem;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(8px);
            background-color: rgba(255, 255, 255, 0.9);
        }

        .logo {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            letter-spacing: -0.5px;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .icon {
            font-size: 1.25rem;
            color: var(--primary);
            transition: all 0.2s ease;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
        }

        .icon:hover {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-dark);
        }

        .logout-btn {
            background: var(--primary);
            padding: 0.625rem 1.25rem;
            color: white;
            font-weight: 500;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .logout-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        /* Container */
        .container {
            display: flex;
            min-height: calc(100vh - 4rem);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--surface);
            box-shadow: var(--shadow);
            padding: 1.5rem 0;
            position: relative;
            transition: all 0.3s ease;
        }

        .sidebar ul {
            list-style: none;
            padding: 0;
        }

        .sidebar li {
            align-items: center;
            padding: 0.875rem 1.5rem;
            color: var(--text);
            cursor: pointer;
            transition: all 0.2s ease;
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            margin: 0 0.75rem;
        }

        .sidebar li:hover {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .sidebar li i {
            margin-right: 0.75rem;
            font-size: 1.125rem;
            width: 1.5rem;
            text-align: center;
        }

        .sidebar a {
            color: inherit;
            text-decoration: none;
            width: 100%;
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        /* Dropdown */
        .sidebar .dropdown .arrow {
            margin-left: auto;
            transition: transform 0.2s ease;
        }

        .sidebar .dropdown.active .arrow {
            transform: rotate(180deg);
        }

        .sidebar .dropdown-menu {
            display: none;
            background-color: rgba(37, 99, 235, 0.05);
            margin: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            overflow: hidden;
            padding: 0.5rem;
        }

        .sidebar .dropdown.active .dropdown-menu {
            display: block;
        }

        .sidebar .dropdown-menu li {
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            color: var(--text);
            margin: 0.25rem 0;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        .sidebar .dropdown-menu li:hover {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
        }

        .sidebar .dropdown-menu li a {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: inherit;
            text-decoration: none;
            width: 100%;
        }

        .sidebar .dropdown-menu li a i {
            font-size: 1rem;
            width: 1.25rem;
            text-align: center;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem;
            background: var(--background);
        }

        .main-content h2 {
            color: var(--text);
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        /* Form Styles */
        .form-container {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 2rem;
            max-width: 500px;
            margin: 2rem auto;
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 500;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            font-size: 1rem;
            color: var(--text);
            background: var(--background);
            transition: border-color 0.2s ease;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.2);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: var(--secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        .alert.success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }

        .alert.error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .alert.warning {
            background-color: #fffbeb;
            color: #9a3412;
            border: 1px solid #f59e0b;
        }

        .alert i {
            font-size: 1.25rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">RS BUILDERS PMS</div>
        <div class="header-right">
            <span class="icon"><i class="fa-regular fa-comments"></i></span>
            <a href="logout.php" class="logout-btn">Log-out</a>
        </div>
    </div>
    <div class="container">
        <nav class="sidebar">
            <ul>
                <li><a href="dashboard.php"><i class="fa fa-home"></i> DASHBOARD</a></li>
                <li><a href="employee_list.php"><i class="fa fa-users"></i> Employee List</a></li>
                <li><a href="project_list.php"><i class="fa fa-list"></i> Project List</a></li>
                <li><a href="user_list.php"><i class="fa fa-user"></i> Users</a></li>
                <li class="dropdown active">
                    <a href="#"><i class="fa fa-wrench"></i> Maintenance <span class="arrow">&#9662;</span></a>
                    <ul class="dropdown-menu">
                        <li><a href="position.php"><i class="fa fa-user-tie"></i> Position</a></li>
                        <li><a href="project_team.php"><i class="fa fa-users-cog"></i> Project Team</a></li>
                    </ul>
                </li>
                <li><a href="#"><i class="fa fa-money-bill"></i> Payroll</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <div class="form-container">
                <h2>Add New Project Division</h2>
                <?php echo $message; ?>
                <form action="add_project_division.php" method="POST">
                    <div class="form-group">
                        <label for="division_name">Division Name:</label>
                        <input type="text" id="division_name" name="division_name" required>
                    </div>
                    <div class="form-group">
                        <label for="project_type">Project Type:</label>
                        <input type="text" id="project_type" name="project_type" required>
                    </div>
                    <div class="form-actions">
                        <a href="project_division.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to List</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Add Project Division</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdowns = document.querySelectorAll('.sidebar .dropdown');
            
            dropdowns.forEach(dropdown => {
                const dropdownLink = dropdown.querySelector('a');
                
                dropdownLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    dropdowns.forEach(otherDropdown => {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.classList.remove('active');
                        }
                    });
                    
                    dropdown.classList.toggle('active');
                });
            });

            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    dropdowns.forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });
        });
    </script>
</body>
</html> 