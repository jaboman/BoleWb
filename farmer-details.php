<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

if (!isset($_GET['id'])) {
    redirect('farmers.php');
}

$farmer_id = intval($_GET['id']);

// Get farmer details
$query = "SELECT u.*, f.* 
          FROM farmers f 
          JOIN users u ON f.user_id = u.user_id 
          WHERE f.farmer_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

if (!$farmer) {
    $_SESSION['error'] = "Farmer not found.";
    redirect('farmers.php');
}

// Get productions
$productions_query = "SELECT * FROM farmer_productions WHERE farmer_id = ? AND is_available = TRUE";
$stmt = $conn->prepare($productions_query);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$productions = $stmt->get_result();

$page_title = $farmer['full_name'] . ' - Farmer Profile';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm border-0 text-center p-4 mb-4">
                <img src="assets/uploads/profile/<?php echo $farmer['profile_image'] ?: 'default-avatar.png'; ?>" 
                     alt="<?php echo htmlspecialchars($farmer['full_name']); ?>" 
                     class="rounded-circle mx-auto mb-3" 
                     style="width: 150px; height: 150px; object-fit: cover; border: 5px solid #e8f5e9;">
                <h3><?php echo htmlspecialchars($farmer['full_name']); ?></h3>
                <p class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($farmer['kebele_name']); ?></p>
                <hr>
                <div class="d-grid gap-2">
                    <a href="contact-farmer.php?id=<?php echo $farmer['farmer_id']; ?>" class="btn btn-primary btn-block">
                        <i class="fas fa-envelope"></i> Contact Farmer
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm border-0 p-4">
                <h5><i class="fas fa-info-circle text-success"></i> Farm Details</h5>
                <ul class="list-unstyled mt-3">
                    <li class="mb-2"><strong>Farm Size:</strong> <?php echo number_format($farmer['farm_size'], 2); ?> ha</li>
                    <li class="mb-2"><strong>Farm Type:</strong> <?php echo $farmer['farm_type']; ?></li>
                    <li class="mb-2"><strong>Location:</strong> <?php echo htmlspecialchars($farmer['farm_location']); ?></li>
                    <li class="mb-2"><strong>Experience:</strong> <?php echo $farmer['experience_years']; ?> years</li>
                </ul>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card shadow-sm border-0 p-4 mb-4">
                <h4>About the Farm</h4>
                <p class="mt-3"><?php echo nl2br(htmlspecialchars($farmer['farming_history'] ?: 'No history provided.')); ?></p>
                
                <h5 class="mt-4">Major Productions</h5>
                <p><?php echo htmlspecialchars($farmer['production_types']); ?></p>
            </div>
            
            <h4 class="mb-3">Available Products</h4>
            <div class="row">
                <?php if ($productions->num_rows > 0): ?>
                    <?php while ($product = $productions->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                                    <p class="text-success h4">ETB <?php echo number_format($product['price_per_unit'], 2); ?> / <?php echo $product['unit']; ?></p>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                                    <a href="product-details.php?id=<?php echo $product['production_id']; ?>" class="btn btn-sm btn-outline-success border-0 p-0 text-decoration-none">View Details <i class="fas fa-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-light border">No products currently listed for sale.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
