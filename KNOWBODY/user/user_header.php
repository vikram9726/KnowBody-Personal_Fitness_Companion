<?php
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . FEATURE_URL . '/login_form.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Panel</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/feature/css/style.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        
        .admin-container {
            display: flex;
            min-height: 100vh;
            padding-top: 60px;
        }
        .admin-sidebar {
            width: 280px;
            position: fixed;
            left: 0;
            top: 60px;
            bottom: 0;
            overflow-y: auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 100;
        }
        .admin-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
            position: relative;
        }
        .menu-section {
            margin-bottom: 20px;
        }
        .menu-section h3 {
            color: #ffffff;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            padding-left: 15px;
            opacity: 0.8;
        }
        .menu-item {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: all 0.3s ease;
        }
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        .menu-item.active {
            background: rgba(255, 255, 255, 0.2);
            border-left: 4px solid #4481eb;
        }
        .menu-item .material-icons {
            margin-right: 10px;
            font-size: 20px;
            opacity: 0.9;
        }
        .menu-footer {
            position: absolute;
            bottom: 5px;
            left: 20px;
            right: 20px;
            padding: 5px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            text-align: center;
        }
        .admin-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 1000;
        }
        .page-title {
            color: #ffffff;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-title .material-icons {
            font-size: 2rem;
        }
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            .admin-sidebar.active {
                transform: translateX(0);
            }
            .admin-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div style="display: flex; align-items: center; gap: 20px;">
            <button class="menu-toggle" style="display: none;">
                <span class="material-icons">menu</span>
            </button>
            <h1 style="color: #ffffff; font-size: 1.5rem;">
                <span class="material-icons">fitness_center</span> KnowBody 
            </h1>
        </div>
        <div style="margin-left: auto; display: flex; align-items: center; gap: 20px;">
            <span style="color: #ffffff;">
                <span class="material-icons">person</span>
                <?php echo htmlspecialchars($_SESSION['user']); ?>
            </span>
            <a href="<?php echo BASE_URL; ?>/feature/logout.php" class="btn" style="padding: 8px 15px;">
                <span class="material-icons">logout</span> Logout
            </a>
        </div>
    </div>

    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="menu-section">
                <h3>Main</h3>
                <a href="user_dashboard2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'user_dashboard2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">dashboard</span> Dashboard
                </a>
            </div>

            <div class="menu-section">
                <h3>Workout Session</h3>
                <a href="my_workouts2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'my_workouts2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">fitness_center</span> My Workouts
                </a>
                  <a href="exercise_list2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'exercise_list2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">fitness_center</span> Exercise List
                </a>
                  <a href="progress_report2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'progress_report2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">fitness_center</span> Progress Report
                </a>
            </div>

            <div class="menu-section">
                <h3>Features</h3>
                <a href="bmi_calc2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'bmi_calc2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">fitness_center</span> BMI Calculator
                </a>
                 <a href="calorie_calc2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'calorie_calc2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">fitness_center</span> Calorie Calculator
                </a>
            </div>

            <div class="menu-section">
                <h3>Feedback</h3>
                <a href="give_feedback2.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'give_feedback2.php' ? 'active' : ''; ?>">
                    <span class="material-icons">feedback</span>Provide Feedback
                </a>
            </div>

            <div class="menu-footer">
                <p style="color: #ffffff; margin-bottom: 10px;">KnowBody User Panel</p>
                <small style="color: rgba(255, 255, 255, 0.7);">Version 1.0</small>
            </div>
        </div>

 <div class="admin-content glass-container">

