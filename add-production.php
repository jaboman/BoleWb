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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = sanitize_input($_POST['product_name']);
    $product_type = sanitize_input($_POST['product_type']);
    $quantity = floatval($_POST['quantity']);
    $unit = sanitize_input($_POST['unit']);
    $price = floatval($_POST['price']);
    $description = sanitize_input($_POST['description']);
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    // Simple image upload (if any)
    $image_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = upload_file($_FILES['image'], 'products');
        if ($upload['success']) {
            $image_url = $upload['file_name'];
        }
    }

    $insert_query = "INSERT INTO farmer_productions (farmer_id, product_name, product_type, available_quantity, unit, price_per_unit, description, image_url, is_available) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issssdssi", $farmer_id, $product_name, $product_type, $quantity, $unit, $price, $description, $image_url, $is_available);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Product added successfully!";
        redirect('my-productions.php');
    } else {
        $error = "Error adding product: " . $conn->error;
    }
}

$page_title = 'Add New Production';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Product</h3>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="form-group mb-3">
                                    <label for="product_name">Product Name *</label>
                                    <input type="text" name="product_name" id="product_name" class="form-control" placeholder="e.g. Wheat, Tomato, Honey" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="product_type">Product Type *</label>
                                    <select name="product_type" id="product_type" class="form-control" required>
                                        <option value="Crop">Crop</option>
                                        <option value="Livestock">Livestock</option>
                                        <option value="Dairy">Dairy</option>
                                        <option value="Poultry">Poultry</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="quantity">Available Quantity *</label>
                                    <input type="number" step="0.01" name="quantity" id="quantity" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="unit">Unit *</label>
                                    <input type="text" name="unit" id="unit" class="form-control" placeholder="e.g. kg, liters, quintal" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group mb-3">
                                    <label for="price">Price Per Unit (ETB) *</label>
                                    <input type="number" step="0.01" name="price" id="price" class="form-control" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="description">Description (Optional)</label>
                            <textarea name="description" id="description" class="form-control" rows="3" placeholder="Tell buyers more about your product quality, harvest date, etc."></textarea>
                        </div>

                        <div class="form-group mb-3">
                            <label for="image">Product Image (Optional)</label>
                            <input type="file" name="image" id="image" class="form-control-file">
                            <small class="text-muted">JPG, PNG or GIF. Max 5MB.</small>
                        </div>

                        <div class="form-group mb-4">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="is_available" name="is_available" checked>
                                <label class="custom-control-label" for="is_available">Available for sale immediately</label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="my-productions.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success px-4">
                                <i class="fas fa-save"></i> Save Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
