<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Handle direct service redirection
if (isset($_GET['service'])) {
    $code = strtolower(sanitize_input($_GET['service']));
    if (file_exists("services/$code.php")) {
        header("Location: services/$code.php");
        exit();
    }
}

$page_title = 'Our Services';
include 'includes/header.php';

// Get all active services from database
$services_query = "SELECT * FROM services WHERE is_active = TRUE ORDER BY service_name";
$services_result = $conn->query($services_query);
?>

<section class="page-hero" style="background: linear-gradient(rgba(45, 90, 160, 0.9), rgba(45, 90, 160, 0.9)), url('<?php echo SITE_URL; ?>/assets/images/services-hero.jpg'); background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center;">
    <div class="container">
        <h1><i class="fas fa-concierge-bell"></i> Bole Town Digital Services</h1>
        <p class="lead">Select a service to get started with your application or payment.</p>
    </div>
</section>

<div class="container my-5">
    <div class="row">
        <?php if($services_result->num_rows > 0): ?>
            <?php while($service = $services_result->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm service-card transition-hover">
                        <div class="card-body text-center p-4">
                            <div class="service-icon-wrapper mb-3" style="font-size: 3rem; color: #2c5aa0;">
                                <i class="<?php echo $service['icon_class']; ?>"></i>
                            </div>
                            <h3 class="card-title"><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($service['description']); ?></p>
                        </div>
                        <div class="card-footer bg-white border-0 text-center pb-4">
                            <a href="<?php echo SITE_URL; ?>/services/<?php echo strtolower($service['service_code']); ?>.php" class="btn btn-primary btn-block">
                                Access Service
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-tools fa-4x text-muted mb-3"></i>
                <h3>Services Coming Soon</h3>
                <p>We are currently digitizing our services. Please check back later.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
