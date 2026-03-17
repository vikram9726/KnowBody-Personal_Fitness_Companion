<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $feedback_id = $_POST['feedback_id'];
    $status = $_POST['status'];
    $admin_response = $_POST['admin_response'] ?? '';
    
    $stmt = $mysqli->prepare("UPDATE feedback SET status = ?, admin_response = ?, reviewed_by = ? WHERE id = ?");
    $stmt->bind_param("ssii", $status, $admin_response, $_SESSION['admin_id'], $feedback_id);
    $stmt->execute();
}

// Fetch all feedback
$feedback_query = "SELECT f.*, u.username 
                  FROM feedback f 
                  LEFT JOIN users u ON f.user_id = u.id 
                  ORDER BY f.created_at DESC";
$feedback = $mysqli->query($feedback_query);
?>

<?php include 'admin_header.php'; ?>

        <div class="main-content">
            <h2>Review User Feedback</h2>

            <div class="feedback-container">
                <?php while ($item = $feedback->fetch_assoc()): ?>
                <div class="feedback-card">
                    <div class="feedback-header">
                        <div class="feedback-meta">
                            <strong>From:</strong> <?php echo htmlspecialchars($item['username'] ?? 'Anonymous'); ?>
                            <br>
                            <strong>Date:</strong> <?php echo date('Y-m-d H:i', strtotime($item['created_at'])); ?>
                        </div>
                        <span class="status-badge status-<?php echo strtolower($item['status']); ?>">
                            <?php echo htmlspecialchars($item['status']); ?>
                        </span>
                    </div>

                    <div class="feedback-content">
                        <?php echo htmlspecialchars($item['message']); ?>
                    </div>

                    <div class="feedback-response">
                        <form method="post">
                            <input type="hidden" name="feedback_id" value="<?php echo $item['id']; ?>">
                            
                            <div class="form-group">
                                <label for="admin_response_<?php echo $item['id']; ?>">Admin Response:</label>
                                <textarea id="admin_response_<?php echo $item['id']; ?>" 
                                        name="admin_response" 
                                        rows="2"><?php echo htmlspecialchars($item['admin_response'] ?? ''); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="status_<?php echo $item['id']; ?>">Update Status:</label>
                                <select id="status_<?php echo $item['id']; ?>" name="status">
                                    <option value="Pending" <?php if($item['status'] == 'Pending') echo 'selected'; ?>>Pending</option>
                                    <option value="Reviewed" <?php if($item['status'] == 'Reviewed') echo 'selected'; ?>>Reviewed</option>
                                </select>
                            </div>

                            <button type="submit" name="update_status" class="submit-button">Update</button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
   
</body>
</html>