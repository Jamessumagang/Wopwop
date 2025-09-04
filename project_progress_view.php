<?php
session_start();
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Invalid project ID.');
}
$project_id = (int)$_GET['id'];
include 'db.php';

$project_details = null;
$division_progress_data = [];
$display_divisions_array = [];

// Fetch project divisions only
$sql = "SELECT project_divisions FROM projects WHERE project_id = ?";
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
    <style>
        body {
            background: url('./images/background.webp') no-repeat center center fixed;
            
            background-blend-mode: overlay;
            background-size: cover;
            font-family: 'Inter', Arial, sans-serif;
            min-height: 100vh;
            margin: 0;
        }
        .main-container {
            max-width: 1000px;
            margin: 60px auto;
            background: #fff;
            border-radius: 1.25rem;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 1.5px 6px rgba(0,0,0,0.04);
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
            position: relative;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.45rem 0.8rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.95rem;
            margin-top: 0.25rem;
            border: 1px solid transparent;
        }
        .status-badge.completed { color: #065f46; background: #d1fae5; border-color: #10b981; }
        .status-badge.completed i { color: #10b981; }
        .status-badge.pending { color: #92400e; background: #fef3c7; border-color: #f59e0b; }
        .status-badge.pending i { color: #f59e0b; }
        .home-btn {
            background: linear-gradient(90deg, #2563eb 60%, #1d4ed8 100%);
            color: #fff;
            border: none;
            border-radius: 0.75rem;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            text-decoration: none;
            margin-bottom: 2rem;
            margin-right: auto;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            transition: background 0.2s, transform 0.2s, box-shadow 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .home-btn:hover {
            background: linear-gradient(90deg, #1d4ed8 60%, #2563eb 100%);
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 6px 24px rgba(37,99,235,0.13);
        }
        .chart-container {
            margin-top: 2.5rem;
            background: #f1f5f9;
            border-radius: 1rem;
            padding: 2rem 1rem 1.5rem 1rem;
            box-shadow: 0 2px 12px rgba(37,99,235,0.06);
            width: 100%;
            max-width: 900px;
            margin-left: auto;
            margin-right: auto;
        }
        #progressChart {
            width: 900px !important;
            max-width: 100%;
            height: 420px !important;
        }
        @media (max-width: 1000px) {
            .main-container {
                max-width: 98vw;
                padding: 1.2rem 0.5rem 1.5rem 0.5rem;
            }
            .chart-container {
                padding: 1rem 0.2rem 1rem 0.2rem;
                max-width: 98vw;
            }
            #progressChart {
                width: 98vw !important;
                height: 260px !important;
            }
            .home-btn {
                font-size: 1rem;
                padding: 0.6rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <a href="client_dashboard.php" class="home-btn"><i class="fa fa-home"></i> Home</a>
        <div id="overallStatus"></div>
        <div class="chart-container">
            <canvas id="progressChart"></canvas>
        </div>
    </div>
    <script>
        // Fixed pleasant palette for phases (rotates if more phases than colors)
        const PHASE_PALETTE = [
            '#f8a5b6', // soft pink
            '#87c5ff', // sky blue
            '#ffd89a', // warm yellow
            '#a3e7d1', // mint
            '#cdb4ff', // soft purple
            '#ffb3c7', // rose
            '#9ad0ff', // light blue
            '#ffe29a', // light amber
            '#a8e6cf', // pale green
            '#b5a7ff'  // violet
        ];
        const labels = <?= json_encode(array_keys($division_progress_data)) ?>.map(l => l.replace('Phase ', 'P'));
        const data = <?= json_encode(array_values($division_progress_data)) ?>;
        // Calculate total (average) progress
        const totalProgress = data.reduce((a, b) => a + b, 0);
        const divisionsCount = data.length;
        const averageTotalProgress = divisionsCount > 0 ? (totalProgress / divisionsCount) : 0;
        labels.push('Total');
        data.push(Number(averageTotalProgress.toFixed(2)));
        // Assign distinct colors per phase; last bar (Total) highlighted in gold
        const backgroundColors = data.map((v, i) => i === data.length - 1 ? '#ffb347' : PHASE_PALETTE[i % PHASE_PALETTE.length]);
        Chart.register(window.ChartDataLabels);
        const ctx = document.getElementById('progressChart').getContext('2d');
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Progress (%)',
                    data: data,
                    backgroundColor: backgroundColors,
                    borderRadius: 8,
                    maxBarThickness: 100
                }]
            },
            options: {
                plugins: {
                    legend: { display: false },
                    datalabels: {
                        anchor: 'end',
                        align: 'end',
                        color: '#222',
                        font: { weight: 'bold', size: 16 },
                        formatter: function(value) { return value + '%'; }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        title: { display: true, text: 'Progress (%)', font: { size: 16, weight: 'bold' } },
                        grid: { color: '#e5e7eb' },
                        ticks: { font: { size: 14 } }
                    },
                    x: {
                        title: { display: true, text: 'Division', font: { size: 16, weight: 'bold' } },
                        grid: { color: '#e5e7eb' },
                        ticks: { font: { size: 14 } }
                    }
                }
            },
            plugins: [ChartDataLabels]
        });

        // Show overall status if all phases are fully complete (100%)
        const onlyPhases = data.slice(0, data.length - 1); // exclude Total bar
        const allComplete = onlyPhases.length > 0 && onlyPhases.every(v => Number(v) >= 100);
        const status = document.getElementById('overallStatus');
        if (status) {
            if (allComplete) {
                status.innerHTML = '<span class="status-badge completed"><i class="fa fa-check-circle"></i> Completed</span>';
            } else {
                status.innerHTML = '<span class="status-badge pending"><i class="fa fa-hourglass-half"></i> Pending</span>';
            }
        }
    </script>
</body>
</html> 