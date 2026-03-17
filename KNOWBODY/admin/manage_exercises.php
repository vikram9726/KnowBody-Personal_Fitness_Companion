<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

// Upload folder (relative to this file)
$uploadDirRelative = 'uploads/gifs/'; // web path used in <img src=...>
$uploadDir = __DIR__ . '/' . $uploadDirRelative; // filesystem path

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Messages
$errors = [];
$success = '';

// Handle exercise creation (with optional GIF)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_exercise'])) {
        $name = trim($_POST['name'] ?? '');
        $muscle_group = trim($_POST['muscle_group'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($name === '') {
            $errors[] = "Exercise name is required.";
        }

        // Handle GIF upload if provided
        $gifFilenameForDB = null;
        if (isset($_FILES['gif']) && $_FILES['gif']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['gif'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "GIF upload error (code {$file['error']}).";
            } else {
                // MIME type check
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($file['tmp_name']);
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if ($mime !== 'image/gif' || $ext !== 'gif') {
                    $errors[] = "Only GIF files are allowed.";
                }

                // Size limit 3 MB
                $maxBytes = 3 * 1024 * 1024;
                if ($file['size'] > $maxBytes) {
                    $errors[] = "GIF is too large. Max size is 3 MB.";
                }

                // Move file
                if (empty($errors)) {
                    try {
                        $basename = bin2hex(random_bytes(8)); // 16 chars
                    } catch (Exception $e) {
                        $basename = time() . '-' . mt_rand(1000,9999);
                    }
                    $gifFilenameForDB = $basename . '.gif';
                    $destination = $uploadDir . $gifFilenameForDB;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        $errors[] = "Failed to save uploaded GIF.";
                        $gifFilenameForDB = null;
                    } else {
                        @chmod($destination, 0644);
                    }
                }
            }
        }

        // Insert into DB
        if (empty($errors)) {
            if ($gifFilenameForDB === null) {
                $stmt = $mysqli->prepare("INSERT INTO exercises (name, muscle_group, description) VALUES (?, ?, ?)");
                if (!$stmt) $errors[] = "DB prepare failed: " . $mysqli->error;
                else {
                    $stmt->bind_param("sss", $name, $muscle_group, $description);
                    if ($stmt->execute()) {
                        $success = "Exercise added successfully.";
                    } else {
                        $errors[] = "DB insert failed: " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $stmt = $mysqli->prepare("INSERT INTO exercises (name, muscle_group, description, gif) VALUES (?, ?, ?, ?)");
                if (!$stmt) {
                    $errors[] = "DB prepare failed: " . $mysqli->error;
                } else {
                    $stmt->bind_param("ssss", $name, $muscle_group, $description, $gifFilenameForDB);
                    if ($stmt->execute()) {
                        $success = "Exercise with GIF added successfully.";
                    } else {
                        $errors[] = "DB insert failed: " . $stmt->error;
                        // cleanup uploaded file on DB error
                        if ($gifFilenameForDB && file_exists($uploadDir . $gifFilenameForDB)) {
                            unlink($uploadDir . $gifFilenameForDB);
                        }
                    }
                    $stmt->close();
                }
            }
        }

        // Redirect to avoid form re-submission (preserve a brief success via GET param optional)
        if (empty($errors)) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Handle exercise deletion (also delete gif file on disk if present)
if (isset($_POST['delete_exercise'])) {
    $exercise_id = (int)($_POST['exercise_id'] ?? 0);
    if ($exercise_id > 0) {
        // fetch gif filename first
        $stmt = $mysqli->prepare("SELECT gif FROM exercises WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $exercise_id);
            $stmt->execute();
            $stmt->bind_result($gifFilename);
            $stmt->fetch();
            $stmt->close();

            // delete db row
            $del = $mysqli->prepare("DELETE FROM exercises WHERE id = ?");
            if ($del) {
                $del->bind_param("i", $exercise_id);
                if ($del->execute()) {
                    // remove file if exists
                    if (!empty($gifFilename)) {
                        $gifPath = $uploadDir . $gifFilename;
                        if (file_exists($gifPath)) {
                            @unlink($gifPath);
                        }
                    }
                    // optional success message (we won't redirect here)
                } else {
                    $errors[] = "Failed to delete exercise: " . $del->error;
                }
                $del->close();
            } else {
                $errors[] = "Failed to prepare delete query: " . $mysqli->error;
            }
        } else {
            $errors[] = "Failed to fetch exercise GIF: " . $mysqli->error;
        }
    } else {
        $errors[] = "Invalid exercise id.";
    }
}

// Fetch all exercises
$result = $mysqli->query("SELECT * FROM exercises ORDER BY name");
$exercises = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>

<?php include 'admin_header.php'; ?>

<style>
/* Page-specific CSS: modern Add Exercise button + table styling */
.main-content { padding: 20px; background: #f7f9fc; border-radius: 10px; margin: 18px; }
h2 { margin-top: 0; color: #0f172a; }

.admin-form { background: #fff; padding: 14px; border-radius: 10px; box-shadow: 0 8px 30px rgba(15,23,42,0.04); margin-bottom: 18px; }
.admin-form h3 { margin-top: 0; }

.form-group { margin-bottom: 12px; }
.form-group label { display:block; margin-bottom:6px; color:#334155; font-weight:600; }
.form-group input[type="text"],
.form-group select,
.form-group textarea { width:100%; padding:10px; border-radius:8px; border:1px solid #e6eef9; background:#ffffff; font-size:14px; color:#0b1220; }

/* Prominent Add Exercise button */
.submit-button {
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding: 10px 18px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 15px;
    color: white;
    background: linear-gradient(135deg,#06b6d4 0%, #3b82f6 100%);
    border: none;
    cursor: pointer;
    box-shadow: 0 10px 24px rgba(59,130,246,0.18);
    transition: transform .12s ease, box-shadow .12s ease;
}
.submit-button:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(59,130,246,0.22); }
.submit-button:active { transform: translateY(0); }

/* table */
.data-container { background:#fff; padding:14px; border-radius:10px; box-shadow: 0 6px 20px rgba(15,23,42,0.04); }
.data-table { width:100%; border-collapse: collapse; font-size:14px; }
.data-table th, .data-table td { padding:10px 12px; border-bottom: 1px solid #eef2f7; text-align:left; vertical-align: middle; }
.data-table th { background: #fbfdff; color:#0f172a; font-weight:700; }
.data-table img.gif-thumb { width: 110px; height: 70px; object-fit: contain; border-radius:8px; background:#f7fbff; border:1px solid #e8f0fb; padding:6px; }

/* action buttons */
.action-button { padding: 6px 12px; border-radius: 8px; border: none; cursor: pointer; font-weight:600; }
.delete-button { background: linear-gradient(180deg,#ff7a7a,#ff4a4a); color:#fff; box-shadow: 0 6px 18px rgba(255,74,74,0.18); }
.delete-button:hover { transform: translateY(-2px); }
.small-note { font-size:12px; color:#64748b; margin-top:6px; }

.data-table tbody td, .history-table tbody td {
  padding:12px 14px;
  color:#333;
  border-bottom:1px solid rgba(0,0,0,0.04);
  font-size:14px;
}
/* responsive */
@media (max-width:800px){
    .data-table img.gif-thumb { width:80px; height:50px; }
}
</style>

<div class="main-content">
    <h2>Exercise Management</h2>

    <div class="admin-form">
        <h3>Add New Exercise</h3>

        <?php if (!empty($errors)): ?>
            <div style="padding:10px;border-radius:8px;background:#fff1f1;color:#9b2c2c;margin-bottom:12px;">
                <strong>Errors:</strong>
                <ul style="margin:8px 0 0 18px;">
                <?php foreach ($errors as $e): ?>
                    <li><?php echo htmlspecialchars($e); ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="padding:10px;border-radius:8px;background:#ecfdf5;color:#064e3b;margin-bottom:12px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Note: enctype required for file upload -->
        <form method="post" enctype="multipart/form-data" novalidate>
            <div class="form-group">
                <label for="name">Exercise Name:</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="muscle_group">Muscle Group:</label>
                <select id="muscle_group" name="muscle_group" required>
                    <?php 
                    $muscle_groups = ['Chest', 'Back', 'Legs', 'Shoulders', 'Arms', 'Core', 'Full Body'];
                    foreach ($muscle_groups as $group): ?>
                        <option value="<?php echo htmlspecialchars($group); ?>"><?php echo htmlspecialchars($group); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description:</label>
                <textarea id="description" name="description" rows="4" required></textarea>
            </div>

            <div class="form-group">
                <label for="gif">Upload GIF (optional):</label>
                <input type="file" id="gif" name="gif" accept=".gif,image/gif" onchange="previewGIF(this)">
                <div class="small-note">Only .gif allowed. Max size 3 MB. Optional.</div>
            </div>

            <div class="form-group">
                <button type="submit" name="add_exercise" class="submit-button">
                    <!-- emoji icon is simple, you can replace with SVG if you prefer -->
                    ➕ Add Exercise
                </button>
            </div>

            <div class="form-group" style="margin-top:8px;">
                <label>GIF Preview</label>
                <img id="gifPreview" src="" alt="GIF preview" style="display:none; max-width:200px; max-height:140px; border-radius:8px; border:1px solid #e6eef9; padding:6px; background:#fff;">
            </div>
        </form>
    </div>

    <div class="data-container">
        <h3>Exercise List</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Preview</th>
                    <th>Name</th>
                    <th>Muscle Group</th>
                    <th>Description</th>
                    <th style="width:120px">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($exercises)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:18px;">No exercises found.</td></tr>
                <?php else: ?>
                    <?php foreach ($exercises as $exercise): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($exercise['id']); ?></td>
                            <td>
                                <?php if (!empty($exercise['gif']) && file_exists($uploadDir . $exercise['gif'])): ?>
                                    <img class="gif-thumb" src="<?php echo htmlspecialchars($uploadDirRelative . $exercise['gif']); ?>" alt="GIF">
                                <?php else: ?>
                                    <div style="width:110px;height:70px;display:flex;align-items:center;justify-content:center;background:#fbfdff;border-radius:8px;border:1px dashed #e8f0fb;color:#94a3b8;">No GIF</div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($exercise['name']); ?></td>
                            <td><?php echo htmlspecialchars($exercise['muscle_group']); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($exercise['description'])); ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this exercise?');">
                                    <input type="hidden" name="exercise_id" value="<?php echo (int)$exercise['id']; ?>">
                                    <button type="submit" name="delete_exercise" class="action-button delete-button">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function previewGIF(input) {
    var preview = document.getElementById('gifPreview');
    if (input.files && input.files[0]) {
        var file = input.files[0];
        if (file.type !== 'image/gif' && !file.name.toLowerCase().endsWith('.gif')) {
            alert('Only GIF files are allowed for preview.');
            input.value = '';
            preview.style.display = 'none';
            preview.src = '';
            return;
        }
        if (file.size > 3 * 1024 * 1024) {
            alert('GIF too large. Max size is 3 MB.');
            input.value = '';
            preview.style.display = 'none';
            preview.src = '';
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    } else {
        preview.src = '';
        preview.style.display = 'none';
    }
}
</script>

</body>
</html>
