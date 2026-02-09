<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    redirect('traders.php');
}

$trader_id = intval($_GET['id']);

// Get trader details
$query = "SELECT u.*, t.* 
          FROM traders t 
          JOIN users u ON t.user_id = u.user_id 
          WHERE t.trader_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $trader_id);
$stmt->execute();
$trader = $stmt->get_result()->fetch_assoc();

if (!$trader) {
    $_SESSION['error'] = "Trader not found.";
    redirect('traders.php');
}

// Get products
$products_query = "SELECT * FROM trader_products WHERE trader_id = ? AND is_available = TRUE";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $trader_id);
$stmt->execute();
$products = $stmt->get_result();

$page_title = $trader['business_name'] . ' - Store Details';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center p-4 mb-4">
                <img src="assets/uploads/profile/<?php echo $trader['profile_image'] ?: 'default-store.png'; ?>" 
                     alt="<?php echo htmlspecialchars($trader['business_name']); ?>" 
                     class="rounded-circle mx-auto mb-3" 
                     style="width: 150px; height: 150px; object-fit: cover; border: 5px solid #e3f2fd;">
                <h3><?php echo htmlspecialchars($trader['business_name']); ?></h3>
                <p class="badge badge-info"><?php echo $trader['business_type']; ?></p>
                <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($trader['kebele_name']); ?></p>
                <div class="rating mb-3">
                    <?php 
                    $rating = floor($trader['rating']);
                    for($i=1; $i<=5; $i++) {
                        echo $i <= $rating ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
                    }
                    ?>
                    <small>(<?php echo number_format($trader['rating'], 1); ?>)</small>
                </div>
                <hr>
                <div class="d-grid gap-2">
                    <a href="contact-trader.php?id=<?php echo $trader['trader_id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-envelope"></i> Contact Trader
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 p-4">
                <h5><i class="fas fa-info-circle text-primary"></i> Business Info</h5>
                <ul class="list-unstyled mt-3">
                    <li class="mb-2"><strong>Type:</strong> <?php echo $trader['business_type']; ?></li>
                    <li class="mb-2"><strong>Years:</strong> <?php echo $trader['years_in_business']; ?> years</li>
                    <li class="mb-2"><strong>Address:</strong> <?php echo htmlspecialchars($trader['business_address']); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm border-0 p-4 mb-4">
                <h4>About <?php echo htmlspecialchars($trader['business_name']); ?></h4>
                <p class="mt-3"><?php echo nl2br(htmlspecialchars($trader['business_history'] ?: 'No history provided.')); ?></p>
                
                <h5 class="mt-4">Products Handled</h5>
                <p><?php echo htmlspecialchars($trader['products_handled'] ?: 'General merchandise'); ?></p>
            </div>
            
            <h4 class="mb-3">Available Products</h4>
            <div class="row">
                <?php if ($products->num_rows > 0): ?>
                    <?php while ($product = $products->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                    <p class="text-primary h4">ETB <?php echo number_format($product['price'], 2); ?> / <?php echo $product['unit']; ?></p>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <span class="badge badge-light"><?php echo $product['category']; ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-light border">No specific products currently listed.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
