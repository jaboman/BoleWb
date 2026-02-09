<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

$page_title = 'Visitor Center';
include 'includes/header.php';
?>

<section class="page-hero" style="background: linear-gradient(rgba(45, 90, 160, 0.8), rgba(45, 90, 160, 0.8)), url('<?php echo SITE_URL; ?>/assets/images/visitors-hero.jpg'); background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center;">
    <div class="container">
        <h1><i class="fas fa-map-marked-alt"></i> Visitor Center</h1>
        <p class="lead">Explore the beauty and landmarks of Bole Town.</p>
    </div>
</section>

<div class="container my-5">
    <div class="row text-center mb-5">
        <div class="col-12">
            <h2>Discover Bole Town</h2>
            <p class="lead text-muted">From historical landmarks to modern attractions, see what makes our town special.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <img src="<?php echo SITE_URL; ?>/assets/images/statue.jpg" class="card-img-top" alt="Historical Landmark">
                <div class="card-body">
                    <h3>Historical Landmarks</h3>
                    <p>Visit the sites that shaped our town's rich history and culture.</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Explore Map</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <img src="<?php echo SITE_URL; ?>/assets/images/market.jpg" class="card-img-top" alt="Main Market">
                <div class="card-body">
                    <h3>Public Parks</h3>
                    <p>Relax in our well-maintained green spaces and community parks.</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">Directions</a>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm overflow-hidden h-100">
                <img src="<?php echo SITE_URL; ?>/assets/images/town-hall.jpg" class="card-img-top" alt="Cultural Center">
                <div class="card-body">
                    <h3>Cultural Center</h3>
                    <p>Experience local art, music, and community events year-round.</p>
                    <a href="#" class="btn btn-outline-primary btn-sm">View Schedule</a>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-light p-5 rounded mt-5">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h2>Planning a visit?</h2>
                <p>Register as a Visitor to receive notifications about town events and access exclusive guides.</p>
            </div>
            <div class="col-md-4 text-md-right">
                <a href="<?php echo SITE_URL; ?>/register.php?role=Visitor" class="btn btn-primary btn-lg">Register Now</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
