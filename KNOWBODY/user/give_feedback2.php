<?php
session_start();
include '../config.php';
include '../database/db.php';

if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

// Get user ID from the users table based on the username
$username = $_SESSION['user'];
$user_result = $mysqli->query("SELECT id FROM users WHERE username = '" . $mysqli->real_escape_string($username) . "'");
if ($user_result && $user_result->num_rows > 0) {
    $user_id = $user_result->fetch_assoc()['id'];
    $_SESSION['user_id'] = $user_id; // Store for future use
} else {
    // Handle error - user not found
    header('Location: ' . BASE_URL . '/feature/login_form.php');
    exit;
}

include 'user_header.php';

$success_message = '';
$error_message = '';

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $message = $mysqli->real_escape_string($_POST['message']);
    
    $stmt = $mysqli->prepare("
        INSERT INTO feedback (user_id, message, created_at)
        VALUES (?, ?, CURRENT_TIMESTAMP)
    ");
    $stmt->bind_param("is", $user_id, $message);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        $success_message = "Thank you! Your feedback has been submitted successfully.";
    } else {
        $error_message = "Sorry, there was an error submitting your feedback. Please try again.";
    }
}

// Fetch user's previous feedback with safe query
$previous_feedback = $mysqli->query("
    SELECT message, created_at, status, admin_response
    FROM feedback
    WHERE user_id = " . (int)$user_id . "
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Give Feedback</title>
    </head>
 <style>
        .feedback-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .feedback-form textarea {
            width: 100%;
            min-height: 150px;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: vertical;
        }

        .submit-btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .submit-btn:hover {
            background: #2980b9;
        }

        .feedback-history {
            margin-top: 30px;
        }

        .feedback-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 15px;
        }

        .feedback-meta {
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .admin-response {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-reviewed { background: #e3f2fd; color: #1565c0; }
        .status-addressed { background: #e8f5e9; color: #2e7d32; }
    </style>

    <body>

<div class="main-content">
            <h2>Give Feedback</h2>

             <?php if ($success_message): ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="feedback-form">
            <form method="POST" action="give_feedback2.php">
                <label for="message"><strong>Your Feedback:</strong></label>
                <textarea name="message" id="message" required 
                    placeholder="Share your thoughts, suggestions, or report any issues..."></textarea>
                <button type="submit" name="submit_feedback" class="submit-btn">Submit Feedback</button>
            </form>
        </div>

        <div class="feedback-history">
            <h3>Your Previous Feedback</h3>
            <?php if ($previous_feedback && $previous_feedback->num_rows > 0): ?>
                <?php while ($feedback = $previous_feedback->fetch_assoc()): ?>
                    <div class="feedback-card">
                        <div class="feedback-meta">
                            Submitted on <?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?>
                            <span class="status-badge status-<?php echo strtolower($feedback['status']); ?>">
                                <?php echo $feedback['status']; ?>
                            </span>
                        </div>
                        
                        <div class="feedback-content">
                            <?php echo nl2br(htmlspecialchars($feedback['message'])); ?>
                        </div>

                        <?php if ($feedback['admin_response']): ?>
                            <div class="admin-response">
                                <strong>Admin Response:</strong><br>
                                <?php echo nl2br(htmlspecialchars($feedback['admin_response'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p>You haven't submitted any feedback yet.</p>
            <?php endif; ?>
        </div>
</div>
  <?php include '../feature/footer.php'; ?>
    </body>
    </html> 
