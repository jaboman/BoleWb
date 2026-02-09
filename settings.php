<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Handle Password Change
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Get current password hash
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (password_verify($current_password, $user['password_hash'])) {
        if ($new_password === $confirm_password) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $new_hash, $user_id);
            if ($update_stmt->execute()) {
                $_SESSION['success'] = "Password changed successfully!";
            } else {
                $error = "Error updating password.";
            }
        } else {
            $error = "New passwords do not match.";
        }
    } else {
        $error = "Current password is incorrect.";
    }
}

// Handle Preference Update
if (isset($_POST['update_preferences'])) {
    // Logic for other settings like notification preferences
    $_SESSION['success'] = "Preferences updated successfully!";
}

$page_title = 'Account Settings';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-lock"></i> Account Security</h5>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="form-group mb-3">
                            <label for="current_password">Current Password</label>
                            <input type="password" name="current_password" id="current_password" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="new_password">New Password</label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                        </div>
                        <div class="form-group mb-4">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                        </div>
                        <button type="submit" name="change_password" class="btn btn-primary">
                            Change Password
                        </button>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> User Preferences</h5>
                </div>
                <div class="card-body p-4">
                    <form action="" method="POST">
                        <div class="form-group mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="email_notif" checked>
                                <label class="custom-control-label" for="email_notif">Email Notifications</label>
                                <small class="text-muted d-block">Receive email alerts for important updates.</small>
                            </div>
                        </div>
                        <div class="form-group mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="sms_notif" checked>
                                <label class="custom-control-label" for="sms_notif">SMS Notifications</label>
                                <small class="text-muted d-block">Receive OTPs and alerts on your phone.</small>
                            </div>
                        </div>
                        <button type="submit" name="update_preferences" class="btn btn-outline-secondary mt-3">
                            Update Preferences
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="mt-5 p-4 bg-light rounded border text-center">
                <h5 class="text-danger">Danger Zone</h5>
                <p class="text-muted">Deleting your account is permanent and cannot be undone.</p>
                <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Contact administrator to delete account.')">
                    Request Account Deletion
                </button>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
