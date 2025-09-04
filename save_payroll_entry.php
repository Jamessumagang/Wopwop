<?php
session_start();
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $position = $_POST['position'];
    $salary = $_POST['salary'];
    $days = $_POST['days'];
    $halfday = $_POST['halfday'];
    $absent = $_POST['absent'];
    $holiday = $_POST['holiday'];
    $overtime = $_POST['overtime'];
    $subtotal = $_POST['subtotal'];
    $date = $_POST['date'];

    $stmt = $conn->prepare("INSERT INTO payroll_entries (name, position, salary, days_of_attendance, halfday, absent, holiday_pay, overtime_pay, subtotal, date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
        exit();
    }
    $stmt->bind_param("ssddddddds", $name, $position, $salary, $days, $halfday, $absent, $holiday, $overtime, $subtotal, $date);
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