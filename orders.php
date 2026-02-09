<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

if ($_SESSION['role'] !== 'Trader') {
    $_SESSION['error'] = "Access denied. Only traders can access this page.";
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Get messages as "orders" for now
$orders_query = "SELECT m.*, u.full_name as customer_name 
                FROM messages m 
                JOIN users u ON m.sender_id = u.user_id 
                WHERE m.receiver_id = ? 
                ORDER BY m.sent_at DESC";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

$page_title = 'Product Orders & Inquiries';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="mb-4">
        <h2><i class="fas fa-shopping-cart text-info"></i> Orders & Inquiries</h2>
        <p class="text-muted">Manage your customer requests and orders directly via messages.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Customer</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($orders->num_rows > 0): ?>
                            <?php while ($row = $orders->fetch_assoc()): ?>
                                <tr class="<?php echo !$row['is_read'] ? 'font-weight-bold bg-light' : ''; ?>">
                                    <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                    <td><?php echo substr(htmlspecialchars($row['message']), 0, 50) . '...'; ?></td>
                                    <td><?php echo time_ago($row['sent_at']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['is_read'] ? 'secondary' : 'primary'; ?>">
                                            <?php echo $row['is_read'] ? 'Read' : 'New'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="messages.php?id=<?php echo $row['message_id']; ?>" class="btn btn-sm btn-outline-info">View</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No orders or inquiries found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
