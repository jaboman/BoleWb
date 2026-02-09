<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get all transactions
$trans_query = "SELECT st.*, s.service_name, s.icon_class 
               FROM service_transactions st 
               JOIN services s ON st.service_id = s.service_id 
               WHERE st.user_id = ? 
               ORDER BY st.transaction_date DESC";
$stmt = $conn->prepare($trans_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();

$page_title = 'My Service Transactions';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="mb-4">
        <h2><i class="fas fa-exchange-alt text-primary"></i> My Transactions</h2>
        <p class="text-muted">A complete history of your service payments and requests.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Code</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($transactions->num_rows > 0): ?>
                            <?php while ($row = $transactions->fetch_assoc()): ?>
                                <tr>
                                    <td><code><?php echo $row['transaction_code']; ?></code></td>
                                    <td>
                                        <i class="<?php echo $row['icon_class']; ?> text-muted mr-2"></i>
                                        <?php echo htmlspecialchars($row['service_name']); ?>
                                    </td>
                                    <td>ETB <?php echo number_format($row['amount'], 2); ?></td>
                                    <td><?php echo date('M j, Y h:i A', strtotime($row['transaction_date'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo get_status_badge($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['payment_method'] ?: 'N/A'; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No transactions found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
