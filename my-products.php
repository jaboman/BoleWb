<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

if ($_SESSION['role'] !== 'Trader') {
    $_SESSION['error'] = "Access denied. Only traders can access this page.";
    redirect('dashboard.php');
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get trader_id
$trader_query = "SELECT trader_id FROM traders WHERE user_id = ?";
$stmt = $conn->prepare($trader_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$trader = $stmt->get_result()->fetch_assoc();

if (!$trader) {
    $_SESSION['error'] = "Trader profile not found.";
    redirect('dashboard.php');
}

$trader_id = $trader['trader_id'];

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = sanitize_input($_POST['product_name']);
    $category = sanitize_input($_POST['category']);
    $description = sanitize_input($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $unit = sanitize_input($_POST['unit']);
    
    $insert_query = "INSERT INTO trader_products (trader_id, product_name, category, description, price, quantity_available, unit) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isssdis", $trader_id, $name, $category, $description, $price, $quantity, $unit);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product added successfully!";
        redirect('my-products.php');
    } else {
        $error = "Error adding product: " . $conn->error;
    }
}

// Get products
$products_query = "SELECT * FROM trader_products WHERE trader_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($products_query);
$stmt->bind_param("i", $trader_id);
$stmt->execute();
$products = $stmt->get_result();

$page_title = 'My Products';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box text-primary"></i> My Products</h2>
        <?php if ($action !== 'add'): ?>
            <a href="my-products.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Add New Product</h4>
            </div>
            <div class="card-body">
                <form action="" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Product Name *</label>
                            <input type="text" name="product_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Category</label>
                            <select name="category" class="form-control">
                                <option value="Electronics">Electronics</option>
                                <option value="Clothing">Clothing</option>
                                <option value="Food & Beverage">Food & Beverage</option>
                                <option value="Home & Garden">Home & Garden</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Price (ETB) *</label>
                            <input type="number" name="price" step="0.01" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Quantity Available *</label>
                            <input type="number" name="quantity" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Unit (e.g., kg, pcs) *</label>
                            <input type="text" name="unit" class="form-control" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="my-products.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" name="add_product" class="btn btn-primary px-5">Save Product</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Product</th>
                            <th>Category</th>
                            <th>Stock</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($products->num_rows > 0): ?>
                            <?php while ($row = $products->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['product_name']); ?></strong></td>
                                    <td><?php echo $row['category']; ?></td>
                                    <td><?php echo $row['quantity_available'] . ' ' . $row['unit']; ?></td>
                                    <td>ETB <?php echo number_format($row['price'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo $row['is_available'] ? 'success' : 'secondary'; ?>">
                                            <?php echo $row['is_available'] ? 'Active' : 'Hidden'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-outline-info"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">No products found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
