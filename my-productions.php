<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

if ($_SESSION['role'] !== 'Farmer') {
    $_SESSION['error'] = "Access denied. Only farmers can access this page.";
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];

// Get farmer_id
$farmer_query = "SELECT farmer_id FROM farmers WHERE user_id = ?";
$stmt = $conn->prepare($farmer_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$farmer_result = $stmt->get_result();
$farmer = $farmer_result->fetch_assoc();

if (!$farmer) {
    $_SESSION['error'] = "Farmer profile not found. Please complete your profile first.";
    redirect('dashboard.php');
}

$farmer_id = $farmer['farmer_id'];

// Get productions
$productions_query = "SELECT * FROM farmer_productions WHERE farmer_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($productions_query);
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$productions = $stmt->get_result();

$page_title = 'My Productions';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-seedling text-success"></i> My Productions</h2>
        <a href="add-production.php" class="btn btn-success">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Product</th>
                            <th>Type</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($productions->num_rows > 0): ?>
                            <?php while ($row = $productions->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($row['image_url']): ?>
                                                <img src="assets/uploads/products/<?php echo $row['image_url']; ?>" alt="" class="rounded mr-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            <?php else: ?>
                                                <div class="bg-light rounded d-flex align-items-center justify-content-center mr-2" style="width: 40px; height: 40px;">
                                                    <i class="fas fa-image text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                            <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo $row['product_type']; ?></td>
                                    <td><?php echo number_format($row['available_quantity'], 2) . ' ' . $row['unit']; ?></td>
                                    <td>ETB <?php echo number_format($row['price_per_unit'], 2); ?></td>
                                    <td>
                                        <?php if ($row['is_available']): ?>
                                            <span class="badge badge-success">Available</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Pending/Sold</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit-production.php?id=<?php echo $row['production_id']; ?>" class="btn btn-outline-info" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete-production.php?id=<?php echo $row['production_id']; ?>" class="btn btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this product?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-box-open fa-3x mb-3"></i>
                                        <p>You haven't listed any productions yet.</p>
                                        <a href="add-production.php" class="btn btn-sm btn-outline-success">List Your First Product</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
