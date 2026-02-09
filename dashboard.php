<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Get user data
$user_query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Get notifications
$notif_query = "SELECT * FROM notifications WHERE user_id = ? AND is_read = FALSE ORDER BY created_at DESC LIMIT 10";
$stmt = $conn->prepare($notif_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

// Get recent transactions
$trans_query = "SELECT st.*, s.service_name, s.icon_class 
               FROM service_transactions st 
               JOIN services s ON st.service_id = s.service_id 
               WHERE st.user_id = ? 
               ORDER BY st.transaction_date DESC 
               LIMIT 5";
$stmt = $conn->prepare($trans_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result();

// Get upcoming appointments
$appointments_query = "SELECT a.*, s.service_name, s.icon_class 
                      FROM appointments a 
                      JOIN services s ON a.service_id = s.service_id 
                      WHERE a.user_id = ? 
                      AND a.appointment_date >= CURDATE() 
                      AND a.status IN ('Scheduled', 'Confirmed')
                      ORDER BY a.appointment_date, a.appointment_time 
                      LIMIT 5";
$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get dashboard statistics based on role
switch($role) {
    case 'Farmer':
        $stats_query = "SELECT 
            (SELECT farm_size FROM farmers WHERE user_id = ?) as farm_size,
            (SELECT COUNT(*) FROM farmer_productions fp JOIN farmers f ON fp.farmer_id = f.farmer_id WHERE f.user_id = ?) as total_productions,
            (SELECT COUNT(*) FROM agriculture_requests WHERE user_id = ? AND status = 'Pending') as pending_requests,
            (SELECT COUNT(*) FROM service_transactions WHERE user_id = ? AND status = 'Completed') as completed_transactions";
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("iiii", $user_id, $user_id, $user_id, $user_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        break;
        
    case 'Trader':
        $stats_query = "SELECT 
            t.business_name,
            t.rating,
            (SELECT COUNT(*) FROM trader_products WHERE trader_id = t.trader_id) as total_products,
            (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = FALSE) as unread_messages,
            (SELECT COUNT(*) FROM service_transactions WHERE user_id = ? AND status = 'Completed') as completed_transactions
            FROM traders t WHERE t.user_id = ?";
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        break;
        
    case 'Administrator':
        $stats_query = "SELECT 
            (SELECT COUNT(*) FROM users WHERE status = 'Active') as total_users,
            (SELECT COUNT(*) FROM users WHERE status = 'Pending') as pending_users,
            (SELECT COUNT(*) FROM service_transactions WHERE DATE(transaction_date) = CURDATE()) as today_transactions,
            (SELECT SUM(amount) FROM service_transactions WHERE status = 'Completed' AND DATE(transaction_date) = CURDATE()) as today_revenue,
            (SELECT COUNT(*) FROM notifications WHERE is_read = FALSE) as unread_notifications";
        $stats_result = $conn->query($stats_query);
        $stats = $stats_result->fetch_assoc();
        break;
        
    case 'Visitor':
    case 'Traveler':
        $stats_query = "SELECT 
            (SELECT COUNT(*) FROM bookings WHERE user_id = ?) as total_bookings,
            (SELECT COUNT(*) FROM service_transactions WHERE user_id = ?) as total_transactions,
            (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = FALSE) as unread_messages";
        $stmt = $conn->prepare($stats_query);
        $stmt->bind_param("iii", $user_id, $user_id, $user_id);
        $stmt->execute();
        $stats_result = $stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        break;
}

// Get weather information (simulated)
$weather = [
    'temperature' => rand(20, 30),
    'condition' => ['Sunny', 'Partly Cloudy', 'Cloudy', 'Rainy'][rand(0, 3)],
    'icon' => ['fas fa-sun', 'fas fa-cloud-sun', 'fas fa-cloud', 'fas fa-cloud-rain'][rand(0, 3)]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Bole Town</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="user-profile">
                <div class="profile-image">
                    <img src="<?php echo SITE_URL; ?>/assets/uploads/profile/<?php echo $user['profile_image']; ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>">
                </div>
                <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                <p class="user-role"><?php echo get_role_name($user['role']); ?></p>
                <p class="user-kebele"><?php echo htmlspecialchars($user['kebele_name']); ?></p>
                <div class="profile-status">
                    <span class="status-indicator active"></span>
                    <span>Online</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <a href="<?php echo SITE_URL; ?>/dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                
                <?php if($role === 'Farmer'): ?>
                    <a href="farmers.php"><i class="fas fa-tractor"></i> My Farm</a>
                    <a href="my-productions.php"><i class="fas fa-seedling"></i> My Productions</a>
                    <a href="marketplace.php"><i class="fas fa-store"></i> Marketplace</a>
                <?php elseif($role === 'Trader'): ?>
                    <a href="traders.php"><i class="fas fa-store"></i> My Business</a>
                    <a href="my-products.php"><i class="fas fa-box"></i> My Products</a>
                    <a href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <?php elseif($role === 'Administrator'): ?>
                    <a href="admin/users.php"><i class="fas fa-users-cog"></i> User Management</a>
                    <a href="admin/services.php"><i class="fas fa-cogs"></i> Service Management</a>
                    <a href="admin/reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                    <a href="admin/settings.php"><i class="fas fa-sliders-h"></i> Settings</a>
                <?php endif; ?>
                
                <a href="<?php echo SITE_URL; ?>/services.php"><i class="fas fa-concierge-bell"></i> Services</a>
                <a href="<?php echo SITE_URL; ?>/messages.php"><i class="fas fa-envelope"></i> Messages</a>
            </nav>
            
            <!-- Weather Widget -->
            <div class="weather-widget">
                <div class="weather-icon">
                    <i class="<?php echo $weather['icon']; ?>"></i>
                </div>
                <div class="weather-info">
                    <div class="temperature"><?php echo $weather['temperature']; ?>°C</div>
                    <div class="condition"><?php echo $weather['condition']; ?></div>
                    <div class="location">Bole Town</div>
                </div>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <header class="dashboard-header">
                <div class="header-left">
                    <div class="date-time-compact">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('l, F j, Y'); ?></span>
                        <span class="mx-2">•</span>
                        <i class="fas fa-clock"></i>
                        <span id="currentTime"><?php echo date('h:i A'); ?></span>
                    </div>
                </div>
                <div class="header-right">
                    <div class="notification-bell" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <?php if($notifications->num_rows > 0): ?>
                            <span class="notification-count"><?php echo $notifications->num_rows; ?></span>
                        <?php endif; ?>
                        <div class="notification-dropdown" id="notificationDropdown">
                            <div class="notification-header">
                                <h4>Notifications</h4>
                                <a href="notifications.php">View All</a>
                            </div>
                            <div class="notification-list">
                                <?php if($notifications->num_rows > 0): ?>
                                    <?php while($notification = $notifications->fetch_assoc()): ?>
                                        <div class="notification-item">
                                            <div class="notification-icon">
                                                <?php 
                                                $icon_map = [
                                                    'Payment' => 'fas fa-money-bill-wave',
                                                    'Appointment' => 'fas fa-calendar-alt',
                                                    'Announcement' => 'fas fa-bullhorn',
                                                    'System' => 'fas fa-info-circle',
                                                    'Alert' => 'fas fa-exclamation-triangle'
                                                ];
                                                $icon = isset($icon_map[$notification['notification_type']]) ? $icon_map[$notification['notification_type']] : 'fas fa-info-circle';
                                                ?>
                                                <i class="<?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="notification-content">
                                                <h5><?php echo htmlspecialchars($notification['title']); ?></h5>
                                                <p><?php echo htmlspecialchars($notification['message']); ?></p>
                                                <small><?php echo time_ago($notification['created_at']); ?></small>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <div class="no-notifications">
                                        <i class="fas fa-bell-slash"></i>
                                        <p>No new notifications</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="user-actions">
                        <div class="dropdown">
                            <button class="btn btn-user dropdown-toggle" type="button" id="userMenu" data-toggle="dropdown">
                                <img src="<?php echo SITE_URL; ?>/assets/uploads/profile/<?php echo $user['profile_image'] ?: 'default-avatar.png'; ?>" alt="User">
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php"><i class="fas fa-user"></i> My Profile</a>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/settings.php"><i class="fas fa-cog"></i> Settings</a>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/messages.php"><i class="fas fa-envelope"></i> Messages</a>
                                <div class="dropdown-divider"></div>
                                <?php if($role === 'Administrator'): ?>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/"><i class="fas fa-shield-alt"></i> Admin Panel</a>
                                    <div class="dropdown-divider"></div>
                                <?php endif; ?>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Section -->
                <div class="welcome-section">
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h1><?php echo get_greeting(); ?>, <?php echo htmlspecialchars(explode(' ', $user['full_name'])[0]); ?>!</h1>
                            <p>Everything is looking good today. You have <strong><?php echo $notifications->num_rows; ?></strong> new notifications and <strong><?php echo $appointments->num_rows; ?></strong> upcoming appointments.</p>
                            
                            <div class="welcome-meta">
                                <div class="meta-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($user['kebele_name']); ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-shield-alt"></i>
                                    <span><?php echo $user['faida_id'] ?: 'Bole ID: verified'; ?></span>
                                </div>
                                <div class="meta-item">
                                    <i class="fas fa-user-tag"></i>
                                    <span><?php echo get_role_name($user['role']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <?php if($role === 'Farmer'): ?>
                        <div class="stat-card stat-primary">
                            <div class="stat-icon">
                                <i class="fas fa-tractor"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['farm_size'] ? number_format($stats['farm_size'], 2) : '0'; ?> ha</h3>
                                <p>Farm Size</p>
                            </div>
                        </div>
                        <div class="stat-card stat-success">
                            <div class="stat-icon">
                                <i class="fas fa-seedling"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_productions']; ?></h3>
                                <p>Active Productions</p>
                            </div>
                        </div>
                        <div class="stat-card stat-warning">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_requests']; ?></h3>
                                <p>Pending Requests</p>
                            </div>
                        </div>
                        <div class="stat-card stat-info">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['completed_transactions']; ?></h3>
                                <p>Completed Services</p>
                            </div>
                        </div>
                    <?php elseif($role === 'Trader'): ?>
                        <div class="stat-card stat-primary">
                            <div class="stat-icon">
                                <i class="fas fa-store"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo htmlspecialchars($stats['business_name']); ?></h3>
                                <p>Business</p>
                            </div>
                        </div>
                        <div class="stat-card stat-success">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['rating'], 1); ?>/5</h3>
                                <p>Rating</p>
                            </div>
                        </div>
                        <div class="stat-card stat-warning">
                            <div class="stat-icon">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_products']; ?></h3>
                                <p>Products Listed</p>
                            </div>
                        </div>
                        <div class="stat-card stat-info">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['unread_messages']; ?></h3>
                                <p>Unread Messages</p>
                            </div>
                        </div>
                    <?php elseif($role === 'Administrator'): ?>
                        <div class="stat-card stat-primary">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_users']; ?></h3>
                                <p>Active Users</p>
                            </div>
                        </div>
                        <div class="stat-card stat-success">
                            <div class="stat-icon">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-info">
                                <h3>ETB <?php echo number_format($stats['today_revenue'] ?: 0, 2); ?></h3>
                                <p>Today's Revenue</p>
                            </div>
                        </div>
                        <div class="stat-card stat-warning">
                            <div class="stat-icon">
                                <i class="fas fa-user-clock"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['pending_users']; ?></h3>
                                <p>Pending Users</p>
                            </div>
                        </div>
                        <div class="stat-card stat-danger">
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['unread_notifications']; ?></h3>
                                <p>Unread Notifications</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="stat-card stat-primary">
                            <div class="stat-icon">
                                <i class="fas fa-hotel"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_bookings'] ?: 0; ?></h3>
                                <p>Total Bookings</p>
                            </div>
                        </div>
                        <div class="stat-card stat-success">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['total_transactions'] ?: 0; ?></h3>
                                <p>Transactions</p>
                            </div>
                        </div>
                        <div class="stat-card stat-warning">
                            <div class="stat-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $stats['unread_messages'] ?: 0; ?></h3>
                                <p>Unread Messages</p>
                            </div>
                        </div>
                        <div class="stat-card stat-info">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo $appointments->num_rows; ?></h3>
                                <p>Upcoming Appointments</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Actions -->
                <div class="quick-actions-section">
                    <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                    <div class="quick-actions-grid">
                        <?php if($role === 'Farmer'): ?>
                            <a href="services/agr.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-seedling"></i>
                                </div>
                                <span>Order Fertilizer</span>
                            </a>
                            <a href="services/wtr.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-tint"></i>
                                </div>
                                <span>Pay Water Bill</span>
                            </a>
                            <a href="marketplace.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-store"></i>
                                </div>
                                <span>Sell Products</span>
                            </a>
                            <a href="services/cln.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-hospital"></i>
                                </div>
                                <span>Book Clinic</span>
                            </a>
                        <?php elseif($role === 'Trader'): ?>
                            <a href="my-products.php?action=add" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <span>Add Product</span>
                            </a>
                            <a href="orders.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <span>View Orders</span>
                            </a>
                            <a href="services/tax.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-file-invoice-dollar"></i>
                                </div>
                                <span>Pay Tax</span>
                            </a>
                            <a href="messages.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <span>Messages</span>
                            </a>
                        <?php elseif($role === 'Administrator'): ?>
                            <a href="admin/users.php?action=add" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-user-plus"></i>
                                </div>
                                <span>Add User</span>
                            </a>
                            <a href="admin/reports.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <span>View Reports</span>
                            </a>
                            <a href="admin/announcements.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-bullhorn"></i>
                                </div>
                                <span>Send Announcement</span>
                            </a>
                            <a href="admin/settings.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-cogs"></i>
                                </div>
                                <span>System Settings</span>
                            </a>
                        <?php else: ?>
                            <a href="services/htl.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-hotel"></i>
                                </div>
                                <span>Book Hotel</span>
                            </a>
                            <a href="services/cln.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-hospital"></i>
                                </div>
                                <span>Health Services</span>
                            </a>
                            <a href="travel-guide.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-map-marked-alt"></i>
                                </div>
                                <span>Travel Guide</span>
                            </a>
                            <a href="contact.php" class="quick-action">
                                <div class="action-icon">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <span>Contact Support</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dashboard-row">
                    <!-- Recent Transactions -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-exchange-alt"></i> Recent Transactions</h3>
                            <a href="transactions.php" class="btn-view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if($transactions->num_rows > 0): ?>
                                <div class="transactions-list">
                                    <?php while($transaction = $transactions->fetch_assoc()): ?>
                                        <div class="transaction-item">
                                            <div class="transaction-icon">
                                                <i class="<?php echo $transaction['icon_class']; ?>"></i>
                                            </div>
                                            <div class="transaction-details">
                                                <h5><?php echo htmlspecialchars($transaction['service_name']); ?></h5>
                                                <p><?php echo format_date($transaction['transaction_date'], 'M j, Y'); ?></p>
                                            </div>
                                            <div class="transaction-amount">
                                                <span class="amount">ETB <?php echo number_format($transaction['amount'], 2); ?></span>
                                                <span class="status <?php echo strtolower($transaction['status']); ?>">
                                                    <?php echo $transaction['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-exchange-alt"></i>
                                    <p>No recent transactions</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Upcoming Appointments -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h3><i class="fas fa-calendar-alt"></i> Upcoming Appointments</h3>
                            <a href="appointments.php" class="btn-view-all">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if($appointments->num_rows > 0): ?>
                                <div class="appointments-list">
                                    <?php while($appointment = $appointments->fetch_assoc()): ?>
                                        <div class="appointment-item">
                                            <div class="appointment-icon">
                                                <i class="<?php echo $appointment['icon_class']; ?>"></i>
                                            </div>
                                            <div class="appointment-details">
                                                <h5><?php echo htmlspecialchars($appointment['service_name']); ?></h5>
                                                <p><?php echo format_date($appointment['appointment_date'], 'M j, Y'); ?> at <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?></p>
                                                <?php if(!empty($appointment['purpose'])): ?>
                                                    <small><?php echo htmlspecialchars($appointment['purpose']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                            <div class="appointment-actions">
                                                <span class="badge badge-<?php echo get_status_badge($appointment['status']); ?>">
                                                    <?php echo $appointment['status']; ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <p>No upcoming appointments</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Charts & Analytics (For Admin and Farmers) -->
                <?php if($role === 'Administrator' || $role === 'Farmer'): ?>
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Analytics</h3>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="analyticsChart"></canvas>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Recent Activities -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Recent Activities</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        $activity_query = "SELECT * FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";
                        $stmt = $conn->prepare($activity_query);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $activities = $stmt->get_result();
                        ?>
                        
                        <?php if($activities->num_rows > 0): ?>
                            <div class="activities-timeline">
                                <?php while($activity = $activities->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-icon">
                                            <i class="fas fa-circle"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <p><?php echo htmlspecialchars($activity['description']); ?></p>
                                            <small><?php echo time_ago($activity['created_at']); ?> • <?php echo $activity['activity_type']; ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-history"></i>
                                <p>No recent activities</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="js/dashboard.js"></script>
    <script>
        // Update current time
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            document.getElementById('currentTime').textContent = timeString;
        }
        setInterval(updateTime, 1000);
        
        /* Dropdown handled by dashboard.js */
        
        // Chart initialization
        <?php if($role === 'Administrator' || $role === 'Farmer'): ?>
        const ctx = document.getElementById('analyticsChart').getContext('2d');
        const analyticsChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Services Used',
                    data: [12, 19, 3, 5, 2, 3],
                    borderColor: '#2c5aa0',
                    backgroundColor: 'rgba(44, 90, 160, 0.1)',
                    borderWidth: 2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        <?php endif; ?>
        
        // Mark notifications as read
        document.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function() {
                const notificationId = this.dataset.id;
                if (notificationId) {
                    fetch('ajax/mark_notification_read.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ notification_id: notificationId })
                    });
                }
            });
        });
    </script>
</body>
</html>

<?php
// Helper functions for dashboard
function get_greeting() {
    $hour = date('H');
    if ($hour < 12) return 'Good morning';
    if ($hour < 18) return 'Good afternoon';
    return 'Good evening';
}

function get_status_badge($status) {
    $badge_map = [
        'Completed' => 'success',
        'Pending' => 'warning',
        'Scheduled' => 'info',
        'Confirmed' => 'primary',
        'Cancelled' => 'danger',
        'Failed' => 'danger'
    ];
    return $badge_map[$status] ?? 'secondary';
}