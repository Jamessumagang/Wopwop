<?php
$conn = new mysqli("localhost", "root", "", "capstone_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_photo']) && $_FILES['new_photo']['error'] == UPLOAD_ERR_OK) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $targetDir = "uploads/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $fileName = uniqid() . "_" . basename($_FILES["new_photo"]["name"]);
    $targetFile = $targetDir . $fileName;
    if (move_uploaded_file($_FILES["new_photo"]["tmp_name"], $targetFile)) {
        // Update the employee's photo in the database
        $stmt = $conn->prepare("UPDATE employees SET photo = ? WHERE id = ?");
        $stmt->bind_param("si", $targetFile, $id);
        $stmt->execute();
        // Refresh to show new photo
        header("Location: employee_profile.php?id=" . $id);
        exit();
    }
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sql = "SELECT * FROM employees WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

function calculate_age($birthday) {
    $birthDate = new DateTime($birthday);
    $today = new DateTime('today');
    $age = $birthDate->diff($today)->y;
    return $age;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Profile</title>
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
        .profile-outer {
            min-height: 100vh;
            width: 100vw;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: relative;
            z-index: 1;
        }
        .profile-container {
            max-width: 900px;
            width: 100%;
            margin: 150px auto 0 auto;
            border: none;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 1.5px 8px rgba(0,0,0,0.04);
            padding: 40px 48px 40px 48px;
            display: flex;
            align-items: flex-start;
            gap: 48px;
            position: relative;
        }
        @media (max-width: 900px) {
            .profile-container {
                flex-direction: column;
                align-items: center;
                padding: 32px 12px;
                gap: 24px;
            }
        }
        .profile-photo {
            width: 260px;
            text-align: center;
        }
        .profile-photo img {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: #e0e2e3;
            object-fit: cover;
            box-shadow: 0 4px 16px rgba(37,99,235,0.10);
            border: 4px solid #e0e7ff;
        }
        .change-picture {
            color: #2563eb;
            text-decoration: none;
            display: block;
            margin-top: 16px;
            font-size: 1.08em;
            font-weight: 500;
            transition: color 0.2s;
        }
        .change-picture:hover {
            color: #1746a0;
        }
        .profile-details {
            flex: 1;
            font-size: 1.18em;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }
        .profile-details span {
            display: inline-block;
            min-width: 140px;
            color: #2563eb;
            font-weight: 600;
        }
        .profile-details div {
            background: #f7fafd;
            border-radius: 8px;
            padding: 10px 18px;
            margin-bottom: 0;
            box-shadow: 0 1px 4px rgba(37,99,235,0.03);
        }
        .profile-actions {
            text-align: center;
            margin: 36px auto 0 auto;
            width: 100%;
            z-index: 2;
        }
        .profile-actions a, .profile-actions button {
            background: linear-gradient(90deg, #2563eb 0%, #4db3ff 100%);
            color: white;
            border: none;
            border-radius: 6px;
            padding: 12px 36px;
            font-size: 1.1em;
            margin: 0 12px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
            transition: background 0.2s, box-shadow 0.2s;
        }
        .profile-actions a:hover, .profile-actions button:hover {
            background: linear-gradient(90deg, #1746a0 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        /* Green style for Edit button */
        .profile-actions a.edit-link {
            background: #22c55e;
        }
        .profile-actions a.edit-link:hover {
            background: #16a34a;
        }
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="profile-outer">
        <div class="profile-container">
            <div class="profile-photo">
                <img src="<?= htmlspecialchars($employee['photo'] ?: 'uploads/default.png') ?>" alt="Photo" id="profileImg">
                <form method="POST" enctype="multipart/form-data" style="margin-top:10px;">
                    <input type="file" name="new_photo" id="new_photo" accept="image/*" style="display:none;" onchange="this.form.submit()">
                    <a href="#" class="change-picture" onclick="document.getElementById('new_photo').click(); return false;">Change Picture</a>
                </form>
            </div>
            <div class="profile-details">
                <div><span>Name</span>: <?= htmlspecialchars($employee['firstname'] . ' ' . $employee['middlename'] . ' ' . $employee['lastname']) ?></div>
                <div><span>Birthday</span>: <?= date('F d, Y', strtotime($employee['birthday'])) ?></div>
                <div><span>Age</span>: <?= calculate_age($employee['birthday']) ?></div>
                <div><span>Address</span>: <?= htmlspecialchars($employee['address']) ?></div>
                <div><span>Status</span>: <?= htmlspecialchars($employee['status']) ?></div>
                <div><span>Position</span>: <?= htmlspecialchars($employee['position']) ?></div>
                <div><span>Profile Status</span>: <?= htmlspecialchars($employee['status']) ?></div>
            </div>
        </div>
        <div class="profile-actions">
            <a href="edit_employee.php?id=<?= $employee['id'] ?>" aria-label="Edit Profile" class="edit-link">Edit</a>
            <a href="employee_list.php" aria-label="Back to Employee List">Back</a>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
