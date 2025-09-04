<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

include 'db.php';

// Build WHERE clause for search
$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = "WHERE position_name LIKE ? OR daily_rate LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types = 'ss';
}

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total FROM positions $where";
$count_stmt = $conn->prepare($count_sql);
if ($where !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$count_stmt->close();

// Fetch positions with search, limit, and offset
$sql = "SELECT * FROM positions $where LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($where !== '') {
    $types_with_limit = $types . 'ii';
    $params_with_limit = array_merge($params, [$records_per_page, $offset]);
    $stmt->bind_param($types_with_limit, ...$params_with_limit);
} else {
    $stmt->bind_param('ii', $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Calculate pagination info
$total_pages = max(1, ceil($total_records / $records_per_page));
$start_entry = $total_records == 0 ? 0 : $offset + 1;
$end_entry = min($offset + $records_per_page, $total_records);

function build_query($overrides = []) {
    $params = $_GET;
    foreach ($overrides as $key => $value) {
        $params[$key] = $value;
    }
    return http_build_query($params);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Positions</title>
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

        /* Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .dashboard-card {
            background: var(--surface);
            border-radius: 1rem;
            padding: 1.5rem;
            display: flex;
            gap: 1.25rem;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .dashboard-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .card-icon {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            border-radius: 0.75rem;
            width: 3.5rem;
            height: 3.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .card-title {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--text-light);
            margin-bottom: 0.25rem;
        }

        .card-value {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text);
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 1rem;
            overflow: hidden;
            background: var(--surface);
            box-shadow: var(--shadow);
            margin-top: 1rem;
        }

        th, td {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }

        th {
            background-color: rgba(37, 99, 235, 0.05);
            font-weight: 600;
            color: var(--text);
            text-align: left;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: rgba(37, 99, 235, 0.02);
        }

        .employee-img {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            object-fit: cover;
            border: 2px solid var(--border);
        }

        /* Buttons */
        .action-btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.5rem;
            color: white;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin-right: 0.5rem;
        }

        .edit-btn {
            background-color: var(--warning);
        }

        .edit-btn:hover {
            background-color: #d97706;
            transform: translateY(-1px);
        }

        .delete-btn {
            background-color: var(--danger);
        }

        .delete-btn:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }

        /* Table specific styles for positions page */
        .table-container {
            background: var(--surface);
            border-radius: 1rem;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .table-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text);
        }

        .add-btn {
            background: var(--primary);
            padding: 0.75rem 1.5rem;
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

        .add-btn:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .data-table th, .data-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid var(--border);
        }

        .data-table th {
            background-color: rgba(37, 99, 235, 0.05);
            font-weight: 600;
            color: var(--text-light);
            text-align: left;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .data-table tr:last-child td {
            border-bottom: none;
        }

        .data-table tr:hover {
            background-color: rgba(37, 99, 235, 0.02);
        }

        .pagination-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: var(--text-light);
        }

        .pagination-controls {
            display: flex;
            gap: 0.5rem;
        }

        .pagination-btn {
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            color: var(--text);
            transition: all 0.2s ease;
            font-weight: 500;
        }

        .pagination-btn:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination-btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .records-per-page {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .records-per-page select {
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            background: var(--surface);
            color: var(--text);
            font-size: 0.875rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 240px;
            }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                padding: 1rem 0;
            }

            .main-content {
                padding: 1.5rem;
            }

            .dashboard-cards {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 1rem;
            }

            .table-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .add-btn {
                width: 100%;
                justify-content: center;
            }

            .pagination-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
        }
        .search-bar-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 1rem;
        }
        .search-input {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            font-size: 1em;
            background: #fff;
            color: #222;
            min-width: 220px;
        }
        .search-btn {
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 8px 18px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-btn:hover {
            background: var(--primary-dark);
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                <li><a href="Admindashboard.php"><i class="fa fa-home"></i> DASHBOARD</a></li>
                <li><a href="employee_list.php"><i class="fa fa-users"></i> Employee List</a></li>
                <li><a href="project_list.php"><i class="fa fa-list"></i> Project List</a></li>
                <li><a href="user_list.php"><i class="fa fa-user"></i> Users</a></li>
                <li class="dropdown active">
                    <a href="#"><i class="fa fa-wrench"></i> Maintenance <span class="arrow">&#9662;</span></a>
                    <ul class="dropdown-menu">
                        <li class="active"><a href="position.php"><i class="fa fa-user-tie"></i> Position</a></li>
                        <li><a href="project_team.php"><i class="fa fa-users-cog"></i> Project Team</a></li>
                    </ul>
                </li>
                <li><a href="payroll.php"><i class="fa fa-money-bill"></i> Payroll</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <div class="table-container">
                <div class="table-header">
                    <h2 class="table-title">Positions</h2>
                    <a href="add_position.php" class="add-btn"><i class="fa fa-plus"></i> Add</a>
                </div>

                <div class="search-bar-container">
                    <form method="get" style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" name="search" class="search-input" placeholder="Search position or daily rate..." value="<?= htmlspecialchars($search) ?>">
                        <button type="submit" class="search-btn"><i class="fa fa-search"></i> Search</button>
                    </form>
                </div>

                <div class="records-per-page">
                    <span>10 records per page</span>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Position</th>
                            <th>Daily Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                $pos_name = htmlspecialchars($row["position_name"]);
                                echo "<td><a href=\"employee_list.php?status=Active&page=1&per_page=10&search=&position=" . urlencode($row["position_name"]) . "\" style=\"color: var(--primary); text-decoration: none;\">" . $pos_name . "</a></td>";
                                echo "<td>" . htmlspecialchars($row["daily_rate"]) . " Php.</td>";
                                echo "<td>";
                                echo "<a href=\"edit_position.php?id=" . htmlspecialchars($row["id"]) . "\" class=\"action-btn edit-btn\"><i class=\"fa fa-edit\"></i> Edit</a>";
                                echo "<a href=\"delete_position.php?id=" . htmlspecialchars($row["id"]) . "\" class=\"action-btn delete-btn\"><i class=\"fa fa-trash-alt\"></i> Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan=\"3\">No positions found.</td></tr>";
                        }
                        $stmt->close();
                        $conn->close();
                        ?>
                    </tbody>
                </table>

                <div class="pagination-info">
                    <div class="showing-info">Showing <?= $start_entry ?> to <?= $end_entry ?> of <?= $total_records ?> entries</div>
                    <div class="pagination-controls">
                        <?php
                        $prev_disabled = $page <= 1 ? 'disabled' : '';
                        $next_disabled = $page >= $total_pages ? 'disabled' : '';
                        $prev_page = $page - 1;
                        $next_page = $page + 1;
                        echo '<a href="?' . build_query(['page'=>$prev_page]) . '" class="pagination-btn ' . ($prev_disabled ? 'disabled' : '') . '"' . ($prev_disabled ? ' tabindex="-1" aria-disabled="true"' : '') . '>← Previous</a>';
                        // Show page numbers (max 5 at a time)
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            $active = $i == $page ? 'active' : '';
                            echo '<a href="?' . build_query(['page'=>$i]) . '" class="pagination-btn ' . $active . '">' . $i . '</a>';
                        }
                        echo '<a href="?' . build_query(['page'=>$next_page]) . '" class="pagination-btn ' . ($next_disabled ? 'disabled' : '') . '"' . ($next_disabled ? ' tabindex="-1" aria-disabled="true"' : '') . '>Next →</a>';
                        ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get all dropdown elements
            const dropdowns = document.querySelectorAll('.sidebar .dropdown');
            
            dropdowns.forEach(dropdown => {
                const dropdownLink = dropdown.querySelector('a');
                const dropdownMenu = dropdown.querySelector('.dropdown-menu');
                
                // Toggle dropdown when clicking the link
                dropdownLink.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Close other dropdowns
                    dropdowns.forEach(otherDropdown => {
                        if (otherDropdown !== dropdown) {
                            otherDropdown.classList.remove('active');
                        }
                    });
                    
                    // Toggle current dropdown
                    dropdown.classList.toggle('active');
                });
            });

            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.dropdown')) {
                    dropdowns.forEach(dropdown => {
                        dropdown.classList.remove('active');
                    });
                }
            });
        });
    </script>
    <script>
    // SweetAlert2 confirm modal for deleting a position (same design as payroll)
    (function(){
        document.querySelectorAll('a.delete-btn').forEach(function(link){
            link.addEventListener('click', function(e){
                e.preventDefault();
                var href = this.getAttribute('href');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'Delete this position? This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e11d48',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Yes, delete it',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        fetch(href, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                            .then(() => Swal.fire({
                                title: 'Deleted!',
                                text: 'The position has been removed.',
                                icon: 'success',
                                confirmButtonColor: '#2563eb'
                            }))
                            .then(() => { window.location.reload(); })
                            .catch(() => Swal.fire({
                                title: 'Delete failed',
                                icon: 'error',
                                confirmButtonColor: '#2563eb'
                            }));
                    }
                });
            });
        });
    })();
    </script>
</body>
</html>