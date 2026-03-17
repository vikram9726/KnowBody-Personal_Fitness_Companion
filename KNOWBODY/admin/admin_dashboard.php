<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

//Initialize stats array    
$stats = ['users' => 0, 'exercises' => 0, 'active_plans' => 0, 'pending_feedback' => 0];


function safeCount($mysqli, $query) {
    try {
        $result = $mysqli->query($query);
        return $result ? $result->fetch_assoc()['count'] : 0;
    } catch (Exception $e) {
        return 0;
    }
}

$stats['users'] = safeCount($mysqli, "SELECT COUNT(*) as count FROM users");
$stats['exercises'] = safeCount($mysqli, "SELECT COUNT(*) as count FROM exercises");
$stats['active_plans'] = safeCount($mysqli, "SELECT COUNT(*) as count FROM user_plans WHERE status = 'Active'");
$stats['pending_feedback'] = safeCount($mysqli, "SELECT COUNT(*) as count FROM feedback WHERE status = 'Pending'");
?>

<?php
include 'admin_header.php';
?>

        <div class="main-content">
            <div class="welcome-section">
                <h2>Welcome, Admin!</h2>
                <p>Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>

            <div class="stats-grid">
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">group</span>
                    <h3>Total Users</h3>
                    <div class="stat-number"><?php echo $stats['users']; ?></div>
                    <a href="manage_users.php" class="btn">Manage Users</a>
                </div>
                
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">fitness_center</span>
                    <h3>Total Exercises</h3>
                    <div class="stat-number"><?php echo $stats['exercises']; ?></div>
                    <a href="manage_exercises.php" class="btn">Manage Exercises</a>
                </div>
                
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">schedule</span>
                    <h3>Active Workout Plans</h3>
                    <div class="stat-number"><?php echo $stats['active_plans']; ?></div>
                    <a href="manage_workouts.php" class="btn">View Plans</a>
                </div>
                
                <div class="stat-card glass-container">
                    <span class="material-icons" style="font-size: 2rem; color: #4481eb;">feedback</span>
                    <h3>Pending Feedback</h3>
                    <div class="stat-number"><?php echo $stats['pending_feedback']; ?></div>
                    <a href="feedback_review.php" class="btn">Review</a>
                </div>

            </div>

            <?php if ($stats['pending_feedback'] > 0): ?>
            <div class="alert">
                <strong>Attention!</strong> You have <?php echo $stats['pending_feedback']; ?> pending feedback items to review.
                <a href="feedback_review.php" class="alert-action">Review Now →</a>
            </div>
            <?php endif; ?>

        

            <div class="quick-actions glass-container" style="margin: 20px 0; padding: 20px;">
                <h3><span class="material-icons">flash_on</span> Quick Actions</h3>
                <div class="action-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                    <a href="manage_users.php" class="card">
                        <span class="material-icons">person_add</span>
                        <span>Add New User</span>
                    </a>
                    <a href="manage_exercises.php" class="card">
                        <span class="material-icons">fitness_center</span>
                        <span>Add New Exercise</span>
                    </a>
                    <a href="manage_workouts.php" class="card">
                        <span class="material-icons">playlist_add</span>
                        <span>Create Workout Plan</span>
                    </a>
                    <a href="assign_workout.php" class="card">
                        <span class="material-icons">assignment_turned_in</span>
                        <span>Assign Workout</span>
                    </a>
                    <a href="admin_report.php" class="card">
                        <span class="material-icons">assessment</span>
                        <span>View Reports</span>
                    </a>
                    <a href="feedback_review.php" class="card">
                        <span class="material-icons">rate_review</span>
                        <span>Review Feedback</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php include '../feature/footer.php'; ?>
            
</body>
</html>
