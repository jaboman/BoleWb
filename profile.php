<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

$user_id = $_SESSION['user_id'];

// Get user data
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone_number = sanitize_input($_POST['phone_number']);
    $kebele_name = sanitize_input($_POST['kebele_name']);
    $age = intval($_POST['age']);
    $gender = sanitize_input($_POST['gender']);
    $marital_status = sanitize_input($_POST['marital_status']);

    // Handle Profile Image Upload
    $profile_image = $user['profile_image'];
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload = upload_file($_FILES['profile_image'], 'profile');
        if ($upload['success']) {
            $profile_image = $upload['file_name'];
            $_SESSION['profile_image'] = $profile_image;
        }
    }

    $update_query = "UPDATE users SET 
                    full_name = ?, 
                    email = ?, 
                    phone_number = ?, 
                    kebele_name = ?, 
                    age = ?, 
                    gender = ?, 
                    marital_status = ?,
                    profile_image = ?
                    WHERE user_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ssssisssi", $full_name, $email, $phone_number, $kebele_name, $age, $gender, $marital_status, $profile_image, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $full_name;
        $_SESSION['success'] = "Profile updated successfully!";
        // Refresh data
        redirect('profile.php');
    } else {
        $error = "Error updating profile: " . $conn->error;
    }
}

$page_title = 'My Profile';
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-user-circle"></i> My Profile</h3>
                    <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
                    <?php endif; ?>
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-4 text-center mb-4">
                                <div class="profile-image-container mb-3">
                                    <img src="assets/uploads/profile/<?php echo $user['profile_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                         class="rounded-circle img-thumbnail" 
                                         style="width: 180px; height: 180px; object-fit: cover;">
                                </div>
                                <div class="form-group mb-0">
                                    <label for="profile_image" class="btn btn-outline-primary btn-sm btn-block">
                                        <i class="fas fa-camera"></i> Change Photo
                                    </label>
                                    <input type="file" name="profile_image" id="profile_image" class="d-none" accept="image/*">
                                    <small class="text-muted d-block mt-2">Max 5MB (JPG, PNG)</small>
                                </div>
                                <div class="mt-4 p-3 bg-light rounded text-left">
                                    <h6>Account Details</h6>
                                    <p class="mb-1 small"><strong>Role:</strong> <?php echo get_role_name($user['role']); ?></p>
                                    <p class="mb-1 small"><strong>FAIDA ID:</strong> <?php echo $user['faida_id']; ?></p>
                                    <p class="mb-0 small"><strong>Member Since:</strong> <?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="col-md-8 border-left">
                                <h5 class="mb-3 border-bottom pb-2">Personal Information</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="full_name">Full Name *</label>
                                        <input type="text" name="full_name" id="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email">Email Address *</label>
                                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="phone_number">Phone Number</label>
                                        <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number']); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="kebele_name">Kebele</label>
                                        <input type="text" name="kebele_name" id="kebele_name" class="form-control" value="<?php echo htmlspecialchars($user['kebele_name']); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="age">Age</label>
                                        <input type="number" name="age" id="age" class="form-control" value="<?php echo $user['age']; ?>">
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="gender">Gender</label>
                                        <select name="gender" id="gender" class="form-control">
                                            <option value="Male" <?php echo $user['gender'] == 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo $user['gender'] == 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo $user['gender'] == 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="marital_status">Marital Status</label>
                                        <select name="marital_status" id="marital_status" class="form-control">
                                            <option value="Single" <?php echo $user['marital_status'] == 'Single' ? 'selected' : ''; ?>>Single</option>
                                            <option value="Married" <?php echo $user['marital_status'] == 'Married' ? 'selected' : ''; ?>>Married</option>
                                            <option value="Divorced" <?php echo $user['marital_status'] == 'Divorced' ? 'selected' : ''; ?>>Divorced</option>
                                            <option value="Widowed" <?php echo $user['marital_status'] == 'Widowed' ? 'selected' : ''; ?>>Widowed</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mt-4 p-3 bg-light rounded mb-4">
                                    <h5 class="mb-3 border-bottom pb-2">Account Security</h5>
                                    <p class="small text-muted mb-3">Need to change your password? Head over to the account settings page.</p>
                                    <a href="settings.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-lock"></i> Account Settings
                                    </a>
                                </div>

                                <div class="text-right">
                                    <button type="submit" class="btn btn-primary px-5 btn-lg">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
