<?php
session_start();
if (!isset($_SESSION['client_logged_in']) || $_SESSION['client_logged_in'] !== true) {
    header('Location: client_login.php');
    exit();
}
include 'db.php';
$client_username = isset($_SESSION['client_username']) ? $_SESSION['client_username'] : 'client';

$projects = [];
// Only show projects assigned to this client via client_project_links
$sql = "SELECT p.project_id, p.project_name, p.project_type, p.start_date
        FROM projects p
        INNER JOIN client_project_links cpl ON cpl.project_id = p.project_id
        WHERE cpl.client_user_id = ?
        ORDER BY p.start_date DESC";
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('i', $_SESSION['client_user_id']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        $result->free();
    } else {
        die('Query failed: ' . $conn->error);
    }
    $stmt->close();
} else {
    die('Query prepare failed: ' . $conn->error);
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --primary-600: #1d4ed8;
            --success: #10b981;
            --success-600: #059669;
            --danger: #ef4444;
            --danger-600: #dc2626;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --radius: 12px;
            --shadow: 0 8px 24px rgba(2,6,23,0.08);
            --shadow-sm: 0 1px 2px rgba(2,6,23,0.06);
            --transition: 160ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            background: linear-gradient(135deg, #eef2ff 0%, #f8fafc 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            color: var(--text);
        }
        .dashboard-container {
            max-width: 1000px;
            margin: 56px auto;
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 28px 26px 24px 26px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
        }
        .header h2 {
            color: var(--primary);
            font-size: 1.75rem;
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .logout-btn {
            background: var(--danger);
            color: #fff;
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 0.55rem 1.1rem;
            font-weight: 600;
            font-size: 0.98rem;
            cursor: pointer;
            transition: background var(--transition), transform var(--transition);
            text-decoration: none;
        }
        .logout-btn:hover { background: var(--danger-600); transform: translateY(-1px); }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            border-radius: 12px;
            overflow: hidden;
            background: var(--surface);
            box-shadow: var(--shadow-sm);
        }
        th, td { padding: 0.9rem 1.2rem; border-bottom: 1px solid var(--border); }
        th {
            background: var(--surface-alt);
            color: var(--primary);
            font-weight: 700;
            text-align: left;
            font-size: 0.98rem;
            letter-spacing: 0.2px;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover { background: #f5f7fb; }
        .btn-view, .btn-view-phases {
            border: 1px solid transparent;
            border-radius: 10px;
            padding: 0.45rem 0.95rem;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: background var(--transition), color var(--transition), transform var(--transition);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-view { background: var(--primary); color: #fff; }
        .btn-view:hover { background: var(--primary-600); transform: translateY(-1px); }
        .btn-view-phases { background: var(--success); color: #fff; }
        .btn-view-phases:hover { background: var(--success-600); transform: translateY(-1px); }
        .no-projects { text-align: center; color: var(--muted); padding: 2rem 0; font-size: 1.05rem; }
        .message-btn {
            background: #10b981;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.6rem 1rem;
            font-size: 1.2rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: background 0.2s;
        }
        .message-btn:hover {
            background: #059669;
            color: #e0e7ff;
        }
        .messenger-modal {
            display: flex;
            align-items: center;
            justify-content: center;
            position: fixed;
            z-index: 9999;
            left: 0; top: 0; width: 100vw; height: 100vh;
            background: rgba(16, 134, 249, 0.18);
        }
        .messenger-content {
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(37,99,235,0.13), 0 1.5px 6px rgba(0,0,0,0.04);
            padding: 2rem 1.5rem 1.2rem 1.5rem;
            min-width: 340px;
            max-width: 98vw;
            position: relative;
            width: 100%;
            max-width: 400px;
        }
        .close-messenger {
            position: absolute;
            top: 1rem;
            right: 1.2rem;
            font-size: 1.5rem;
            color: #2563eb;
            cursor: pointer;
            font-weight: bold;
        }
        .messenger-title {
            text-align: center;
            color: #2563eb;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            font-weight: 700;
        }
        .chat-messages {
            background: #f1f5f9;
            border-radius: 8px;
            min-height: 120px;
            max-height: 260px;
            overflow-y: auto;
            margin-bottom: 1rem;
            padding: 1rem;
            font-size: 1.05rem;
        }
        .chat-message {
            margin-bottom: 0.7rem;
            display: flex;
            flex-direction: column;
        }
        .chat-message.client {
            align-items: flex-end;
        }
        .chat-message.admin {
            align-items: flex-start;
        }
        .chat-bubble {
            display: inline-block;
            padding: 0.6rem 1rem;
            border-radius: 1.1rem;
            margin-bottom: 0.2rem;
            max-width: 80%;
        }
        .chat-message.client .chat-bubble {
            background: #2563eb;
            color: #fff;
            border-bottom-right-radius: 0.3rem;
        }
        .chat-message.admin .chat-bubble {
            background: #e0e7ff;
            color: #1a2330;
            border-bottom-left-radius: 0.3rem;
        }
        /* Messenger Chat Bubble Styles */
        .message-bubble {
            display: inline-block;
            padding: 12px 18px;
            border-radius: 18px;
            margin-bottom: 6px;
            max-width: 80%;
            word-break: break-word;
            font-size: 1em;
            box-shadow: 0 2px 8px rgba(37,99,235,0.04);
        }
        .message-sent {
            background: #2563eb;
            color: #fff;
            align-self: flex-end;
            border-bottom-right-radius: 6px;
            margin-left: 20%;
            text-align: left;
        }
        .message-received {
            background: #f1f5fa;
            color: #222;
            align-self: flex-start;
            border-bottom-left-radius: 6px;
            margin-right: 20%;
            text-align: left;
        }
        .message-meta {
            font-size: 0.8em;
            color: #888;
            margin-top: 2px;
            margin-bottom: 10px;
            text-align: right;
        }
        .date-separator {
            text-align: center;
            color: #aaa;
            font-size: 0.9em;
            margin: 18px 0 10px 0;
            font-weight: 500;
        }
        @media (max-width: 700px) {
            .dashboard-container {
                max-width: 96vw;
                padding: 1rem 0.6rem 1rem 0.6rem;
            }
            .header h2 {
                font-size: 1.25rem;
            }
            th, td {
                padding: 0.7rem 0.6rem;
                font-size: 0.96rem;
            }
            .messenger-content {
                min-width: 90vw;
                padding: 1rem 0.6rem 1rem 0.6rem;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Placeholder for sending message (AJAX to be implemented)
            document.getElementById('sendMessageForm').addEventListener('submit', function(e) {
                e.preventDefault();
                // TODO: AJAX send message
                const input = document.getElementById('messageInput');
                if (input.value.trim() !== '') {
                    const chat = document.getElementById('chatMessages');
                    const msgDiv = document.createElement('div');
                    msgDiv.className = 'chat-message client';
                    msgDiv.innerHTML = `<div class='chat-bubble'>${input.value}</div>`;
                    chat.appendChild(msgDiv);
                    chat.scrollTop = chat.scrollHeight;
                    input.value = '';
                }
            });
        });
    </script>
</head>
<body>  
    <div class="dashboard-container">
        <div class="header">
            <h2>Welcome, Client!</h2>
            <div style="display:flex;align-items:center;gap:1.2rem;">
                <a href="client_logout.php" class="logout-btn">Log-out</a>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Project Type</th>
                    <th>Start Date</th>
                    <th>View</th>
                    <th>View Phases</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($projects) > 0): ?>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?= htmlspecialchars($project['project_name']) ?></td>
                            <td><?= htmlspecialchars($project['project_type']) ?></td>
                            <td><?= htmlspecialchars($project['start_date']) ?></td>
                            <td><a href="project_progress_view.php?id=<?= urlencode($project['project_id']) ?>" class="btn-view"><i class="fa fa-eye"></i> View</a></td>
                            <td><a href="view_project_phases.php?project_id=<?= urlencode($project['project_id']) ?>" class="btn-view-phases"><i class="fa fa-list"></i> View Phases</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="no-projects">No projects found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html> 