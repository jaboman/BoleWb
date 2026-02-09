<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'About Us';
include 'includes/header.php';
?>

<section class="page-hero" style="background: linear-gradient(rgba(44, 90, 160, 0.8), rgba(44, 90, 160, 0.8)), url('<?php echo SITE_URL; ?>/assets/images/1749759813997.jpg'); background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center;">
    <div class="container">
        <h1>About Bole Town</h1>
        <p class="lead">Building a digitally empowered and connected community.</p>
    </div>
</section>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6">
            <h2>Our Vision</h2>
            <p>To become a leading smart town in Ethiopia, where technology enhances the lives of every citizen, farmer, and trader through efficient digital services and a connected ecosystem.</p>
            
            <h2>Our Mission</h2>
            <p>We strive to provide accessible, transparent, and user-friendly digital solutions for urban management, agricultural support, and local commerce, fostering growth and prosperity for the Bole Town community.</p>
        </div>
        <div class="col-md-6">
            <img src="<?php echo SITE_URL; ?>/assets/images/1749759813997.jpg" alt="Bole Town Hall" class="img-fluid rounded shadow">
        </div>
    </div>

    <hr class="my-5">

    <div class="row text-center mt-5">
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h3>Community First</h3>
                    <p>Designed with the needs of our diverse residents in mind, from lifelong locals to newcomers.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                    <h3>Secure & Reliable</h3>
                    <p>Your data is protected with modern security standards, ensuring safe transactions and privacy.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body">
                    <i class="fas fa-chart-line fa-3x text-primary mb-3"></i>
                    <h3>Growth Driven</h3>
                    <p>Empowering local farmers and businesses through better market access and resource management.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
