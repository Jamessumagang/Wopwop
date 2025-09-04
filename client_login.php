<?php
session_start();
if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
    header('Location: client_dashboard.php');
    exit();
}

include 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    if (!empty($username) && !empty($password)) {
        $stmt = $conn->prepare('SELECT id, password FROM client_users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $db_password);
            $stmt->fetch();
            if ($password === $db_password) {
                $_SESSION['client_logged_in'] = true;
                $_SESSION['client_user_id'] = $id;
                $_SESSION['client_username'] = $username;

                // Ensure the is_logged_in column exists, then flag this client as logged in
                $colCheck = $conn->query("SHOW COLUMNS FROM client_users LIKE 'is_logged_in'");
                if ($colCheck && $colCheck->num_rows === 0) {
                    $conn->query("ALTER TABLE client_users ADD COLUMN is_logged_in TINYINT(1) NOT NULL DEFAULT 0");
                }
                if ($update = $conn->prepare('UPDATE client_users SET is_logged_in = 1 WHERE id = ?')) {
                    $update->bind_param('i', $id);
                    $update->execute();
                    $update->close();
                }

                header('Location: client_dashboard.php');
                exit();
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        $stmt->close();
    } else {
        $error = 'Please enter both username and password.';
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Client Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            background: url('./images/background.webp') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            margin: 0;
            font-family: 'Inter', Arial, sans-serif;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(16, 134, 249, 0.65);
            z-index: 0;
        }
        .login-container {
            position: relative;
            z-index: 1;
            width: 380px;
            max-width:300px;
            margin: 0 auto;
            background: rgba(255,255,255,0.93);
            border-radius: 1.2rem;
            box-shadow: 0 8px 32px rgba(37,99,235,0.13), 0 1.5px 6px rgba(0,0,0,0.04);
            padding: 2.5rem 2.2rem 2.2rem 2.2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        h2 {
            text-align: center;
            color:rgb(1, 2, 5);
            margin-bottom: 1.5rem;
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .form-group {
            margin-bottom: 1.25rem;
            width: 100%;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #1e293b;
            font-weight: 500;
        }
        input[type=text], input[type=password] {
            width: 95%;
            padding: 0.85rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 1.1rem;
            background: #f8fafc;
            color: #222;
        }
        .btn {
            width: 100%;
            background: #2563eb;
            color: #fff;
            border: none;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 0.5rem;
        }
        .btn:hover {
            background: #1d4ed8;
        }
        .error {
            color: #ef4444;
            background: #fee2e2;
            border: 1px solid #ef4444;
            border-radius: 0.5rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
            text-align: center;
            width: 100%;
        }
        @media (max-width: 600px) {
            .login-container {
                width: 98vw;
                min-height: 340px;
                padding: 1.2rem 0.5rem 1.2rem 0.5rem;
            }
            h2 {
                font-size: 1.3rem;
            }
        }
        .back-link {
            position: absolute;
            top: 32px;
            left: 32px;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            z-index: 2;
            background: rgba(16,134,249,0.85);
            padding: 0.45rem 1.1rem 0.45rem 0.8rem;
            border-radius: 0.7rem;
            box-shadow: 0 2px 8px rgba(16,134,249,0.10);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.18s, color 0.18s;
        }
        .back-link:hover {
            background: #2563eb;
            color: #e0e7ff;
        }
        @media (max-width: 600px) {
            .back-link {
                top: 10px;
                left: 10px;
                font-size: 0.98rem;
                padding: 0.35rem 0.8rem 0.35rem 0.6rem;
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-link"><i class="fa fa-arrow-left"></i> Back</a>
    <div class="login-container">
        <h2>Login</h2>
        <?php if ($error): ?><div class="error"><i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post" style="width:100%">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Log In</button>
        </form>
    </div>
</body>
</html> 