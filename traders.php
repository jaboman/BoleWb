<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

// Access: Traders, Admin, or logged in users looking for traders
$user_role = $_SESSION['role'] ?? null;

// Get search parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$business_type = isset($_GET['business_type']) ? sanitize_input($_GET['business_type']) : '';
$base_kebele = isset($_GET['kebele']) ? sanitize_input($_GET['kebele']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT 
            u.user_id, u.full_name, u.email, u.phone_number, u.kebele_name, u.profile_image,
            t.trader_id, t.business_name, t.business_type, t.business_address, t.rating,
            (SELECT COUNT(*) FROM trader_products tp WHERE tp.trader_id = t.trader_id AND tp.is_available = TRUE) as active_products
          FROM users u 
          JOIN traders t ON u.user_id = t.user_id 
          WHERE u.status = 'Active' AND u.role = 'Trader'";
          
$count_query = "SELECT COUNT(*) as total FROM users u JOIN traders t ON u.user_id = t.user_id WHERE u.status = 'Active' AND u.role = 'Trader'";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (t.business_name LIKE ? OR u.full_name LIKE ? OR t.business_type LIKE ?)";
    $count_query .= " AND (t.business_name LIKE ? OR u.full_name LIKE ? OR t.business_type LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($business_type)) {
    $query .= " AND t.business_type = ?";
    $count_query .= " AND t.business_type = ?";
    $params[] = $business_type;
    $types .= 's';
}

if (!empty($base_kebele)) {
    $query .= " AND u.kebele_name = ?";
    $count_query .= " AND u.kebele_name = ?";
    $params[] = $base_kebele;
    $types .= 's';
}

// Get total count
$stmt = $conn->prepare($count_query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$count_result = $stmt->get_result();
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Add pagination
$query .= " ORDER BY t.rating DESC, t.business_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Get traders
$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$traders = $stmt->get_result();

// Get filter options
$types_query = "SELECT DISTINCT business_type FROM traders WHERE business_type IS NOT NULL ORDER BY business_type";
$types_result = $conn->query($types_query);

$kebeles_query = "SELECT DISTINCT kebele_name FROM users WHERE role = 'Trader' ORDER BY kebele_name";
$kebeles_result = $conn->query($kebeles_query);

$page_title = 'Traders Directory';
include 'includes/header.php';
?>

<section class="page-hero" style="background: linear-gradient(rgba(45, 90, 160, 0.9), rgba(45, 90, 160, 0.9)), url('<?php echo SITE_URL; ?>/assets/images/traders-hero.jpg'); background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center;">
    <div class="container">
        <h1><i class="fas fa-store"></i> Bole Town Traders Directory</h1>
        <p class="lead">Discover local businesses, shops, and service providers in Bole Town.</p>
    </div>
</section>

<div class="container my-5">
    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="search">Search</label>
                    <input type="text" name="search" id="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Business name or type...">
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="business_type">Business Type</label>
                    <select name="business_type" id="business_type" class="form-control">
                        <option value="">All Types</option>
                        <?php while($type = $types_result->fetch_assoc()): ?>
                            <option value="<?php echo $type['business_type']; ?>" <?php echo $business_type == $type['business_type'] ? 'selected' : ''; ?>>
                                <?php echo $type['business_type']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <label for="kebele">Kebele</label>
                    <select name="kebele" id="kebele" class="form-control">
                        <option value="">All Kebeles</option>
                        <?php while($keb = $kebeles_result->fetch_assoc()): ?>
                            <option value="<?php echo $keb['kebele_name']; ?>" <?php echo $base_kebele == $keb['kebele_name'] ? 'selected' : ''; ?>>
                                <?php echo $keb['kebele_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-block">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Traders Grid -->
    <?php if($traders->num_rows > 0): ?>
        <div class="row">
            <?php while($trader = $traders->fetch_assoc()): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100 shadow-sm trader-card hover-lift">
                        <div class="card-header bg-white border-0 text-center pt-4">
                            <img src="<?php echo SITE_URL; ?>/assets/uploads/profile/<?php echo $trader['profile_image'] ?: 'default-store.png'; ?>" 
                                 alt="<?php echo htmlspecialchars($trader['business_name']); ?>" 
                                 class="rounded-circle shadow-sm mb-3" style="width: 80px; height: 80px; object-fit: cover;">
                            <h4 class="mb-0"><?php echo htmlspecialchars($trader['business_name']); ?></h4>
                            <span class="badge badge-info"><?php echo $trader['business_type']; ?></span>
                        </div>
                        <div class="card-body text-center">
                            <p class="text-muted small"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($trader['kebele_name']); ?></p>
                            <div class="rating mb-3">
                                <?php 
                                $rating = floor($trader['rating']);
                                for($i=1; $i<=5; $i++) {
                                    echo $i <= $rating ? '<i class="fas fa-star text-warning"></i>' : '<i class="far fa-star text-warning"></i>';
                                }
                                ?>
                                <small>(<?php echo number_format($trader['rating'], 1); ?>)</small>
                            </div>
                            <p class="card-text truncate-2"><?php echo htmlspecialchars($trader['business_address']); ?></p>
                        </div>
                        <div class="card-footer bg-light border-0 d-flex justify-content-between align-items-center">
                            <span class="text-primary font-weight-bold"><?php echo $trader['active_products']; ?> Products</span>
                            <a href="trader-details.php?id=<?php echo $trader['trader_id']; ?>" class="btn btn-outline-primary btn-sm">View Store</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for($i=1; $i<=$total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&business_type=<?php echo urlencode($business_type); ?>&kebele=<?php echo urlencode($base_kebele); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-store-slash fa-4x text-muted mb-3"></i>
            <h3>No traders found</h3>
            <p>Try adjusting your search or filters.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
