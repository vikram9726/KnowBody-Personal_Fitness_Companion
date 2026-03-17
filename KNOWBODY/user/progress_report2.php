<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

// Get user ID from the users table based on the username (safe)
$username = $_SESSION['user'];
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $user_id = (int)$res->fetch_assoc()['id'];
    $_SESSION['user_id'] = $user_id;
} else {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}
$stmt->close();

include 'user_header.php';


// Helper: get single integer count
function getCount($mysqli, $sql, $types = '', $params = []) {
    $stmt = $mysqli->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $r = $stmt->get_result();
    $count = 0;
    if ($r && $row = $r->fetch_assoc()) {
        // Works for SELECT COUNT(*) AS cnt
        $count = (int)($row['cnt'] ?? $row['count'] ?? array_values($row)[0]);
    }
    $stmt->close();
    return $count;
}

// Stats
$stats = [
    'total_workouts' => getCount($mysqli, "SELECT COUNT(*) AS cnt FROM user_plans WHERE user_id = ?", 'i', [$user_id]),
    'completed_workouts' => getCount($mysqli, "SELECT COUNT(*) AS cnt FROM user_plans WHERE user_id = ? AND status = 'Completed'", 'i', [$user_id]),
    'active_workouts' => getCount($mysqli, "SELECT COUNT(*) AS cnt FROM user_plans WHERE user_id = ? AND status = 'Active'", 'i', [$user_id])
];

// Fetch BMI history (last 10 entries) - expects columns: bmi (or bmi_value) and recorded_at (or created_at).
// We'll try a few column name fallbacks to be robust.
$bmi_sql = "SELECT bmi_value, created_at FROM user_bmi WHERE id = ? ORDER BY created_at DESC LIMIT 10";
$stmt = $mysqli->prepare($bmi_sql);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $bmi_result = $stmt->get_result();
    $stmt->close();
} else {
    // fallback in case columns are named differently
    $bmi_sql2 = "SELECT bmi_value AS bmi_value, created_at AS created_at FROM user_bmi WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $mysqli->prepare($bmi_sql2);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $bmi_result = $stmt->get_result();
    $stmt->close();
}

// Fetch calorie history (last 10 entries)
$cal_sql = "SELECT calorie, recorded_at FROM user_calorie WHERE id = ? ORDER BY recorded_at DESC LIMIT 10";
$stmt = $mysqli->prepare($cal_sql);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cal_result = $stmt->get_result();
    $stmt->close();
} else {
    // fallback if columns different
    $cal_sql2 = "SELECT calories AS calorie, created_at AS recorded_at FROM user_calorie WHERE id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $mysqli->prepare($cal_sql2);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cal_result = $stmt->get_result();
    $stmt->close();
}

// Fetch workout history
$workout_sql = "
    SELECT wp.name, up.completion_rate, up.status, up.start_date
    FROM user_plans up
    JOIN workout_plans wp ON up.plan_id = wp.id
    WHERE up.user_id = ?
    ORDER BY up.start_date DESC
    LIMIT 50
";
$stmt = $mysqli->prepare($workout_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$workout_result = $stmt->get_result();
$stmt->close();

// Build arrays for charts (we want oldest -> newest, so reverse after collecting)
$bmi_labels = $bmi_values = [];
if (!empty($bmi_result)) {
    while ($row = $bmi_result->fetch_assoc()) {
        $label = !empty($row['recorded_at']) ? date('M j', strtotime($row['recorded_at'])) : '';
        $bmi_labels[] = $label;
        // tolerate different column names
        $bmi_val = $row['bmi'] ?? $row['bmi_value'] ?? null;
        $bmi_values[] = is_null($bmi_val) ? null : (float)$bmi_val;
    }
    // reverse to oldest-first for plotting
    $bmi_labels = array_reverse($bmi_labels);
    $bmi_values = array_reverse($bmi_values);
}

$cal_labels = $cal_values = [];
if (!empty($cal_result)) {
    while ($row = $cal_result->fetch_assoc()) {
        $label = !empty($row['recorded_at']) ? date('M j', strtotime($row['recorded_at'])) : '';
        $cal_labels[] = $label;
        $cal_values[] = isset($row['calorie']) ? (int)$row['calorie'] : 0;
    }
    $cal_labels = array_reverse($cal_labels);
    $cal_values = array_reverse($cal_values);
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Progress Report</title>
    <style>
       /* ===== Progress Report Page Styling ===== */

.main-content {
    max-width: 1100px;
    margin: 0 auto;
    padding: 20px;
    color: #222;
    font-family: "Poppins", sans-serif;
}

/* --- Page Title --- */
.main-content h2 {
    font-size: 1.8rem;
    font-weight: 600;
    margin-bottom: 20px;
    color: #111;
}

/* --- Stats Grid --- */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #ffffff;
    padding: 18px;
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(40, 40, 40, 0.08);
}

.stat-card h3 {
    font-size: 0.95rem;
    color: #444;
    margin-bottom: 8px;
}

.stat-number {
    font-size: 1.8rem;
    font-weight: 700;
    color: #222;
}

/* --- Chart Containers --- */
.chart-container {
    background: #fff;
    padding: 20px;
    margin-bottom: 25px;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(40, 40, 40, 0.08);
}

.chart-container h3 {
    margin-bottom: 15px;
    font-size: 1.2rem;
    font-weight: 600;
    color: #222;
}

canvas {
    width: 100% !important;
    height: 320px !important;
}

/* --- Workout History Table --- */
.history-section h3 {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 15px 0;
    color: #111;
}

.history-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(40, 40, 40, 0.08);
}

.history-table th {
    background: #dfe3e6;
    padding: 12px;
    font-size: 0.95rem;
    color: #333;
    text-align: left;
    font-weight: 600;
}

.history-table td {
    padding: 12px;
    border-bottom: 1px solid #eee;
    color: #333;
    font-size: 0.95rem;
}

/* --- Status Badges --- */
.status-badge {
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 700;
    display: inline-block;
}

.status-completed {
    background: rgba(46, 204, 113, 0.15);
    color: #1e8c52;
}

.status-active {
    background: rgba(245, 166, 35, 0.15);
    color: #a56600;
}

/* --- Responsive --- */
@media (max-width: 600px) {
    .main-content {
        padding: 10px;
    }

    canvas {
        height: 240px !important;
    }

    .history-table th,
    .history-table td {
        padding: 10px;
    }
}

    </style>
</head>
<body>

<div class="main-content">
    <h2>Progress Report</h2>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Workout Plans</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['total_workouts']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Completed Plans</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['completed_workouts']); ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Plans</h3>
            <div class="stat-number"><?php echo htmlspecialchars($stats['active_workouts']); ?></div>
        </div>
    </div>

    <div class="chart-container">
        <h3>BMI History</h3>
        <canvas id="bmiChart" aria-label="BMI chart" role="img"></canvas>
    </div>

    <div class="chart-container">
        <h3>Calorie Tracking</h3>
        <canvas id="calorieChart" aria-label="Calorie chart" role="img"></canvas>
    </div>

    <div class="history-section">
        <h3>Workout History</h3>
        <table class="history-table">
            <thead>
                <tr>
                    <th>Workout Plan</th>
                    <th>Start Date</th>
                    <th>Completion Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($workout_result && $workout_result->num_rows > 0): ?>
                    <?php while ($w = $workout_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($w['name']); ?></td>
                            <td><?php echo !empty($w['start_date']) ? date('M j, Y', strtotime($w['start_date'])) : '-'; ?></td>
                            <td><?php echo is_null($w['completion_rate']) ? '0' : (int)$w['completion_rate']; ?>%</td>
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
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="4">No workout history found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Use server-generated JSON arrays (safe)
    const bmiLabels = <?php echo json_encode($bmi_labels, JSON_UNESCAPED_UNICODE); ?> || [];
    const bmiValues = <?php echo json_encode($bmi_values, JSON_UNESCAPED_UNICODE); ?> || [];

    const calLabels = <?php echo json_encode($cal_labels, JSON_UNESCAPED_UNICODE); ?> || [];
    const calValues = <?php echo json_encode($cal_values, JSON_UNESCAPED_UNICODE); ?> || [];

    // Create BMI chart only if there's a canvas and data
    const bmiCtx = document.getElementById('bmiChart');
    if (bmiCtx && typeof Chart !== 'undefined') {
        new Chart(bmiCtx, {
            type: 'line',
            data: {
                labels: bmiLabels,
                datasets: [{
                    label: 'BMI',
                    data: bmiValues,
                    borderColor: 'rgba(52,152,219,0.9)',
                    backgroundColor: 'rgba(52,152,219,0.05)',
                    fill: true,
                    tension: 0.15,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }

    // Create Calorie chart
    const calCtx = document.getElementById('calorieChart');
    if (calCtx && typeof Chart !== 'undefined') {
        new Chart(calCtx, {
            type: 'line',
            data: {
                labels: calLabels,
                datasets: [{
                    label: 'Daily Calories',
                    data: calValues,
                    borderColor: 'rgba(46,204,113,0.9)',
                    backgroundColor: 'rgba(46,204,113,0.05)',
                    fill: true,
                    tension: 0.15,
                    pointRadius: 3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: false
                    }
                }
            }
        });
    }
});
</script>

</body>
</html>
