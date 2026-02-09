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

// Get current farmer details
$query = "SELECT * FROM farmers WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();

// If farmer profile doesn't exist, we might need to create one (this shouldn't happen if they are a Farmer role)
if (!$farmer) {
    // Initial insert
    $conn->query("INSERT INTO farmers (user_id) VALUES ($user_id)");
    $stmt->execute();
    $farmer = $stmt->get_result()->fetch_assoc();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farm_size = floatval($_POST['farm_size']);
    $farm_type = sanitize_input($_POST['farm_type']);
    $farm_location = sanitize_input($_POST['farm_location']);
    $production_types = sanitize_input($_POST['production_types']);
    $experience_years = intval($_POST['experience_years']);
    $farming_history = sanitize_input($_POST['farming_history']);

    $update_query = "UPDATE farmers SET 
                    farm_size = ?, 
                    farm_type = ?, 
                    farm_location = ?, 
                    production_types = ?, 
                    experience_years = ?, 
                    farming_history = ? 
                    WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("dsssisi", $farm_size, $farm_type, $farm_location, $production_types, $experience_years, $farming_history, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Farm profile updated successfully!";
        // Refresh data
        $stmt_refresh = $conn->prepare($query);
        $stmt_refresh->bind_param("i", $user_id);
        $stmt_refresh->execute();
        $farmer = $stmt_refresh->get_result()->fetch_assoc();
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

$page_title = 'My Farm Profile';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0"><i class="fas fa-tractor"></i> My Farm Profile</h3>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="farm_size">Farm Size (in Hectares)</label>
                                    <input type="number" step="0.01" name="farm_size" id="farm_size" class="form-control" value="<?php echo $farmer['farm_size']; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="farm_type">Farm Type</label>
                                    <select name="farm_type" id="farm_type" class="form-control">
                                        <option value="Irrigation" <?php echo $farmer['farm_type'] == 'Irrigation' ? 'selected' : ''; ?>>Irrigation</option>
                                        <option value="Rain-fed" <?php echo $farmer['farm_type'] == 'Rain-fed' ? 'selected' : ''; ?>>Rain-fed</option>
                                        <option value="Mixed" <?php echo $farmer['farm_type'] == 'Mixed' ? 'selected' : ''; ?>>Mixed</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="farm_location">Farm Location/Address</label>
                            <input type="text" name="farm_location" id="farm_location" class="form-control" value="<?php echo htmlspecialchars($farmer['farm_location']); ?>" placeholder="e.g. Near River Awash, Kebele 04">
                        </div>

                        <div class="form-group mb-3">
                            <label for="production_types">Major Productions (Comma separated)</label>
                            <input type="text" name="production_types" id="production_types" class="form-control" value="<?php echo htmlspecialchars($farmer['production_types']); ?>" placeholder="e.g. Wheat, Maize, Coffee">
                        </div>

                        <div class="form-group mb-3">
                            <label for="experience_years">Years of Farming Experience</label>
                            <input type="number" name="experience_years" id="experience_years" class="form-control" value="<?php echo $farmer['experience_years']; ?>">
                        </div>

                        <div class="form-group mb-4">
                            <label for="farming_history">Farming Bio / History</label>
                            <textarea name="farming_history" id="farming_history" class="form-control" rows="4"><?php echo htmlspecialchars($farmer['farming_history']); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-success px-5">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
