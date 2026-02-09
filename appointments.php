<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get all appointments
$appointments_query = "SELECT a.*, s.service_name, s.icon_class 
                      FROM appointments a 
                      JOIN services s ON a.service_id = s.service_id 
                      WHERE a.user_id = ? 
                      ORDER BY a.appointment_date DESC, a.appointment_time DESC";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

$page_title = 'My Appointments';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="mb-4">
        <h2><i class="fas fa-calendar-alt text-primary"></i> My Appointments</h2>
        <p class="text-muted">Manage your upcoming and past clinic visits or meetings.</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Code</th>
                            <th>Service</th>
                            <th>Date & Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($appointments->num_rows > 0): ?>
                            <?php while ($row = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><code><?php echo $row['appointment_code']; ?></code></td>
                                    <td>
                                        <i class="<?php echo $row['icon_class']; ?> text-muted mr-2"></i>
                                        <?php echo htmlspecialchars($row['service_name']); ?>
                                    </td>
                                    <td>
                                        <?php echo format_date($row['appointment_date'], 'M j, Y'); ?> at 
                                        <?php echo date('h:i A', strtotime($row['appointment_time'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo get_status_badge($row['status']); ?>">
                                            <?php echo $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-times"></i></button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No appointments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
