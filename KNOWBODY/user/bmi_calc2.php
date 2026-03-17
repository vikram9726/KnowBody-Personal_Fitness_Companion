<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}
$user_id = intval($_SESSION['user_id'] ?? 0);
$message = "";
$category_label = "";
$category_class = "";

// Helper function for BMI category
function bmi_category($bmi) {
    if ($bmi < 18.5) return ["Underweight", "warning"];
    if ($bmi < 25) return ["Normal", "success"];
    if ($bmi < 30) return ["Overweight", "warning"];
    return ["Obese", "danger"];
}

// Validate that user exists
$chk = $mysqli->prepare("SELECT id FROM users WHERE id = ?");
$chk->bind_param("i", $user_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows === 0) {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}
$chk->close();

include 'user_header.php';


// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $weight = trim($_POST['weight']);
    $height = trim($_POST['height']);

    if ($weight === '' || $height === '') {
        $message = "Please enter both weight and height.";
    } elseif (!is_numeric($weight) || !is_numeric($height)) {
        $message = "Weight and height must be numeric.";
    } else {
        $weight = floatval($weight);
        $height = floatval($height);

        if ($height > 3) {   // convert cm → m
            $height = $height / 100.0;
        }

        if ($weight > 0 && $height > 0) {
            $bmi = round($weight / ($height * $height), 2);

            list($category_label, $category_class) = bmi_category($bmi);

            // Insert into DB
            $sql = "INSERT INTO user_bmi (`id`, `weight`, `height`, `bmi_value`) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iddd", $user_id, $weight, $height, $bmi);

            if ($stmt->execute()) {
                $message = "BMI Saved Successfully!";
            } else {
                $message = "Error: " . $stmt->error;
            }

            $stmt->close();

        } else {
            $message = "Please enter valid numbers.";
        }
    }
}

// Fetch last 10 BMI entries
$history = [];
$q = $mysqli->prepare("SELECT weight, height, bmi_value, created_at 
                       FROM user_bmi WHERE id = ? ORDER BY created_at DESC LIMIT 4");
$q->bind_param("i", $user_id);
$q->execute();
$res = $q->get_result();
while ($row = $res->fetch_assoc()) {
    $history[] = $row;
}
$q->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>BMI Calculator</title>
    <link rel="stylesheet" href="/mnt/data/style.css">
    <style>
        .container { max-width: 550px; margin: 90px auto; }
        .card { padding: 20px; }
        .table-container table { width:100%; border-collapse: collapse; }
        .table-container th, td { padding: 10px; text-align:left; }
        .success { color: #2ecc71; }
        .warning { color: #130ff1ff; }
        .danger  { color: #e74c3c; }
    </style>
</head>

<body>

<div class="container glass-container card">
    <h2>BMI Calculator</h2>

    <?php if ($message): ?>
        <div><b><?php echo htmlspecialchars($message); ?></b></div>
    <?php endif; ?>

    <form method="post" style="margin-top: 15px;">
        <label style="margin-top: 10px;">Weight (kg):</label>
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        <input type="text" name="weight" required>
<br>
<br>
        <label style="margin-top: 10px;">Height (m or cm):</label>
        <input type="text" name="height" required>
<br>
        <button type="submit" class="btn btn-primary" style="margin-top: 12px;">Calculate</button>
    </form>

    <?php if (!empty($category_label)): ?>
        <p style="margin-top: 20px;">
            <b>BMI Category:</b>
            <span class="<?php echo $category_class; ?>">
                <?php echo $category_label; ?>
            </span>
        </p>
    <?php endif; ?>
</div>

<div class="container glass-container card" style="margin-top: 25px;">
    <h3>Recent BMI Records</h3>

    <?php if (empty($history)): ?>
        <p>No records yet.</p>
    <?php else: ?>
        <div class="table-container">
            <table>
                <tr>
                    <th>Date</th>
                    <th>Weight</th>
                    <th>Height</th>
                    <th>BMI</th>
                </tr>
                <?php foreach ($history as $h): ?>
                    <tr>
                        <td><?php echo date("d M Y H:i", strtotime($h['created_at'])); ?></td>
                        <td><?php echo $h['weight']; ?></td>
                        <td><?php echo $h['height']; ?></td>
                        <td><?php echo $h['bmi_value']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    <?php endif; ?>
</div>

</body>
</html>
