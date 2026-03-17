<?php
session_start();
require_once '../config.php';
require_once '../database/db.php';

// Read form inputs
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validation
    if ($username === '') {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^\w{3,}$/', $username)) {
        $errors[] = "Username must be 3+ chars and contain only letters, numbers, or underscores.";
    }

    if ($password === '') {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // If no errors → Insert user
    if (empty($errors)) {

        $check = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
        if ($check) {
            $check->bind_param('s', $username);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $errors[] = "Username is already taken.";
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $insert = $mysqli->prepare("INSERT INTO users (username, password) VALUES (?, ?)");

                if ($insert) {
                    $insert->bind_param('ss', $username, $hash);

                    if ($insert->execute()) {
                        header("Location: " . rtrim(BASE_URL, '/') . "/feature/login_form.php", true, 303);
                        exit;
                    } else {
                        $errors[] = "Registration failed, please try again.";
                    }
                    $insert->close();
                } else {
                    $errors[] = "Database error: " . $mysqli->error;
                }
            }
            $check->close();
        } else {
            $errors[] = "Database error: " . $mysqli->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                url('https://images.unsplash.com/photo-1534438327276-14e5300c3a48?ixlib=rb-1.2.1&auto=format&fit=crop&w=1950&q=80');
            background-size: cover;
            background-position: center;
            padding: 2rem;
            margin: 0;
        }

        .register-container {
            max-width: 450px;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .register-container h2 {
            color: var(--text-dark);
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        .password-requirements {
            font-size: 0.85em;
            color: var(--text-light);
            margin: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
        }

        .password-requirements::before {
            content: "info";
            font-family: 'Material Icons';
            position: absolute;
            left: 0;
            top: 0;
            font-size: 1rem;
            color: var(--primary-color);
        }

        .error {
            color: #dc3545;
            padding: 0.5rem;
            margin-bottom: 1rem;
            border-radius: 8px;
            background: rgba(220, 53, 69, 0.1);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error::before {
            content: "error";
            font-family: 'Material Icons';
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-dark);
        }

        .login-link a {
            color: #4481eb;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <h2>
            <span class="material-icons"
                style="font-size: 2.5rem; margin-bottom: 15px; color: var(--primary-color);">fitness_center</span>
            Create Account
        </h2>
        <div id="message"></div>

        <form method="POST" action="register_form.php" id="registerForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">
                    <span class="material-icons">person</span>
                    Username
                </label>
                <input type="text" id="username" name="username" required minlength="3" pattern="[a-zA-Z0-9_]+"
                    title="Username must contain only letters, numbers, and underscores">
                <small class="password-requirements">
                    Username must be at least 3 characters long and contain only letters, numbers, and underscores.
                </small>
            </div>

            <div class="form-group">
                <label for="password">
                    <span class="material-icons">lock</span>
                    Password
                </label>
                <input type="password" id="password" name="password" required minlength="6">
                <small class="password-requirements">
                    Password must be at least 6 characters long and contain a mix of letters and numbers.
                </small>
            </div>

            <div class="form-group">
                <label for="confirm_password">
                    <span class="material-icons">lock_clock</span>
                    Confirm Password
                </label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>

            <button type="submit" style="background : #4481eb" class="btn btn-primary">
                <span class="material-icons">person_add </span> <span style=" font-size:15px; font-weight:600;">Create
                    Account</span>
            </button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login_form.php">Login here</a>
        </div>
    </div>

    <script>
        function validateForm() {
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const message = document.getElementById('message');

            if (password.value !== confirmPassword.value) {
                message.innerHTML = '<div class="error">Passwords do not match.</div>';
                return false;
            }
				
            return true;
        }
    </script>
</body>

</html>