<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

// Handle user deletion
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

// Fetch all users
$result = $mysqli->query("SELECT id, username FROM users ORDER BY id DESC");
$users = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php include 'admin_header.php'; ?>

        <div class="main-content">
            <h2>User Management</h2>
            
            <div class="data-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                    
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td>
                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" class="action-button delete-button">Delete</button>
                                </form>

                    
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        </div>
    </div>
    <?php include '../feature/footer.php'; ?>
</body>
</html>