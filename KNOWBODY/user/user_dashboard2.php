<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . FEATURE_URL . '/login_form.php');
    exit;
}
?>
<?php
$user_id = (int) $_SESSION['user_id'];

// defaults
$active_plans = 0;
$latest_bmi_value = null;
$latest_calorie = ['calorie' => null];

try {
    // Active plans (prepared)
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM user_plans WHERE user_id = ? AND status = 'Active'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $active_plans = (int) $row['count'];
    }
    $stmt->close();

    // Latest BMI (prepared) — NOTE: use the correct column that references user (user_id)
      $stmt = $mysqli->prepare("SELECT bmi_value FROM user_bmi WHERE id = ? AND bmi_value IS NOT NULL ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        // keep a numeric or null value
        $latest_bmi_value = is_numeric($row['bmi_value']) ? (float) $row['bmi_value'] : null;
    }
    $stmt->close();



    // Latest calorie (prepared)
    $stmt = $mysqli->prepare("SELECT calorie FROM user_calorie WHERE id = ? ORDER BY recorded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $latest_calorie = ['calorie' => $row['calorie']];
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<?php
include 'user_header.php';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Knowbody</title>
</head>
<style>
/*User Dashboard*/

    .action-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 15px;
    }

    .action-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-decoration: none;
        color: inherit;
        transition: transform 0.2s;
        text-align: center;
    }

    .action-card:hover {
        transform: translateY(-5px);
    }

    .action-card .icon {
        font-size: 2em;
        margin-bottom: 10px;
        display: block;
    }

    .action-card h4 {
        margin: 10px 0;
        color: #2c3e50;
    }

    .action-card p {
        color: #7f8c8d;
        font-size: 0.9em;
        margin: 0;
    }
</style>
<body>
<div class="main-content">
            <div class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user']); ?>!</h2>
            </div>

             <div class="stats-grid">
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">group</span>
                    <h3>Active Workout Plan</h3>
                    <div class="stat-number"><?php echo $active_plans; ?></div>
                    <a href="my_workouts2.php" class="btn">Manage Workout Plan --></a>
                </div>
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">group</span>
                    <h3>Latest BMI Count</h3>
                    <div class="stat-number"> <?php
                if (is_numeric($latest_bmi_value)) {
                    // format to 1 decimal (e.g., 24.3)
                    echo number_format($latest_bmi_value, 1);
                } else {
                    echo 'N/A';
                }
                ?></div>
                    <a href="bmi_calc2.php" class="btn">Calculate BMI --></a>
                </div>
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">group</span>
                    <h3>Required Calorie Intake</h3>
                    <div class="stat-number"><?php
if (isset($latest_calorie['calorie']) && is_numeric($latest_calorie['calorie'])) {
    echo number_format($latest_calorie['calorie'], 0);
} else {
    echo 'N/A';
}
?></div>
                    <a href="calorie_calc2.php" class="btn">Track Calorie --></a>
                </div>
</div>

<div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-grid">
            <a href="my_workouts2.php" class="action-card">
                <span class="icon">💪</span>
                <h4>My Workouts</h4>
                <p>View and track your workout plans</p>
            </a>
            <a href="progress_report2.php" class="action-card">
                <span class="icon">📈</span>
                <h4>Progress Report</h4>
                <p>Check your fitness journey progress</p>
            </a>
            <a href="give_feedback2.php" class="action-card">
                <span class="icon">📝</span>
                <h4>Give Feedback</h4>
                <p>Share your thoughts with us</p>
            </a>
            <a href="exercise_list2.php" class="action-card">
                <span class="icon">📋</span>
                <h4>Exercise List</h4>
                <p>Browse available exercises</p>
            </a>
        </div>
    </div>

     <?php include '../feature/footer.php'; ?>
</body>
</html>