<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$type = isset($_GET['type']) ? sanitize_input($_GET['type']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT fp.*, u.full_name as farmer_name, u.kebele_name 
          FROM farmer_productions fp 
          JOIN farmers f ON fp.farmer_id = f.farmer_id 
          JOIN users u ON f.user_id = u.user_id 
          WHERE fp.is_available = TRUE";

if (!empty($search)) {
    $query .= " AND (fp.product_name LIKE '%$search%' OR fp.description LIKE '%$search%' OR u.full_name LIKE '%$search%')";
}

if (!empty($type)) {
    $query .= " AND fp.product_type = '$type'";
}

$query .= " ORDER BY fp.created_at DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($query);

$page_title = 'Marketplace';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2><i class="fas fa-store text-primary"></i> Agricultural Marketplace</h2>
            <p class="text-muted">Buy fresh produce directly from local farmers</p>
        </div>
        <div class="col-md-6">
            <form action="" method="GET" class="d-flex">
                <input type="text" name="search" class="form-control mr-2" placeholder="Search products or farmers..." value="<?php echo htmlspecialchars($search); ?>">
                <select name="type" class="form-control mr-2" style="width: 150px;">
                    <option value="">All Types</option>
                    <option value="Crop" <?php echo $type == 'Crop' ? 'selected' : ''; ?>>Crops</option>
                    <option value="Livestock" <?php echo $type == 'Livestock' ? 'selected' : ''; ?>>Livestock</option>
                    <option value="Dairy" <?php echo $type == 'Dairy' ? 'selected' : ''; ?>>Dairy</option>
                    <option value="Poultry" <?php echo $type == 'Poultry' ? 'selected' : ''; ?>>Poultry</option>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>
    </div>

    <div class="row">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="col-md-3 mb-4">
                    <div class="card h-100 shadow-sm border-0 transition-hover">
                        <div style="height: 180px; overflow: hidden; background: #f8f9fa;" class="position-relative">
                            <?php if ($product['image_url']): ?>
                                <img src="assets/uploads/products/<?php echo $product['image_url']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['product_name']); ?>" style="height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                                    <i class="fas fa-image fa-3x"></i>
                                </div>
                            <?php endif; ?>
                            <div class="position-absolute" style="top: 10px; right: 10px;">
                                <span class="badge badge-primary"><?php echo $product['product_type']; ?></span>
                            </div>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h5>
                            <p class="small text-muted mb-2">
                                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($product['farmer_name']); ?><br>
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($product['kebele_name']); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mb-0">
                                <span class="h5 text-success mb-0">ETB <?php echo number_format($product['price_per_unit'], 2); ?></span>
                                <small class="text-muted">per <?php echo $product['unit']; ?></small>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 pt-0">
                            <a href="product-details.php?id=<?php echo $product['production_id']; ?>" class="btn btn-outline-primary btn-block btn-sm">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search fa-4x text-muted mb-3"></i>
                <h3>No products found</h3>
                <p>Try adjusting your search or filters.</p>
                <a href="marketplace.php" class="btn btn-primary">Reset Filters</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.transition-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
    transition: all .3s ease-in-out;
}
</style>

<?php include 'includes/footer.php'; ?>
