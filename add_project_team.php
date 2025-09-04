<?php
include 'db.php';

$employees = [];
$foremen = [];

// Fetch all active employees for dropdowns
$sql_employees = "SELECT id, firstname, lastname FROM employees WHERE status = 'Active' ORDER BY lastname, firstname";
$result_employees = $conn->query($sql_employees);
if ($result_employees->num_rows > 0) {
    while ($row = $result_employees->fetch_assoc()) {
        $employees[] = $row;
    }
}

// Fetch only active foremen for foreman dropdown
$sql_foremen = "SELECT id, firstname, lastname FROM employees WHERE status = 'Active' AND position = 'Foreman' ORDER BY lastname, firstname";
$result_foremen = $conn->query($sql_foremen);
if ($result_foremen && $result_foremen->num_rows > 0) {
    while ($row = $result_foremen->fetch_assoc()) {
        $foremen[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Add New Project Team</title>
    <!-- Add necessary CSS links here -->
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
            padding: 40px 48px 40px 48px;
            box-sizing: border-box;
            position: relative;
        }
        .form-container h1 {
            font-size: 2em;
            font-weight: 700;
            margin-bottom: 24px;
            color: #2563eb;
            text-align: center;
            letter-spacing: 1px;
        }
        .form-group {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .form-group label {
            flex: 0 0 150px;
            font-weight: 600;
            color: #2563eb;
            margin-right: 10px;
            font-size: 1.15em;
        }
        .form-group input[type="text"],
        .form-group select {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e0e7ef;
            border-radius: 12px;
            box-sizing: border-box;
            font-size: 1.1em;
            background: #f7fafd;
            transition: border 0.2s, background 0.2s;
        }
        .form-group input[type="text"]:focus,
        .form-group select:focus {
            border: 2px solid #2563eb;
            background: #fff;
        }
        .member-input-group {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        .member-table-container {
            margin-top: 20px;
            border: 1px solid #e0e7ef;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fafd;
            box-shadow: 0 1px 4px rgba(37,99,235,0.03);
        }
        .member-table {
            width: 100%;
            border-collapse: collapse;
        }
        .member-table th, .member-table td {
            border: 1px solid #e5e7eb;
            padding: 10px;
            text-align: left;
        }
        .member-table th {
            background-color: #e0e7ff;
            color: #2563eb;
            font-weight: 600;
        }
        .remove-member-btn {
            background-color: #ef4444;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            transition: background 0.2s;
        }
        .remove-member-btn:hover {
            background-color: #b91c1c;
        }
        .submit-btn, .cancel-btn {
            background: linear-gradient(90deg, #2563eb 0%, #4db3ff 100%);
            color: #fff;
            border: none;
            padding: 12px 28px;
            font-size: 1em;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
            box-shadow: 0 2px 8px rgba(37,99,235,0.08);
        }
        .submit-btn:hover, .cancel-btn:hover {
            background: linear-gradient(90deg, #1746a0 0%, #2563eb 100%);
            box-shadow: 0 4px 16px rgba(37,99,235,0.12);
        }
        .button-group {
            margin-top: 30px;
            text-align: right;
        }
        @media (max-width: 700px) {
            .form-container {
                padding: 10px 5px 40px 5px;
            }
            .form-group label {
                font-size: 1em;
                flex: none;
                margin-right: 0;
                margin-bottom: 5px;
            }
            .form-group {
                flex-direction: column;
                align-items: flex-start;
            }
            .form-group input[type="text"],
            .form-group select {
                width: 100%;
                padding: 8px 10px;
            }
            .button-group {
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="background-overlay"></div>
    <div class="form-outer">
        <div class="form-container">
            <h1>Add New Project Team</h1>
            <form action="process_add_project_team.php" method="POST">
                <input type="hidden" name="members" id="members_hidden_input" value="">

                <div class="form-group">
                    <label for="foreman_id">Foreman:</label>
                    <select id="foreman_id" name="foreman_id" required>
                        <option value="">Select Foreman</option>
                        <?php foreach ($foremen as $employee): ?>
                            <option value="<?php echo $employee['id']; ?>">
                                <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="member_select">Member:</label>
                    <div class="member-input-group">
                        <select id="member_select">
                            <option value="">Select Employee</option>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?php echo $employee['id']; ?>">
                                    <?php echo htmlspecialchars($employee['firstname'] . ' ' . $employee['lastname']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button type="button" id="add_member_btn" class="submit-btn">+</button>
                    </div>
                </div>

                <div class="member-table-container">
                    <table class="member-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="members_table_body">
                            <!-- Members will be added here dynamically -->
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>

                <div class="button-group">
                    <button type="submit" class="submit-btn">Add Team</button>
                    <a href="project_team.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const memberSelect = document.getElementById('member_select');
            const addMemberBtn = document.getElementById('add_member_btn');
            const membersTableBody = document.getElementById('members_table_body');
            const membersHiddenInput = document.getElementById('members_hidden_input');
            const foremanSelect = document.getElementById('foreman_id');

            function updateMembersHiddenInput() {
                const memberIds = [];
                membersTableBody.querySelectorAll('tr').forEach(row => {
                    memberIds.push(row.dataset.memberId);
                });
                membersHiddenInput.value = memberIds.join(',');
            }

            addMemberBtn.addEventListener('click', function() {
                const selectedOption = memberSelect.options[memberSelect.selectedIndex];
                const memberId = selectedOption.value;
                const memberName = selectedOption.textContent;

                // Prevent adding the foreman as a member
                if (foremanSelect && foremanSelect.value && memberId === foremanSelect.value) {
                    alert('The selected employee is the Foreman and cannot be added as a member.');
                    return;
                }

                if (memberId && !membersTableBody.querySelector(`[data-member-id="${memberId}"]`)) {
                    const newRow = document.createElement('tr');
                    newRow.dataset.memberId = memberId;
                    newRow.innerHTML = `
                        <td>${memberName}</td>
                        <td><button type="button" class="remove-member-btn">X</button></td>
                    `;
                    membersTableBody.appendChild(newRow);
                    updateMembersHiddenInput();
                    selectedOption.disabled = true;
                    memberSelect.value = "";
                }
            });

            membersTableBody.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove-member-btn')) {
                    const rowToRemove = event.target.closest('tr');
                    const memberIdToRemove = rowToRemove.dataset.memberId;
                    rowToRemove.remove();
                    updateMembersHiddenInput();
                    
                    const optionToEnable = memberSelect.querySelector(`option[value="${memberIdToRemove}"]`);
                    if (optionToEnable) {
                        optionToEnable.disabled = false;
                    }
                }
            });

            // Disable the current foreman in the members dropdown and ensure not in list
            function syncForemanExclusion(prevForemanId = null) {
                // Re-enable previous foreman option if any
                if (prevForemanId) {
                    const prevOpt = memberSelect.querySelector(`option[value="${prevForemanId}"]`);
                    if (prevOpt) prevOpt.disabled = false;
                }
                const currentForemanId = foremanSelect ? foremanSelect.value : '';
                if (!currentForemanId) return;
                // Disable current foreman in member select
                const opt = memberSelect.querySelector(`option[value="${currentForemanId}"]`);
                if (opt) opt.disabled = true;
                // If already added as member, remove it
                const existingRow = membersTableBody.querySelector(`tr[data-member-id="${currentForemanId}"]`);
                if (existingRow) {
                    existingRow.remove();
                    updateMembersHiddenInput();
                }
            }

            let lastForemanId = '';
            if (foremanSelect) {
                foremanSelect.addEventListener('change', function() {
                    syncForemanExclusion(lastForemanId);
                    lastForemanId = foremanSelect.value;
                });
            }
        });
    </script>
</body>
</html> 