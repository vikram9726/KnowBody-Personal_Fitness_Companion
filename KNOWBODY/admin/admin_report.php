<?php
// admin_reports.php
session_start();
include '../config.php';
include '../database/db.php';

// ensure admin access (adjust role check if your app uses a different role name)
if (!isset($_SESSION['user']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . FEATURE_URL . '/login_form.php');
    exit;
}

// Optional: preselected user via GET
$selected_user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : null;

// ----- Fetch all users for dropdown & mapping ----- //
$limitUsers = 200; // safe cap
$users = [];
try {
    $stmt = $mysqli->prepare("SELECT id, username FROM users ORDER BY username ASC LIMIT ?");
    $stmt->bind_param('i', $limitUsers);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $users[(int)$r['id']] = $r['username'];
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Could not load users: " . $e->getMessage());
}

// ----- Fetch plan assignments (Active only) ----- //
$onlyActive = true;
$planNames = [];         // unique plan names
$userPlans = [];         // user_id => [planName, ...]
try {
    $sql = "
      SELECT u.id AS user_id, u.username, wp.id AS plan_id, wp.name AS plan_name
      FROM users u
      LEFT JOIN user_plans up ON up.user_id = u.id
        " . ($onlyActive ? "AND up.status = 'Active'" : "") . "
      LEFT JOIN workout_plans wp ON wp.id = up.plan_id
      ORDER BY u.username ASC
      LIMIT ?
    ";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $limitUsers);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $uid = (int)$row['user_id'];
        $uname = $row['username'] ?: "User {$uid}";
        $pname = $row['plan_name'];

        if (!isset($userPlans[$uid])) $userPlans[$uid] = [];
        if (!empty($pname)) {
            // collect unique plan names
            if (!in_array($pname, $planNames, true)) $planNames[] = $pname;
            if (!in_array($pname, $userPlans[$uid], true)) $userPlans[$uid][] = $pname;
        }
        // if user list missing username (shouldn't happen), use fetched name
        if (!isset($users[$uid])) $users[$uid] = $uname;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Could not load plan assignments: " . $e->getMessage());
}

// sort plan names for consistent ordering
sort($planNames, SORT_STRING | SORT_FLAG_CASE);

// Build chart structures
$userIds = array_keys($users);
$userLabels = array_values($users);

// datasets: one dataset per plan, data aligned to $userIds
$datasets = [];
function plan_color($i) {
    $h = ($i * 47) % 360;
    return "hsl({$h}deg 70% 55%)";
}
foreach ($planNames as $idx => $pname) {
    $data = [];
    foreach ($userIds as $uid) {
        $data[] = (isset($userPlans[$uid]) && in_array($pname, $userPlans[$uid], true)) ? 1 : 0;
    }
    $datasets[] = [
        'label' => $pname,
        'data' => $data,
        'backgroundColor' => plan_color($idx),
        'stack' => 'plansStack'
    ];
}

// Build table rows for raw mapping
$table_rows = [];
$counter = 1;
foreach ($userIds as $uid) {
    $table_rows[] = [
        'index' => $counter++,
        'user_id' => $uid,
        'username' => $users[$uid],
        'plans' => isset($userPlans[$uid]) ? $userPlans[$uid] : []
    ];
}

// Header include (adjust path if needed)
include 'admin_header.php';
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — User / Plan Reports</title>
<style>
:root{
  --bg-grad: linear-gradient(135deg,#f3e88a,#f49866);
  --card-bg: #ffffff;
  --accent: #2f7be0;
  --shadow: 0 8px 30px rgba(18,25,33,0.07);
}
body{ font-family: Inter, system-ui, Roboto, Arial, sans-serif; margin:0; padding:0; background:var(--bg-grad); color:#222; }
.container{ max-width:1200px; margin:18px auto; padding:18px; }
.header-row { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px; }
.controls { display:flex; gap:10px; align-items:center; }
.select { padding:7px 10px; border-radius:8px; border:1px solid rgba(0,0,0,0.06); background:#fff; min-width:220px; }
.btn { display:inline-block; padding:8px 12px; background:#fff; color:var(--accent); border-radius:8px; border:1px solid rgba(47,123,224,0.12); cursor:pointer; font-weight:600; text-decoration:none; }
.btn:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(47,123,224,0.06); }
.card { background:var(--card-bg); padding:14px; border-radius:12px; box-shadow:var(--shadow); border:1px solid rgba(0,0,0,0.03); }
.chart-area { margin-top:12px; height: min(68vh, 560px); padding:10px; border-radius:10px; background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(255,255,255,0.96)); border:1px solid rgba(0,0,0,0.03); overflow:auto; }
#userPlanChart { width:100% !important; height:100% !important; display:block; }
.legend { margin-top:12px; display:flex; gap:10px; flex-wrap:wrap; }
.legend-item { padding:8px 10px; background: #fbfbfb; border-radius:10px; display:inline-flex; gap:8px; align-items:center; font-size:13px; }
.color-box { width:14px; height:14px; border-radius:3px; display:inline-block; }
.table-wrap { margin-top:16px; }
table { width:100%; border-collapse:collapse; background:#fff; border-radius:8px; overflow:hidden; box-shadow: 0 6px 18px rgba(0,0,0,0.04); }
thead th { position:sticky; top:0; background:#fafafa; padding:12px 14px; text-align:left; font-weight:700; border-bottom:1px solid rgba(0,0,0,0.04); }
tbody td { padding:12px 14px; border-bottom:1px solid rgba(0,0,0,0.04); color:#333; }
tbody tr:nth-child(even){ background:#fbfbfb; } tbody tr:hover { background: #fffde7; }
.action-link { display:inline-block; padding:6px 10px; background:#fff; color:var(--accent); border-radius:8px; border:1px solid rgba(47,123,224,0.12); text-decoration:none; font-weight:600; }
.small-note { color:#555; font-size:13px; margin-top:8px; }
@media(max-width:900px){ .header-row { flex-direction:column; align-items:flex-start; } .select{ min-width:160px; } #userPlanChart { height:420px !important; } }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
<div class="container">
    <div class="header-row">
        <h2 style="margin:0">User Reports & Statistics</h2>

        <div class="controls" role="region" aria-label="select user control">
            <label style="font-weight:600; margin-right:6px;">Select User:</label>
            <select id="userSelect" class="select" aria-label="Select user">
                <option value="">-- Choose user --</option>
                <?php foreach ($users as $uid => $uname): 
                    $sel = ($selected_user_id === (int)$uid) ? ' selected' : '';
                ?>
                    <option value="<?php echo (int)$uid; ?>"<?php echo $sel; ?>><?php echo htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'); ?></option>
                <?php endforeach; ?>
            </select>

            <button id="viewStatsBtn" class="btn" type="button">View stats →</button>
        </div>
    </div>

    <div class="card">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div style="font-weight:600">User → Plan mapping (stacked)</div>
            <div class="small-note">Each horizontal bar represents a user. Colored segments = assigned plan(s).</div>
        </div>

        <div class="chart-area card">
            <?php if (empty($userLabels)): ?>
                <div style="padding:30px; text-align:center; color:#666;">No users available</div>
            <?php else: ?>
                <canvas id="userPlanChart" aria-label="User to plan stacked chart"></canvas>
            <?php endif; ?>
        </div>

        <?php if (!empty($planNames)): ?>
            <div class="legend">
                <?php foreach ($planNames as $i => $p): ?>
                    <div class="legend-item"><span class="color-box" style="background: <?php echo htmlspecialchars(plan_color($i)); ?>;"></span><?php echo htmlspecialchars($p); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="table-wrap">
        <div class="card">
            <h3 style="margin-top:0">Raw mapping</h3>
            <table>
                <thead>
                    <tr><th style="width:50px">#</th><th>Username</th><th>Assigned Plans</th><th style="width:140px">Actions</th></tr>
                </thead>
                <tbody>
                <?php if (empty($table_rows)): ?>
                    <tr><td colspan="4" style="text-align:center; padding:18px;">No users</td></tr>
                <?php else: foreach ($table_rows as $r): ?>
                    <tr>
                        <td><?php echo (int)$r['index']; ?></td>
                        <td><?php echo htmlspecialchars($r['username']); ?></td>
                        <td><?php echo empty($r['plans']) ? '—' : htmlspecialchars(implode(', ', $r['plans'])); ?></td>
                        <td>
                          <a class="action-link" href="user_reports.php?user_id=<?php echo (int)$r['user_id']; ?>">View stats</a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Data from PHP
const userLabels = <?php echo json_encode(array_values($userLabels), JSON_UNESCAPED_UNICODE); ?>;
const rawDatasets = <?php echo json_encode($datasets, JSON_UNESCAPED_UNICODE); ?>;

// Build chart datasets properly for Chart.js
const chartDatasets = rawDatasets.map(d => ({
    label: d.label,
    data: d.data,
    backgroundColor: d.backgroundColor,
    stack: d.stack
}));

if (userLabels.length > 0 && chartDatasets.length > 0) {
    const ctx = document.getElementById('userPlanChart').getContext('2d');
    if (window._userPlanChart instanceof Chart) window._userPlanChart.destroy();

    window._userPlanChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: userLabels,
            datasets: chartDatasets
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(ctx) {
                            const plan = ctx.dataset.label || '';
                            const val = ctx.parsed.x || ctx.raw || 0;
                            if (val === 0) return null;
                            return plan + ': Assigned';
                        }
                    }
                },
                legend: { display: false }
            },
            scales: {
                x: { stacked: true, beginAtZero: true, ticks: { precision: 0, stepSize: 1 }, title: { display:true, text:'Assigned (1 = assigned)'} },
                y: { stacked: true, title: { display:true, text:'Users' }, ticks: { autoSkip:false } }
            },
            elements: { bar: { borderRadius: 6 } },
            animation: { duration: 600 }
        }
    });
}

// View stats button behavior - uses selected user id from dropdown
document.getElementById('viewStatsBtn').addEventListener('click', function() {
    const sel = document.getElementById('userSelect') || document.getElementById('user_id');
    if (!sel) return alert('User select not found.');
    const uid = sel.value && sel.value.trim();
    if (!uid) return alert('Please select a user.');
    const url = 'user_reports.php?user_id=' + encodeURIComponent(uid);
    // navigate in same tab
    window.location.href = url;
});

</script>

<?php include '../feature/footer.php'; ?>
</body>
</html>
