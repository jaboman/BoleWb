<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

// Publicly accessible directory, no restrictive role check needed here
// but we still need auth if they want to contact farmers or view details? 
// The user said "farmer taking me to login page", which means they were redirected to login.
// Looking at header.php, the "Farmers" link is public.
// So this check is what causes the redirect if not logged in or not the right role.


$user_id = $_SESSION['user_id'] ?? null;
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'Administrator');

// Get search parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$farm_type = isset($_GET['farm_type']) ? sanitize_input($_GET['farm_type']) : '';
$kebele = isset($_GET['kebele']) ? sanitize_input($_GET['kebele']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT 
            u.user_id, u.full_name, u.email, u.phone_number, u.kebele_name, u.profile_image,
            f.farmer_id, f.farm_size, f.farm_type, f.farm_location, f.production_types, f.farming_history,
            f.experience_years, f.average_yield, f.certification,
            (SELECT COUNT(*) FROM farmer_productions fp WHERE fp.farmer_id = f.farmer_id AND fp.is_available = TRUE) as active_productions
          FROM users u 
          JOIN farmers f ON u.user_id = f.user_id 
          WHERE u.status = 'Active' AND u.role = 'Farmer'";
          
$count_query = "SELECT COUNT(*) as total FROM users u JOIN farmers f ON u.user_id = f.user_id WHERE u.status = 'Active' AND u.role = 'Farmer'";

$params = [];
$types = '';

if (!empty($search)) {
    $query .= " AND (u.full_name LIKE ? OR u.kebele_name LIKE ? OR f.production_types LIKE ?)";
    $count_query .= " AND (u.full_name LIKE ? OR u.kebele_name LIKE ? OR f.production_types LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

if (!empty($farm_type)) {
    $query .= " AND f.farm_type = ?";
    $count_query .= " AND f.farm_type = ?";
    $params[] = $farm_type;
    $types .= 's';
}

if (!empty($kebele)) {
    $query .= " AND u.kebele_name = ?";
    $count_query .= " AND u.kebele_name = ?";
    $params[] = $kebele;
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

// Add pagination to query
$query .= " ORDER BY u.full_name ASC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

// Get farmers
$stmt = $conn->prepare($query);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$farmers = $stmt->get_result();

// Get unique farm types for filter
$farm_types_query = "SELECT DISTINCT farm_type FROM farmers WHERE farm_type IS NOT NULL ORDER BY farm_type";
$farm_types_result = $conn->query($farm_types_query);

// Get unique kebeles for filter
$kebeles_query = "SELECT DISTINCT kebele_name FROM users WHERE kebele_name IS NOT NULL AND role = 'Farmer' ORDER BY kebele_name";
$kebeles_result = $conn->query($kebeles_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmers Directory - Bole Town</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
    <style>
        .farmers-header {
            background: linear-gradient(rgba(76, 175, 80, 0.9), rgba(56, 142, 60, 0.9));
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-bottom: 30px;
        }
        .farmers-header h1 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        .filter-section {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .farmers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .farmer-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .farmer-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .farmer-header {
            position: relative;
            height: 150px;
            background: linear-gradient(135deg, #4CAF50, #2E7D32);
        }
        .farmer-profile {
            position: absolute;
            bottom: -50px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 5px solid white;
            overflow: hidden;
            background: #f5f5f5;
        }
        .farmer-profile img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .farmer-body {
            padding: 60px 20px 20px;
            text-align: center;
        }
        .farmer-name {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .farmer-kebele {
            color: #666;
            margin-bottom: 15px;
        }
        .farmer-details {
            display: flex;
            justify-content: space-around;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #eee;
            border-bottom: 1px solid #eee;
        }
        .detail-item {
            text-align: center;
        }
        .detail-value {
            font-weight: bold;
            color: #2c5aa0;
            font-size: 1.1rem;
        }
        .detail-label {
            font-size: 0.85rem;
            color: #666;
        }
        .farmer-productions {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .productions-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            justify-content: center;
        }
        .production-tag {
            background: #e8f5e9;
            color: #2E7D32;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        .no-farmers {
            text-align: center;
            padding: 50px;
            color: #666;
        }
        .no-farmers i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        .pagination-container {
            text-align: center;
            margin-top: 30px;
        }
        .stats-summary {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 30px 0;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-item {
            text-align: center;
            padding: 10px 20px;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #4CAF50;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <section class="farmers-header">
        <div class="container">
            <h1><i class="fas fa-tractor"></i> Bole Town Farmers Directory</h1>
            <p class="lead">Connect with local farmers and explore agricultural products</p>
        </div>
    </section>
    
    <div class="container">
        <!-- Statistics Summary -->
        <?php
        $total_farmers_query = "SELECT COUNT(*) as total FROM farmers";
        $total_farmers_result = $conn->query($total_farmers_query);
        $total_farmers = $total_farmers_result->fetch_assoc()['total'];
        
        $total_area_query = "SELECT SUM(farm_size) as total_area FROM farmers WHERE farm_size IS NOT NULL";
        $total_area_result = $conn->query($total_area_query);
        $total_area = $total_area_result->fetch_assoc()['total_area'];
        
        $active_productions_query = "SELECT COUNT(*) as total FROM farmer_productions WHERE is_available = TRUE";
        $active_productions_result = $conn->query($active_productions_query);
        $active_productions = $active_productions_result->fetch_assoc()['total'];
        ?>
        
        <div class="stats-summary">
            <div class="stat-item">
                <div class="stat-value"><?php echo $total_farmers; ?></div>
                <div class="stat-label">Registered Farmers</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo number_format($total_area ?: 0, 2); ?> ha</div>
                <div class="stat-label">Total Farm Area</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $active_productions; ?></div>
                <div class="stat-label">Active Productions</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $farm_types_result->num_rows; ?></div>
                <div class="stat-label">Farming Types</div>
            </div>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <h3><i class="fas fa-filter"></i> Filter Farmers</h3>
            <form method="GET" action="" class="filter-form">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search by Name, Kebele or Products</label>
                        <input type="text" id="search" name="search" class="form-control" 
                               value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search farmers...">
                    </div>
                    <div class="filter-group">
                        <label for="farm_type">Farm Type</label>
                        <select id="farm_type" name="farm_type" class="form-control">
                            <option value="">All Types</option>
                            <?php while($type = $farm_types_result->fetch_assoc()): ?>
                                <option value="<?php echo $type['farm_type']; ?>" 
                                    <?php echo $farm_type == $type['farm_type'] ? 'selected' : ''; ?>>
                                    <?php echo $type['farm_type']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="kebele">Kebele</label>
                        <select id="kebele" name="kebele" class="form-control">
                            <option value="">All Kebeles</option>
                            <?php while($keb = $kebeles_result->fetch_assoc()): ?>
                                <option value="<?php echo $keb['kebele_name']; ?>" 
                                    <?php echo $kebele == $keb['kebele_name'] ? 'selected' : ''; ?>>
                                    <?php echo $keb['kebele_name']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a href="farmers.php" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Farmers Grid -->
        <?php if($farmers->num_rows > 0): ?>
            <div class="farmers-grid">
                <?php while($farmer = $farmers->fetch_assoc()): ?>
                    <?php
                    // Parse production types
                    $productions = [];
                    if (!empty($farmer['production_types'])) {
                        if (strpos($farmer['production_types'], '[') === 0) {
                            // JSON format
                            $productions = json_decode($farmer['production_types'], true);
                        } else {
                            // Comma separated
                            $productions = array_map('trim', explode(',', $farmer['production_types']));
                        }
                    }
                    ?>
                    
                    <div class="farmer-card">
                        <div class="farmer-header">
                            <div class="farmer-profile">
                                <img src="<?php echo SITE_URL; ?>/assets/uploads/profile/<?php echo $farmer['profile_image'] ?: 'default-avatar.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($farmer['full_name']); ?>">
                            </div>
                        </div>
                        <div class="farmer-body">
                            <h3 class="farmer-name"><?php echo htmlspecialchars($farmer['full_name']); ?></h3>
                            <p class="farmer-kebele">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($farmer['kebele_name']); ?>
                            </p>
                            
                            <div class="farmer-details">
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo number_format($farmer['farm_size'] ?: 0, 2); ?> ha</div>
                                    <div class="detail-label">Farm Size</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo $farmer['farm_type']; ?></div>
                                    <div class="detail-label">Farm Type</div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-value"><?php echo $farmer['active_productions']; ?></div>
                                    <div class="detail-label">Products</div>
                                </div>
                            </div>
                            
                            <?php if(!empty($productions)): ?>
                                <div class="farmer-productions">
                                    <h5><i class="fas fa-seedling"></i> Main Productions</h5>
                                    <div class="productions-list">
                                        <?php 
                                        $display_productions = array_slice($productions, 0, 3);
                                        foreach($display_productions as $production): ?>
                                            <span class="production-tag"><?php echo htmlspecialchars($production); ?></span>
                                        <?php endforeach; ?>
                                        <?php if(count($productions) > 3): ?>
                                            <span class="production-tag">+<?php echo count($productions) - 3; ?> more</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="farmer-actions mt-3">
                                <a href="farmer-details.php?id=<?php echo $farmer['farmer_id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View Profile
                                </a>
                                <a href="contact-farmer.php?id=<?php echo $farmer['farmer_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-envelope"></i> Contact
                                </a>
                                <?php if($is_admin): ?>
                                    <a href="admin/edit-farmer.php?id=<?php echo $farmer['farmer_id']; ?>" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Pagination -->
            <?php if($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&farm_type=<?php echo urlencode($farm_type); ?>&kebele=<?php echo urlencode($kebele); ?>">
                                        <i class="fas fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php 
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&farm_type=<?php echo urlencode($farm_type); ?>&kebele=<?php echo urlencode($kebele); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&farm_type=<?php echo urlencode($farm_type); ?>&kebele=<?php echo urlencode($kebele); ?>">
                                        Next <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="no-farmers">
                <i class="fas fa-tractor"></i>
                <h3>No farmers found</h3>
                <p>Try adjusting your search filters or check back later.</p>
                <?php if($search || $farm_type || $kebele): ?>
                    <a href="farmers.php" class="btn btn-primary">Clear Filters</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Additional Information -->
        <?php if($_SESSION['role'] === 'Farmer'): ?>
            <div class="alert alert-info mt-4">
                <h4><i class="fas fa-info-circle"></i> For Farmers</h4>
                <p>You can update your farm information and add products in your <a href="my-farm.php">My Farm</a> section.</p>
                <div class="mt-2">
                    <a href="my-farm.php" class="btn btn-outline-info">
                        <i class="fas fa-edit"></i> Manage My Farm
                    </a>
                    <a href="add-production.php" class="btn btn-info">
                        <i class="fas fa-plus"></i> Add Product
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        // Auto-submit form on filter change
        document.getElementById('farm_type').addEventListener('change', function() {
            if(this.value) this.form.submit();
        });
        
        document.getElementById('kebele').addEventListener('change', function() {
            if(this.value) this.form.submit();
        });
        
        // Live search with debounce
        let searchTimeout;
        document.getElementById('search').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });
    </script>
</body>
</html>