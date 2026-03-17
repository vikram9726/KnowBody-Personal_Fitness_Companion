<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}
include 'user_header.php';

// Fetch all exercises with their details
$query = "SELECT * FROM exercises ORDER BY muscle_group, name";
$result = $mysqli->query($query);
$exercises = [];
while ($row = $result->fetch_assoc()) {
    $exercises[$row['muscle_group']][] = $row;
}

// Helper: resolve GIF file web path by checking common filesystem locations.
// We'll test several likely folders and return the web-accessible relative path for the first match.
function resolve_gif_webpath($gifFilename) {
    if (empty($gifFilename)) return null;

    // List of filesystem => web path candidates (ordered)
    // Adjust these if your uploads live in a different folder.
    $candidates = [
        // If this user page is in /user/ and gifs are in same folder '/user/uploads/gifs/'
        __DIR__ . '/uploads/gifs/'          => 'uploads/gifs/',
        // If uploads are in project root /uploads/gifs/
        __DIR__ . '/../uploads/gifs/'       => '../uploads/gifs/',
        // If admin page stores gifs in /admin/uploads/gifs/
        __DIR__ . '/../admin/uploads/gifs/' => '../admin/uploads/gifs/',
        // If uploads are stored at site root uploads/gifs (from document root)
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/gifs/' => '/uploads/gifs/',
    ];

    foreach ($candidates as $fsPrefix => $webPrefix) {
        // If $fsPrefix is a string path, check the file exists there.
        if (@file_exists($fsPrefix . $gifFilename)) {
            // Normalize web path (remove duplicate slashes)
            $web = $webPrefix . $gifFilename;
            $web = str_replace('//', '/', $web);
            return $web;
        }
    }
    return null;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Workout Plans - KnowBody</title>
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        .main-content { max-width:1100px; margin:18px auto; padding:16px; font-family:system-ui,Segoe UI,Roboto,Arial; }
        h2 { color:#0f172a; margin:6px 0 14px; }
        .filter-section { margin-bottom:14px; display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .filter-section label { font-weight:600; color:#334155; }
        select#muscle-filter { padding:8px 10px; border-radius:8px; border:1px solid #e6eef9; background:#fff; }

        .muscle-group { margin-bottom:22px; }
        .muscle-group-title { font-size:1.15rem; margin:8px 0; color:#0b1220; }

        .exercise-grid {
            display:grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap:14px;
        }
        .exercise-card {
            background:#fff;
            border-radius:10px;
            padding:12px;
            box-shadow: 0 6px 20px rgba(15,23,42,0.04);
            border:1px solid #eef3fb;
            display:flex;
            gap:12px;
            align-items:flex-start;
        }

        .thumb-wrap { min-width:110px; max-width:140px; display:flex; align-items:center; justify-content:center; }
        .thumb { width:120px; height:80px; object-fit:contain; border-radius:8px; border:1px solid #e8f0fb; background:#fbfdff; padding:6px; }
        .no-thumb { width:120px; height:80px; display:flex; align-items:center; justify-content:center; color:#94a3b8; border-radius:8px; border:1px dashed #e8f0fb; background:#fbfdff; padding:6px; font-size:0.9rem; }

        .exercise-body { flex:1; min-width:0; }
        .exercise-name { font-weight:700; color:#0b1220; margin-bottom:6px; }
        .exercise-description { color:#334155; font-size:0.95rem; line-height:1.3; max-height:5.2rem; overflow:hidden; text-overflow:ellipsis; }

        .admin-controls { margin-top:10px; }
        .admin-button { display:inline-block; padding:6px 10px; border-radius:8px; text-decoration:none; font-weight:700; margin-right:8px; }
        .edit-btn { background:#f3f4f6; color:#0f172a; border:1px solid #e6eef9; }
        .delete-btn { background:#fee2e2; color:#7f1d1d; border:1px solid #fecaca; }

        @media (max-width:700px) {
            .exercise-card { flex-direction:column; align-items:stretch; }
            .thumb-wrap { width:100%; display:block; text-align:center; }
            .thumb, .no-thumb { width:80%; height:auto; max-height:160px; margin: 0 auto; }
        }
    </style>
</head>
<body>
<div class="main-content">
    <h2>Exercise Library</h2>

    <div class="filter-section">
        <label for="muscle-filter">Filter by Muscle Group:</label>
        <select id="muscle-filter" onchange="filterExercises(this.value)">
            <option value="all">All Muscle Groups</option>
            <?php foreach (array_keys($exercises) as $muscleGroup): ?>
                <option value="<?php echo htmlspecialchars($muscleGroup); ?>">
                    <?php echo htmlspecialchars($muscleGroup); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <?php foreach ($exercises as $muscleGroup => $muscleExercises): ?>
        <div class="muscle-group" data-muscle-group="<?php echo htmlspecialchars($muscleGroup); ?>">
            <h2 class="muscle-group-title"><?php echo htmlspecialchars($muscleGroup); ?></h2>
            <div class="exercise-grid">
                <?php foreach ($muscleExercises as $exercise): ?>
                    <?php
                        // Try to resolve GIF web path (returns null if not found)
                        $gifWeb = resolve_gif_webpath($exercise['gif'] ?? null);
                    ?>
                    <div class="exercise-card">
                        <div class="thumb-wrap">
                            <?php if ($gifWeb): ?>
                                <!-- show gif thumbnail -->
                                <img class="thumb" src="<?php echo htmlspecialchars($gifWeb); ?>" alt="<?php echo htmlspecialchars($exercise['name']); ?> GIF">
                            <?php else: ?>
                                <div class="no-thumb">No GIF</div>
                            <?php endif; ?>
                        </div>

                        <div class="exercise-body">
                            <div class="exercise-name"><?php echo htmlspecialchars($exercise['name']); ?></div>
                            <div class="exercise-description"><?php echo nl2br(htmlspecialchars($exercise['description'])); ?></div>

                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <div class="admin-controls">
                                    <a href="<?php echo BASE_URL; ?>/admin/manage_exercises.php?edit=<?php echo $exercise['id']; ?>"
                                       class="admin-button edit-btn">Edit</a>
                                    <a href="<?php echo BASE_URL; ?>/admin/manage_exercises.php?delete=<?php echo $exercise['id']; ?>"
                                       class="admin-button delete-btn" onclick="return confirm('Are you sure you want to delete this exercise?')">Delete</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<script>
function filterExercises(muscleGroup) {
    const groups = document.querySelectorAll('.muscle-group');
    groups.forEach(group => {
        if (muscleGroup === 'all' || group.dataset.muscleGroup === muscleGroup) {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });
}
</script>

<?php include '../feature/footer.php'; ?>
</body>
</html>
