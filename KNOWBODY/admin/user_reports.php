<?php
// admin/user_reports.php
// Full working single-file page — shows BMI (last 5), Calorie (last 5), and Workout history.
// Place in /admin and open like: admin/user_reports.php?user_id=13

session_start();
include '../config.php';
include '../database/db.php';

// permission check (adjust role logic if needed)
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . (defined('BASE_URL') ? BASE_URL : '/') . '/feature/login_form.php');
    exit;
}

// ---------- settings ----------
$history_limit = 5; // show last 5 rows

// ---------- helper to detect user-ref column in given table ----------
function detect_user_column(mysqli $m, string $table, array $candidates = ['user_id','id','user']) {
    $cols = [];
    $safe = $m->real_escape_string($table);
    $res = $m->query("SHOW COLUMNS FROM `{$safe}`");
    if ($res) {
        while ($r = $res->fetch_assoc()) $cols[] = $r['Field'];
    }
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    // fallback: return null
    return null;
}

// ---------- read input ----------
$user_id = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// ---------- fetch users for dropdown ----------
$users = [];
try {
    $res = $mysqli->query("SELECT id, username FROM users ORDER BY username ASC");
    while ($r = $res->fetch_assoc()) $users[(int)$r['id']] = $r['username'];
} catch (Exception $e) {
    error_log("Users fetch failed: " . $e->getMessage());
}

// ---------- detect which column refers to user in BMI/Calorie tables ----------
$bmi_user_col = detect_user_column($mysqli, 'user_bmi', ['id','user_id','user']);
$cal_user_col = detect_user_column($mysqli, 'user_calorie', ['id','user_id','user']);

// ---------- initialize containers ----------
$bmi_history = [];
$calorie_history = [];
$workout_history = [];
$workout_result = null;

// ---------- fetch data if user selected ----------
if ($user_id) {
    // BMI history (last N)
    if ($bmi_user_col) {
        $sql = "SELECT created_at, bmi_value, weight, height FROM user_bmi WHERE `{$bmi_user_col}` = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $user_id, $history_limit);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $bmi_history[] = $row;
            $stmt->close();
        } else {
            error_log("Prepare BMI failed: " . $mysqli->error);
        }
    } else {
        error_log("Could not detect user column in user_bmi table");
    }

    // Calorie history
    if ($cal_user_col) {
        $sql = "SELECT recorded_at, calorie, age, gender FROM user_calorie WHERE `{$cal_user_col}` = ? ORDER BY recorded_at DESC LIMIT ?";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('ii', $user_id, $history_limit);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) $calorie_history[] = $row;
            $stmt->close();
        } else {
            error_log("Prepare calorie failed: " . $mysqli->error);
        }
    } else {
        error_log("Could not detect user column in user_calorie table");
    }

    // Workout history (user_plans uses user_id in your dump)
    $q = "SELECT wp.name AS plan_name, up.start_date, up.completion_rate, up.status
          FROM user_plans up
          LEFT JOIN workout_plans wp ON up.plan_id = wp.id
          WHERE up.user_id = ?
          ORDER BY up.start_date DESC";
    $stmt = $mysqli->prepare($q);
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $workout_result = $stmt->get_result();
        while ($row = $workout_result->fetch_assoc()) $workout_history[] = $row;
        $stmt->close();
    } else {
        error_log("Prepare workout history failed: " . $mysqli->error);
    }
}

// include header (keeps UI consistent)
include 'admin_header.php';

// ---------- prepare arrays for Chart.js (oldest -> newest) ----------
$bmi_labels = $bmi_values = $cal_labels = $cal_values = [];

if (!empty($bmi_history)) {
    $rev = array_reverse($bmi_history);
    foreach ($rev as $r) {
        $bmi_labels[] = isset($r['created_at']) ? date('M d', strtotime($r['created_at'])) : '';
        $val = $r['bmi_value'] ?? null;
        $bmi_values[] = is_numeric($val) ? (float)$val : null;
    }
}

if (!empty($calorie_history)) {
    $rev = array_reverse($calorie_history);
    foreach ($rev as $r) {
        $cal_labels[] = isset($r['recorded_at']) ? date('M d', strtotime($r['recorded_at'])) : '';
        $cal_values[] = is_numeric($r['calorie']) ? (float)$r['calorie'] : null;
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>User Reports & Statistics</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
/* ===== Polished visuals for user_reports.php (paste into your <head> style block) ===== */
:root{
  --page-max: 1200px;
  --bg1: linear-gradient(135deg,#f3e88a 0%, #f49866 100%);
  --card-bg: #ffffff;
  --muted: #7b7b7b;
  --accent: #2f7be0;
  --accent-2: #44c185;
  --radius: 14px;
  --shadow-strong: 0 20px 40px rgba(15,20,30,0.08);
  --shadow-soft: 0 8px 22px rgba(15,20,30,0.06);
}

/* Page base */
html,body { height:100%; }
body{
  margin:0;
  font-family: "Inter", "Segoe UI", Roboto, Arial, sans-serif;
  background: var(--bg1);
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
  color:#14202b;
}

/* Main content container */
.main-content{
  max-width: var(--page-max);
  margin: 26px auto;
  padding: 22px;
  box-sizing: border-box;
}

/* Headings */
h2 { margin: 0 0 14px; font-size:28px; font-weight:800; color:#0f1720; }
h3 { margin:0 0 10px; font-size:18px; color:#111; }

/* Card / panel style */
.card {
  background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.96));
  border-radius: var(--radius);
  padding: 16px;
  box-shadow: var(--shadow-strong);
  border: 1px solid rgba(12,20,30,0.03);
  margin-bottom: 18px;
}

/* Nice user selector row */
.user-selector { display:flex; align-items:center; gap:12px; margin-bottom: 18px; }
.user-selector label { font-weight:700; color:#0e1720; }
.user-selector select {
  appearance:none;
  padding:8px 12px;
  border-radius:10px;
  border:1px solid rgba(12,20,30,0.06);
  background: #fff;
  box-shadow: var(--shadow-soft);
}

/* Chart wrapper */
.chart-grid { display:grid; gap:18px; grid-template-columns: 1fr; }
@media (min-width:1100px) { .chart-grid { grid-template-columns: 1fr 1fr; } }

.chart-wrap {
  height: 360px;
  border-radius: 10px;
  padding: 10px;
  background: #fff;
  border: 1px solid rgba(12,20,30,0.04);
  overflow: hidden;
  display:flex;
  align-items:stretch;
}
canvas { width:100% !important; height:100% !important; display:block; }

/* Tables: modern, sticky header, zebra, hover */
.data-table, .history-table {
  width:100%;
  border-collapse:collapse;
  background:#fff;
  border-radius:10px;
  overflow:hidden;
  box-shadow: var(--shadow-soft);
}

.data-table thead th, .history-table thead th {
  padding:12px 14px;
  text-align:left;
  background: #fbfbfb;
  color:#111;
  font-weight:800;
  border-bottom:1px solid rgba(0,0,0,0.04);
  position: sticky;
  top: 0;
  z-index: 3;
}

.data-table tbody td, .history-table tbody td {
  padding:12px 14px;
  color:#333;
  border-bottom:1px solid rgba(0,0,0,0.04);
  font-size:14px;
}

/* zebra/hover */
.data-table tbody tr:nth-child(odd), .history-table tbody tr:nth-child(odd) { background:#fbfbfb; }
.data-table tbody tr:nth-child(even), .history-table tbody tr:nth-child(even) { background:#ffffff; }
.data-table tbody tr:hover, .history-table tbody tr:hover { background:#fffde7; transform: translateZ(0); }

/* No-data state */
.no-data {
  padding:24px;
  text-align:center;
  color:var(--muted);
  background: rgba(255,255,255,0.95);
  border-radius:8px;
}

/* Workout status badges */
.status-badge {
  display:inline-block;
  padding:6px 8px;
  border-radius:8px;
  font-weight:700;
  font-size:13px;
}
.status-completed { background:#e6ffef; color:#137a2f; border:1px solid #b7ebc6; }
.status-active    { background:#e8f4ff; color:#0b5ed7; border:1px solid #cfe8ff; }
.status-other     { background:#fff7e6; color:#a65b00; border:1px solid #f0dab0; }

/* Small helpers */
.spacer { height:14px; }
.legend { display:flex; gap:8px; justify-content:center; margin-top:12px; }
.legend .chip { background:#fff; padding:6px 10px; border-radius:10px; box-shadow:var(--shadow-soft); border:1px solid rgba(0,0,0,0.03); }

/* Responsive */
@media (max-width:720px) {
  .chart-wrap { height:260px; }
  .main-content { padding:16px; }
  h2 { font-size:22px; }
  .data-table thead th, .data-table tbody td { padding:10px; font-size:13px; }
}

</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="main-content">
    <h2>User Reports and Statistics</h2>

    <div class="card user-selector">
        <form method="get">
            <label for="user_id">Select User:</label>
            <select id="user_id" name="user_id" onchange="this.form.submit()">
                <option value="">Choose a user...</option>
                <?php foreach ($users as $id => $username): ?>
                    <option value="<?php echo (int)$id; ?>" <?php if ($user_id === (int)$id) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($user_id): ?>
                <span style="margin-left:12px;color:#666;">Showing latest <?php echo $history_limit; ?> rows</span>
            <?php endif; ?>
        </form>
    </div>

    <?php if (!$user_id): ?>
        <div class="card no-data">Please select a user to view reports.</div>
    <?php else: ?>

    <!-- Charts -->
    <div style="display:grid; grid-template-columns:1fr; gap:18px; margin-bottom:18px;">
        <div class="card">
            <h3>BMI History</h3>
            <div class="chart-wrap" id="bmi-wrap">
                <canvas id="bmiChart"></canvas>
            </div>

            <?php if (empty($bmi_history)): ?>
                <div class="no-data">No BMI records found for this user.</div>
            <?php else: ?>
                <table class="data-table" style="margin-top:12px;">
                    <thead><tr><th>Date</th><th>BMI</th><th>Weight</th><th>Height</th></tr></thead>
                    <tbody>
                        <?php $bmi_display = array_reverse($bmi_history);
                        foreach ($bmi_display as $b): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(isset($b['created_at']) ? date('Y-m-d', strtotime($b['created_at'])) : ''); ?></td>
                                <td><?php echo is_numeric($b['bmi_value']) ? number_format($b['bmi_value'],2) : htmlspecialchars($b['bmi_value'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($b['weight'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($b['height'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <div class="card">
            <h3>Calorie Tracking</h3>
            <div class="chart-wrap" id="cal-wrap">
                <canvas id="calorieChart"></canvas>
            </div>

            <?php if (empty($calorie_history)): ?>
                <div class="no-data">No calorie records found for this user.</div>
            <?php else: ?>
                <table class="data-table" style="margin-top:12px;">
                    <thead><tr><th>Date</th><th>Calories</th><th>Age</th><th>Gender</th></tr></thead>
                    <tbody>
                        <?php $cal_display = array_reverse($calorie_history);
                        foreach ($cal_display as $c): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(isset($c['recorded_at']) ? date('Y-m-d', strtotime($c['recorded_at'])) : ''); ?></td>
                                <td><?php echo htmlspecialchars($c['calorie'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['age'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['gender'] ?? ''); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Workout History -->
    <div class="card">
        <h3>Workout History</h3>
        <?php if (empty($workout_history)): ?>
            <div class="no-data">No workout plans found for this user.</div>
        <?php else: ?>
            <table class="history-table">
                <thead>
                    <tr><th>Plan Name</th><th>Start Date</th><th>Completion</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($workout_history as $w): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($w['plan_name'] ?? $w['name'] ?? ''); ?></td>
                            <td><?php echo !empty($w['start_date']) ? htmlspecialchars(date('Y-m-d', strtotime($w['start_date']))) : '—'; ?></td>
                            <td><?php echo is_numeric($w['completion_rate']) ? ((int)$w['completion_rate']) . '%' : htmlspecialchars($w['completion_rate'] ?? '0%'); ?></td>
                            <td>
                                <?php
                                    $status = $w['status'] ?? '';
                                    $cls = 'status-badge';
                                    if (strtolower($status) === 'completed') $cls .= ' status-completed';
                                    elseif (strtolower($status) === 'active') $cls .= ' status-active';
                                ?>
                                <span class="<?php echo $cls; ?>"><?php echo htmlspecialchars($status); ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div> <!-- main-content -->

<script>
/* Server-provided arrays (oldest -> newest) */
const bmiLabels = <?php echo json_encode($bmi_labels, JSON_UNESCAPED_UNICODE); ?> || [];
const bmiValues = <?php echo json_encode($bmi_values); ?> || [];
const calLabels = <?php echo json_encode($cal_labels, JSON_UNESCAPED_UNICODE); ?> || [];
const calValues = <?php echo json_encode($cal_values); ?> || [];

document.addEventListener('DOMContentLoaded', function() {
    function showNoData(wrapperId, message) {
        const wrap = document.getElementById(wrapperId);
        if (!wrap) return;
        wrap.innerHTML = '<div class="no-data">'+message+'</div>';
    }

    if (typeof Chart === 'undefined') {
        showNoData('bmi-wrap','Chart library missing');
        showNoData('cal-wrap','Chart library missing');
        return;
    }

    if (bmiLabels.length && bmiValues.length && bmiValues.some(v => v !== null && v !== undefined)) {
        const ctx = document.getElementById('bmiChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: bmiLabels,
                datasets: [{
                    label: 'BMI',
                    data: bmiValues,
                    borderColor: 'rgba(54,162,235,0.95)',
                    backgroundColor: 'rgba(54,162,235,0.08)',
                    fill: true,
                    tension: 0.18,
                    pointRadius: 3
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:false } } }
        });
    } else {
        showNoData('bmi-wrap', 'No BMI data to plot for this user.');
    }

    if (calLabels.length && calValues.length && calValues.some(v => v !== null && v !== undefined)) {
        const ctx2 = document.getElementById('calorieChart').getContext('2d');
        new Chart(ctx2, {
            type: 'line',
            data: {
                labels: calLabels,
                datasets: [{
                    label: 'Daily Calories',
                    data: calValues,
                    borderColor: 'rgba(46,204,113,0.95)',
                    backgroundColor: 'rgba(46,204,113,0.08)',
                    fill: true,
                    tension: 0.18,
                    pointRadius: 3
                }]
            },
            options: { responsive:true, maintainAspectRatio:false, scales:{ y:{ beginAtZero:false } } }
        });
    } else {
        showNoData('cal-wrap', 'No calorie data to plot for this user.');
    }
});
</script>

<?php include '../feature/footer.php'; ?>
</body>
</html>
