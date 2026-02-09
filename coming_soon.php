<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

$page_title = 'Feature Coming Soon';
include 'includes/header.php';
?>

<div class="container my-5 text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="coming-soon-wrapper">
                <div class="mb-4">
                    <i class="fas fa-tools fa-5x text-primary animate-bounce"></i>
                </div>
                <h1 class="display-4 mb-3">Feature Coming Soon</h1>
                <p class="lead text-muted mb-5">
                    We are working hard to bring this feature to you. The Bole Town digital platform is constantly evolving to serve you better.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="dashboard.php" class="btn btn-primary btn-lg px-5">
                        <i class="fas fa-home mr-2"></i> Back to Dashboard
                    </a>
                    <button onclick="window.history.back()" class="btn btn-outline-secondary btn-lg px-5">
                        <i class="fas fa-arrow-left mr-2"></i> Go Back
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.animate-bounce {
    animation: bounce 2s infinite;
}
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% {transform: translateY(0);}
    40% {transform: translateY(-20px);}
    60% {transform: translateY(-10px);}
}
</style>

<?php include 'includes/footer.php'; ?>
