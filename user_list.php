<?php
include 'db.php';

// Ensure users and client_users tables exist with required columns
$conn->query("CREATE TABLE IF NOT EXISTS users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255) UNIQUE, password VARCHAR(255), is_logged_in TINYINT(1) NOT NULL DEFAULT 0)");
$usersHasPassword = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
if ($usersHasPassword && $usersHasPassword->num_rows === 0) {
	$conn->query("ALTER TABLE users ADD COLUMN password VARCHAR(255) AFTER username");
}
$colUsers = $conn->query("SHOW COLUMNS FROM users LIKE 'is_logged_in'");
if ($colUsers && $colUsers->num_rows === 0) {
	$conn->query("ALTER TABLE users ADD COLUMN is_logged_in TINYINT(1) NOT NULL DEFAULT 0");
}
$conn->query("CREATE TABLE IF NOT EXISTS client_users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255) UNIQUE, password VARCHAR(255), is_logged_in TINYINT(1) NOT NULL DEFAULT 0)");
$clientsHasPassword = $conn->query("SHOW COLUMNS FROM client_users LIKE 'password'");
if ($clientsHasPassword && $clientsHasPassword->num_rows === 0) {
	$conn->query("ALTER TABLE client_users ADD COLUMN password VARCHAR(255) AFTER username");
}
$colClients = $conn->query("SHOW COLUMNS FROM client_users LIKE 'is_logged_in'");
if ($colClients && $colClients->num_rows === 0) {
	$conn->query("ALTER TABLE client_users ADD COLUMN is_logged_in TINYINT(1) NOT NULL DEFAULT 0");
}

// Ensure client to project link table exists
$conn->query("CREATE TABLE IF NOT EXISTS client_project_links (client_user_id INT NOT NULL, project_id INT NOT NULL, PRIMARY KEY (client_user_id, project_id))");
// Ensure unique assignment per project (one project -> one client)
$idx = $conn->query("SHOW INDEX FROM client_project_links WHERE Key_name='uniq_project_id'");
if ($idx && $idx->num_rows === 0) {
    $conn->query("CREATE UNIQUE INDEX uniq_project_id ON client_project_links (project_id)");
}

// Handle create/reset actions
$message = '';
$justAssigned = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	$userType = $_POST['user_type'] ?? '';
	if ($action === 'create' && in_array($userType, ['Admin','Client'], true)) {
		$username = trim($_POST['username'] ?? '');
		$password = $_POST['password'] ?? '';
		if ($username !== '' && $password !== '') {
			$hash = $password;
			if ($userType === 'Admin') {
				$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
			} else {
				$stmt = $conn->prepare("INSERT INTO client_users (username, password) VALUES (?, ?)");
			}
			if ($stmt) {
				$stmt->bind_param('ss', $username, $hash);
				try {
					if ($stmt->execute()) { $message = '<div class="msg success">User created</div>'; }
				} catch (\mysqli_sql_exception $e) {
					if ((int)$e->getCode() === 1062) {
						$message = '<div class="msg error">Username already exists</div>';
					} else {
						$message = '<div class="msg error">Database error</div>';
					}
				}
				$stmt->close();
			}
		} else {
			$message = '<div class="msg error">Username and password required</div>';
		}
	}
	if ($action === 'reset' && in_array($userType, ['Admin','Client'], true)) {
		$username = trim($_POST['username'] ?? '');
		$newPass = $_POST['new_password'] ?? '';
		if ($username !== '' && $newPass !== '') {
			$hash = $newPass;
			if ($userType === 'Admin') {
				$stmt = $conn->prepare("UPDATE users SET password=? WHERE username=?");
			} else {
				$stmt = $conn->prepare("UPDATE client_users SET password=? WHERE username=?");
			}
			if ($stmt) {
				$stmt->bind_param('ss', $hash, $username);
				if ($stmt->execute() && $stmt->affected_rows >= 0) { $message = '<div class="msg success">Password reset</div>'; } else { $message = '<div class="msg error">User not found</div>'; }
				$stmt->close();
			}
		} else {
			$message = '<div class="msg error">Username and new password required</div>';
		}
	}
	if ($action === 'assign_project' && $userType === 'Client') {
		$username = trim($_POST['username'] ?? '');
		$projectId = 0;
		$projectName = trim($_POST['project_name'] ?? '');
		if ($username !== '' && $projectName !== '') {
			// Resolve project name to ID
			if ($ps = $conn->prepare('SELECT project_id FROM projects WHERE project_name = ? LIMIT 1')) {
				$ps->bind_param('s', $projectName);
				$ps->execute();
				$ps->bind_result($pid);
				if ($ps->fetch()) { $projectId = (int)$pid; }
				$ps->close();
			}
		}
		if ($username !== '' && $projectId > 0) {
			if ($stmt = $conn->prepare('SELECT id FROM client_users WHERE username = ?')) {
				$stmt->bind_param('s', $username);
				$stmt->execute();
				$stmt->bind_result($cid);
				if ($stmt->fetch()) {
					$stmt->close();
					// For single-assignment per client, clear previous links then insert
					$conn->query('DELETE FROM client_project_links WHERE client_user_id = ' . (int)$cid);
					if ($ins = $conn->prepare('INSERT IGNORE INTO client_project_links (client_user_id, project_id) VALUES (?, ?)')) {
						$ins->bind_param('ii', $cid, $projectId);
						if ($ins->execute()) {
							$message = '<div class="msg success">Project assigned</div>';
							$justAssigned = true;
						} else {
							$message = '<div class="msg error">Failed to assign project</div>';
						}
						$ins->close();
					}
				} else {
					$stmt->close();
					$message = '<div class="msg error">Client not found</div>';
				}
			}
		} else {
			$message = '<div class="msg error">Username and valid project name required</div>';
		}
		// If AJAX request, return JSON and exit early
		if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
			header('Content-Type: application/json');
			echo json_encode([
				'success' => $justAssigned,
				'message' => $justAssigned ? 'Project assigned' : 'Assignment failed',
			]);
			$conn->close();
			exit;
		}
	}
	// AJAX: fetch assigned project for a client by username
	if ($action === 'get_assigned' && $userType === 'Client') {
		$username = trim($_POST['username'] ?? '');
		$projectName = null;
		if ($username !== '') {
			if ($stmt = $conn->prepare('SELECT cpl.project_id FROM client_users cu INNER JOIN client_project_links cpl ON cpl.client_user_id = cu.id WHERE cu.username = ? LIMIT 1')) {
				$stmt->bind_param('s', $username);
				$stmt->execute();
				$stmt->bind_result($pid);
				if ($stmt->fetch()) {
					$stmt->close();
					if ($ps = $conn->prepare('SELECT project_name FROM projects WHERE project_id = ?')) {
						$ps->bind_param('i', $pid);
						$ps->execute();
						$ps->bind_result($pname);
						if ($ps->fetch()) { $projectName = $pname; }
						$ps->close();
					}
				} else {
					$stmt->close();
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode([
			'success' => $projectName !== null,
			'project' => $projectName,
		]);
		$conn->close();
		exit;
	}

	// AJAX: unassign a project from a client
	if ($action === 'unassign' && $userType === 'Client') {
		$username = trim($_POST['username'] ?? '');
		$ok = false;
		if ($username !== '') {
			if ($stmt = $conn->prepare('SELECT id FROM client_users WHERE username = ?')) {
				$stmt->bind_param('s', $username);
				$stmt->execute();
				$stmt->bind_result($cid);
				if ($stmt->fetch()) {
					$stmt->close();
					$ok = (bool)$conn->query('DELETE FROM client_project_links WHERE client_user_id = ' . (int)$cid);
				} else {
					$stmt->close();
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode(['success' => $ok]);
		$conn->close();
		exit;
	}

	// AJAX: check if new password equals current password (no change)
	if ($action === 'check_password_same' && in_array($userType, ['Admin','Client'], true)) {
		$username = trim($_POST['username'] ?? '');
		$newPass = $_POST['new_password'] ?? '';
		$isSame = false;
		if ($username !== '' && $newPass !== '') {
			if ($userType === 'Admin') {
				if ($stmt = $conn->prepare('SELECT password FROM users WHERE username = ?')) {
					$stmt->bind_param('s', $username);
					$stmt->execute();
					$stmt->bind_result($cur);
					if ($stmt->fetch()) { $isSame = ($cur === $newPass); }
					$stmt->close();
				}
			} else {
				if ($stmt = $conn->prepare('SELECT password FROM client_users WHERE username = ?')) {
					$stmt->bind_param('s', $username);
					$stmt->execute();
					$stmt->bind_result($cur);
					if ($stmt->fetch()) { $isSame = ($cur === $newPass); }
					$stmt->close();
				}
			}
		}
		header('Content-Type: application/json');
		echo json_encode(['same' => $isSame]);
		$conn->close();
		exit;
	}

	// Handle delete user (Admin or Client)
	if ($action === 'delete' && in_array($userType, ['Admin','Client'], true)) {
		$username = trim($_POST['username'] ?? '');
		if ($username !== '') {
			if ($userType === 'Admin') {
				if ($stmt = $conn->prepare('DELETE FROM users WHERE username = ?')) {
					$stmt->bind_param('s', $username);
					if ($stmt->execute() && $stmt->affected_rows > 0) {
						$message = '<div class="msg success">Admin deleted</div>';
					} else {
						$message = '<div class="msg error">Admin not found</div>';
					}
					$stmt->close();
				}
			} else { // Client
				// Find client id, delete links first, then client
				if ($find = $conn->prepare('SELECT id FROM client_users WHERE username = ?')) {
					$find->bind_param('s', $username);
					$find->execute();
					$find->bind_result($cid);
					if ($find->fetch()) {
						$find->close();
						$conn->query('DELETE FROM client_project_links WHERE client_user_id = ' . (int)$cid);
						if ($del = $conn->prepare('DELETE FROM client_users WHERE id = ?')) {
							$del->bind_param('i', $cid);
							if ($del->execute() && $del->affected_rows > 0) {
								$message = '<div class="msg success">Client deleted</div>';
							} else {
								$message = '<div class="msg error">Client deletion failed</div>';
							}
							$del->close();
						}
					} else {
						$find->close();
						$message = '<div class="msg error">Client not found</div>';
					}
				}
			}
		} else {
			$message = '<div class="msg error">Username required</div>';
		}
	}
}

// Fetch admin users (from standard login)
$usersSql = "SELECT username, is_logged_in, 'Admin' AS user_type FROM users";
$usersRes = $conn->query($usersSql);

// Fetch client users with assignment info
$clientsSql = "SELECT cu.username, cu.is_logged_in, 'Client' AS user_type, IF(cpl.project_id IS NULL, 0, 1) AS has_assignment
               FROM client_users cu
               LEFT JOIN client_project_links cpl ON cpl.client_user_id = cu.id";
$clientsRes = $conn->query($clientsSql);

// Fetch all projects for assignment list (exclude already assigned)
$allProjects = [];
if ($projRes = $conn->query("SELECT p.project_name FROM projects p LEFT JOIN client_project_links cpl ON cpl.project_id = p.project_id WHERE cpl.project_id IS NULL ORDER BY p.project_name")) {
	while ($p = $projRes->fetch_assoc()) { $allProjects[] = $p['project_name']; }
	$projRes->close();
}

// Close the database connection (should be done after fetching results)
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>User List</title>
    <!-- Include your CSS links and styles here -->
     <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
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
            margin: 120px auto 0 auto;
            max-width: 900px;
            background: #fff;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(37,99,235,0.10), 0 1.5px 8px rgba(0,0,0,0.04);
            padding: 40px 48px 32px 48px;
            position: relative;
            z-index: 1;
        }
        .header {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 24px;
            color: #2563eb;
            text-align: center;
            letter-spacing: 1px;
        }
         .top-buttons {
             display: flex;
             justify-content: flex-start;
             align-items: center;
             margin-bottom: 18px;
         }
        .home-btn {
            background: linear-gradient(90deg, #2563eb 0%, #4db3ff 100%);
            color: #fff;
            border: none;
            padding: 12px 28px;
            font-size: 1em;
            border-radius: 999px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .home-btn:hover {
            background: linear-gradient(90deg, #1746a0 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
         table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(37,99,235,0.04);
            margin-bottom: 20px;
        }
        th, td {
            border: 1.5px solid #e5e7eb;
            padding: 14px 8px;
            text-align: left;
            font-size: 1em;
        }
        th {
            background: #e0e7ff;
            color: #2563eb;
            font-weight: 700;
        }
        tr:hover {
            background: #f1f5fd;
        }
         .status-online {
             color: #28a745;
             font-weight: 600;
         }
         .status-offline {
             color: #6c757d;
             font-weight: 600;
         }
         @media (max-width: 700px) {
            .container {
                margin: 8px;
                padding: 6px;
            }
            .header {
                font-size: 1.5em;
            }
            th, td {
                padding: 8px 2px;
                font-size: 0.95em;
            }
             .home-btn {
                 padding: 8px 12px;
                 font-size: 0.95em;
             }
        }
     </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="container">
        <div class="top-buttons">
            <a href="Admindashboard.php" class="home-btn">Home</a>
        </div>
        <div class="header">User List</div>
        <?php if (!empty($message)) { echo $message; } ?>
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
            <form id="createUserForm" method="POST" style="background:#f8fafc;padding:12px;border-radius:10px;border:1px solid #e5e7eb;display:flex;gap:12px;align-items:end;justify-content:space-between;width:100%;">
                <input type="hidden" name="action" value="create">
                <div>
                    <label style="display:block;font-weight:600;color:#2563eb;margin-bottom:4px;">Type</label>
                    <select name="user_type" required style="padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;">
                        <option>Admin</option>
                        <option>Client</option>
                    </select>
                </div>
                <div style="flex:1;min-width:220px;">
                    <label style="display:block;font-weight:600;color:#2563eb;margin-bottom:4px;">Username</label>
                    <input name="username" required placeholder="new username" style="width:90%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;">
                </div>
                <div style="flex:1;min-width:220px;">
                    <label style="display:block;font-weight:600;color:#2563eb;margin-bottom:4px;">Password</label>
                    <input name="password" type="password" required placeholder="password" style="width:90%;padding:8px 10px;border:1px solid #e5e7eb;border-radius:8px;">
                </div>
                <button class="home-btn" type="submit" style="padding:10px 16px;">Create</button>
            </form>

            
        </div>
        <table>
            <thead>
                <tr>
                    <th>User Type</th>
                    <th>Username</th>
                    <th>Status</th>
                    <th style="text-align:center;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $rowsPrinted = 0;
                $renderRow = function($row) use (&$rowsPrinted) {
                    $status_class = 'status-offline';
                    $status_text = 'Logged Out';
                    if (isset($row['is_logged_in']) && (int)$row['is_logged_in'] === 1) {
                        $status_class = 'status-online';
                        $status_text = 'Logged In';
                    }
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['username']) . "</td>";
                    echo "<td><span class=\"$status_class\">$status_text</span></td>";
                    $typeJs = htmlspecialchars($row['user_type'], ENT_QUOTES);
                    $userJs = htmlspecialchars($row['username'], ENT_QUOTES);
                    echo "<td style=\"text-align:center;\">";
                    echo "<button type=\"button\" class=\"home-btn\" style=\"padding:6px 10px;font-size:0.9em;background:linear-gradient(90deg,#dc2626 0%,#ef4444 100%);box-shadow:0 4px 12px rgba(239,68,68,0.35);transition: box-shadow 0.15s ease;\" onmousedown=\"this.style.boxShadow='0 6px 18px rgba(248,113,113,0.6)'\" onmouseup=\"this.style.boxShadow='0 4px 12px rgba(239,68,68,0.35)'\" onmouseleave=\"this.style.boxShadow='0 4px 12px rgba(239,68,68,0.35)'\" onclick=\"resetUserPrompt('{$typeJs}','{$userJs}')\">Reset Password</button>";
                    if ($typeJs === 'Client') {
                        $disabled = (isset($row['has_assignment']) && (int)$row['has_assignment'] === 1);
                        $style = 'padding:6px 10px;font-size:0.9em;background:linear-gradient(90deg,#4f46e5 0%,#3b82f6 100%);margin-left:6px;';
                        if ($disabled) { $style .= 'opacity:0.6;cursor:not-allowed;'; }
                        echo " <button type=\"button\" class=\"home-btn\" style=\"$style\" ".($disabled?"disabled":"onclick=\"assignProjectPrompt('{$typeJs}','{$userJs}')\"").">Assign Project</button>";
                        echo " <button type=\"button\" class=\"home-btn\" style=\"padding:6px 10px;font-size:0.9em;background:linear-gradient(90deg,#059669 0%,#10b981 100%);margin-left:6px;\" onclick=\"viewAssignedProject('{$userJs}')\">View Assigned</button>";
                    }
                    echo " <button type=\"button\" class=\"home-btn\" style=\"padding:6px 10px;font-size:0.9em;background:linear-gradient(90deg,#6b7280 0%,#111827 100%);margin-left:6px;\" onclick=\"deleteUserPrompt('{$typeJs}','{$userJs}')\">Delete</button>";
                    echo "</td>";
                    echo "</tr>";
                    $rowsPrinted++;
                };

                if ($usersRes && $usersRes->num_rows > 0) {
                    while ($row = $usersRes->fetch_assoc()) {
                        $renderRow($row);
                    }
                }
                if ($clientsRes && $clientsRes->num_rows > 0) {
                    while ($row = $clientsRes->fetch_assoc()) {
                        $renderRow($row);
                    }
                }
                if ($rowsPrinted === 0) {
                    echo "<tr><td colspan=\"3\">No users found</td></tr>";
                }
                ?>
            </tbody>
        </table>
        <form id="resetForm" method="POST" style="display:none;">
            <input type="hidden" name="action" value="reset">
            <input type="hidden" name="user_type" id="rf_type">
            <input type="hidden" name="username" id="rf_username">
            <input type="hidden" name="new_password" id="rf_password">
        </form>
		<form id="assignForm" method="POST" style="display:none;">
			<input type="hidden" name="action" value="assign_project">
			<input type="hidden" name="user_type" id="af_type" value="Client">
			<input type="hidden" name="username" id="af_username">
			<input type="hidden" name="project_name" id="af_project_name">
		</form>
        <form id="deleteForm" method="POST" style="display:none;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="user_type" id="df_type">
            <input type="hidden" name="username" id="df_username">
        </form>
        <!-- Assign Project Modal -->
        <div id="assignModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.35);align-items:center;justify-content:center;">
            <div style="background:#fff;border-radius:12px;box-shadow:0 8px 32px rgba(0,0,0,0.18);padding:18px 18px 14px 18px;min-width:320px;max-width:90vw;">
                <div style="font-weight:700;color:#2563eb;margin-bottom:10px;">Assign Project</div>
                <div style="display:flex;flex-direction:column;gap:8px;">
                    <label style="font-weight:600;color:#2563eb;">Select project</label>
                    <select id="assignSelect" style="padding:10px;border:1px solid #e5e7eb;border-radius:10px;">
                        <option value="">-- Choose a project --</option>
                        <?php foreach ($allProjects as $pname): ?>
                            <option value="<?= htmlspecialchars($pname) ?>"><?= htmlspecialchars($pname) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:10px;">
                        <button type="button" onclick="closeAssignModal()" style="padding:8px 14px;border:none;border-radius:999px;background:#e5e7eb;color:#111;font-weight:600;cursor:pointer;">Cancel</button>
                        <button type="button" onclick="confirmAssignProject()" style="padding:8px 14px;border:none;border-radius:999px;background:linear-gradient(90deg,#2563eb 0%,#4db3ff 100%);color:#fff;font-weight:700;cursor:pointer;">Assign</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
    function resetUserPrompt(type, username) {
        var overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed;inset:0;z-index:10000;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;';

        var box = document.createElement('div');
        box.style.cssText = 'background:#fff;border-radius:14px;box-shadow:0 12px 36px rgba(0,0,0,0.18);padding:18px;min-width:320px;max-width:92vw;';

        var title = document.createElement('div');
        title.textContent = 'Reset Password for ' + username + ' (' + type + ')';
        title.style.cssText = 'font-weight:800;margin-bottom:10px;font-size:18px;color:#2563eb;';

        var fieldWrap = document.createElement('div');
        fieldWrap.style.cssText = 'display:flex;flex-direction:column;gap:8px;margin-top:4px;';

        var label = document.createElement('label');
        label.textContent = 'New Password';
        label.style.cssText = 'font-weight:600;color:#2563eb;';

        var inputWrap = document.createElement('div');
        inputWrap.style.cssText = 'position:relative;display:flex;align-items:center;';

        var input = document.createElement('input');
        input.type = 'password';
        input.placeholder = 'Enter new password';
        input.autofocus = true;
        input.style.cssText = 'width:100%;padding:10px 40px 10px 12px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;font-size:14px;';

        var toggle = document.createElement('span');
        toggle.innerHTML = '\u{1F441}';
        toggle.title = 'Show/Hide';
        toggle.style.cssText = 'position:absolute;right:10px;cursor:pointer;opacity:0.7;';
        toggle.onclick = function(){ input.type = (input.type === 'password' ? 'text' : 'password'); };

        var hint = document.createElement('div');
        hint.textContent = 'Minimum 6 characters recommended.';
        hint.style.cssText = 'color:#6b7280;font-size:12px;';

        var actions = document.createElement('div');
        actions.style.cssText = 'display:flex;gap:8px;justify-content:flex-end;margin-top:12px;';

        var cancelBtn = document.createElement('button');
        cancelBtn.textContent = 'Cancel';
        cancelBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:#e5e7eb;color:#111;font-weight:600;cursor:pointer;';
        cancelBtn.onclick = function(){ document.body.removeChild(overlay); };

        var resetBtn = document.createElement('button');
        resetBtn.textContent = 'Reset';
        resetBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:linear-gradient(90deg,#059669 0%,#10b981 100%);color:#fff;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(16,185,129,0.35);';
        resetBtn.onmousedown = function(){ resetBtn.style.boxShadow='0 6px 18px rgba(16,185,129,0.55)'; };
        resetBtn.onmouseup = function(){ resetBtn.style.boxShadow='0 4px 12px rgba(16,185,129,0.35)'; };
        resetBtn.onclick = function(){
            var pwd = input.value || '';
        if (pwd.trim() === '') { alert('Password cannot be empty.'); return; }
            if (pwd.length < 6) {
                showConfirm({
                    title: 'Weak Password',
                    message: 'Password is shorter than 6 characters. Continue?',
                    confirmText: 'Continue',
                    cancelText: 'Cancel',
                    variant: 'default'
                }, function(){
                    submitReset(pwd);
                });
                return;
            }
            submitReset(pwd);
        };

        function submitReset(pwd){
            // First check if same as existing
            var fd = new FormData();
            fd.append('action', 'check_password_same');
            fd.append('user_type', type);
            fd.append('username', username);
            fd.append('new_password', pwd);
            fetch('user_list.php', { method: 'POST', body: fd }).then(r=>r.json()).then(function(res){
                if (res && res.same) {
                    showToast({ title: 'No changes made', message: 'New password matches current password.', variant: 'danger' });
                    return;
                }
                showToast({ title: 'Password reset', message: 'Submitting new password for ' + username, variant: 'success' });
        document.getElementById('rf_type').value = type;
        document.getElementById('rf_username').value = username;
        document.getElementById('rf_password').value = pwd;
                setTimeout(function(){ document.getElementById('resetForm').submit(); }, 1000);
                document.body.removeChild(overlay);
            }).catch(function(){ alert('Network error'); });
        }

        inputWrap.appendChild(input);
        inputWrap.appendChild(toggle);
        fieldWrap.appendChild(label);
        fieldWrap.appendChild(inputWrap);
        fieldWrap.appendChild(hint);
        actions.appendChild(cancelBtn);
        actions.appendChild(resetBtn);
        box.appendChild(title);
        box.appendChild(fieldWrap);
        box.appendChild(actions);
        overlay.appendChild(box);
        document.body.appendChild(overlay);
        input.focus();
    }

    // Simple toast notification
    function showToast(opts){
        var title = opts.title || '';
        var message = opts.message || '';
        var variant = opts.variant || 'default'; // success | danger | default

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
        setTimeout(function(){ toast.style.opacity='0'; toast.style.transform='translateY(-10px)'; setTimeout(function(){ if (toast.parentNode) toast.parentNode.removeChild(toast); }, 200); }, 10000);
    }

    function assignProjectPrompt(type, username) {
        if (type !== 'Client') { alert('Only clients can be assigned projects.'); return; }
        document.getElementById('af_username').value = username;
        document.getElementById('assignSelect').value = '';
        document.getElementById('assignModal').style.display = 'flex';
    }

    function closeAssignModal() {
        document.getElementById('assignModal').style.display = 'none';
    }

    function confirmAssignProject() {
        var sel = document.getElementById('assignSelect');
        var pname = sel.value ? sel.value.trim() : '';
        if (pname === '') { alert('Please select a project.'); return; }
        document.getElementById('af_project_name').value = pname;
        document.getElementById('assignForm').submit();
        closeAssignModal();
    }

    function viewAssignedProject(username) {
        var fd = new FormData();
        fd.append('action', 'get_assigned');
        fd.append('user_type', 'Client');
        fd.append('username', username);
        fetch('user_list.php', { method: 'POST', body: fd }).then(r => r.json()).then(function(res){
            var modal = document.createElement('div');
            modal.style.cssText = 'position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.35);display:flex;align-items:center;justify-content:center;';
            var card = document.createElement('div');
            card.style.cssText = 'background:#fff;border-radius:14px;box-shadow:0 12px 36px rgba(0,0,0,0.18);padding:18px 18px 14px 18px;min-width:320px;max-width:92vw;';
            var title = document.createElement('div');
            title.textContent = 'Assigned Project';
            title.style.cssText = 'font-weight:800;color:#2563eb;margin-bottom:10px;font-size:18px;';
            var content = document.createElement('div');
            content.style.cssText = 'display:flex;flex-direction:column;gap:10px;min-width:300px;';
            var name = document.createElement('div');
            name.textContent = (res && res.success && res.project) ? res.project : 'No project assigned';
            name.style.cssText = 'padding:12px 14px;border:1px solid #e5e7eb;border-radius:10px;background:#f8fafc;font-weight:700;color:#111827;';
            var actions = document.createElement('div');
            actions.style.cssText = 'display:flex;gap:8px;justify-content:flex-end;margin-top:6px;';
            var closeBtn = document.createElement('button');
            closeBtn.textContent = 'Close';
            closeBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:#e5e7eb;color:#111;font-weight:600;cursor:pointer;';
            closeBtn.onclick = function(){ document.body.removeChild(modal); };
            actions.appendChild(closeBtn);
            if (res && res.success && res.project) {
                var delBtn = document.createElement('button');
                delBtn.textContent = 'Delete Assignment';
                delBtn.style.cssText = 'padding:8px 14px;border:none;border-radius:999px;background:linear-gradient(90deg,#dc2626 0%,#ef4444 100%);color:#fff;font-weight:800;cursor:pointer;box-shadow:0 4px 12px rgba(239,68,68,0.35);';
                delBtn.onmousedown = function(){ delBtn.style.boxShadow='0 6px 18px rgba(248,113,113,0.6)'; };
                delBtn.onmouseup = function(){ delBtn.style.boxShadow='0 4px 12px rgba(239,68,68,0.35)'; };
                delBtn.onclick = function(){
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Remove the assigned project for ' + username + '?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e11d48',
                        cancelButtonColor: '#6b7280',
                        confirmButtonText: 'Yes, delete it',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (!result.isConfirmed) return;
                        var uf = new FormData();
                        uf.append('action', 'unassign');
                        uf.append('user_type', 'Client');
                        uf.append('username', username);
                        fetch('user_list.php', { method:'POST', body: uf })
                        .then(r=>r.json())
                        .then(function(resp){
                            if (resp && resp.success) {
                                Swal.fire({ title: 'Deleted!', text: 'Assignment removed.', icon: 'success', confirmButtonColor: '#2563eb' })
                                .then(function(){ document.body.removeChild(modal); location.reload(); });
                            } else {
                                Swal.fire({ title: 'Delete failed', text: 'Failed to remove assignment.', icon: 'error', confirmButtonColor: '#2563eb' });
                            }
                        })
                        .catch(function(){ Swal.fire({ title: 'Network error', icon: 'error', confirmButtonColor: '#2563eb' }); });
                    });
                };
                actions.appendChild(delBtn);
            }
            content.appendChild(name);
            content.appendChild(actions);
            card.appendChild(title);
            card.appendChild(content);
            modal.appendChild(card);
            document.body.appendChild(modal);
        }).catch(function(){ alert('Network error'); });
    }

    function deleteUserPrompt(type, username) {
        Swal.fire({
            title: 'Are you sure?',
            text: 'Delete ' + username + ' (' + type + ')? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (!result.isConfirmed) return;
            var fd = new FormData();
            fd.append('action', 'delete');
            fd.append('user_type', type);
            fd.append('username', username);
            fd.append('ajax', '1');
            fetch('user_list.php', { method: 'POST', body: fd })
                .then(function(){
                    return Swal.fire({
                        title: 'Deleted!',
                        text: username + ' has been removed.',
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    });
                })
                .then(function(){ location.reload(); })
                .catch(function(){
                    Swal.fire({ title: 'Delete failed', icon: 'error', confirmButtonColor: '#2563eb' });
                });
        });
    }

    // Reusable styled confirm modal
    function showConfirm(opts, onConfirm){
        var title = opts.title || 'Confirm';
        var message = opts.message || 'Are you sure?';
        var confirmText = opts.confirmText || 'OK';
        var cancelText = opts.cancelText || 'Cancel';
        var variant = opts.variant || 'default'; // 'danger' | 'default'

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

    // Intercept Create form submit to show a styled confirm
    (function(){
        var form = document.getElementById('createUserForm');
        if (!form) return;
        form.addEventListener('submit', function(e){
            e.preventDefault();
            var type = (form.querySelector('select[name="user_type"]')||{}).value || 'Admin';
            var username = (form.querySelector('input[name="username"]')||{}).value || '';
            showConfirm({
                title: 'Create ' + type,
                message: 'Create user "' + username + '" as ' + type + '?',
                confirmText: 'Create',
                cancelText: 'Cancel',
                variant: 'default'
            }, function(){
                showToast({ title: 'Creating user...', message: username + ' (' + type + ')', variant: 'success' });
                form.submit();
            });
        });
    })();
    </script>
</body>
</html> 