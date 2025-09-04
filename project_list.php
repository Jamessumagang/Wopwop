<?php
include 'db.php';

// Determine the current status filter from the URL
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'Ongoing'; // Default to Ongoing

// Search logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build SQL with search and status
$sql = "SELECT project_id, project_name, location, deadline, image_path FROM projects";
$where = [];
if ($status_filter !== 'All') {
    $where[] = "project_status = '" . $conn->real_escape_string($status_filter) . "'";
}
if ($search !== '') {
    $search_esc = $conn->real_escape_string($search);
    $where[] = "(project_name LIKE '%$search_esc%' OR location LIKE '%$search_esc%' OR deadline LIKE '%$search_esc%')";
}
if (count($where) > 0) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$result = $conn->query($sql);

// Close the database connection (should be done after fetching results)
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Project List</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            max-width: 1600px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            padding: 32px 48px 24px 48px;
            position: relative;
            z-index: 1;
            margin: 100px;
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
        .home-btn, .new-project-btn {
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
        .home-btn:hover, .new-project-btn:hover {
            background: #1746a0;
        }
         .status-filters {
             margin-bottom: 16px;
             display: flex;
             gap: 8px;
         }
        .status-filter-btn {
            padding: 8px 24px;
            border: none;
            border-radius: 999px;
            font-size: 1em;
            background: #e0e7ff;
            color: #2563eb;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, color 0.2s;
             text-decoration: none; /* Remove underline */
        }
        .status-filter-btn.active {
            background: #2563eb;
            color: #fff;
        }
        .status-filter-btn:not(.active):hover {
            background: #c7d2fe;
        }
        .controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .records-per-page label {
            margin-right: 10px;
            color: #555;
        }
        .records-per-page select {
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(0,0,0,0.04);
            margin-bottom: 20px; /* Added margin-bottom */
        }
        th, td {
            border: 1px solid #e5e7eb;
            padding: 18px 16px;
            text-align: left; /* Changed to left align */
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
        .view-btn {
            background: #40c56fff;
            color: #fff;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
            transition: background 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .view-btn:hover {
            background: #307849ff;
        }
        .pagination {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .pagination a, .pagination span {
            margin: 0 5px;
            padding: 8px 12px;
            text-decoration: none;
            color: #4a7bd8;
            border: 1px solid #4a7bd8;
            border-radius: 4px;
            transition: background-color 0.2s, color 0.2s;
        }
        .pagination a:hover {
            background-color: #4a7bd8;
            color: #fff;
        }
        .pagination span.active {
            background-color: #4a7bd8;
            color: #fff;
            border-color: #4a7bd8;
        }
        .pagination-info {
            color: #555;
        }
         @media (max-width: 700px) {
            .container {
                margin: 8px;
                padding: 6px;
            }
            .header {
                font-size: 1.5em; /* Adjusted font size */
            }
            th, td {
                padding: 8px 2px;
                font-size: 0.95em;
            }
            .home-btn, .new-project-btn, .status-filter-btn {
                padding: 8px 12px;
                font-size: 0.95em;
            }
             .controls {
                 flex-direction: column;
                 align-items: flex-start;
             }
             .records-per-page {
                 margin-bottom: 10px;
             }
             .pagination {
                 flex-direction: column;
                 align-items: flex-start;
             }
             .pagination div {
                 margin-top: 10px;
             }
             .status-filters {
                 flex-direction: column;
                 gap: 5px;
             }
        }
        .search-bar-container {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 16px;
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
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    <?php if (isset($_GET['added']) && $_GET['added'] == '1'): ?>
    <div id="proj-added-toast" style="position:fixed;top:20px;right:20px;z-index:10001;">
        <div style="min-width:260px;max-width:360px;background:#10b981;color:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);padding:12px 14px;">
            <div style="font-weight:800;margin-bottom:4px;">Project Added</div>
            <div>New project has been created successfully.</div>
        </div>
        <script>
            setTimeout(function(){ var el = document.getElementById('proj-added-toast'); if (el) el.remove(); }, 3000);
        </script>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated']) && $_GET['updated'] == '1'): ?>
    <div id="proj-updated-toast" style="position:fixed;top:20px;right:20px;z-index:10001;">
        <div style="min-width:260px;max-width:360px;background:#2563eb;color:#fff;border-radius:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18);padding:12px 14px;">
            <div style="font-weight:800;margin-bottom:4px;">Project Updated</div>
            <div>Project changes have been saved successfully.</div>
        </div>
        <script>
            setTimeout(function(){ var el = document.getElementById('proj-updated-toast'); if (el) el.remove(); }, 3000);
        </script>
    </div>
    <?php endif; ?>
    <div class="container">
        <div class="top-buttons">
             <a href="Admindashboard.php" class="home-btn">Home</a>
             <a href="add_project.php" class="new-project-btn"><i class="fas fa-plus"></i> New Project</a>
        </div>
        <div class="header">List of Projects</div>

         <div class="status-filters">
            <a href="?status=Ongoing&search=<?= urlencode($search) ?>" class="status-filter-btn <?php echo ($status_filter == 'Ongoing') ? 'active' : ''; ?>">Ongoing</a>
            <a href="?status=Finished&search=<?= urlencode($search) ?>" class="status-filter-btn <?php echo ($status_filter == 'Finished') ? 'active' : ''; ?>">Finished</a>
            <a href="?status=Cancelled&search=<?= urlencode($search) ?>" class="status-filter-btn <?php echo ($status_filter == 'Cancelled') ? 'active' : ''; ?>">Cancelled</a>
        </div>

        <div class="search-bar-container">
            <form method="get" style="display: flex; gap: 8px; align-items: center;">
                <input type="hidden" name="status" value="<?= htmlspecialchars($status_filter) ?>">
                <input type="text" name="search" class="search-input" placeholder="Search project, location, or deadline..." value="<?= htmlspecialchars($search) ?>">
                <button type="submit" class="search-btn"><i class="fa fa-search"></i> Search</button>
            </form>
        </div>

        <div class="controls">
            <div class="records-per-page">
                <label for="records">Show</label>
                <select name="records" id="records">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span>records per page</span>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Project</th>
                    <th>Location</th>
                    <th>Deadline</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        // Project image cell
                        echo "<td>";
                        if (!empty($row['image_path'])) {
                            echo "<img src='" . htmlspecialchars($row['image_path']) . "' alt='Project Image' style='width:70px;height:48px;object-fit:cover;border-radius:6px;box-shadow:0 1px 4px rgba(0,0,0,0.07);'>";
                        } else {
                            echo "<img src='https://placehold.co/70x48?text=No+Image' alt='No Image' style='width:70px;height:48px;object-fit:cover;border-radius:6px;opacity:0.7;'>";
                        }
                        echo "</td>";
                        echo "<td>" . htmlspecialchars($row["project_name"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["location"]) . "</td>";
                        echo "<td>" . htmlspecialchars($row["deadline"]) . "</td>";
                        echo "<td>"
                            . "<a href=\"view_project.php?id=" . $row["project_id"] . "\" class=\"view-btn\"><i class=\"fas fa-eye\"></i> View</a>"
                            . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan=\"4\">No projects found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <div class="pagination">
            <div class="pagination-info">
                Showing 1 to X of Y entries
            </div>
            <div>
                <!-- Pagination links will go here -->
            </div>
        </div>
    </div>
</body>
</html> 