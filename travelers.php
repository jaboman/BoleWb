<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

$page_title = 'Travelers Information';
include 'includes/header.php';
?>

<section class="page-hero" style="background: linear-gradient(rgba(45, 90, 160, 0.8), rgba(45, 90, 160, 0.8)), url('<?php echo SITE_URL; ?>/assets/images/travelers-hero.jpg'); background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center;">
    <div class="container">
        <h1><i class="fas fa-plane"></i> Travelers Guide to Bole Town</h1>
        <p class="lead">Everything you need for a comfortable stay in our community.</p>
    </div>
</section>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8">
            <h2>Welcome to Bole Town!</h2>
            <p>Whether you're here for business or pleasure, Bole Town offers a range of services to make your visit seamless. Our digital portal connects you to transportation, accommodation, and essential local services.</p>
            
            <div class="row mt-4">
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <i class="fas fa-hotel fa-2x text-primary mb-3"></i>
                            <h4>Accommodation</h4>
                            <p>Browse verified hotels and guest houses in various kebeles. Book directly through our platform.</p>
                            <a href="services.php?service=HTL" class="btn btn-link p-0">Find Hotels →</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm border-0">
                        <div class="card-body">
                            <i class="fas fa-bus fa-2x text-primary mb-3"></i>
                            <h4>Transportation</h4>
                            <p>Information on local transport options, schedules, and reliable taxi services.</p>
                            <a href="services.php?service=TRP" class="btn btn-link p-0">View Options →</a>
                        </div>
                    </div>
                </div>
            </div>

            <h3 class="mt-4">Travel Tips</h3>
            <div class="accordion" id="travelTips">
                <div class="card shadow-none border-bottom">
                    <div class="card-header bg-white" id="headingOne">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left font-weight-bold" type="button" data-toggle="collapse" data-target="#collapseOne">
                                <i class="fas fa-info-circle mr-2"></i> Local Customs
                            </button>
                        </h2>
                    </div>
                    <div id="collapseOne" class="collapse show" data-parent="#travelTips">
                        <div class="card-body">
                            Bole Town is a friendly community. Respecting local elders and traditional greetings is highly appreciated.
                        </div>
                    </div>
                </div>
                <div class="card shadow-none border-bottom">
                    <div class="card-header bg-white" id="headingTwo">
                        <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left font-weight-bold collapsed" type="button" data-toggle="collapse" data-target="#collapseTwo">
                                <i class="fas fa-medkit mr-2"></i> Health & Safety
                            </button>
                        </h2>
                    </div>
                    <div id="collapseTwo" class="collapse" data-parent="#travelTips">
                        <div class="card-body">
                            The Bole Town Clinic is available 24/7 for travelers. Ensure you have your Faida ID or passport for registration.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h4>Quick Booking</h4>
                    <p class="small text-muted">Register as a Traveler to book services faster.</p>
                    <a href="<?php echo SITE_URL; ?>/register.php?role=Traveler" class="btn btn-primary btn-block mb-3">Register as Traveler</a>
                    <a href="services.php" class="btn btn-outline-primary btn-block">Explore All Services</a>
                </div>
            </div>
            
            <div class="card shadow-sm bg-primary text-white">
                <div class="card-body text-center">
                    <i class="fas fa-headset fa-3x mb-3"></i>
                    <h4>Help Center</h4>
                    <p>Facing issues? Our support team is here for you 24/7.</p>
                    <a href="contact.php" class="btn btn-light btn-sm">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
