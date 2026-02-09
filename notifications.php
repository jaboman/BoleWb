<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Handle Mark All as Read
if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $_SESSION['success'] = "All notifications marked as read.";
}

// Get notifications
$notifications = get_user_notifications($user_id, 50);

$page_title = 'Notifications';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-bell text-warning"></i> Notifications</h2>
                <?php if ($notifications->num_rows > 0): ?>
                    <form action="" method="POST">
                        <button type="submit" name="mark_all_read" class="btn btn-outline-secondary btn-sm">
                            Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php if ($notifications->num_rows > 0): ?>
                            <?php while ($notif = $notifications->fetch_assoc()): ?>
                                <div class="list-group-item p-4 border-bottom <?php echo $notif['is_read'] ? 'bg-light opacity-75' : 'bg-white'; ?>">
                                    <div class="d-flex w-100 justify-content-between align-items-start">
                                        <div class="d-flex">
                                            <div class="notification-icon mr-3 rounded-circle bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px; min-width: 50px;">
                                                <?php 
                                                $icon_map = [
                                                    'Payment' => 'fas fa-money-bill-wave text-success',
                                                    'Appointment' => 'fas fa-calendar-alt text-primary',
                                                    'Announcement' => 'fas fa-bullhorn text-warning',
                                                    'System' => 'fas fa-info-circle text-info',
                                                    'Alert' => 'fas fa-exclamation-triangle text-danger'
                                                ];
                                                $icon = isset($icon_map[$notif['notification_type']]) ? $icon_map[$notif['notification_type']] : 'fas fa-bell text-secondary';
                                                ?>
                                                <i class="<?php echo $icon; ?> fa-lg"></i>
                                            </div>
                                            <div>
                                                <h5 class="mb-1 <?php echo $notif['is_read'] ? 'font-weight-normal' : 'font-weight-bold'; ?>">
                                                    <?php echo htmlspecialchars($notif['title']); ?>
                                                </h5>
                                                <p class="mb-1 text-muted"><?php echo htmlspecialchars($notif['message']); ?></p>
                                                <small class="text-secondary"><?php echo time_ago($notif['created_at']); ?></small>
                                            </div>
                                        </div>
                                        <?php if (!$notif['is_read']): ?>
                                            <span class="badge badge-primary badge-pill">New</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($notif['link']): ?>
                                        <div class="mt-2 pl-5">
                                            <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="btn btn-sm btn-outline-primary px-3">View Details</a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bell-slash fa-4x text-light mb-4"></i>
                                <h4>No notifications</h4>
                                <p class="text-muted">You're all caught up! New notifications will appear here.</p>
                                <a href="dashboard.php" class="btn btn-primary mt-3">Go to Dashboard</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
