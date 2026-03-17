<?php
session_start();
include '../config.php';
include '../database/db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// If AJAX request is sent (check header)
$is_ajax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_ajax) {

    header("Content-Type: application/json");

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        echo json_encode(["status" => "error", "message" => "All fields are required."]);
        exit;
    }

    // ==================================
    // ADMIN LOGIN
    // ==================================
    $stmt = $mysqli->prepare("SELECT id, adminname, password FROM admins WHERE adminname = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $adminResult = $stmt->get_result();
    $adminRow = $adminResult->fetch_assoc();
    $stmt->close();

    if ($adminRow) {

        if (password_verify($password, $adminRow['password'])) {

            session_regenerate_id(true);
            $_SESSION['user'] = $adminRow['adminname'];
            $_SESSION['role'] = "admin";
            $_SESSION['admin_id'] = $adminRow['id'];

            echo json_encode([
                "status" => "success",
                "redirect" => ADMIN_URL . "/admin_dashboard.php"
            ]);
            exit;

        } else {
            echo json_encode(["status" => "error", "message" => "Invalid password."]);
            exit;
        }
    }

    // ==================================
    // USER LOGIN
    // ==================================
    $stmt = $mysqli->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $userResult = $stmt->get_result();
    $userRow = $userResult->fetch_assoc();
    $stmt->close();

    if (!$userRow) {
        echo json_encode(["status" => "error", "message" => "Username not found."]);
        exit;
    }

    if (password_verify($password, $userRow['password'])) {

        session_regenerate_id(true);
        $_SESSION['user_id'] = $userRow['id'];
        $_SESSION['user'] = $userRow['username'];
        $_SESSION['role'] = "user";

        echo json_encode([
            "status" => "success",
            "redirect" => USER_URL . "/user_dashboard2.php"
        ]);
        exit;

    } else {
        echo json_encode(["status" => "error", "message" => "Invalid password."]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Login | KnowBody</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;700&display=swap" rel="stylesheet">
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
	.login-container {
		max-width: 400px;
		width: 100%;
		background: rgba(255, 255, 255, 0.95);
		backdrop-filter: blur(10px);
		padding: 2rem;
		border-radius: 15px;
		box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
	}
	.login-container h2 {
		color: var(--text-dark);
		text-align: center;
		margin-bottom: 2rem;
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
	.form-group input,
	.form-group select {
		width: 100%;
		padding: 0.8rem;
		border: 1px solid #ddd;
		border-radius: 8px;
		font-size: 1rem;
		transition: border-color 0.3s;
	}
	.form-group input:focus,
	.form-group select:focus {
		border-color: var(--primary-color);
		outline: none;
	}
	.btn-primary {
		width: 100%;
		padding: 0.8rem;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 0.5rem;
		font-size: 1rem;
	}
	.error {
		color: #dc3545;
		padding: 0.5rem;
		margin-bottom: 1rem;
		border-radius: 8px;
		background: rgba(220, 53, 69, 0.1);
	}
	.register-link {
		text-align: center;
		margin-top: 1.5rem;
		color: var(--text-dark);
	}
	.register-link a {
		color: #4481eb;
		text-decoration: none;
		font-weight: 500;
	}
	.register-link a:hover {
		text-decoration: underline;
	}
	.info-banner {
		position: fixed;
		bottom: 20px;
		left: 50%;
		transform: translateX(-50%);
		background: rgba(255, 255, 255, 0.9);
		backdrop-filter: blur(10px);
		padding: 1rem 2rem;
		border-radius: 50px;
		box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
		display: flex;
		align-items: center;
		gap: 0.5rem;
		color: var(--text-dark);
	}
    .error {
        color:#dc3545; background:rgba(220,53,69,.1); padding:.5rem; border-radius:8px;
        margin-bottom:1rem; text-align:center;
    }
	.error:empty {
    display: none;
}
</style>
</head>

<body>

<div class="login-container">

    <h2 style="text-align:center;">
        <span class="material-icons" style="font-size:2.5rem;color:var(--primary-color);">fitness_center</span>
        <br>Welcome to KnowBody
    </h2>

    <form id="loginForm">
        <div class="form-group">
            <label><span class="material-icons">person</span> Username</label>
            <input type="text" name="username" required>
        </div>

        <div class="form-group">
            <label><span class="material-icons">lock</span> Password</label>
            <input type="password" name="password" required>
        </div>

		<div id="errorBox" class="error"></div>
		
        <button class="btn btn-primary" style="background:#4481eb" type="submit">
            <span class="material-icons">login</span> Login
        </button>
    </form>

    <div class="register-link">
        Don't have an account? <a href="register_form.php">Register here</a>
    </div>

</div>

<script>
document.getElementById("loginForm").addEventListener("submit", function(e) {
    e.preventDefault();

    let formData = new FormData(this);

    fetch("", {
        method: "POST",
        body: formData,
        headers: {
            "X-Requested-With": "XMLHttpRequest" 
        }
    })
    .then(res => res.json())
    .then(data => {

        let errorBox = document.getElementById("errorBox");

        if (data.status === "error") {
            errorBox.style.display = "block";
            errorBox.innerText = data.message;
        }

        if (data.status === "success") {
            window.location.href = data.redirect;
        }
    })
    .catch(err => console.error(err));
});
</script>

</body>
</html>
