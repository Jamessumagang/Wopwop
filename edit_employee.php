<?php
$conn = new mysqli("localhost", "root", "", "capstone_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lastname = $_POST['lastname'];
    $firstname = $_POST['firstname'];
    $middlename = $_POST['middlename'];
    $birthday = $_POST['birthday'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];
    $contact = $_POST['contact'];
    $status = $_POST['status'];
    $position = $_POST['position'];

    // Handle photo upload (optional)
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = uniqid() . "_" . basename($_FILES["photo"]["name"]);
        $targetFile = $targetDir . $fileName;
        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
            $photo = $targetFile;
            $stmt = $conn->prepare("UPDATE employees SET photo=?, lastname=?, firstname=?, middlename=?, birthday=?, gender=?, address=?, contact_no=?, status=?, position=? WHERE id=?");
            $stmt->bind_param("ssssssssssi", $photo, $lastname, $firstname, $middlename, $birthday, $gender, $address, $contact, $status, $position, $id);
        }
    } else {
        $stmt = $conn->prepare("UPDATE employees SET lastname=?, firstname=?, middlename=?, birthday=?, gender=?, address=?, contact_no=?, status=?, position=? WHERE id=?");
        $stmt->bind_param("sssssssssi", $lastname, $firstname, $middlename, $birthday, $gender, $address, $contact, $status, $position, $id);
    }
    $stmt->execute();
    header("Location: employee_profile.php?id=" . $id);
    exit();
}

// Fetch employee data
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// Fetch available positions for dropdown
$positions = [];
$pos_stmt = $conn->prepare("SELECT position_name FROM positions ORDER BY position_name ASC");
if ($pos_stmt) {
    $pos_stmt->execute();
    $pos_res = $pos_stmt->get_result();
    while ($row = $pos_res->fetch_assoc()) {
        $positions[] = $row['position_name'];
    }
    $pos_stmt->close();
}
// Track if current employee position exists in list
$has_current_position = in_array($employee['position'] ?? '', $positions, true);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Edit Employee</title>
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
        .background-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            width: 100vw; height: 100vh;
            background: rgba(255,255,255,0.75);
            z-index: 0;
        }
        .form-outer {
            min-height: 100vh;
            width: 100vw;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            position: relative;
            z-index: 1;
        }
        .form-container {
            max-width: 800px;
            width: 100%;
            margin: 120px auto 0 auto;
            border: none;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 1.5px 8px rgba(0,0,0,0.04);
            padding: 40px 48px 80px 48px;
            box-sizing: border-box;
            position: relative;
        }
        .form-container h2 {
            margin-top: 0;
            font-size: 2em;
            margin-bottom: 30px;
            color: #2563eb;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-group {
            display: flex;
            align-items: center;
            margin-bottom: 28px;
        }
        .form-group label {
            flex: 0 0 180px;
            font-size: 1.15em;
            margin-right: 10px;
            color: #2563eb;
            font-weight: 600;
        }
        .form-group input[type="text"],
        .form-group input[type="date"],
        .form-group select {
            flex: 1;
            padding: 14px 18px;
            font-size: 1.1em;
            border: 2px solid #e0e7ef;
            border-radius: 12px;
            outline: none;
            transition: border 0.2s;
            background: #f7fafd;
        }
        .form-group input[type="text"]:focus,
        .form-group input[type="date"]:focus,
        .form-group select:focus {
            border: 2px solid #2563eb;
            background: #fff;
        }
        .form-group input[type="file"] {
            flex: 1;
            font-size: 1.1em;
        }
        .form-actions {
            position: absolute;
            bottom: 30px;
            right: 40px;
            display: flex;
            gap: 20px;
        }
        .form-actions button,
        .form-actions a {
            background: linear-gradient(90deg, #2563eb 0%, #4db3ff 100%);
            color: white;
            padding: 12px 50px;
            border: none;
            border-radius: 6px;
            font-size: 1.2em;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, box-shadow 0.2s;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .form-actions button:hover,
        .form-actions a:hover {
            background: linear-gradient(90deg, #1746a0 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        /* Red styling for Cancel link */
        .form-actions a.cancel-link {
            background: #e74c3c;
        }
        .form-actions a.cancel-link:hover {
            background: #c0392b;
        }
        @media (max-width: 700px) {
            .form-container {
                padding: 10px 5px 70px 5px;
            }
            .form-group label {
                font-size: 1em;
                flex: 0 0 100px;
            }
            .form-actions {
                right: 10px;
                bottom: 10px;
            }
            .form-actions button,
            .form-actions a {
                padding: 10px 20px;
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="form-outer">
        <form id="editEmployeeForm" class="form-container" method="POST" action="edit_employee.php?id=<?= $employee['id'] ?>" enctype="multipart/form-data">
            <h2>Edit Employee</h2>
            <div class="form-group">
                <label for="lastname">Lastname :</label>
                <input type="text" id="lastname" name="lastname" value="<?= htmlspecialchars($employee['lastname']) ?>" required>
            </div>
            <div class="form-group">
                <label for="firstname">Firstname :</label>
                <input type="text" id="firstname" name="firstname" value="<?= htmlspecialchars($employee['firstname']) ?>" required>
            </div>
            <div class="form-group">
                <label for="middlename">Middlename :</label>
                <input type="text" id="middlename" name="middlename" value="<?= htmlspecialchars($employee['middlename']) ?>">
            </div>
            <div class="form-group">
                <label for="birthday">Birthday :</label>
                <input type="date" id="birthday" name="birthday" value="<?= htmlspecialchars($employee['birthday']) ?>" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender :</label>
                <input type="text" id="gender" name="gender" value="<?= htmlspecialchars($employee['gender']) ?>" required>
            </div>
            <div class="form-group">
                <label for="address">Address :</label>
                <input type="text" id="address" name="address" value="<?= htmlspecialchars($employee['address']) ?>" required>
            </div>
            <div class="form-group">
                <label for="contact">Contact no :</label>
                <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($employee['contact_no']) ?>" required>
            </div>
            <div class="form-group">
                <label for="status">Status :</label>
                <input type="text" id="status" name="status" value="<?= htmlspecialchars($employee['status']) ?>" required>
            </div>
            <div class="form-group">
                <label for="position">Position :</label>
                <select id="position" name="position" required>
                    <?php if (!$has_current_position && !empty($employee['position'])): ?>
                        <option value="<?= htmlspecialchars($employee['position']) ?>" selected><?= htmlspecialchars($employee['position']) ?> (current)</option>
                    <?php endif; ?>
                    <?php foreach ($positions as $p): ?>
                        <option value="<?= htmlspecialchars($p) ?>" <?= ($employee['position'] === $p ? 'selected' : '') ?>><?= htmlspecialchars($p) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="photo">Photo :</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </div>
            <div class="form-actions">
                <button type="submit">Save</button>
                <a href="employee_profile.php?id=<?= $employee['id'] ?>" class="cancel-link">Cancel</a>
            </div>
        </form>
    </div>
<script>
(function(){
    var form = document.getElementById('editEmployeeForm');
    if (!form) return;
    form.addEventListener('submit', function(e){
        e.preventDefault();
        var name = (document.getElementById('lastname')?.value || '') + ', ' + (document.getElementById('firstname')?.value || '');
        showConfirm({
            title: 'Save Employee',
            message: 'Save changes for ' + name + '?',
            confirmText: 'Save',
            cancelText: 'Cancel'
        }, function(){
            showToast({ title: 'Saving...', message: 'Updating employee details', variant: 'success' });
            form.submit();
        });
    });

    function showConfirm(opts, onConfirm){
        var title = opts.title || 'Confirm';
        var message = opts.message || 'Are you sure?';
        var confirmText = opts.confirmText || 'OK';
        var cancelText = opts.cancelText || 'Cancel';

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
<?php
$stmt->close();
$conn->close();
?>
