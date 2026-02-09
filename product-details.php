<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    redirect('marketplace.php');
}

$product_id = intval($_GET['id']);

// Get product and farmer details
$query = "SELECT fp.*, f.farmer_id, u.full_name as farmer_name, u.kebele_name, u.phone_number 
          FROM farmer_productions fp 
          JOIN farmers f ON fp.farmer_id = f.farmer_id 
          JOIN users u ON f.user_id = u.user_id 
          WHERE fp.production_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    $_SESSION['error'] = "Product not found.";
    redirect('marketplace.php');
}

$page_title = $product['product_name'] . ' - Product Details';
include 'includes/header.php';
?>

<div class="container my-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="marketplace.php">Marketplace</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['product_name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm border-0 overflow-hidden" style="border-radius: 15px;">
                <?php if ($product['image_url']): ?>
                    <img src="assets/uploads/products/<?php echo $product['image_url']; ?>" class="img-fluid" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="width: 100%; height: 400px; object-fit: cover;">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center bg-light text-muted" style="height: 400px; width: 100%;">
                        <i class="fas fa-image fa-6x"></i>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm border-0 p-4 h-100" style="border-radius: 15px;">
                <div class="mb-2">
                    <span class="badge badge-success"><?php echo $product['product_type']; ?></span>
                    <?php if ($product['is_available']): ?>
                        <span class="badge badge-primary">In Stock</span>
                    <?php else: ?>
                        <span class="badge badge-danger">Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <h1 class="display-5 mb-3"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                
                <div class="price-section mb-4">
                    <span class="h2 text-success">ETB <?php echo number_format($product['price_per_unit'], 2); ?></span>
                    <span class="text-muted"> / <?php echo $product['unit']; ?></span>
                </div>
                
                <div class="description mb-4">
                    <h5>Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($product['description'] ?: 'No description provided.')); ?></p>
                </div>
                
                <div class="farmer-info p-3 bg-light rounded mb-4">
                    <div class="d-flex align-items-center">
                        <div class="farmer-avatar bg-success text-white rounded-circle d-flex align-items-center justify-content-center mr-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-tractor fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Farmer: <a href="farmer-details.php?id=<?php echo $product['farmer_id']; ?>"><?php echo htmlspecialchars($product['farmer_name']); ?></a></h6>
                            <small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($product['kebele_name']); ?></small>
                        </div>
                    </div>
                </div>
                
                <div class="actions">
                    <?php if (is_logged_in()): ?>
                        <a href="contact-farmer.php?id=<?php echo $product['farmer_id']; ?>" class="btn btn-primary btn-lg btn-block mb-3">
                            <i class="fas fa-envelope"></i> Contact Farmer to Buy
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=product-details.php?id=<?php echo $product_id; ?>" class="btn btn-primary btn-lg btn-block mb-3">
                            Login to Contact Farmer
                        </a>
                    <?php endif; ?>
                    <div class="text-center">
                        <small class="text-muted"><i class="fas fa-phone"></i> Member since <?php echo date('M Y'); ?></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
