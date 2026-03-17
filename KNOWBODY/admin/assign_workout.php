<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

$plan_id = (int)($_GET['plan_id'] ?? 0);

// If plan_id missing or invalid, redirect
if ($plan_id <= 0) {
    header('Location: manage_workouts.php');
    exit;
}

// --- 0) Auto-mark expired active plans as Inactive (users who didn't complete by end_date)
// Only change status for those still 'Active' and with end_date before today.
$markStmt = $mysqli->prepare("
    UPDATE user_plans
    SET status = 'Inactive'
    WHERE status = 'Active' AND end_date IS NOT NULL AND end_date < CURDATE()
");
if ($markStmt) {
    $markStmt->execute();
    $markStmt->close();
} else {
    // optional: log error, but do not block
    // error_log("Failed to prepare mark-inactive query: " . $mysqli->error);
}

// --- 1) Fetch plan details
$stmt = $mysqli->prepare("SELECT * FROM workout_plans WHERE id = ?");
if (!$stmt) {
    die("DB error: " . $mysqli->error);
}
$stmt->bind_param("i", $plan_id);
$stmt->execute();
$plan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$plan) {
    header('Location: manage_workouts.php');
    exit;
}

// Messages
$errors = [];
$success = '';

// --- 2) Handle plan assignment with start_date and computed end_date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_plan'])) {
    // Validate user_ids
    $user_ids = $_POST['user_ids'] ?? [];
    if (!is_array($user_ids) || count($user_ids) === 0) {
        $errors[] = "Please select at least one user to assign the plan.";
    }

    // Validate and parse start_date
    $start_date_input = trim($_POST['start_date'] ?? '');
    if ($start_date_input === '') {
        $errors[] = "Start date is required.";
    } else {
        // Expect format YYYY-MM-DD (HTML date input)
        $start_date_obj = DateTime::createFromFormat('Y-m-d', $start_date_input);
        $start_date_errors = DateTime::getLastErrors();
        if (!$start_date_obj || $start_date_errors['warning_count'] || $start_date_errors['error_count']) {
            $errors[] = "Invalid start date format.";
        } else {
            // Ensure start_date is not in the past (today allowed)
            $today = new DateTime('today');
            if ($start_date_obj < $today) {
                $errors[] = "Start date cannot be in the past. Choose today or a future date.";
            }
        }
    }

    // If no validation errors, compute end_date and insert rows
    if (empty($errors)) {
        // Compute end_date = start_date + duration_weeks weeks
        // Ensure duration exists in plan (fall back to 0 if missing)
        $duration_weeks = (int)($plan['duration_weeks'] ?? 0);
        $end_date_obj = clone $start_date_obj;
        if ($duration_weeks > 0) {
            // Add duration_weeks weeks; add 1 day less? Here we treat end_date as start + (duration_weeks * 7) - 1?
            // We'll set end_date as start_date + duration_weeks weeks - 1 day so a 1-week plan started on 2025-11-01 ends on 2025-11-07.
            $daysToAdd = $duration_weeks * 7 - 1;
            if ($daysToAdd >= 0) {
                $end_date_obj->modify("+{$daysToAdd} days");
            }
        }
        // If duration_weeks=0 -> set end_date to start_date (same day)
        if ($duration_weeks === 0) {
            $end_date_obj = clone $start_date_obj;
        }

        $start_date = $start_date_obj->format('Y-m-d');
        $end_date = $end_date_obj->format('Y-m-d');

        // Prepare insert statement (use prepared stmt)
        // Ensure your user_plans table has columns: user_id, plan_id, status, start_date, end_date
        $insertStmt = $mysqli->prepare("
            INSERT INTO user_plans (user_id, plan_id, status, start_date, end_date)
            VALUES (?, ?, 'Active', ?, ?)
        ");
        if (!$insertStmt) {
            $errors[] = "DB error (prepare): " . $mysqli->error;
        } else {
            // Loop through user_ids and insert each
            $inserted = 0;
            foreach ($user_ids as $uid_raw) {
                $uid = (int)$uid_raw;
                if ($uid <= 0) continue;

                // Optional: skip if already active for this plan (extra safety)
                $check = $mysqli->prepare("
                    SELECT COUNT(*) FROM user_plans
                    WHERE user_id = ? AND plan_id = ? AND status = 'Active'
                ");
                if ($check) {
                    $check->bind_param("ii", $uid, $plan_id);
                    $check->execute();
                    $check->bind_result($countActive);
                    $check->fetch();
                    $check->close();
                    if ($countActive > 0) {
                        // User already has active plan — skip
                        continue;
                    }
                }

                $insertStmt->bind_param("iiss", $uid, $plan_id, $start_date, $end_date);
                if ($insertStmt->execute()) {
                    $inserted++;
                } else {
                    // Collect error but continue
                    $errors[] = "Failed to assign to user ID {$uid}: " . $insertStmt->error;
                }
            }
            $insertStmt->close();

            if ($inserted > 0 && empty($errors)) {
                $success = "{$inserted} user(s) assigned the plan successfully. Start: {$start_date}, End: {$end_date}.";
                // redirect back with success flag (optional)
                header('Location: manage_workouts.php?assigned=1');
                exit;
            } elseif ($inserted > 0) {
                $success = "{$inserted} user(s) assigned but some errors occurred.";
            } else {
                if (empty($errors)) $errors[] = "No users were assigned (maybe they already have an active plan).";
            }
        }
    }
}

// --- 3) Fetch available users who do not already have an active instance of this plan
$users = $mysqli->query("
    SELECT id, username 
    FROM users 
    WHERE id NOT IN (
        SELECT user_id 
        FROM user_plans 
        WHERE plan_id = {$plan_id} AND status = 'Active'
    )
    ORDER BY username
");
?>

<?php include 'admin_header.php'; ?>

        <div class="main-content">
            <h2>Assign Workout Plan</h2>
            
            <div class="plan-details">
                <h3><?php echo htmlspecialchars($plan['name']); ?></h3>
                <p><strong>Duration:</strong> <?php echo (int)$plan['duration_weeks']; ?> weeks</p>
                <p><strong>Level:</strong> <?php echo htmlspecialchars($plan['level']); ?></p>
                <p><?php echo nl2br(htmlspecialchars($plan['description'])); ?></p>
            </div>

            <div class="admin-form">
                <h3>Assign to Users</h3>

                <?php if (!empty($errors)): ?>
                    <div style="padding:10px;border-radius:6px;background:#fff1f1;color:#9b2c2c;margin-bottom:12px;">
                        <strong>Errors:</strong>
                        <ul>
                        <?php foreach ($errors as $e): ?>
                            <li><?php echo htmlspecialchars($e); ?></li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div style="padding:10px;border-radius:6px;background:#ecfdf5;color:#064e3b;margin-bottom:12px;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <div class="form-group">
                        <label>Select Users:</label>
                        <select name="user_ids[]" multiple required size="10" class="user-select">
                            <?php while ($user = $users->fetch_assoc()): ?>
                            <option value="<?php echo (int)$user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                        <small>Hold Ctrl/Cmd to select multiple users</small>
                    </div>

                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <!-- start_date: default to today -->
                        <input type="date" id="start_date" name="start_date" required
                               value="<?php echo htmlspecialchars($_POST['start_date'] ?? (new DateTime('today'))->format('Y-m-d')); ?>">
                        <small>Choose today or a future date.</small>
                    </div>

                    <button type="submit" name="assign_plan" class="submit-button">Assign Workout Plan</button>
                    <a href="manage_workouts.php" class="cancel-button">Cancel</a>
                </form>
            </div>
        </div>
    </div>
  
</body>
</html>
