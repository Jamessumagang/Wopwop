<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}       
include 'db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $salary = floatval($_POST['salary'] ?? 0);
    $days = intval($_POST['days'] ?? 0);
    $halfday = intval($_POST['halfday'] ?? 0);
    $absent = intval($_POST['absent'] ?? 0);
    $holiday = intval($_POST['holiday'] ?? 0);
    $overtime = intval($_POST['overtime'] ?? 0);
    $subtotal = floatval($_POST['subtotal'] ?? 0);
    $date = $_POST['date'];
    // Removed date handling
    $stmt = $conn->prepare("UPDATE payroll_entries SET name=?, position=?, salary=?, days_of_attendance=?, halfday=?, absent=?, holiday_pay=?, overtime_pay=?, subtotal=?, date=? WHERE id=?");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ssddddddssi", $name, $position, $salary, $days, $halfday, $absent, $holiday, $overtime, $subtotal, $date, $id);
    $success = $stmt->execute();
    if (!$success) {
        echo json_encode(['success' => false, 'error' => 'Execute failed: ' . $stmt->error]);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();
    $conn->close();
    echo json_encode(['success' => true]);
    exit();
}
echo json_encode(['success' => false, 'error' => 'Invalid request']); 