<?php
include 'db.php';

$project_id = null;
$project_details = null;

// Check if project_id is provided via GET (for displaying the form)
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $project_id = $_GET['id'];

    // Fetch project details
    $sql = "SELECT * FROM projects WHERE project_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $project_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $project_details = $result->fetch_assoc();
        } else {
            echo "Error: Project not found.";
        }
        $stmt->close();
    }
} elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle form submission for updating project
    $project_id = $_POST['project_id'];
    $project_name = $_POST['project_name'];
    $start_date = $_POST['start_date'];
    $deadline = $_POST['deadline'];
    $location = $_POST['location'];
    $project_cost = $_POST['project_cost'];
    $client_name = $_POST['client_name'] ?? '';
    $client_number = $_POST['client_number'] ?? '';
    $foreman = $_POST['foreman'];
    $project_type = $_POST['project_type'];
    $project_status = $_POST['project_status'];
    $project_divisions = $_POST['project_divisions'];

    // Handle image upload (similar logic as process_add_project.php)
    $image_path = $_POST['existing_image'] ?? null; // Keep existing image if no new one is uploaded
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] == 0) {
        $allowed_types = array('jpg' => 'image/jpg', 'jpeg' => 'image/jpeg', 'gif' => 'image/gif', 'png' => 'image/png');
        $file_name = $_FILES['project_image']['name'];
        $file_type = $_FILES['project_image']['type'];
        $file_size = $_FILES['project_image']['size'];

        $ext = pathinfo($file_name, PATHINFO_EXTENSION);
        if (array_key_exists($ext, $allowed_types)) {
            $maxsize = 5 * 1024 * 1024;
            if ($file_size <= $maxsize && in_array($file_type, $allowed_types)) {
                 $new_file_name = uniqid() . '.' . $ext;
                 $upload_directory = 'uploads/';
                 $destination = $upload_directory . $new_file_name;

                 if (move_uploaded_file($_FILES['project_image']['tmp_name'], $destination)) {
                     $image_path = $destination;
                     // Optional: Delete old image if it exists
                     // if (!empty($_POST['existing_image']) && file_exists($_POST['existing_image'])) {
                     //     unlink($_POST['existing_image']);
                     // }
                 } else {
                     echo "Error uploading new image.";
                 }
            } else {
                echo "Error: File size or type is invalid.";
            }
        } else {
            echo "Error: Invalid file extension.";
        }
    }

    // Ensure columns exist for client fields
    $colClientName = $conn->query("SHOW COLUMNS FROM projects LIKE 'client_name'");
    if ($colClientName && $colClientName->num_rows === 0) {
        $conn->query("ALTER TABLE projects ADD COLUMN client_name VARCHAR(255) NULL");
    }
    $colClientNumber = $conn->query("SHOW COLUMNS FROM projects LIKE 'client_number'");
    if ($colClientNumber && $colClientNumber->num_rows === 0) {
        $conn->query("ALTER TABLE projects ADD COLUMN client_number VARCHAR(255) NULL");
    }

    // Prepare an update statement including client fields
    $sql = "UPDATE projects SET project_name=?, start_date=?, deadline=?, location=?, project_cost=?, client_name=?, client_number=?, foreman=?, project_type=?, project_status=?, project_divisions=?, image_path=? WHERE project_id=?";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters (all as strings except last id)
        $stmt->bind_param("ssssssssssssi", $project_name, $start_date, $deadline, $location, $project_cost, $client_name, $client_number, $foreman, $project_type, $project_status, $project_divisions, $image_path, $project_id);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
            // Redirect back to project list with success flag
            header("location: project_list.php?updated=1");
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Project</title>
    <!-- Include your CSS links and styles here -->
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
     <style>
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: url('./images/background.webp') no-repeat center center fixed;
            
            background-blend-mode: overlay;
            background-size: cover;
            margin: 0;
            min-height: 100vh;
        }
        .container {
            margin: 40px auto;
            max-width: 540px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 1.5px 8px rgba(0,0,0,0.04);
            padding: 40px 32px 32px 32px;
            transition: box-shadow 0.2s;
        }
        .container:hover {
            box-shadow: 0 12px 40px rgba(37,99,235,0.13), 0 2px 12px rgba(0,0,0,0.06);
        }
        .header {
            font-size: 2.1em;
            font-weight: 700;
            margin-bottom: 28px;
            color: #2563eb;
            letter-spacing: 0.5px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            color: #2563eb;
            font-size: 1.08em;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group input[type="file"],
        .form-group select {
            width: 100%;
            padding: 13px 16px;
            border: 2px solid #e0e7ef;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 1.08em;
            background: #f7fafd;
            transition: border 0.2s, background 0.2s;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group input[type="file"]:focus,
        .form-group select:focus {
            border: 2px solid #2563eb;
            background: #fff;
        }
        .form-group img {
            margin-top: 10px;
            max-width: 220px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .submit-btn {
            background: linear-gradient(90deg, #2563eb 0%, #4db3ff 100%);
            color: #fff;
            border: none;
            padding: 14px 36px;
            font-size: 1.1em;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            display: block;
            margin: 0 auto;
        }
        .submit-btn:hover {
            background: linear-gradient(90deg, #1746a0 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        @media (max-width: 700px) {
            .container {
                padding: 18px 5px 32px 5px;
            }
            .header {
                font-size: 1.4em;
            }
            .form-group label {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Edit Project</div>
        <?php if ($project_details): ?>
        <form id="editProjectForm" action="edit_project.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="project_id" value="<?php echo $project_details['project_id']; ?>">
             <input type="hidden" name="existing_image" value="<?php echo htmlspecialchars($project_details['image_path']); ?>">
            <div class="form-group">
                <label for="project_name">Project Name:</label>
                <input type="text" id="project_name" name="project_name" value="<?php echo htmlspecialchars($project_details['project_name']); ?>" required>
            </div>
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($project_details['start_date']); ?>" required>
            </div>
            <div class="form-group">
                <label for="deadline">Deadline:</label>
                <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($project_details['deadline']); ?>" required>
            </div>
            <div class="form-group">
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($project_details['location']); ?>" required>
            </div>
            <div class="form-group">
                <label for="project_cost">Project Cost:</label>
                <input type="text" id="project_cost" name="project_cost" value="<?php echo htmlspecialchars($project_details['project_cost']); ?>" required>
            </div>
            <div class="form-group">
                <label for="client_name">Client Name:</label>
                <input type="text" id="client_name" name="client_name" value="<?php echo htmlspecialchars($project_details['client_name'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="client_number">Client Number:</label>
                <input type="text" id="client_number" name="client_number" value="<?php echo htmlspecialchars($project_details['client_number'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="foreman">Foreman:</label>
                <input type="text" id="foreman" name="foreman" value="<?php echo htmlspecialchars($project_details['foreman']); ?>" required>
            </div>
            <div class="form-group">
                <label for="project_type">Project Type:</label>
                <input type="text" id="project_type" name="project_type" value="<?php echo htmlspecialchars($project_details['project_type']); ?>" required>
            </div>
            <div class="form-group">
                <label for="project_status">Project Status:</label>
                <select id="project_status" name="project_status" required>
                    <option value="Ongoing" <?php if ($project_details['project_status'] === 'Ongoing') echo 'selected'; ?>>Ongoing</option>
                    <option value="Finished" <?php if ($project_details['project_status'] === 'Finished') echo 'selected'; ?>>Finished</option>
                    <option value="Cancelled" <?php if ($project_details['project_status'] === 'Cancelled') echo 'selected'; ?>>Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label for="project_divisions">Project Divisions:</label>
                <input type="text" id="project_divisions" name="project_divisions" value="<?php echo htmlspecialchars($project_details['project_divisions']); ?>" required>
            </div>
             <div class="form-group">
                <label for="project_image">Project Image:</label>
                <input type="file" id="project_image" name="project_image" accept="image/*">
                <?php if (isset($project_details['image_path']) && !empty($project_details['image_path'])): ?>
                    <img src="<?php echo htmlspecialchars($project_details['image_path']); ?>" alt="Project Image">
                <?php endif; ?>
            </div>
            <button type="submit" class="submit-btn">Update Project</button>
        </form>
        <?php else: ?>
            <p>Project not found.</p>
        <?php endif; ?>
    </div>
    <script>
    // Styled confirm modal and toast
    (function(){
        var form = document.getElementById('editProjectForm');
        if (!form) return;
        form.addEventListener('submit', function(e){
            e.preventDefault();
            showConfirm({
                title: 'Update Project',
                message: 'Are you sure you want to save these changes?',
                confirmText: 'Update',
                cancelText: 'Cancel',
                variant: 'default'
            }, function(){
                showToast({ title: 'Saving...', message: 'Updating project details', variant: 'success' });
                form.submit();
            });
        });

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
            h.style.cssText = 'font-weight:800;margin-bottom:8px;font-size:18px;color:#2563eb;';

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
            confirmBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:linear-gradient(90deg,#2563eb 0%,#4db3ff 100%);color:#fff;font-weight:700;cursor:pointer;box-shadow:0 4px 12px rgba(37,99,235,0.25);';
            confirmBtn.onmousedown = function(){ confirmBtn.style.boxShadow='0 6px 18px rgba(37,99,235,0.4)'; };
            confirmBtn.onmouseup = function(){ confirmBtn.style.boxShadow='0 4px 12px rgba(37,99,235,0.25)'; };
            confirmBtn.onclick = function(){ document.body.removeChild(overlay); if (typeof onConfirm==='function') onConfirm(); };

            actions.appendChild(cancelBtn);
            actions.appendChild(confirmBtn);
            box.appendChild(h);
            box.appendChild(p);
            box.appendChild(actions);
            overlay.appendChild(box);
            document.body.appendChild(overlay);
        }

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
    })();
    </script>
</body>
</html> 