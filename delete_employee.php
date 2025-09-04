<?php
$conn = new mysqli("localhost", "root", "", "capstone_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$isJsonRequest = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
$response = [ 'success' => false, 'message' => 'Invalid request' ];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Primary action: soft delete to avoid constraint errors
    $soft = $conn->prepare("UPDATE employees SET status = 'Inactive' WHERE id = ?");
    if ($soft) {
        $soft->bind_param("i", $id);
        if ($soft->execute() && $soft->affected_rows > 0) {
            $response = [ 'success' => true, 'message' => 'Employee set to Inactive' ];
        } else {
            // If nothing updated (not found or already inactive), try hard delete as fallback
            $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    if ($stmt->affected_rows > 0) {
                        $response = [ 'success' => true, 'message' => 'Employee deleted' ];
                    } else {
                        $response = [ 'success' => false, 'message' => 'Employee not found' ];
                    }
                } else {
                    $errno = $stmt->errno ?: $conn->errno;
                    $errorMsg = $stmt->error ?: $conn->error;
                    if ($errno === 1451) {
                        $response = [ 'success' => true, 'message' => 'Employee already inactive and linked; kept as Inactive' ];
                    } else {
                        $response = [ 'success' => false, 'message' => 'Failed to delete employee', 'detail' => $errorMsg, 'code' => $errno ];
                    }
                }
                $stmt->close();
            } else {
                $response = [ 'success' => false, 'message' => 'Failed to prepare hard delete', 'detail' => $conn->error ];
            }
        }
        $soft->close();
    } else {
        $response = [ 'success' => false, 'message' => 'Failed to prepare soft delete', 'detail' => $conn->error ];
    }
}

// If it's an AJAX/JSON request, return JSON; otherwise redirect back to list
if ($isJsonRequest) {
    header('Content-Type: application/json');
    echo json_encode($response);
    $conn->close();
    exit();
}

$conn->close();
header("Location: employee_list.php");
exit();
?>
