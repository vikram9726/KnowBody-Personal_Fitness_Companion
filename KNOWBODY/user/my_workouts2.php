<?php
session_start();
include '../config.php';
include '../database/db.php';

// require logged-in user role 'user'
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

// Get user id safely
$username = $_SESSION['user'];
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $row = $res->fetch_assoc()) {
    $user_id = (int)$row['id'];
    $_SESSION['user_id'] = $user_id;
} else {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}
$stmt->close();

include 'user_header.php';


// --- Auto-mark expired active plans as Inactive for this user ---
// This ensures users who didn't finish before end_date become Inactive.
$markStmt = $mysqli->prepare("
    UPDATE user_plans
    SET status = 'Inactive'
    WHERE user_id = ? AND status = 'Active' AND end_date IS NOT NULL AND end_date < CURDATE()
");
if ($markStmt) {
    $markStmt->bind_param('i', $user_id);
    $markStmt->execute();
    $markStmt->close();
}

// Handle POST update (simple dropdown)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $user_plan_id = (int)($_POST['plan_id'] ?? 0); // this is user_plans.id
    // Expected values: 0,20,40,60,80,100
    $progress = (int)($_POST['progress'] ?? 0);
    $valid = [0,20,40,60,80,100];

    if ($user_plan_id > 0 && in_array($progress, $valid, true)) {
        // Verify the plan belongs to this user and is still Active and not expired
        $checkSql = "SELECT status, end_date FROM user_plans WHERE id = ? AND user_id = ?";
        $cstmt = $mysqli->prepare($checkSql);
        if ($cstmt) {
            $cstmt->bind_param('ii', $user_plan_id, $user_id);
            $cstmt->execute();
            $cres = $cstmt->get_result();
            $row = $cres ? $cres->fetch_assoc() : null;
            $cstmt->close();

            $allowUpdate = false;
            if ($row) {
                $currentStatus = $row['status'];
                $endDate = $row['end_date']; // string or null

                // If end_date exists and is before today, do not allow update (and mark inactive if necessary)
                if (!empty($endDate) && (new DateTime($endDate)) < new DateTime('today')) {
                    // mark inactive just in case (defensive)
                    $u = $mysqli->prepare("UPDATE user_plans SET status = 'Inactive' WHERE id = ? AND user_id = ?");
                    if ($u) { $u->bind_param('ii', $user_plan_id, $user_id); $u->execute(); $u->close(); }
                    $allowUpdate = false;
                } else {
                    // allow update only if status is Active (or maybe allow Completed->no)
                    if (strtolower($currentStatus) === 'active') {
                        $allowUpdate = true;
                    } else {
                        $allowUpdate = false;
                    }
                }
            }

            if ($allowUpdate) {
                // Update completion_rate and set status to 'Completed' when progress >= 100
                $update_sql = "
                    UPDATE user_plans
                    SET completion_rate = ?, status = CASE WHEN ? >= 100 THEN 'Completed' ELSE status END
                    WHERE id = ? AND user_id = ?
                ";
                $u_stmt = $mysqli->prepare($update_sql);
                if ($u_stmt) {
                    $u_stmt->bind_param('iiii', $progress, $progress, $user_plan_id, $user_id);
                    $u_stmt->execute();
                    $u_stmt->close();
                }
            }
        }
    }
}

// Fetch user's assigned workout plans (including user_plans.start_date, end_date, completion_rate, status, and user_plans.id)
$sql = "
    SELECT wp.*, up.start_date, up.end_date, up.completion_rate, up.status, up.id as user_plan_id
    FROM workout_plans wp
    JOIN user_plans up ON wp.id = up.plan_id
    WHERE up.user_id = ?
    ORDER BY FIELD(up.status, 'Active') DESC, up.start_date DESC
";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$active_plans = $stmt->get_result();
$stmt->close();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>My Workouts — Dropdown Progress</title>
<style>
/* ===== progress_report2.php — theme-matched CSS (kept from your version) ===== */
/* Tokens (based on your style.css) */
:root{
  --bg-grad: linear-gradient(135deg, #c4d85d 0%, rgb(203, 70, 40) 100%);
  --glass-bg: rgba(255,255,255,0.10);
  --glass-border: rgba(255,255,255,0.18);
  --glass-shadow: 0 8px 32px rgba(31,38,135,0.12);
  --card-radius: 14px;
  --accent: #04befe;
  --accent-2: #4481eb;
  --muted-white: rgba(255,255,255,0.9);
  --muted-2: rgba(255,255,255,0.75);
  --danger: #ff6b6b;
  --success: #38ef7d;
  --surface-light: rgba(255,255,255,0.06);
}
body {
  background: var(--bg-grad);
  font-family: 'Poppins', system-ui, -apple-system, "Segoe UI", Roboto, Arial;
  color: var(--muted-white);
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
}
.container { max-width: 1100px; margin: 20px auto; padding: 22px; }
h2 { font-family: 'Montserrat', sans-serif; font-weight: 600; font-size: 1.5rem; color: var(--muted-white); margin-bottom: 14px; }
.card {
  background: var(--glass-bg);
  border-radius: var(--card-radius);
  padding: 18px;
  border: 1px solid var(--glass-border);
  box-shadow: var(--glass-shadow);
  margin-bottom: 14px;
}
.badge { display:inline-block; padding:6px 12px; border-radius:999px; font-weight:700; font-size:.85rem; color:var(--muted-white); background:rgba(196, 43, 43, 0.68); border:1px solid rgba(255,255,255,0.03); }
.badge.active { background: linear-gradient(90deg, rgba(17, 98, 237, 0.86), rgba(25, 178, 229, 0.74)); }
.badge.completed { background: linear-gradient(90deg, rgba(19, 212, 93, 0.75), rgba(30, 220, 109, 0.88)); }
.badge.inactive { background: rgba(212, 33, 33, 0.84); }
.plan-meta { margin-top:8px; color:var(--muted-2); }
.form-inline { display:flex; gap:12px; align-items:center; flex-wrap:wrap; margin-top:12px; }
.select { min-width:140px; padding:10px 12px; border-radius:10px; border:1px solid rgba(211, 20, 20, 0.65); background: linear-gradient(180deg, rgba(102, 47, 47, 1), rgba(229, 27, 27, 0.66)); color:var(--muted-white); font-weight:700; cursor:pointer; }
.update-btn { background: linear-gradient(45deg, var(--accent-2), var(--accent)); color:white; border:none; padding:10px 14px; border-radius:10px; font-weight:800; cursor:pointer; box-shadow:0 10px 24px rgba(37,99,235,0.12); }
.progress-label { font-weight:700; color:var(--muted-white); }
.small-muted { color:var(--muted-2); font-size:.92rem; margin-top:8px; display:block; }
.disabled-note { color: rgba(255,255,255,0.6); font-size:0.95rem; margin-top:8px; }
@media (max-width:720px){ .container { padding:12px; } }


</style>
</head>
<body>
<div class="container">
  <h2>My Workout Plans</h2>

  <div class="grid">
    <?php if ($active_plans && $active_plans->num_rows > 0): ?>
      <?php while ($plan = $active_plans->fetch_assoc()):
          $plan_name = htmlspecialchars($plan['name']);
          $plan_desc = htmlspecialchars($plan['description']);
          $duration = (int)($plan['duration_weeks'] ?? 0);
          $start = !empty($plan['start_date']) ? date('M j, Y', strtotime($plan['start_date'])) : '-';
          $end = !empty($plan['end_date']) ? date('M j, Y', strtotime($plan['end_date'])) : '-';
          $status = strtolower($plan['status'] ?? 'active');
          $completion = (int)($plan['completion_rate'] ?? 0);
          $user_plan_id = (int)$plan['user_plan_id'];

          // Compute days remaining (if applicable)
          $days_remaining = null;
          if (!empty($plan['end_date'])) {
              $today = new DateTime('today');
              $endObj = new DateTime($plan['end_date']);
              $interval = $today->diff($endObj);
              // if end_date >= today, interval->invert == 0, days remaining is difference + 1 maybe
              if ($endObj >= $today) {
                  $days_remaining = (int)$interval->days;
              } else {
                  $days_remaining = - (int)$interval->days;
              }
          }
      ?>
        <div class="card">
          <div>
            <span class="badge <?php echo ($status==='completed'?'completed':($status==='inactive'?'inactive':'active')); ?>">
              <?php echo htmlspecialchars(ucfirst($status)); ?>
            </span>
          </div>

          <h3 style="margin-top:8px;"><?php echo $plan_name; ?></h3>
          <p style="margin:8px 0 4px;"><?php echo $plan_desc; ?></p>

          <div class="plan-meta">
            <p style="margin:6px 0 0;"><strong>Duration:</strong> <?php echo $duration; ?> weeks &nbsp; • &nbsp; <strong>Started:</strong> <?php echo $start; ?> &nbsp; • &nbsp; <strong>Ends:</strong> <?php echo $end; ?></p>
            <?php if ($days_remaining !== null): ?>
                <?php if ($days_remaining >= 0): ?>
                    <div class="small-muted">Days remaining: <?php echo $days_remaining; ?> day<?php echo $days_remaining!=1 ? 's' : ''; ?></div>
                <?php else: ?>
                    <div class="small-muted">Plan expired <?php echo abs($days_remaining); ?> day<?php echo abs($days_remaining)!=1 ? 's' : ''; ?> ago.</div>
                <?php endif; ?>
            <?php endif; ?>
          </div>

          <?php
            // Determine if update controls should be shown:
            // Show only when status is Active and end_date is not passed.
            $showControls = ($status === 'active');
            if (!empty($plan['end_date'])) {
                $endObjCheck = new DateTime($plan['end_date']);
                if ($endObjCheck < new DateTime('today')) {
                    $showControls = false;
                }
            }
          ?>

          <?php if ($showControls): ?>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="form-inline" style="margin-top:12px;">
              <input type="hidden" name="plan_id" value="<?php echo $user_plan_id; ?>">
              <div>
                <select name="progress" id="select-<?php echo $user_plan_id; ?>" class="form-control" onchange="onSelectChange(<?php echo $user_plan_id; ?>)">
                  <option value="0"  <?php echo $completion===0 ? 'selected' : '';  ?>>0%</option>
                  <option value="20" <?php echo $completion===20 ? 'selected' : ''; ?>>20%</option>
                  <option value="40" <?php echo $completion===40 ? 'selected' : ''; ?>>40%</option>
                  <option value="60" <?php echo $completion===60 ? 'selected' : ''; ?>>60%</option>
                  <option value="80" <?php echo $completion===80 ? 'selected' : ''; ?>>80%</option>
                  <option value="100"<?php echo $completion===100 ? 'selected' : ''; ?>>100%</option>
                </select>
              </div>

              <div class="progress-label" id="label-<?php echo $user_plan_id; ?>"><?php echo $completion; ?>%</div>

              <div>
                <button class="update-btn" type="submit" name="update_progress">Update</button>
              </div>
            </form>
            <div class="small-muted">Select percentage and click Update to save progress.</div>
          <?php else: ?>
            <div class="disabled-note">You cannot update progress for this plan because its status is "<strong><?php echo htmlspecialchars(ucfirst($status)); ?></strong>" or the plan has expired.</div>
            <div style="margin-top:8px;"><strong>Progress:</strong> <?php echo $completion; ?>%</div>
          <?php endif; ?>

        </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="card">
        <p>No workout plans assigned. Contact admin to get started.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php include '../feature/footer.php'; ?>

<script>
function onSelectChange(id) {
  var sel = document.getElementById('select-' + id);
  var label = document.getElementById('label-' + id);
  if (sel && label) {
    label.textContent = sel.value + '%';
  }
}
</script>
</body>
</html>
