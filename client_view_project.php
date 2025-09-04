<?php
session_start();
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    header('Location: client_login.php');
    exit();
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid project ID.');
}
$project_id = (int)$_GET['id'];
include 'db.php';

$project_details = null;
$division_progress_data = [];
$display_divisions_array = [];

// Fetch project details
$sql = "SELECT * FROM projects WHERE project_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $project_details = $result->fetch_assoc();
        // Fetch progress for divisions
        $raw_db_progress_data = [];
        $sql_progress = "SELECT division_name, progress_percentage FROM project_progress WHERE project_id = ?";
        if ($stmt_progress = $conn->prepare($sql_progress)) {
            $stmt_progress->bind_param("i", $project_id);
            $stmt_progress->execute();
            $result_progress = $stmt_progress->get_result();
            while ($row_progress = $result_progress->fetch_assoc()) {
                $raw_db_progress_data[trim($row_progress['division_name'])] = (int)$row_progress['progress_percentage'];
            }
            $stmt_progress->close();
        }
        // Process project_divisions string
        $raw_divisions_from_project_string = $project_details['project_divisions'];
        $temp_divisions = array_map('trim', explode(',', $raw_divisions_from_project_string));
        $processed_divisions_temp = [];
        foreach ($temp_divisions as $div) {
            if (trim($div) === 'Phase' || trim($div) === '1' || trim($div) === 'Phase 1') {
                $processed_divisions_temp[] = 'Phase 1';
            } else {
                $processed_divisions_temp[] = $div;
            }
        }
        $display_divisions_array = array_values(array_unique($processed_divisions_temp));
        sort($display_divisions_array);
        foreach ($display_divisions_array as $canonical_div_name) {
            if ($canonical_div_name === 'Phase 1') {
                if (isset($raw_db_progress_data['Phase 1'])) {
                    $division_progress_data['Phase 1'] = $raw_db_progress_data['Phase 1'];
                } elseif (isset($raw_db_progress_data['Phase'])) {
                    $division_progress_data['Phase 1'] = $raw_db_progress_data['Phase'];
                } elseif (isset($raw_db_progress_data['1'])) {
                    $division_progress_data['Phase 1'] = $raw_db_progress_data['1'];
                } else {
                    $division_progress_data['Phase 1'] = 0;
                }
            } else {
                $division_progress_data[$canonical_div_name] = isset($raw_db_progress_data[$canonical_div_name]) ?
                                                                   $raw_db_progress_data[$canonical_div_name] : 0;
            }
        }
    } else {
        die('Project not found.');
    }
    $stmt->close();
} else {
    die('Error: Could not prepare statement.');
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Project Progress</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-container { max-width: 900px; margin: 30px auto; background: #fff; border-radius: 1rem; box-shadow: 0 4px 16px rgba(0,0,0,0.08); padding: 2.5rem; }
        .project-title { color: #2563eb; font-size: 2rem; font-weight: 700; margin-bottom: 1.5rem; }
        .project-header { display: flex; gap: 2rem; align-items: flex-start; margin-bottom: 1.5rem; }
        .project-image-container { flex: 0 0 200px; }
        .project-image-container img { max-width: 200px; border-radius: 8px; background: #222; display: block; }
        .project-info { flex: 1; display: flex; flex-wrap: wrap; gap: 1.2rem 2.5rem; }
        .info-label { color: #64748b; font-weight: 500; min-width: 110px; display: inline-block; }
        .info-value { color: #1e293b; font-weight: 600; }
        .project-meta { margin-bottom: 1.5rem; }
        .meta-row { margin-bottom: 0.5rem; }
        .section-title { font-weight: 600; color: #2563eb; margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .chart-container { margin-top: 2.5rem; background: #fafbfc; border-radius: 1rem; padding: 2rem; }
        .back-btn { background: #64748b; color: #fff; border: none; border-radius: 0.5rem; padding: 0.6rem 1.2rem; font-weight: 600; font-size: 1rem; cursor: pointer; text-decoration: none; margin-bottom: 1.5rem; display: inline-block; }
        .back-btn:hover { background: #475569; }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="client_dashboard.php" class="back-btn"><i class="fa fa-arrow-left"></i> Back to Dashboard</a>
        <div class="project-title">PROJECT NAME: <?= htmlspecialchars($project_details['project_name']) ?></div>
        <div class="project-header">
            <div class="project-image-container">
                <?php if (!empty($project_details['project_image'])): ?>
                    <img src="<?= htmlspecialchars($project_details['project_image']) ?>" alt="Project Image">
                <?php else: ?>
                    <img src="https://placehold.co/200x120?text=No+Image" alt="No Image">
                <?php endif; ?>
            </div>
            <div class="project-info">
                <div><span class="info-label">start date:</span> <span class="info-value"><?= htmlspecialchars($project_details['start_date']) ?></span></div>
                <div><span class="info-label">deadline:</span> <span class="info-value"><?= htmlspecialchars($project_details['end_date'] ?? '-') ?></span></div>
                <div><span class="info-label">location:</span> <span class="info-value"><?= htmlspecialchars($project_details['location'] ?? '-') ?></span></div>
                <div><span class="info-label">Project cost:</span> <span class="info-value"><?= htmlspecialchars(number_format($project_details['project_cost'],2)) ?></span></div>
                <div><span class="info-label">Foreman:</span> <span class="info-value"><?= htmlspecialchars($project_details['foreman'] ?? '-') ?></span></div>
            </div>
        </div>
        <div class="project-meta">
            <div class="meta-row"><span class="info-label">Project Type:</span> <span class="info-value"><?= htmlspecialchars($project_details['project_type'] ?? '-') ?></span></div>
            <div class="meta-row"><span class="info-label">Project Status:</span> <span class="info-value"><?= htmlspecialchars($project_details['project_status'] ?? '-') ?></span></div>
            <div class="meta-row"><span class="info-label">Project Division:</span> <span class="info-value"><?= htmlspecialchars($project_details['project_divisions'] ?? '-') ?></span></div>
        </div>
        <div class="section-title">Progress</div>
        <div class="chart-container">
            <canvas id="progressChart"></canvas>
        </div>
    </div>
    <script>
        const ctx = document.getElementById('progressChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($division_progress_data)) ?>,
                datasets: [{
                    label: 'Progress (%)',
                    data: <?= json_encode(array_values($division_progress_data)) ?>,
                    backgroundColor: '#ffb6b6',
                    borderRadius: 6,
                    maxBarThickness: 60
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Progress (%)' }
                    },
                    x: {
                        title: { display: true, text: 'Division' }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    </script>
</body>
</html> 