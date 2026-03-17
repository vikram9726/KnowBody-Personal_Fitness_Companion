<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}
?>
<?php
$user_id = $_SESSION['user_id'] ?? null;
$message = "";

if ($user_id) {
    $chk = $mysqli->prepare("SELECT id FROM users WHERE id = ?");
    $chk->bind_param("i", $user_id);
    $chk->execute();
    $chk->store_result();
    if ($chk->num_rows === 0) {
        header('Location: ' . BASE_URL . '/feature/login_form.php');
        exit;
    }
    $chk->close();
} else {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

include 'user_header.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user_id) {
    $age = intval($_POST['age']);
    $weight = floatval($_POST['weight']);
    $height = floatval($_POST['height']);
    $gender = $_POST['gender'];

    if ($gender == 'male') {
        $calorie = 66 + (13.7 * $weight) + (5 * $height) - (6.8 * $age);
    } else {
        $calorie = 655 + (9.6 * $weight) + (1.8 * $height) - (4.7 * $age);
    }
    $calorie = round($calorie);

    $stmt = $mysqli->prepare("INSERT INTO user_calorie (id, age, weight, height, gender, calorie) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iiddsi", $user_id, $age, $weight, $height, $gender, $calorie);
        $stmt->execute();
        $stmt->close();
    }

    $message = "Estimated daily calories: $calorie kcal";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Calorie Calculator</title>
    </head>
    <body>

<div class="main-content">
            <h2>Calorie Calculator</h2>
         <form method="post" class="form-container">
            <div class="form-group">
                <label for="age">Age</label>
                <input class="form-control" type="number" id="age" name="age" required>
            </div>
            <div class="form-group">
                <label for="weight">Weight (kg)</label>
                <input class="form-control" type="number" step="any" id="weight" name="weight" required>
            </div>
            <div class="form-group">
                <label for="height">Height (cm)</label>
                <input class="form-control" type="number" step="any" id="height" name="height" required>
            </div>
            <div class="form-group">
                <label for="gender">Gender</label>
                <select class="form-control" id="gender" name="gender" required>
                    <option value="male">Male</option>
                    <option value="female">Female</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">Calculate</button>
        </form>

        <?php if ($message): ?>
            <div class="result-message mt-2 text-center"><?php echo $message; ?></div>
        <?php endif; ?>
    

</div>
   

    </body>
    </html>

