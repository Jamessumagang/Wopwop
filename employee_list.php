<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "capstone_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Filter by status
$status = isset($_GET['status']) ? $_GET['status'] : 'Active';
$position_filter = isset($_GET['position']) ? trim($_GET['position']) : '';

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Records per page dropdown
$allowed_per_page = [10, 25, 50, 100];
$records_per_page = isset($_GET['per_page']) && in_array((int)$_GET['per_page'], $allowed_per_page) ? (int)$_GET['per_page'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Build WHERE clause for search
$where = 'status = ?';
$params = [$status];
$types = 's';
if ($position_filter !== '') {
    $where .= ' AND position = ?';
    $params[] = $position_filter;
    $types .= 's';
}
if ($search !== '') {
    $where .= ' AND (firstname LIKE ? OR lastname LIKE ? OR middlename LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}

// Get total number of records for pagination (with search)
$count_sql = "SELECT COUNT(*) as total FROM employees WHERE $where";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_records = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_records / $records_per_page);
$count_stmt->close();

// Fetch employees with pagination (with search)
$sql = "SELECT * FROM employees WHERE $where LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$all_params = array_merge($params, [$records_per_page, $offset]);
$all_types = $types . 'ii';
$bind_names = [];
$bind_names[] = $all_types;
foreach ($all_params as $k => $v) {
    $bind_names[] = &$all_params[$k];
}
call_user_func_array([$stmt, 'bind_param'], $bind_names);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>List of Employee</title>
    <style>
        body {
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Poppins', 'Montserrat', 'Inter', Arial, sans-serif;
            background: url('images/background.webp') no-repeat center center fixed, linear-gradient(135deg, #e0e7ff 0%, #f7fafc 100%);
            background-size: cover;
            position: relative;
        }
     
        
        .container {
            margin: 40px auto;
            margin: 100px;
            max-width: 1600px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 32px 48px 24px 48px;
            position: relative;
            z-index: 1;
        }
        .header {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 24px;
            color: #2563eb;
        }
        .top-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 18px;
        }
        .home-btn, .new-employee-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            padding: 12px 28px;
            font-size: 1em;
            border-radius: 999px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s;
        }
        .home-btn:hover, .new-employee-btn:hover {
            background: #1746a0;
        }
        .tabs {
            margin-bottom: 16px;
            display: flex;
            gap: 8px;
        }
        .tab {
            padding: 8px 24px;
            border: none;
            border-radius: 999px;
            font-size: 1em;
            background: #e0e7ff;
            color: #2563eb;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
        }
        .tab.active {
            background: #2563eb;
            color: #fff;
        }
        .tab:not(.active):hover {
            background: #c7d2fe;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 18px 16px;
            text-align: center;
            font-size: 1em;
        }
        th {
            background: #f5f7fa;
            color: #2563eb;
            font-weight: 700;
        }
        tr:hover {
            background: #f1f5fd;
        }
        .employee-img {
            width: 48px;
            height: 48px;
            border-radius: 8px;
            background: #e5e7eb;
            object-fit: cover;
        }
        .profile-link {
            color: #2563eb;
            text-decoration: none;
            font-weight: 600;
            margin: 0 8px;
        }
        .profile-link:hover {
            text-decoration: underline;
        }
        .delete-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 7px 16px;
            border-radius: 999px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            margin-left: 6px;
            transition: background 0.2s;
        }
        .delete-btn:hover {
            background: #c0392b;
        }
        /* Actions Flex Layout */
        .actions-flex {
            display: flex;
            align-items: center;
            gap: 14px;
            justify-content: center;
        }
        /* Pagination Styles */
        .pagination-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 24px;
            gap: 8px;
        }
        .pagination-info {
            font-size: 0.9em;
            color: #6b7280;
            margin: 0 16px;
        }
        .pagination-btn {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s;
        }
        .pagination-btn:hover {
            background: #e5e7eb;
            border-color: #9ca3af;
        }
        .pagination-btn.active {
            background: #2563eb;
            color: #fff;
            border-color: #2563eb;
        }
        .pagination-btn:disabled {
            background: #f9fafb;
            color: #9ca3af;
            cursor: not-allowed;
            border-color: #e5e7eb;
        }
        .pagination-btn:disabled:hover {
            background: #f9fafb;
            border-color: #e5e7eb;
        }
        .records-per-page-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .records-per-page-select {
            padding: 4px 12px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            font-size: 1em;
            background: #fff;
            color: #222;
            cursor: pointer;
        }
        .search-bar-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }
        .search-input {
            padding: 8px 14px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            font-size: 1em;
            background: #fff;
            color: #222;
            min-width: 220px;
        }
        .search-btn {
            background: #2563eb;
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
            background: #1746a0;
        }
        @media (max-width: 700px) {
            .container {
                margin: 8px;
                padding: 6px;
            }
            .header {
                font-size: 1.1em;
            }
            th, td {
                padding: 8px 2px;
                font-size: 0.95em;
            }
            .employee-img {
                width: 32px;
                height: 32px;
            }
            .home-btn, .new-employee-btn {
                padding: 8px 12px;
                font-size: 0.95em;
            }
            .pagination-container {
                flex-wrap: wrap;
                gap: 4px;
            }
            .pagination-btn {
                padding: 6px 12px;
                font-size: 0.8em;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<div class="background-overlay"></div>
<?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
<div id="added-toast" style="position:fixed;top:20px;right:20px;z-index:10001;">
    <div style="min-width:260px;max-width:360px;background:#10b981;color:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);padding:12px 14px;">
        <div style="font-weight:800;margin-bottom:4px;">Employee Saved</div>
        <div>New employee has been added successfully.</div>
    </div>
    <script>
        setTimeout(function(){ var el = document.getElementById('added-toast'); if (el) el.remove(); }, 3000);
    </script>
</div>
<?php endif; ?>
<div class="container">
    <div class="top-buttons">
        <a href="Admindashboard.php" class="home-btn">Home</a>
        <a href="add_employee.php" class="new-employee-btn">New Employee</a>
    </div>
    <div class="header">List of Employee</div>
    <div class="tabs">
        <a href="?status=Active&page=1&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>&position=<?= urlencode($position_filter) ?>"><button class="tab <?= $status=='Active'?'active':'' ?>">Active</button></a>
        <a href="?status=Inactive&page=1&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>&position=<?= urlencode($position_filter) ?>"><button class="tab <?= $status=='Inactive'?'active':'' ?>">Inactive</button></a>
    </div>
    <div class="search-bar-container">
        <form method="get" style="display: flex; gap: 8px; align-items: center;">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <input type="hidden" name="per_page" value="<?= $records_per_page ?>">
            <input type="hidden" name="position" value="<?= htmlspecialchars($position_filter) ?>">
            <input type="text" name="search" class="search-input" placeholder="Search employee..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn"><i class="fa fa-search"></i> Search</button>
        </form>
    </div>
    <div class="records-per-page-container">
        <form id="perPageForm" method="get" style="display:inline;">
            <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
            <input type="hidden" name="page" value="1">
            <input type="hidden" name="position" value="<?= htmlspecialchars($position_filter) ?>">
            <select name="per_page" class="records-per-page-select" onchange="document.getElementById('perPageForm').submit();">
                <?php foreach ($allowed_per_page as $opt): ?>
                    <option value="<?= $opt ?>" <?= $records_per_page == $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
            </select>
            records per page
        </form>
    </div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Position</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php 
        $rownum = $offset + 1; 
        while($row = $result->fetch_assoc()): 
        ?>
            <tr>
                <td><?= $rownum++ ?></td>
                <td><?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']) ?></td>
                <td><?= htmlspecialchars($row['position']) ?></td>
                <td>
                    <div class="actions-flex">
                        <?php if ($row['photo'] && file_exists($row['photo'])): ?>
                            <img src="<?= htmlspecialchars($row['photo']) ?>" class="employee-img" alt="Photo">
                        <?php else: ?>
                            <img src="uploads/default.png" class="employee-img" alt="No Photo">
                        <?php endif; ?>
                        <a href="employee_profile.php?id=<?= $row['id'] ?>" class="profile-link">Profile</a>
                        <form method="POST" action="delete_employee.php" style="display:inline;" data-delete-employee="1" data-employee="<?= htmlspecialchars($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']) ?>">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination-container">
        <!-- Previous Button -->
        <a href="?status=<?= $status ?>&page=<?= max(1, $page - 1) ?>&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>" class="pagination-btn">&larr; Previous</a>
        
        <!-- Page Numbers -->
        <?php
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        if ($start_page > 1): ?>
            <a href="?status=<?= $status ?>&page=1&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>" class="pagination-btn">1</a>
            <?php if ($start_page > 2): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
            <a href="?status=<?= $status ?>&page=<?= $i ?>&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>" class="pagination-btn <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
        <?php endfor; ?>
        
        <?php if ($end_page < $total_pages): ?>
            <?php if ($end_page < $total_pages - 1): ?>
                <span class="pagination-btn" style="cursor: default;">...</span>
            <?php endif; ?>
            <a href="?status=<?= $status ?>&page=<?= $total_pages ?>&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>" class="pagination-btn"><?= $total_pages ?></a>
        <?php endif; ?>
        
        <!-- Next Button -->
        <a href="?status=<?= $status ?>&page=<?= min($total_pages, $page + 1) ?>&per_page=<?= $records_per_page ?>&search=<?= urlencode($search) ?>" class="pagination-btn">Next &rarr;</a>
        
        <!-- Page Info -->
        <div class="pagination-info">
            Showing <?= $offset + 1 ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> employees
        </div>
    </div>
    <?php else: ?>
    <div style="text-align: center; margin-top: 20px; color: #6b7280; font-size: 0.9em;">
        No pagination needed - all employees fit on one page (<?= $total_records ?> total employees)
    </div>
    <?php endif; ?>
</div>
<script>
// SweetAlert2 confirm modal for deleting an employee (same design as payroll)
document.querySelectorAll('form[data-delete-employee]')?.forEach(function(frm){
    frm.addEventListener('submit', function(e){
        e.preventDefault();
        var employee = this.getAttribute('data-employee') || 'this employee';
        var formEl = this;
        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete "' + employee + '"? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            var fd = new FormData(formEl);
            var action = formEl.getAttribute('action') || 'delete_employee.php';
            fetch(action, { 
                method: 'POST', 
                body: fd,
                headers: { 'Accept': 'application/json' }
            })
                .then(function(res){ return res.json().catch(function(){ return { success: false, message: 'Invalid server response' }; }); })
                .then(function(data){
                    if (!data || !data.success) {
                        throw new Error(data && data.message ? data.message : 'Delete failed');
                    }
                    return Swal.fire({
                        title: 'Deleted!',
                        text: 'The employee has been removed.',
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    });
                })
                .then(function(){ location.reload(); })
                .catch(function(err){
                    Swal.fire({ title: 'Delete failed', text: err && err.message ? err.message : 'Please try again.', icon: 'error', confirmButtonColor: '#2563eb' });
                });
        });
    });
});
</script>
</body>
</html>
<?php
// $stmt->close(); // Removed to prevent fatal error
// $count_stmt->close(); // Removed to prevent fatal error
$conn->close();
?>
