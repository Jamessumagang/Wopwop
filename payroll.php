<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';

// Fetch all payroll entries
$payroll_entries = [];
$sql = "SELECT * FROM payroll_entries ORDER BY id ASC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $payroll_entries[] = $row;
    }
}
// Fetch positions and map to daily salary
$positions = [];
$position_salary_map = [];
$posResult = $conn->query("SELECT position_name, daily_rate FROM positions ORDER BY position_name ASC");
if ($posResult && $posResult->num_rows > 0) {
    while ($row = $posResult->fetch_assoc()) {
        $positions[] = $row;
        $position_salary_map[$row['position_name']] = (float)$row['daily_rate'];
    }
}
// Fetch active employees and their positions
$employees = [];
$empResult = $conn->query("SELECT id, firstname, middlename, lastname, position FROM employees WHERE status='Active' ORDER BY lastname, firstname");
if ($empResult && $empResult->num_rows > 0) {
    while ($row = $empResult->fetch_assoc()) {
        $full_name = trim($row['lastname'] . ', ' . $row['firstname'] . ' ' . $row['middlename']);
        $employees[] = [
            'id' => (int)$row['id'],
            'name' => $full_name,
            'position' => $row['position']
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Payroll</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
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
       
        .payroll-container {
            max-width: none;
            width: 100vw;
            border-radius: 14px;
            box-sizing: border-box;
            padding: 0 0 32px 0;
            box-shadow: 0 2px 16px rgba(37,99,235,0.06);
            position: relative;
            z-index: 1;
        }
        .payroll-title {
            text-align: center;
            font-size: 2.1em;
            font-weight: 500;
            margin: 32px 0 18px 0;
            letter-spacing: 0.5px;
            color: #222;
        }
        .search-bar-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 0 18px 0;
            justify-content: flex-end;
            padding: 0 32px;
        }
        .filter-bar-container {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 0 6px 8px 0;
            justify-content: flex-end;
            padding: 0 32px;
        }
        .search-input, .date-input {
            font-family: inherit;
            font-size: 1em;
            padding: 9px 16px;
            border: 1.5px solid #e0e7ef;
            border-radius: 8px;
            outline: none;
            min-width: 200px;
            background: #f7fafd;
            transition: border 0.2s;
        }
        .search-input:focus, .date-input:focus {
            border: 1.5px solid #2563eb;
            background: #fff;
        }
        
        /* Search Input Wrapper */
        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            min-width: 200px;
        }
        
        .search-input {
            padding-right: 50px; /* Make room for voice button */
            width: 100%;
        }
        
        .search-btn {
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 9px 22px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
        }
        .search-btn:hover {
            background: #1746a0;
        }
        
        /* Voice Search Button Styles */
        .voice-search-btn {
            position: absolute;
            right: 8px;
            top: 50%;
            transform: translateY(-50%);
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 6px 10px;
            font-size: 0.9em;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            z-index: 2;
        }
        
        .voice-search-btn:hover {
            background: #059669;
        }
        
        .voice-search-btn.listening {
            background: #ef4444;
            animation: pulse 1.5s infinite;
        }
        
        .voice-search-btn.listening:hover {
            background: #dc2626;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
        
        .voice-status {
            background: #fef3c7;
            color: #92400e;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.9em;
            font-weight: 500;
            margin-left: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #fbbf24;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
            table-layout: fixed;
            margin: 0 auto;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 6px rgba(37,99,235,0.04);
        }
        th, td {
            border-bottom: 1.5px solid #e0e7ef;
            padding: 0 8px;
            text-align: center;
            font-size: 0.75em;
            height: 48px;
            vertical-align: middle;
            background: #fff;
            border-right: 1px solid #e0e7ef;
        }
        
        th:nth-child(1), td:nth-child(1) { min-width: 180px; } /* Employee Name */
        th:nth-child(2), td:nth-child(2) { min-width: 120px; } /* Position */
        th:nth-child(3), td:nth-child(3) { min-width: 120px; } /* Daily Salary */
        th:nth-child(4), td:nth-child(4) { min-width: 100px; } /* Days Present */
        th:nth-child(5), td:nth-child(5) { min-width: 100px; } /* Half Days */
        th:nth-child(6), td:nth-child(6) { min-width: 120px; } /* CA Cash Advance */
        th:nth-child(7), td:nth-child(7) { min-width: 120px; } /* Holiday Pay */
        th:nth-child(8), td:nth-child(8) { min-width: 120px; } /* Overtime Pay */
        th:nth-child(9), td:nth-child(9) { min-width: 140px; } /* Total Pay */
        th:nth-child(10), td:nth-child(10) { min-width: 150px; } /* Actions */
        th {
            background: #f7fafd;
            color: #2563eb;
            font-weight: 600;
            font-size: 0.8em;
            height: 48px;
            letter-spacing: 0.2px;
            border-bottom: 2.5px solid #2563eb;
            position: relative;
        }
        
        /* Add subtle borders to group related columns */
        th:nth-child(3), td:nth-child(3) { border-left: 2px solid #e0e7ef; } /* Daily Salary */
        th:nth-child(7), td:nth-child(7) { border-left: 2px solid #e0e7ef; } /* Holiday Pay */
        th:nth-child(9), td:nth-child(9) { border-left: 2px solid #e0e7ef; } /* Total Pay */
        th:nth-child(10), td:nth-child(10) { border-left: 2px solid #e0e7ef; } /* Actions */
        tr:last-child td {
            border-bottom: none;
        }
        td {
            font-weight: 400;
            height: 48px;
        }
        .input-cell input {
            width: 100%;
            height: 38px;
            padding: 0 8px;
            font-size: 0.75em;
            border: 1.2px solid #e0e7ef;
            outline: none;
            background: #f7fafd;
            text-align: center;
            box-sizing: border-box;
            border-radius: 6px;
            transition: background 0.2s, border 0.2s;
        }
        .input-cell input:focus {
            background: #fff;
            border: 1.2px solid #2563eb;
        }
        tr {
            transition: background 0.2s;
        }
        tr:hover {
            background: #f0f6ff;
        }
        .subtotal {
            font-weight: 500;
            font-size: 1.08em;
        }
        .action-btn, .delete-btn, .edit-btn {
            background: #f7fafd;
            color: #2563eb;
            border: 1.5px solid #e0e7ef;
            border-radius: 8px;
            padding: 6px 14px;
            font-size: 0.75em;
            font-weight: 500;
            cursor: pointer;
            margin: 2px 2px;
            transition: background 0.2s, color 0.2s, border 0.2s;
            box-shadow: none;
        }
        .action-btn:hover, .edit-btn:hover {
            background: #2563eb;
            color: #fff;
            border: 1.5px solid #2563eb;
        }
        .delete-btn {
            color: #e74c3c;
            border: 1.5px solid #f3b1a7;
            background: #fff0f0;
        }
        .delete-btn:hover {
            background: #e74c3c;
            color: #fff;
            border: 1.5px solid #e74c3c;
        }
        .add-row-btn {
            background: #f7fafd;
            color: #2563eb;
            border: 1.5px solid #e0e7ef;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1em;
            font-weight: 500;
            cursor: pointer;
            margin: 24px 0 0 0;
            display: block;
            margin-left: auto;
            margin-right: auto;
            transition: background 0.2s, color 0.2s, border 0.2s;
        }
        .add-row-btn:hover {
            background: #2563eb;
            color: #fff;
            border: 1.5px solid #2563eb;
        }
        .home-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #f7fafd;
            color: #2563eb;
            border: 1.5px solid #e0e7ef;
            border-radius: 8px;
            padding: 10px 28px;
            font-size: 1.08em;
            font-weight: 600;
            text-decoration: none;
            margin: 24px 0 0 24px;
            transition: background 0.2s, color 0.2s, border 0.2s;
            box-shadow: none;
        }
        .home-btn:hover {
            background: #2563eb;
            color: #fff;
            border: 1.5px solid #2563eb;
            text-decoration: none;
        }
        .notif {
            position: fixed;
            top: 30px;
            right: 30px;
            background: #2563eb;
            color: #fff;
            padding: 16px 32px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 9999;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            font-size: 1.1em;
        }
        .notif.error {
            background: #e74c3c;
        }
        th, td, .input-cell input, .action-btn, .delete-btn, .edit-btn, .add-row-btn, .home-btn, .notif {
            font-family: 'Poppins', 'Montserrat', 'Inter', Arial, sans-serif;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Expose positions/salary map and employees to JS
    const POSITION_SALARY_MAP = <?php echo json_encode($position_salary_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const EMPLOYEES = <?php echo json_encode($employees, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    function calculateSubtotal(row) {
        const salary = parseFloat(row.querySelector('.salary').value) || 0;
        const days = parseFloat(row.querySelector('.days').value) || 0;
        const halfday = parseFloat(row.querySelector('.halfday').value) || 0;
        const cashAdvance = parseFloat(row.querySelector('.absent').value) || 0;
        const holiday = parseFloat(row.querySelector('.holiday').value) || 0;
        const overtime = parseFloat(row.querySelector('.overtime').value) || 0;
        let subtotal = (salary * days) + (salary * 0.5 * halfday) + (salary * holiday) + (60 * overtime) - cashAdvance;
        row.querySelector('.subtotal').value = subtotal > 0 ? subtotal.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}) : '';
    }
    function showNotification(msg, type = 'success') {
        let notif = document.createElement('div');
        notif.textContent = msg;
        notif.style.position = 'fixed';
        notif.style.top = '30px';
        notif.style.right = '30px';
        notif.style.background = type === 'success' ? '#22c55e' : '#ef4444';
        notif.style.color = '#fff';
        notif.style.padding = '16px 32px';
        notif.style.borderRadius = '8px';
        notif.style.fontWeight = 'bold';
        notif.style.zIndex = 9999;
        notif.style.boxShadow = '0 2px 8px rgba(0,0,0,0.15)';
        document.body.appendChild(notif);
        setTimeout(() => notif.remove(), 2000);
    }
    function attachListeners(row, id = null) {
        row.querySelectorAll('input').forEach(input => {
            if (input.classList.contains('salary') || input.classList.contains('days') || input.classList.contains('halfday') || input.classList.contains('absent') || input.classList.contains('holiday') || input.classList.contains('overtime')) {
                input.addEventListener('input', () => calculateSubtotal(row));
            }
        });
        // When position changes, auto-fill salary if known
        const positionInput = row.querySelector('.position');
        const salaryInput = row.querySelector('.salary');
        if (positionInput && salaryInput) {
            const syncSalary = () => {
                const pos = positionInput.value;
                if (POSITION_SALARY_MAP && Object.prototype.hasOwnProperty.call(POSITION_SALARY_MAP, pos)) {
                    salaryInput.value = POSITION_SALARY_MAP[pos];
                    calculateSubtotal(row);
                }
            };
            positionInput.addEventListener('change', syncSalary);
            positionInput.addEventListener('input', syncSalary);
        }
        // When salary is chosen from dropdown, try to backfill position if the rate is unique
        if (salaryInput && positionInput) {
            const syncPositionFromRate = () => {
                const rate = salaryInput.value.trim();
                if (rate === '') return;
                // Find positions matching this rate
                const matches = Object.entries(POSITION_SALARY_MAP || {}).filter(([, r]) => String(r) === rate || String(r) === String(parseFloat(rate)));
                if (matches.length === 1) {
                    positionInput.value = matches[0][0];
                }
                calculateSubtotal(row);
            };
            salaryInput.addEventListener('change', syncPositionFromRate);
            salaryInput.addEventListener('input', syncPositionFromRate);
        }
        // When employee name changes, auto-fill position and consequently salary
        const nameInput = row.querySelector('.name');
        if (nameInput && positionInput) {
            const syncEmployee = () => {
                const selectedName = nameInput.value;
                const match = (EMPLOYEES || []).find(e => e.name === selectedName);
                if (match) {
                    positionInput.value = match.position || '';
                    // Trigger salary sync after setting position
                    if (typeof POSITION_SALARY_MAP[match.position] !== 'undefined') {
                        salaryInput.value = POSITION_SALARY_MAP[match.position];
                    }
                    calculateSubtotal(row);
                }
            };
            nameInput.addEventListener('change', syncEmployee);
            nameInput.addEventListener('input', syncEmployee);
        }
        // Delete
        row.querySelector('.delete-btn').addEventListener('click', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e11d48',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, delete it',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (!result.isConfirmed) {
                    showNotification('Delete cancelled');
                    return;
                }
                if (id) {
                    // AJAX delete for saved row
                    fetch('delete_payroll_entry.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            row.remove();
                            Swal.fire({
                                title: 'Deleted!',
                                text: 'The payroll entry has been removed.',
                                icon: 'success',
                                confirmButtonColor: '#2563eb'
                            });
                        } else {
                            Swal.fire({
                                title: 'Delete failed',
                                text: (data.error || ''),
                                icon: 'error',
                                confirmButtonColor: '#2563eb'
                            });
                        }
                    })
                    .catch(() => Swal.fire({
                        title: 'Delete failed',
                        icon: 'error',
                        confirmButtonColor: '#2563eb'
                    }));
                } else {
                    row.remove();
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'The payroll entry has been removed.',
                        icon: 'success',
                        confirmButtonColor: '#2563eb'
                    });
                }
            });
        });
        // Save
        if (row.querySelector('.action-btn')) {
            row.querySelector('.action-btn').addEventListener('click', function() {
                const name = row.querySelector('.name').value;
                const position = row.querySelector('.position').value;
                const salary = row.querySelector('.salary').value;
                const days = row.querySelector('.days').value;
                const halfday = row.querySelector('.halfday').value;
                const absent = row.querySelector('.absent').value;
                const holiday = row.querySelector('.holiday').value;
                const overtime = row.querySelector('.overtime').value;
                const subtotal = row.querySelector('.subtotal').value.replace(/,/g, '');
                const date = row.querySelector('.payroll-date').value;
                if (!name || !position) {
                    showNotification('Name and Position are required!', 'error');
                    return;
                }
                fetch('save_payroll_entry.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        name, position, salary, days, halfday, absent, holiday, overtime, subtotal, date
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        row.querySelectorAll('input').forEach(input => input.setAttribute('readonly', true));
                        row.querySelector('.action-btn').disabled = true;
                        row.querySelector('.action-btn').textContent = 'Saved';
                        // Add Edit button
                        if (!row.querySelector('.edit-btn')) {
                            let editBtn = document.createElement('button');
                            editBtn.type = 'button';
                            editBtn.className = 'edit-btn';
                            editBtn.textContent = 'Edit';
                            editBtn.style.marginLeft = '6px';
                            row.querySelector('td:last-child').appendChild(editBtn);
                            attachEditListener(row, null); // null for new row, will reload on refresh
                        }
                        showNotification('Saved successfully!');
                    } else {
                        showNotification('Save failed! ' + (data.error || ''), 'error');
                    }
                })
                .catch(() => showNotification('Save failed!', 'error'));
            });
        }
        // Edit (for loaded rows)
        if (row.querySelector('.edit-btn')) {
            attachEditListener(row, id);
        }
    }
    function attachEditListener(row, id) {
        row.querySelector('.edit-btn').addEventListener('click', function() {
            row.querySelectorAll('input').forEach(input => input.removeAttribute('readonly'));
            // Add Save button if not present
            if (!row.querySelector('.action-btn')) {
                let saveBtn = document.createElement('button');
                saveBtn.type = 'button';
                saveBtn.className = 'action-btn';
                saveBtn.textContent = 'Save';
                row.querySelector('td:last-child').insertBefore(saveBtn, row.querySelector('.delete-btn'));
                // Save handler for update
                saveBtn.addEventListener('click', function() {
                    const name = row.querySelector('.name').value;
                    const position = row.querySelector('.position').value;
                    const salary = row.querySelector('.salary').value;
                    const days = row.querySelector('.days').value;
                    const halfday = row.querySelector('.halfday').value;
                    const absent = row.querySelector('.absent').value;
                    const holiday = row.querySelector('.holiday').value;
                    const overtime = row.querySelector('.overtime').value;
                    const subtotal = row.querySelector('.subtotal').value.replace(/,/g, '');
                    const date = row.querySelector('.payroll-date').value;
                    if (!name || !position) {
                        showNotification('Name and Position are required!', 'error');
                        return;
                    }
                    fetch('update_payroll_entry.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            id, name, position, salary, days, halfday, absent, holiday, overtime, subtotal, date
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            row.querySelectorAll('input').forEach(input => input.setAttribute('readonly', true));
                            saveBtn.disabled = true;
                            saveBtn.textContent = 'Saved';
                            showNotification('Updated successfully!');
                        } else {
                            showNotification('Update failed! ' + (data.error || ''), 'error');
                        }
                    })
                    .catch(() => showNotification('Update failed!', 'error'));
                });
            }
        });
    }
    function addRow(name='', position='', salary='', days='', halfday='', absent='', holiday='', overtime='', subtotal='', date='', readonly=false, id=null) {
        const tbody = document.getElementById('payroll-tbody');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td class="input-cell"><input list="employees-datalist" type="text" class="name" value="${name}" ${readonly ? 'readonly' : ''} placeholder="Enter employee name"></td>
            <td class="input-cell"><input list="positions-datalist" type="text" class="position" value="${position}" ${readonly ? 'readonly' : ''} placeholder="Enter position"></td>
            <td class="input-cell"><input list="rates-datalist" type="text" class="salary" value="${salary}" ${readonly ? 'readonly' : ''} placeholder="Daily rate"></td>
            <td class="input-cell"><input type="number" class="days" min="0" value="${days}" ${readonly ? 'readonly' : ''} placeholder="Full days"></td>
            <td class="input-cell"><input type="number" class="halfday" min="0" value="${halfday}" ${readonly ? 'readonly' : ''} placeholder="Half days"></td>
            <td class="input-cell"><input type="number" class="absent" min="0" step="0.01" value="${absent}" ${readonly ? 'readonly' : ''} placeholder="Cash advance"></td>
            <td class="input-cell"><input type="number" class="holiday" min="0" step="0.01" value="${holiday}" ${readonly ? 'readonly' : ''} placeholder="Holiday pay"></td>
            <td class="input-cell"><input type="number" class="overtime" min="0" step="0.01" value="${overtime}" ${readonly ? 'readonly' : ''} placeholder="Overtime pay"></td>
            <td class="input-cell"><input type="text" class="subtotal" value="${subtotal}" readonly placeholder="Auto-calculated"></td>
            <td class="input-cell"><input type="date" class="payroll-date" value="${date}" ${readonly ? 'readonly' : ''}></td>
            <td>
                ${readonly ? '<button type="button" class="edit-btn">Edit</button>' : '<button type="button" class="action-btn">Save</button>'}
                <button type="button" class="delete-btn">Delete</button>
            </td>
        `;
        tbody.appendChild(tr);
        attachListeners(tr, id);
    }
    function filterRows() {
        const search = document.getElementById('search-input').value.toLowerCase();
        const dateFilterEl = document.getElementById('date-filter');
        const dateFilter = dateFilterEl ? dateFilterEl.value : '';
        document.querySelectorAll('#payroll-tbody tr').forEach(row => {
            let text = '';
            row.querySelectorAll('input').forEach(input => {
                text += (input.value + ' ').toLowerCase();
            });
            const matchesText = (search === '' || text.includes(search));
            const rowDateInput = row.querySelector('.payroll-date');
            const rowDate = rowDateInput ? rowDateInput.value : '';
            const matchesDate = (dateFilter === '' || rowDate === dateFilter);
            row.style.display = (matchesText && matchesDate) ? '' : 'none';
        });
    }
    window.addEventListener('DOMContentLoaded', function() {
        // Add saved rows from PHP
        <?php foreach ($payroll_entries as $entry): ?>
            addRow(
                <?= json_encode($entry['name']) ?>,
                <?= json_encode($entry['position']) ?>,
                <?= json_encode($entry['salary']) ?>,
                <?= json_encode($entry['days_of_attendance']) ?>,
                <?= json_encode($entry['halfday']) ?>,
                <?= json_encode($entry['absent']) ?>,
                <?= json_encode($entry['holiday_pay']) ?>,
                <?= json_encode($entry['overtime_pay']) ?>,
                <?= json_encode($entry['subtotal']) ?>,
                <?= json_encode(isset($entry['date']) ? $entry['date'] : '') ?>,
                true,
                <?= json_encode($entry['id']) ?>
            );
        <?php endforeach; ?>
        // Add 1 empty row for new entry
        addRow();
        document.getElementById('add-row-btn').addEventListener('click', function() {
            addRow();
        });
        document.getElementById('search-input').addEventListener('input', filterRows);
        document.getElementById('search-btn').addEventListener('click', filterRows);
        const dateInput = document.getElementById('date-filter');
        if (dateInput) {
            dateInput.addEventListener('change', filterRows);
            dateInput.addEventListener('input', filterRows);
        }
        
        // Voice Search functionality
        let recognition = null;
        let isListening = false;
        
        // Check if browser supports speech recognition
        if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
            recognition = new SpeechRecognition();
            
            recognition.continuous = false;
            recognition.interimResults = false;
            recognition.lang = 'en-US';
            
            recognition.onstart = function() {
                isListening = true;
                document.getElementById('voice-search-btn').classList.add('listening');
                document.getElementById('voice-status').style.display = 'flex';
                document.getElementById('voice-status').innerHTML = '<i class="fa fa-microphone"></i> Listening...';
            };
            
            recognition.onresult = function(event) {
                const transcript = event.results[0][0].transcript;
                document.getElementById('search-input').value = transcript;
                filterRows();
                showNotification('Voice search: "' + transcript + '"');
            };
            
            recognition.onerror = function(event) {
                console.error('Speech recognition error:', event.error);
                let errorMessage = 'Voice search error. ';
                switch(event.error) {
                    case 'network':
                        errorMessage += 'Check your internet connection.';
                        break;
                    case 'not-allowed':
                        errorMessage += 'Please allow microphone access.';
                        break;
                    case 'no-speech':
                        errorMessage += 'No speech detected. Please try again.';
                        break;
                    default:
                        errorMessage += 'Please try again.';
                }
                showNotification(errorMessage, 'error');
            };
            
            recognition.onend = function() {
                isListening = false;
                document.getElementById('voice-search-btn').classList.remove('listening');
                document.getElementById('voice-status').style.display = 'none';
            };
            
            document.getElementById('voice-search-btn').addEventListener('click', function() {
                if (isListening) {
                    recognition.stop();
                } else {
                    try {
                        recognition.start();
                    } catch (error) {
                        showNotification('Voice search not available. Please use text search.', 'error');
                    }
                }
            });
        } else {
            // Hide voice search button if not supported
            document.getElementById('voice-search-btn').style.display = 'none';
            console.log('Speech recognition not supported in this browser');
        }
    });
    </script>
</head>
<body>
<div class="background-overlay"></div>
<div class="payroll-container">
    <a href="Admindashboard.php" class="home-btn"><i class="fa fa-home"></i> Home</a>
    <div class="payroll-title">Employee Payroll</div>
    <div class="filter-bar-container">
        <input type="date" id="date-filter" class="date-input" title="Filter by payroll date">
    </div>
    <div class="search-bar-container">
        <div class="search-input-wrapper">
            <input type="text" id="search-input" class="search-input" placeholder="Search employee, position, etc...">
            <button type="button" class="voice-search-btn" id="voice-search-btn" title="Voice Search">
                <i class="fa fa-microphone"></i>
            </button>
        </div>
        <button type="button" class="search-btn" id="search-btn"><i class="fa fa-search"></i> Search</button>
        <div id="voice-status" class="voice-status" style="display: none;">
            <i class="fa fa-microphone-slash"></i> Listening...
        </div>
    </div>
    <form method="POST" action="#" onsubmit="return false;">
    <datalist id="positions-datalist">
        <?php foreach ($positions as $pos): ?>
            <option value="<?= htmlspecialchars($pos['position_name']) ?>"><?= htmlspecialchars($pos['position_name']) ?> (<?= htmlspecialchars($pos['daily_rate']) ?>)</option>
        <?php endforeach; ?>
    </datalist>
    <datalist id="rates-datalist">
        <?php foreach ($positions as $pos): ?>
            <option value="<?= htmlspecialchars($pos['daily_rate']) ?>"><?= htmlspecialchars($pos['position_name']) ?> - <?= htmlspecialchars($pos['daily_rate']) ?></option>
        <?php endforeach; ?>
    </datalist>
    <datalist id="employees-datalist">
        <?php foreach ($employees as $emp): ?>
            <option value="<?= htmlspecialchars($emp['name']) ?>"><?= htmlspecialchars($emp['name']) ?></option>
        <?php endforeach; ?>
    </datalist>
    <table>
        <thead>
            <tr>
                <th title="Full name of the employee">Employee Name</th>
                <th title="Job position or role">Position</th>
                <th title="Daily wage rate">Daily Salary</th>
                <th title="Number of full days worked">Days Present</th>
                <th title="Number of half days worked">Half Days</th>
                <th title="Cash advance amount">CA Cash Advance</th>
                <th title="Additional pay for holidays">Holiday Pay</th>
                <th title="Additional pay for overtime">Overtime Pay</th>
                <th title="Total calculated pay">Total Pay</th>
                <th title="Date of payroll entry">Date</th>
                <th title="Save, edit, or delete entry">Actions</th>
            </tr>
        </thead>
        <tbody id="payroll-tbody">
        </tbody>
    </table>
    <button type="button" class="add-row-btn" id="add-row-btn"><i class="fa fa-plus"></i> Add Row</button>
    </form>
</div>
</body>
</html>
<?php
$conn->close();
function getSalaryForPosition($position) {
    $salaries = [
        'Foreman' => 1000,
        'Mason' => 1500,
        // Add more positions and their salaries here
    ];
    return isset($salaries[$position]) ? $salaries[$position] : 0;
}
?> 