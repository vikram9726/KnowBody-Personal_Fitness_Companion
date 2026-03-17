<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

// Handle workout plan creation/editing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_plan']) || isset($_POST['edit_plan'])) {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $duration_weeks = $_POST['duration_weeks'];
        $level = $_POST['level'];
        
        if (isset($_POST['edit_plan'])) {
            $plan_id = $_POST['plan_id'];
            $stmt = $mysqli->prepare("UPDATE workout_plans SET name=?, description=?, duration_weeks=?, level=? WHERE id=?");
            $stmt->bind_param("ssisi", $name, $description, $duration_weeks, $level, $plan_id);
        } else {
            $stmt = $mysqli->prepare("INSERT INTO workout_plans (name, description, duration_weeks, level) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssis", $name, $description, $duration_weeks, $level);
        }
        $stmt->execute();
    }
}

// Fetch existing plans
$plans = $mysqli->query("SELECT * FROM workout_plans ORDER BY name");
?>

<?php include 'admin_header.php'; ?>

        <div class="main-content">
            <h2>Workout Plan Management</h2>

            <div class="admin-form">
                <h3>Create New Workout Plan</h3>
                <form method="post">
                    <div class="form-group">
                        <label for="name">Plan Name:</label>
                        <input type="text" id="name" name="name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" rows="4" required></textarea>
                    </div>

                    <div class="form-group">
                        <label for="duration_weeks">Duration (weeks):</label>
                        <input type="number" id="duration_weeks" name="duration_weeks" min="1" required>
                    </div>

                    <div class="form-group">
                        <label for="level">Difficulty Level:</label>
                        <select id="level" name="level" required>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate">Intermediate</option>
                            <option value="advanced">Advanced</option>
                        </select>
                    </div>

                    <button type="submit" name="add_plan" class="submit-button">Create Plan</button>
                </form>
            </div>

            <div class="data-container">
                <h3>Existing Workout Plans</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Duration</th>
                            <th>Level</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($plan = $plans->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($plan['name']); ?></td>
                            <td><?php echo htmlspecialchars($plan['duration_weeks']); ?> weeks</td>
                            <td><?php echo htmlspecialchars($plan['level']); ?></td>
                            <td>
                                <a href="assign_workout.php?plan_id=<?php echo $plan['id']; ?>" class="action-button">Assign</a>
                                
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>