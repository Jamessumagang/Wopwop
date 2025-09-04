<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$message = '';
$project_id = null;
$division_name = '';
$steps = [];
$project_name = '';

// Get project ID and division name from URL
if (isset($_GET['project_id']) && isset($_GET['division_name'])) {
    $project_id = $_GET['project_id'];
    $division_name = urldecode($_GET['division_name']);

    // Fetch project name for display
    $stmt = $conn->prepare("SELECT project_name FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $project_name = $result->fetch_assoc()['project_name'];
    }
    $stmt->close();

    // Fetch existing steps for this project and division
    $stmt = $conn->prepare("SELECT * FROM project_phase_steps WHERE project_id = ? AND division_name = ? ORDER BY step_order ASC");
    $stmt->bind_param("is", $project_id, $division_name);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        // Ensure each step entry is properly structured with arrays for image_path and file_path
        if (!isset($steps[$row['step_order'] - 1])) {
            $steps[$row['step_order'] - 1] = [
                'step_id' => $row['step_id'],
                'step_description' => $row['step_description'],
                'is_finished' => $row['is_finished'],
                'step_order' => $row['step_order'],
                'image_path' => [], // Initialize image_path as an empty array
                'file_path' => []   // Initialize file_path as an empty array
            ];
        }
        // Add image path to the specific step's image_path array
        if (!empty($row['image_path'])) {
            $steps[$row['step_order'] - 1]['image_path'][] = $row['image_path'];
        }
        // Add file path to the specific step's file_path array
        if (!empty($row['file_path'])) {
            $steps[$row['step_order'] - 1]['file_path'][] = $row['file_path'];
        }
    }
    $stmt->close();

    // If no steps exist, create 10 empty ones for initial input
    if (empty($steps)) {
        for ($i = 1; $i <= 10; $i++) {
            $steps[] = [
                'step_id' => null,
                'step_description' => '',
                'is_finished' => false,
                'step_order' => $i,
                'image_path' => [], // Also initialize image_path as an array for empty steps
                'file_path' => []   // Also initialize file_path as an array for empty steps
            ];
        }
    } else {
        // If existing steps are loaded, ensure we always have 10 steps for the form
        while (count($steps) < 10) {
            $steps[] = [
                'step_id' => null,
                'step_description' => '',
                'is_finished' => false,
                'step_order' => count($steps) + 1,
                'image_path' => [],
                'file_path' => []
            ];
        }
    }

} else {
    $message = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Project ID or Division Name not provided.</div>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['project_id']) && isset($_POST['division_name'])) {
    $project_id = $_POST['project_id'];
    $division_name = $_POST['division_name'];

    // Create uploads directory if it doesn't exist
    $upload_dir = 'uploads/step_images/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Get existing steps data to preserve images and descriptions correctly
    $existing_data_for_all_steps = [];
    $stmt = $conn->prepare("SELECT * FROM project_phase_steps WHERE project_id = ? AND division_name = ? ORDER BY step_order ASC");
    $stmt->bind_param("is", $project_id, $division_name);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        if (!isset($existing_data_for_all_steps[$row['step_order']])) {
            $existing_data_for_all_steps[$row['step_order']] = [
                'step_id' => $row['step_id'],
                'step_description' => $row['step_description'],
                'is_finished' => $row['is_finished'],
                'step_order' => $row['step_order'],
                'image_path' => [],
                'file_path' => []
            ];
        }
        if (!empty($row['image_path'])) {
            $existing_data_for_all_steps[$row['step_order']]['image_path'][] = $row['image_path'];
        }
        if (!empty($row['file_path'])) {
            $existing_data_for_all_steps[$row['step_order']]['file_path'][] = $row['file_path'];
        }
    }
    $stmt->close();

    $insert_success = true;
    for ($i = 1; $i <= 10; $i++) {
        $step_description = $_POST['step_' . $i];
        $is_finished = isset($_POST['finished_' . $i]) ? 1 : 0;

        $current_step_new_image_paths = [];

        // --- Handle new file uploads for this specific step ---
        if (isset($_FILES['step_image_' . $i]) && is_array($_FILES['step_image_' . $i]['name'])) {
            foreach ($_FILES['step_image_' . $i]['name'] as $key => $filename) {
                if ($_FILES['step_image_' . $i]['error'][$key] == 0) {
                    $file = [
                        'name' => $_FILES['step_image_' . $i]['name'][$key],
                        'type' => $_FILES['step_image_' . $i]['type'][$key],
                        'tmp_name' => $_FILES['step_image_' . $i]['tmp_name'][$key],
                        'error' => $_FILES['step_image_' . $i]['error'][$key],
                        'size' => $_FILES['step_image_' . $i]['size'][$key]
                    ];

                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');

                    if (in_array($file_extension, $allowed_extensions)) {
                        $new_filename = 'step_' . $project_id . '_' . $division_name . '_' . $i . '_' . time() . '_' . $key . '.' . $file_extension;
                        $target_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            $current_step_new_image_paths[] = $target_path;
                        }
                    }
                }
            }
        }

        $current_step_new_file_paths = [];

        // --- Handle new file uploads for this specific step ---
        if (isset($_FILES['step_file_' . $i]) && is_array($_FILES['step_file_' . $i]['name'])) {
            foreach ($_FILES['step_file_' . $i]['name'] as $key => $filename) {
                if ($_FILES['step_file_' . $i]['error'][$key] == 0) {
                    $file = [
                        'name' => $_FILES['step_file_' . $i]['name'][$key],
                        'type' => $_FILES['step_file_' . $i]['type'][$key],
                        'tmp_name' => $_FILES['step_file_' . $i]['tmp_name'][$key],
                        'error' => $_FILES['step_file_' . $i]['error'][$key],
                        'size' => $_FILES['step_file_' . $i]['size'][$key]
                    ];

                    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $allowed_file_extensions = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'zip', 'rar');

                    if (in_array($file_extension, $allowed_file_extensions)) {
                        $new_filename = 'step_file_' . $project_id . '_' . $division_name . '_' . $i . '_' . time() . '_' . $key . '.' . $file_extension;
                        $target_path = $upload_dir . $new_filename;

                        if (move_uploaded_file($file['tmp_name'], $target_path)) {
                            $current_step_new_file_paths[] = $target_path;
                        }
                    }
                }
            }
        }

        // --- Determine the final set of image paths for this step (existing + new uploads) ---
        $existing_images_from_db = $existing_data_for_all_steps[$i]['image_path'] ?? [];
        $final_image_paths_for_this_step = array_merge($existing_images_from_db, $current_step_new_image_paths);

        // --- Handle images marked for deletion from the frontend ---
        $images_to_delete_from_frontend = $_POST['delete_image_path_' . $i] ?? [];
        foreach ($images_to_delete_from_frontend as $deleted_path) {
            // Remove from the final list of images to be saved
            $key_in_final_list = array_search($deleted_path, $final_image_paths_for_this_step);
            if ($key_in_final_list !== false) {
                unset($final_image_paths_for_this_step[$key_in_final_list]);
            }
            // Also delete from filesystem if it exists
            if (file_exists($deleted_path)) {
                unlink($deleted_path);
            }
        }
        $final_image_paths_for_this_step = array_values($final_image_paths_for_this_step); // Re-index array

        // --- Determine the final set of file paths for this step (existing + new uploads) ---
        $existing_files_from_db = $existing_data_for_all_steps[$i]['file_path'] ?? [];
        $final_file_paths_for_this_step = array_merge($existing_files_from_db, $current_step_new_file_paths);

        // --- Handle files marked for deletion from the frontend ---
        $files_to_delete_from_frontend = $_POST['delete_file_path_' . $i] ?? [];
        foreach ($files_to_delete_from_frontend as $deleted_path) {
            // Remove from the final list of files to be saved
            $key_in_final_list = array_search($deleted_path, $final_file_paths_for_this_step);
            if ($key_in_final_list !== false) {
                unset($final_file_paths_for_this_step[$key_in_final_list]);
            }
            // Also delete from filesystem if it exists
            if (file_exists($deleted_path)) {
                unlink($deleted_path);
            }
        }
        $final_file_paths_for_this_step = array_values($final_file_paths_for_this_step); // Re-index array

        // --- Delete old files from filesystem ONLY if the step is effectively being cleared ---
        // This means, if the step description is empty AND there are no images or files (either existing or newly uploaded)
        if (empty($step_description) && empty($final_image_paths_for_this_step) && empty($final_file_paths_for_this_step)) {
            foreach ($existing_images_from_db as $old_image_path) {
                if (file_exists($old_image_path)) {
                    unlink($old_image_path);
                }
            }
            foreach ($existing_files_from_db as $old_file_path) {
                if (file_exists($old_file_path)) {
                    unlink($old_file_path);
                }
            }
        }

        // --- Delete existing records for *this specific step* from the database ---
        $stmt_delete_current_step_records = $conn->prepare("DELETE FROM project_phase_steps WHERE project_id = ? AND division_name = ? AND step_order = ?");
        $stmt_delete_current_step_records->bind_param("isi", $project_id, $division_name, $i);
        $stmt_delete_current_step_records->execute();
        $stmt_delete_current_step_records->close();

        // --- Insert the determined records for *this specific step* ---
        if (!empty($step_description) || !empty($final_image_paths_for_this_step) || !empty($final_file_paths_for_this_step)) {
            // If we have both images and files, we need to create combinations
            $max_count = max(count($final_image_paths_for_this_step), count($final_file_paths_for_this_step), 1);
            
            for ($j = 0; $j < $max_count; $j++) {
                $image_path = isset($final_image_paths_for_this_step[$j]) ? $final_image_paths_for_this_step[$j] : '';
                $file_path = isset($final_file_paths_for_this_step[$j]) ? $final_file_paths_for_this_step[$j] : '';
                
                $stmt_insert = $conn->prepare("INSERT INTO project_phase_steps (project_id, division_name, step_description, is_finished, step_order, image_path, file_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_insert->bind_param("issiiss", $project_id, $division_name, $step_description, $is_finished, $i, $image_path, $file_path);
                
            if (!$stmt_insert->execute()) {
                $message = "<div class=\"alert error\"><i class=\"fa fa-times-circle\"></i> Error saving step " . $i . ": " . $stmt_insert->error . "</div>";
                $insert_success = false;
                break;
            }
            $stmt_insert->close();
            }
        }
    }

    if ($insert_success) {
        $message = "<div class=\"alert success\"><i class=\"fa fa-check-circle\"></i> Project phase steps updated successfully!</div>";
        // Refresh data to show latest changes (re-fetch to update $steps array on the page)
        $steps = [];
        $stmt = $conn->prepare("SELECT * FROM project_phase_steps WHERE project_id = ? AND division_name = ? ORDER BY step_order ASC");
        $stmt->bind_param("is", $project_id, $division_name);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            if (!isset($steps[$row['step_order'] - 1])) {
                $steps[$row['step_order'] - 1] = [
                    'step_id' => $row['step_id'],
                    'step_description' => $row['step_description'],
                    'is_finished' => $row['is_finished'],
                    'step_order' => $row['step_order'],
                    'image_path' => [],
                    'file_path' => []
                ];
            }
            if (!empty($row['image_path'])) {
                $steps[$row['step_order'] - 1]['image_path'][] = $row['image_path'];
            }
            if (!empty($row['file_path'])) {
                $steps[$row['step_order'] - 1]['file_path'][] = $row['file_path'];
            }
        }
        $stmt->close();

        // If less than 10 steps were saved, pad with empty ones
        while (count($steps) < 10) {
            $steps[] = [
                'step_id' => null,
                'step_description' => '',
                'is_finished' => false,
                'step_order' => count($steps) + 1,
                'image_path' => [],
                'file_path' => []
            ];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Project Phase Steps - <?php echo htmlspecialchars($division_name); ?></title>
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
            --muted: #f1f5f9;
            --ring: 0 0 0 3px rgba(37, 99, 235, 0.25);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }

        /* Base Styles */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            background: url('images/background.webp') no-repeat center center fixed, linear-gradient(135deg, #e0e7ff 0%, #f7fafc 100%);
            background-size: cover;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        /* Header */
        .header {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }

        .header h1 {
            color: #2563eb;
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }

        .header p {
            color: #64748b;
        }

        /* Form Styles */
        .form-container {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            padding: 1.75rem;
            margin-bottom: 2rem;
            border: 1px solid var(--border);
        }

        .form-container h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2563eb;
        }

        .form-group {
            margin-bottom: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            align-items: stretch;
            padding: 1.1rem 1.1rem 1.2rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: linear-gradient(180deg, #ffffff, #fbfdff);
            box-shadow: var(--shadow-sm);
        }

        .form-group label {
            color: var(--text);
            font-weight: 500;
        }

        .form-group input[type="text"] {
            width: 100%;
            padding: 0.8rem 0.9rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 1rem;
            color: var(--text);
            background: var(--surface);
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-group input[type="text"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: var(--ring);
        }

        .form-group input[type="checkbox"] {
            transform: scale(1.2);
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.75rem 1.1rem;
            border: 1px solid transparent;
            border-radius: var(--radius-sm);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.15s ease, box-shadow 0.15s ease, background 0.2s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary { background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; }
        .btn-primary:hover { background: linear-gradient(135deg, #1d4ed8, #1e40af); transform: translateY(-1px); box-shadow: 0 8px 18px rgba(29,78,216,.25); }

        .btn-secondary { background: linear-gradient(135deg, #64748b, #475569); color: white; }
        .btn-secondary:hover { background: linear-gradient(135deg, #475569, #334155); transform: translateY(-1px); box-shadow: 0 8px 18px rgba(2,6,23,.15); }

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

        /* Add these styles to the existing CSS */
        .upload-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 0.5rem;
        }
        .preview-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            align-items: start;
            margin-top: 0.5rem;
        }
        @media (max-width: 768px) { .preview-row { grid-template-columns: 1fr; } }
        .image-upload { display: grid; grid-template-columns: 1fr; gap: 0.75rem; margin-top: 0.5rem; }

        .image-upload-btn { background: #0ea5e975; color: #fff; padding: 0.55rem 1rem; border-radius: var(--radius-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; white-space: nowrap; box-shadow: var(--shadow-sm); border: 1px solid transparent; width: fit-content; }
        .image-upload-btn:hover { background: #0284c7; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(2,132,199,.25); }

        .image-preview-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 1rem; margin-top: 0.25rem; min-height: 160px; }

        .image-preview { position: relative; width: 100%; padding-top: 100%; border: 1px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; background: var(--muted); box-shadow: var(--shadow-sm); }

        .image-preview img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }

        .image-number {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        /* Remove delete button styles */
        .delete-image-btn { position: absolute; top: 6px; right: 6px; background: rgba(239,68,68,.9); color: #fff; border: none; border-radius: 999px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.2s ease, transform 0.15s ease; box-shadow: var(--shadow-sm); }
        .image-preview:hover .delete-image-btn { opacity: 1; }
        .delete-image-btn:hover { transform: scale(1.05); }

        .no-image {
            color: #666;
            font-size: 0.9rem;
            text-align: center;
            padding: 1rem;
            border: 2px dashed #ccc;
            border-radius: 0.5rem;
            width: 200px;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Step header and sections */
        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border);
        }

        .step-title {
            font-size: 1rem;
            color: var(--text);
            font-weight: 600;
        }

        .section {
            margin-top: 0.5rem;
        }

        .section-title {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-light);
            margin-bottom: 0.5rem;
        }

        /* Toggle switch */
        .toggle {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
            user-select: none;
        }
        .toggle input { display: none; }
        .toggle-track {
            position: relative;
            width: 42px;
            height: 24px;
            background: #cbd5e1;
            border-radius: 999px;
            transition: background 0.2s ease;
        }
        .toggle-thumb {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 999px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease;
        }
        .toggle input:checked + .toggle-track { background: var(--primary); }
        .toggle input:checked + .toggle-track .toggle-thumb { transform: translateX(18px); }
        .toggle-label { color: var(--text); font-weight: 500; font-size: 0.9rem; }

        /* File Upload Styles */
        .file-upload {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .file-upload-btn { background:#beb8ec; color: #fff; padding: 0.55rem 1rem; border-radius: var(--radius-sm); cursor: pointer; display: inline-flex; align-items: center; gap: 0.5rem; transition: all 0.2s ease; white-space: nowrap; box-shadow: var(--shadow-sm); border: 1px solid transparent; width: fit-content; }
        .file-upload-btn:hover { background: #877af1;9669; transform: translateY(-1px); box-shadow: 0 8px 18px rgba(5,150,105,.25); }

        .file-preview-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem; margin-top: 0.25rem; min-height: 100px; }

        .file-preview { position: relative; width: 100%; min-height: 96px; border: 1px solid var(--border); border-radius: var(--radius-sm); background: var(--muted); display: flex; align-items: center; justify-content: center; flex-direction: column; padding: 0.75rem; gap: 0.35rem; box-shadow: var(--shadow-sm); margin-left: 105%;}

        .file-preview .file-icon { font-size: 1.6rem; color: #475569; }

        .file-preview .file-name {
            font-size: 0.75rem;
            color: #495057;
            text-align: center;
            word-break: break-all;
            max-height: 2.5rem;
            overflow: hidden;
            line-height: 1.2;
        }

        .file-number {
            position: absolute;
            top: 5px;
            left: 5px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
        }

        .delete-file-btn { position: absolute; top: 6px; right: 6px; background: rgba(239,68,68,.9); color: #fff; border: none; border-radius: 999px; width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; cursor: pointer; opacity: 0; transition: opacity 0.2s ease, transform 0.15s ease; box-shadow: var(--shadow-sm); }
        .file-preview:hover .delete-file-btn { opacity: 1; }
        .delete-file-btn:hover { transform: scale(1.05); }

        .no-file {
            margin-left: 150%;
            color: #666;
            font-size: 0.9rem;
            text-align: center;
            padding: 1rem;
            border: 2px dashed #ccc;
            border-radius: 0.5rem;
            width: 200px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .no-file-inline {
            color: var(--text-light);
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Edit Steps for <?php echo htmlspecialchars($division_name); ?></h1>
            <p>Project: <?php echo htmlspecialchars($project_name); ?></p>
        </div>
        
        <div class="form-container">
            <?php echo $message; ?>
            <?php if ($project_id && $division_name): ?>
                <form action="edit_project_phase_steps.php?project_id=<?php echo $project_id; ?>&division_name=<?php echo urlencode($division_name); ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($project_id); ?>">
                    <input type="hidden" name="division_name" value="<?php echo htmlspecialchars($division_name); ?>">
                    
                    <?php for ($i = 0; $i < 10; $i++): ?>
                        <div class="form-group">
                            <div class="step-header">
                                <label for="step_<?php echo $i + 1; ?>" class="step-title">Step <?php echo $i + 1; ?></label>
                                <label class="toggle">
                                    <input type="checkbox" name="finished_<?php echo $i + 1; ?>" <?php echo ($steps[$i]['is_finished'] ?? false) ? 'checked' : ''; ?>>
                                    <span class="toggle-track"><span class="toggle-thumb"></span></span>
                                    <span class="toggle-label">Finished</span>
                                </label>
                            </div>
                            <input type="text" id="step_<?php echo $i + 1; ?>" name="step_<?php echo $i + 1; ?>" placeholder="Describe the step..." value="<?php echo htmlspecialchars($steps[$i]['step_description'] ?? ''); ?>">
                            <div class="upload-row">
                                <div style="display:flex; align-items:center; gap:0.5rem;">
                                    <div class="section-title" style="margin:0;"><i class="fa fa-images"></i> Images</div>
                                <label for="step_image_<?php echo $i + 1; ?>" class="image-upload-btn">
                                        <i class="fa fa-image"></i> Upload Images
                                </label>
                                <input type="file" id="step_image_<?php echo $i + 1; ?>" name="step_image_<?php echo $i + 1; ?>[]" accept="image/*" multiple style="display: none;">
                                </div>
                                <div style="display:flex; align-items:flex-start; gap:0.5rem; flex-direction:column;">
                                    <div style="display:flex; align-items:center; gap:0.5rem;">
                                        <div class="section-title" style="margin:0;"><i class="fa fa-file"></i> Files</div>
                                        <label for="step_file_<?php echo $i + 1; ?>" class="file-upload-btn">
                                            <i class="fa fa-file"></i> Upload Files
                                        </label>
                                        <input type="file" id="step_file_<?php echo $i + 1; ?>" name="step_file_<?php echo $i + 1; ?>[]" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.zip,.rar" multiple style="display: none;">
                                    </div>
                                </div>
                            </div>
                            <div class="preview-row">
                                <div class="image-upload">
                                <div class="image-preview-container" data-step="<?php echo $i + 1; ?>">
                                    <?php if (!empty($steps[$i]['image_path'])): ?>
                                        <?php 
                                        $images = is_array($steps[$i]['image_path']) ? $steps[$i]['image_path'] : [$steps[$i]['image_path']];
                                        foreach ($images as $index => $image): 
                                        ?>
                                            <div class="image-preview" data-image="<?php echo htmlspecialchars($image); ?>">
                                                <span class="image-number"><?php echo $index + 1; ?></span>
                                                <img src="<?php echo htmlspecialchars($image); ?>" alt="Step <?php echo $i + 1; ?> image <?php echo $index + 1; ?>">
                                                <button type="button" class="delete-image-btn" data-step="<?php echo $i + 1; ?>" data-index="<?php echo $index; ?>" title="Delete image">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-image">No images selected</div>
                                    <?php endif; ?>
                                    </div>
                                </div>
                                <div class="file-upload section">
                                    <div class="file-preview-container" data-step="<?php echo $i + 1; ?>">
                                    <?php if (!empty($steps[$i]['file_path'])): ?>
                                        <?php 
                                        $files = is_array($steps[$i]['file_path']) ? $steps[$i]['file_path'] : [$steps[$i]['file_path']];
                                        foreach ($files as $index => $file): 
                                            $file_name = basename($file);
                                            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                            $file_icon = 'fa-file';
                                            if (in_array($file_extension, ['pdf'])) $file_icon = 'fa-file-pdf';
                                            elseif (in_array($file_extension, ['doc', 'docx'])) $file_icon = 'fa-file-word';
                                            elseif (in_array($file_extension, ['xls', 'xlsx'])) $file_icon = 'fa-file-excel';
                                            elseif (in_array($file_extension, ['txt'])) $file_icon = 'fa-file-text';
                                            elseif (in_array($file_extension, ['zip', 'rar'])) $file_icon = 'fa-file-archive';
                                        ?>
                                            <div class="file-preview" data-file="<?php echo htmlspecialchars($file); ?>">
                                                <span class="file-number"><?php echo $index + 1; ?></span>
                                                <i class="fa <?php echo $file_icon; ?> file-icon"></i>
                                                <div class="file-name"><?php echo htmlspecialchars($file_name); ?></div>
                                                <button type="button" class="delete-file-btn" data-step="<?php echo $i + 1; ?>" data-index="<?php echo $index; ?>" title="Delete file">
                                                    <i class="fa fa-times"></i>
                                                </button>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="no-file">No files selected</div>
                                    <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endfor; ?>

                    <div class="form-actions">
                        <a href="view_project.php?id=<?php echo $project_id; ?>" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back to Project</a>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Save Steps</button>
                    </div>
                </form>
            <?php else: ?>
                <p>Could not load project phase steps. Please ensure project ID and division name are provided.</p>
            <?php endif; ?>
        </div>
    </div>
    <!-- Remove Carousel Modal -->
    <div class="carousel-modal">
        <div class="carousel-content">
            <button class="carousel-close"><i class="fa fa-times"></i></button>
            <button class="carousel-nav carousel-prev"><i class="fa fa-chevron-left"></i></button>
            <img class="carousel-image" src="" alt="Carousel image">
            <button class="carousel-nav carousel-next"><i class="fa fa-chevron-right"></i></button>
            <div class="carousel-counter"></div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            // Image upload handling
            const imageInputs = document.querySelectorAll('input[type="file"][id*="image"]');
            imageInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const step = input.id.split('_')[2];
                    const container = document.querySelector(`.image-preview-container[data-step="${step}"]`);
                    
                    if (this.files && this.files.length > 0) {
                        // Remove "No images selected" message if it exists
                        const noImage = container.querySelector('.no-image');
                        if (noImage) {
                            noImage.remove();
                        }
                        
                        // Get current number of images in this step
                        const currentImageCount = container.querySelectorAll('.image-preview').length;
                        
                        // Clear existing images if this is a new upload
                        if (currentImageCount === 0) {
                        container.innerHTML = '';
                        }
                        
                        Array.from(this.files).forEach((file, index) => {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const preview = document.createElement('div');
                                preview.className = 'image-preview';
                                preview.setAttribute('data-step', step);
                                preview.setAttribute('data-index', currentImageCount + index);
                                
                                // Add image number
                                const imageNumber = document.createElement('span');
                                imageNumber.className = 'image-number';
                                imageNumber.textContent = currentImageCount + index + 1;
                                preview.appendChild(imageNumber);
                                
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                preview.appendChild(img);
                                
                                const deleteBtn = document.createElement('button');
                                deleteBtn.className = 'delete-image-btn';
                                deleteBtn.innerHTML = '<i class="fa fa-times"></i>';
                                deleteBtn.setAttribute('data-step', step);
                                deleteBtn.setAttribute('data-index', currentImageCount + index);
                                preview.appendChild(deleteBtn);
                                
                                container.appendChild(preview);
                            }
                            reader.readAsDataURL(file);
                        });
                    }
                });
            });

            // File upload handling
            const fileInputs = document.querySelectorAll('input[type="file"][id*="file"]');
            fileInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const step = input.id.split('_')[2];
                    const container = document.querySelector(`.file-preview-container[data-step="${step}"]`);
                    
                    if (this.files && this.files.length > 0) {
                        // Remove "No files selected" message if it exists
                        const noFile = container.querySelector('.no-file');
                        if (noFile) {
                            noFile.remove();
                        }
                        
                        // Get current number of files in this step
                        const currentFileCount = container.querySelectorAll('.file-preview').length;
                        
                        // Clear existing files if this is a new upload
                        if (currentFileCount === 0) {
                            container.innerHTML = '';
                        }
                        
                        Array.from(this.files).forEach((file, index) => {
                            const preview = document.createElement('div');
                            preview.className = 'file-preview';
                            preview.setAttribute('data-step', step);
                            preview.setAttribute('data-index', currentFileCount + index);
                            
                            // Add file number
                            const fileNumber = document.createElement('span');
                            fileNumber.className = 'file-number';
                            fileNumber.textContent = currentFileCount + index + 1;
                            preview.appendChild(fileNumber);
                            
                            // Determine file icon based on extension
                            const fileName = file.name;
                            const fileExtension = fileName.split('.').pop().toLowerCase();
                            let fileIcon = 'fa-file';
                            if (['pdf'].includes(fileExtension)) fileIcon = 'fa-file-pdf';
                            else if (['doc', 'docx'].includes(fileExtension)) fileIcon = 'fa-file-word';
                            else if (['xls', 'xlsx'].includes(fileExtension)) fileIcon = 'fa-file-excel';
                            else if (['txt'].includes(fileExtension)) fileIcon = 'fa-file-text';
                            else if (['zip', 'rar'].includes(fileExtension)) fileIcon = 'fa-file-archive';
                            
                            // Add file icon
                            const icon = document.createElement('i');
                            icon.className = `fa ${fileIcon} file-icon`;
                            preview.appendChild(icon);
                            
                            // Add file name
                            const nameDiv = document.createElement('div');
                            nameDiv.className = 'file-name';
                            nameDiv.textContent = fileName;
                            preview.appendChild(nameDiv);
                            
                            // Add delete button
                            const deleteBtn = document.createElement('button');
                            deleteBtn.className = 'delete-file-btn';
                            deleteBtn.innerHTML = '<i class="fa fa-times"></i>';
                            deleteBtn.setAttribute('data-step', step);
                            deleteBtn.setAttribute('data-index', currentFileCount + index);
                            preview.appendChild(deleteBtn);
                            
                            container.appendChild(preview);
                        });
                    }
                });
            });

            // Delete image and file functionality
            document.addEventListener('click', function(e) {
                if (e.target.closest('.delete-image-btn')) {
                    const btn = e.target.closest('.delete-image-btn');
                    const step = btn.getAttribute('data-step');
                    const imagePreview = btn.closest('.image-preview');
                    const imagePath = imagePreview.getAttribute('data-image'); // existing images have data-image
                    const container = document.querySelector(`.image-preview-container[data-step="${step}"]`);
                    
                    // Remove the image preview from the DOM
                    btn.closest('.image-preview').remove();
                    
                    // Update image numbers for this step only
                    container.querySelectorAll('.image-preview').forEach((preview, idx) => {
                        preview.querySelector('.image-number').textContent = idx + 1;
                        // Update data-index if needed (for consistency, though not strictly used by PHP for existing images)
                        // preview.setAttribute('data-index', idx);
                    });
                    
                    // If no images left, show "No images selected"
                    if (container.children.length === 0) {
                        container.innerHTML = '<div class="no-image">No images selected</div>';
                    }
                    
                    // Only add a hidden input if this was an existing server image (has data-image)
                    if (imagePath) {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                        hiddenInput.name = `delete_image_path_${step}[]`;
                        hiddenInput.value = imagePath;
                        container.appendChild(hiddenInput);
                    }
                }
                
                if (e.target.closest('.delete-file-btn')) {
                    const btn = e.target.closest('.delete-file-btn');
                    const step = btn.getAttribute('data-step');
                    const filePreview = btn.closest('.file-preview');
                    const filePath = filePreview.getAttribute('data-file'); // existing files have data-file
                    const container = document.querySelector(`.file-preview-container[data-step="${step}"]`);
                    
                    // Remove the file preview from the DOM
                    btn.closest('.file-preview').remove();
                    
                    // Update file numbers for this step only
                    container.querySelectorAll('.file-preview').forEach((preview, idx) => {
                        preview.querySelector('.file-number').textContent = idx + 1;
                    });
                    
                    // If no files left, show "No files selected"
                    if (container.children.length === 0) {
                        container.innerHTML = '<div class="no-file">No files selected</div>';
                    }
                    
                    // Only add a hidden input if this was an existing server file (has data-file)
                    if (filePath) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = `delete_file_path_${step}[]`;
                        hiddenInput.value = filePath;
                        container.appendChild(hiddenInput);
                    }
                }
            });
        });
    </script>
</body>
</html> 