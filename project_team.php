<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db.php';

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination logic
$allowed_per_page = [10, 25, 50, 100];
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $allowed_per_page) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause for search
$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = 'WHERE (e.firstname LIKE ? OR e.lastname LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $types .= 'ss';
}

// Get total number of project teams (with search)
$count_sql = "SELECT COUNT(*) as total FROM project_team pt JOIN employees e ON pt.foreman_id = e.id $where";
$count_stmt = $conn->prepare($count_sql);
if ($where !== '') {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch paginated project team data (with search)
$sql = "SELECT pt.id, e.firstname as foreman_firstname, e.lastname as foreman_lastname, pt.status FROM project_team pt JOIN employees e ON pt.foreman_id = e.id $where LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
if ($where !== '') {
    $types2 = $types . 'ii';
    $bind_params = array_merge($params, [$records_per_page, $offset]);
    $bind_names = [];
    $bind_names[] = $types2;
    foreach ($bind_params as $k => $v) {
        $bind_names[] = &$bind_params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);
} else {
    $stmt->bind_param('ii', $records_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Function to get employee name from ID
function getEmployeeName($employee_id, $conn) {
    $sql_employee = "SELECT firstname, lastname FROM employees WHERE id = ? LIMIT 1";
    if ($stmt_employee = $conn->prepare($sql_employee)) {
        $stmt_employee->bind_param("i", $employee_id);
        $stmt_employee->execute();
        $result_employee = $stmt_employee->get_result();
        if ($result_employee->num_rows > 0) {
            $employee_data = $result_employee->fetch_assoc();
            $stmt_employee->close();
            return htmlspecialchars($employee_data['firstname'] . ' ' . $employee_data['lastname']);
        }
        $stmt_employee->close();
    }
    return "Unknown Employee";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Project Teams</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--background);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }
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
        .container {
            display: flex;
            min-height: calc(100vh - 4rem);
        }
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
            margin-bottom: 1rem;
        }
        .records-per-page select {
            padding: 0.4rem 0.6rem;
            border: 1px solid var(--border);
            border-radius: 0.5rem;
            background: var(--surface);
            color: var(--text);
            font-size: 0.875rem;
            cursor: pointer;
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
                    <h2 class="table-title">Project Teams</h2>
                    <a href="add_project_team.php" class="add-btn"><i class="fa fa-plus"></i> Add</a>
                </div>

                <div class="search-bar-container">
                    <form method="get" style="display: flex; gap: 8px; align-items: center;">
                        <input type="text" name="search" class="search-input" placeholder="Search foreman..." value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="per_page" value="<?= $records_per_page ?>">
                        <button type="submit" class="search-btn"><i class="fa fa-search"></i> Search</button>
                    </form>
                </div>

                <div class="records-per-page">
                    <form id="perPageForm" method="get" style="display:inline;">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <input type="hidden" name="page" value="1">
                        <select name="per_page" onchange="document.getElementById('perPageForm').submit();">
                            <?php foreach ($allowed_per_page as $opt): ?>
                                <option value="<?= $opt ?>" <?= $records_per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span>records per page</span>
                    </form>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Foreman</th>
                            <th>Members</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row["foreman_firstname"] . ' ' . $row["foreman_lastname"]) . "</td>";
                                echo "<td>";
                                $member_names = [];
                                $team_id = $row["id"];
                                $member_sql = "SELECT employee_id FROM project_team_members WHERE team_id = ?";
                                if ($member_stmt = $conn->prepare($member_sql)) {
                                    $member_stmt->bind_param("i", $team_id);
                                    $member_stmt->execute();
                                    $member_result = $member_stmt->get_result();
                                    while ($member_row = $member_result->fetch_assoc()) {
                                        $member_names[] = getEmployeeName($member_row['employee_id'], $conn);
                                    }
                                    $member_stmt->close();
                                }
                                echo implode(', ', $member_names);
                                echo "</td>";
                                echo "<td>";
                                if ($row["status"] == 'Active') {
                                    echo "<span class=\"status-active\">Active</span>";
                                } else {
                                    echo "<span class=\"status-inactive\">Inactive</span>";
                                }
                                echo "</td>";
                                echo "<td>";
                                echo "<a href=\"edit_project_team.php?id=" . $row["id"] . "\" class=\"action-btn edit-btn\"><i class=\"fa fa-edit\"></i> Update</a>";
                                echo "<a href=\"delete_project_team.php?id=" . $row["id"] . "\" class=\"action-btn delete-btn\"><i class=\"fa fa-trash-alt\"></i> Delete</a>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan=\"4\">No project teams found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <div class="pagination-info">
                    <div class="showing-info">
                        <?php
                        $showing_from = $total_records == 0 ? 0 : $offset + 1;
                        $showing_to = min($offset + $records_per_page, $total_records);
                        ?>
                        Showing <?= $showing_from ?> to <?= $showing_to ?> of <?= $total_records ?> entries
                    </div>
                    <div class="pagination-controls">
                        <a href="?search=<?= urlencode($search) ?>&page=<?= max(1, $page - 1) ?>&per_page=<?= $records_per_page ?>" class="pagination-btn<?= $page == 1 ? ' disabled' : '' ?>">&larr; Previous</a>
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        if ($start_page > 1) {
                            echo '<a href="?search=' . urlencode($search) . '&page=1&per_page=' . $records_per_page . '" class="pagination-btn">1</a>';
                            if ($start_page > 2) echo '<span class="pagination-btn disabled">...</span>';
                        }
                        for ($i = $start_page; $i <= $end_page; $i++) {
                            echo '<a href="?search=' . urlencode($search) . '&page=' . $i . '&per_page=' . $records_per_page . '" class="pagination-btn' . ($i == $page ? ' active' : '') . '">' . $i . '</a>';
                        }
                        if ($end_page < $total_pages) {
                            if ($end_page < $total_pages - 1) echo '<span class="pagination-btn disabled">...</span>';
                            echo '<a href="?search=' . urlencode($search) . '&page=' . $total_pages . '&per_page=' . $records_per_page . '" class="pagination-btn">' . $total_pages . '</a>';
                        }
                        ?>
                        <a href="?search=<?= urlencode($search) ?>&page=<?= min($total_pages, $page + 1) ?>&per_page=<?= $records_per_page ?>" class="pagination-btn<?= $page == $total_pages || $total_pages == 0 ? ' disabled' : '' ?>">Next &rarr;</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
<script>
(function(){
    // SweetAlert2 confirm for deleting a project team (match payroll.php style)
    document.querySelectorAll('a.delete-btn').forEach(function(link){
        link.addEventListener('click', function(e){
            e.preventDefault();
            var href = this.getAttribute('href');
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then(function(result){
                if (result.isConfirmed) {
                    fetch(href, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(){
                            return Swal.fire({
                                title: 'Deleted!',
                                text: 'The project team has been removed.',
                                icon: 'success',
                                confirmButtonColor: '#2563eb'
                            });
                        })
                        .then(function(){
                            window.location.reload();
                        })
                        .catch(function(){
                            Swal.fire({
                                title: 'Delete failed',
                                icon: 'error',
                                confirmButtonColor: '#2563eb'
                            });
                        });
                }
            });
        });
    });
})();
</script>
</body>
</html>
<?php
$conn->close();
?> 