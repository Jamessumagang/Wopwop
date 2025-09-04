<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

include 'db.php';

// Build WHERE clause for search
$where = '';
$params = [];
$types = '';
if ($search !== '') {
    $where = "WHERE project_name LIKE ? OR deadline LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types = 'ss';
}

// Fetch recent projects (e.g., last 5), including image_path and project_id, with search
$sql = "SELECT project_id, project_name, deadline, image_path FROM projects $where ORDER BY project_id DESC LIMIT 5";
$stmt = $conn->prepare($sql);
if ($where !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
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
        /* Messenger Chat Bubble Styles */
        .message-bubble {
            display: inline-block;
            padding: 16px 22px;
            border-radius: 22px;
            margin-bottom: 10px;
            max-width: 80%;
            word-break: break-word;
            font-size: 1.08em;
            box-shadow: 0 2px 12px rgba(37,99,235,0.07);
            transition: box-shadow 0.2s, background 0.2s;
        }
        .message-sent {
            background: linear-gradient(135deg, #2563eb 80%, #60a5fa 100%);
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 10px;
            margin-left: 20%;
            text-align: left;
        }
        .message-received {
            background: #f1f5fa;
            color: #222;
            align-self: flex-start;
            border-bottom-left-radius: 10px;
            margin-right: 20%;
            text-align: left;
        }
        .message-bubble:hover {
            box-shadow: 0 4px 18px rgba(37,99,235,0.13);
            background: #e0e7ff;
            color: #222;
        }
        .message-meta {
            font-size: 0.85em;
            color: #888;
            margin-top: 4px;
            margin-bottom: 14px;
            text-align: right;
        }
        .date-separator {
            text-align: center;
            color: #aaa;
            font-size: 0.9em;
            margin: 18px 0 10px 0;
            font-weight: 500;
        }
        .unread-badge {
            display: inline-block;
            background: #ef4444;
            color: #fff;
            font-size: 0.95em;
            font-weight: 700;
            border-radius: 12px;
            padding: 2px 12px;
            min-width: 24px;
            text-align: center;
            line-height: 1.2;
            margin-left: 8px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php if (isset($_GET['msgdel']) && $_GET['msgdel'] === 'fail'): ?>
    <div style="position:fixed;top:30px;right:30px;background:#ef4444;color:#fff;padding:1rem 2.2rem;border-radius:8px;box-shadow:0 2px 12px rgba(239,68,68,0.10);font-size:1.13rem;z-index:9999;transition:opacity 0.5s;">Failed to delete message. Please try again later.</div>
    <script>setTimeout(function(){document.querySelector('body > div[style*="background:#ef4444"]')?.remove();},3000);</script>
    <?php endif; ?>
    <div class="header">
        <div class="logo">RS BUILDERS PMS</div>
        <div class="header-right">
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
                <li class="dropdown <?php echo (basename($_SERVER['PHP_SELF']) == 'position.php' || basename($_SERVER['PHP_SELF']) == 'project_team.php') ? 'active' : ''; ?>">
                    <a href="#"><i class="fa fa-wrench"></i> Maintenance <span class="arrow">&#9662;</span></a>
                    <ul class="dropdown-menu">
                        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'position.php') ? 'active' : ''; ?>"><a href="position.php"><i class="fa fa-user-tie"></i> Position</a></li>
                        <li class="<?php echo (basename($_SERVER['PHP_SELF']) == 'project_team.php') ? 'active' : ''; ?>"><a href="project_team.php"><i class="fa fa-users-cog"></i> Project Team</a></li>
                    </ul>
                </li>
                <li><a href="payroll.php"><i class="fa fa-money-bill"></i> Payroll</a></li>
            </ul>
        </nav>
        <main class="main-content">
            <!-- All contact message code removed; dashboard now only shows recent projects -->
            <h2>Recent Projects</h2>
            <div class="search-bar-container">
                <form method="get" style="display: flex; gap: 8px; align-items: center;">
                    <input type="text" name="search" class="search-input" placeholder="Search project or deadline..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="search-btn"><i class="fa fa-search"></i> Search</button>
                </form>
            </div>
            <?php
            if ($result->num_rows > 0) {
                echo "<table>";
                echo "<thead>";
                echo "<tr>";
                echo "<th>Image</th>";
                echo "<th>Project Name</th>";
                echo "<th>Deadline</th>";
                echo "<th>Actions</th>";
                echo "</tr>";
                echo "</thead>";
                echo "<tbody>";

                // Output data of each row
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>";
                    if (!empty($row['image_path'])) {
                        // Display image if image_path is not empty
                        echo "<img src=\"" . htmlspecialchars($row['image_path']) . "\" alt=\"Project Image\" class=\"employee-img\"> ";
                    } else {
                        echo "No Image"; // Placeholder if no image
                    }
                    echo "</td>";
                    echo "<td>" . htmlspecialchars($row["project_name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["deadline"]) . "</td>";
                    echo "<td>";
                    // Edit and Delete buttons
                    echo "<a href=\"edit_project.php?id=" . $row["project_id"] . "\" class=\"action-btn edit-btn\">Edit</a>";
                    echo "<a href=\"delete_project.php?id=" . $row["project_id"] . "\" class=\"action-btn delete-btn\" data-project=\"" . htmlspecialchars($row["project_name"]) . "\">Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                }

                echo "</tbody>";
                echo "</table>";
            } else {
                echo "<p>No recent projects found.</p>";
            }

            $stmt->close();
            $conn->close();
            ?>
        </main>
    </div>
    <script>
document.querySelectorAll('.sidebar .dropdown > a').forEach(function(dropdownToggle) {
    dropdownToggle.addEventListener('click', function(e) {
        e.preventDefault();
        var parent = this.parentElement;
        parent.classList.toggle('active');
    });
});

// Styled confirm modal for deleting a project (SweetAlert2, same as payroll)
document.querySelectorAll('a.delete-btn').forEach(function(link){
    link.addEventListener('click', function(e){
        e.preventDefault();
        var href = this.getAttribute('href');
        var projectName = this.getAttribute('data-project') || 'this project';
        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete "' + projectName + '"? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch(href, { method: 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(){
                    return Swal.fire({
                        title: 'Deleted!',
                        text: 'The project has been removed.',
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    });
                })
                .then(function(){ window.location.reload(); })
                .catch(function(){
                    Swal.fire({ title: 'Delete failed', icon: 'error', confirmButtonColor: '#2563eb' });
                });
        });
    });
});

// Reusable styled confirm modal
function showConfirm(opts, onConfirm){
    var title = opts.title || 'Confirm';
    var message = opts.message || 'Are you sure?';
    var confirmText = opts.confirmText || 'OK';
    var cancelText = opts.cancelText || 'Cancel';
    var variant = opts.variant || 'default';

    var overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;';

    var box = document.createElement('div');
    box.style.cssText = 'background:#fff;border-radius:14px;box-shadow:0 12px 36px rgba(0,0,0,0.18);padding:18px;min-width:320px;max-width:92vw;';

    var h = document.createElement('div');
    h.textContent = title;
    h.style.cssText = 'font-weight:800;margin-bottom:8px;font-size:18px;color:' + (variant==='danger' ? '#dc2626' : '#2563eb') + ';';

    var p = document.createElement('div');
    p.textContent = message;
    p.style.cssText = 'color:#111827;margin-bottom:12px;';

    var actions = document.createElement('div');
    actions.style.cssText = 'display:flex;gap:8px;justify-content:flex-end;';

    var cancelBtn = document.createElement('button');
    cancelBtn.textContent = cancelText;
    cancelBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:#e5e7eb;color:#111;font-weight:600;cursor:pointer;';
    cancelBtn.onclick = function(){ document.body.removeChild(overlay); };

    var confirmBtn = document.createElement('button');
    confirmBtn.textContent = confirmText;
    if (variant==='danger') {
        confirmBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:linear-gradient(90deg,#dc2626 0%,#ef4444 100%);color:#fff;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(239,68,68,0.35);';
        confirmBtn.onmousedown = function(){ confirmBtn.style.boxShadow='0 6px 18px rgba(248,113,113,0.6)'; };
        confirmBtn.onmouseup = function(){ confirmBtn.style.boxShadow='0 4px 12px rgba(239,68,68,0.35)'; };
    } else {
        confirmBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:linear-gradient(90deg,#2563eb 0%,#4db3ff 100%);color:#fff;font-weight:700;cursor:pointer;';
    }
    confirmBtn.onclick = function(){ document.body.removeChild(overlay); if (typeof onConfirm==='function') onConfirm(); };

    actions.appendChild(cancelBtn);
    actions.appendChild(confirmBtn);
    box.appendChild(h);
    box.appendChild(p);
    box.appendChild(actions);
    overlay.appendChild(box);
    document.body.appendChild(overlay);
}

// Simple toast notification
function showToast(opts){
    var title = opts.title || '';
    var message = opts.message || '';
    var variant = opts.variant || 'default';

    var container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;top:16px;right:16px;z-index:10001;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }

    var bg = '#2563eb';
    if (variant === 'success') bg = '#10b981';
    if (variant === 'danger') bg = '#ef4444';

    var toast = document.createElement('div');
    toast.style.cssText = 'min-width:260px;max-width:360px;background:'+bg+';color:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);padding:12px 14px;transform:translateY(-10px);opacity:0;transition:all .2s ease;';
    toast.innerHTML = '<div style="font-weight:800;margin-bottom:4px;">'+title+'</div><div style="opacity:.95;">'+message+'</div>';
    container.appendChild(toast);
    requestAnimationFrame(function(){ toast.style.transform='translateY(0)'; toast.style.opacity='1'; });
    setTimeout(function(){ toast.style.opacity='0'; toast.style.transform='translateY(-10px)'; setTimeout(function(){ if (toast.parentNode) toast.parentNode.removeChild(toast); }, 200); }, 3000);
}
    </script>
</body>
</html>