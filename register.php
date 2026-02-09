<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Security token invalid. Please try again.';
    } else {
        // Sanitize inputs
        $full_name = sanitize_input($_POST['full_name']);
        $email = sanitize_input($_POST['email']);
        $faida_id = sanitize_input($_POST['faida_id']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone_number = sanitize_input($_POST['phone_number']);
        $kebele_name = sanitize_input($_POST['kebele_name']);
        $age = intval($_POST['age']);
        $gender = sanitize_input($_POST['gender']);
        $marital_status = sanitize_input($_POST['marital_status']);
        $role = sanitize_input($_POST['role']);
        
        // Additional fields based on role
        $farm_size = isset($_POST['farm_size']) ? floatval($_POST['farm_size']) : null;
        $farm_type = isset($_POST['farm_type']) ? sanitize_input($_POST['farm_type']) : null;
        $business_name = isset($_POST['business_name']) ? sanitize_input($_POST['business_name']) : null;
        $business_type = isset($_POST['business_type']) ? sanitize_input($_POST['business_type']) : null;

        // Validation
        if (empty($full_name) || empty($email) || empty($faida_id) || empty($password) || empty($role)) {
            $error = 'All required fields must be filled!';
        } elseif (!validate_email($email)) {
            $error = 'Invalid email address!';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match!';
        } elseif (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters long!';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $error = 'Password must contain at least one uppercase letter and one number!';
        } elseif ($age < 18) {
            $error = 'You must be at least 18 years old to register!';
        } else {
            // Check if user already exists
            $check_query = "SELECT user_id FROM users WHERE email = ? OR faida_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ss", $email, $faida_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email or Faida ID already registered!';
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Generate verification token
                $verification_token = bin2hex(random_bytes(32));
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Insert user
                    $insert_query = "INSERT INTO users (full_name, email, faida_id, password_hash, phone_number, kebele_name, age, gender, marital_status, role, verification_token) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param("ssssssissss", $full_name, $email, $faida_id, $password_hash, $phone_number, $kebele_name, $age, $gender, $marital_status, $role, $verification_token);
                    
                    if ($stmt->execute()) {
                        $user_id = $conn->insert_id;
                        
                        // Create role-specific profile
                        if ($role === 'Farmer') {
                            $farm_query = "INSERT INTO farmers (user_id, farm_size, farm_type) VALUES (?, ?, ?)";
                            $farm_stmt = $conn->prepare($farm_query);
                            $farm_stmt->bind_param("ids", $user_id, $farm_size, $farm_type);
                            $farm_stmt->execute();
                        } elseif ($role === 'Trader') {
                            $trader_query = "INSERT INTO traders (user_id, business_name, business_type) VALUES (?, ?, ?)";
                            $trader_stmt = $conn->prepare($trader_query);
                            $trader_stmt->bind_param("iss", $user_id, $business_name, $business_type);
                            $trader_stmt->execute();
                        }
                        
                        // Add activity log
                        log_activity($user_id, 'registration', 'New user registered as ' . $role);
                        
                        // Send welcome email
                        $subject = "Welcome to Bole Town Website";
                        $message = "
                        <html>
                        <head>
                            <style>
                                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                                .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
                                .content { padding: 20px; background: #f9f9f9; }
                                .footer { text-align: center; padding: 10px; color: #666; font-size: 12px; }
                            </style>
                        </head>
                        <body>
                            <div class='container'>
                                <div class='header'>
                                    <h1>Welcome to Bole Town!</h1>
                                </div>
                                <div class='content'>
                                    <p>Dear $full_name,</p>
                                    <p>Thank you for registering with Bole Town Website. Your account has been created successfully.</p>
                                    <p><strong>Account Details:</strong></p>
                                    <ul>
                                        <li>Name: $full_name</li>
                                        <li>Email: $email</li>
                                        <li>Faida ID: $faida_id</li>
                                        <li>Role: $role</li>
                                        <li>Kebele: $kebele_name</li>
                                    </ul>
                                    <p>You can now login and start using our services.</p>
                                    <p>If you have any questions, please contact our support team.</p>
                                </div>
                                <div class='footer'>
                                    <p>© " . date('Y') . " Bole Town Website. All rights reserved.</p>
                                </div>
                            </div>
                        </body>
                        </html>";
                        
                        send_email($email, $subject, $message);
                        
                        // Commit transaction
                        $conn->commit();
                        
                        $success = 'Registration successful! You can now login.';
                        
                        // Clear form
                        $_POST = array();
                    } else {
                        throw new Exception('Registration failed. Please try again.');
                    }
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Bole Town</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
    <style>
        .registration-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
        }
        .form-step {
            display: none;
        }
        .form-step.active {
            display: block;
            animation: fadeIn 0.5s;
        }
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            position: relative;
        }
        .step-number {
            width: 40px;
            height: 40px;
            background: #ddd;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: bold;
        }
        .step.active .step-number {
            background: #2c5aa0;
            color: white;
        }
        .step.completed .step-number {
            background: #4CAF50;
            color: white;
        }
        .step-line {
            position: absolute;
            top: 25px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #ddd;
            z-index: -1;
        }
        .step:last-child .step-line {
            display: none;
        }
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .password-strength {
            margin-top: 5px;
            font-size: 0.9rem;
        }
        .strength-bar {
            height: 5px;
            background: #ddd;
            border-radius: 5px;
            margin-top: 5px;
            overflow: hidden;
        }
        .strength-fill {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container registration-container">
        <h1 class="text-center"><i class="fas fa-user-plus"></i> Register for Bole Town Services</h1>
        
        <?php if($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="alert alert-success"><?php echo $success; ?> <a href="login.php" class="alert-link">Click here to login</a></div>
        <?php endif; ?>
        
        <?php if(!$success): ?>
        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-title">Personal Info</div>
                <div class="step-line"></div>
            </div>
            <div class="step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-title">Account Details</div>
                <div class="step-line"></div>
            </div>
            <div class="step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-title">Role Specific</div>
                <div class="step-line"></div>
            </div>
            <div class="step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-title">Confirmation</div>
            </div>
        </div>
        
        <form id="registrationForm" method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <!-- Step 1: Personal Information -->
            <div class="form-step active" id="step1">
                <h3><i class="fas fa-user"></i> Personal Information</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="full_name">Full Name *</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                   required placeholder="Enter your full name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                   required placeholder="example@email.com">
                            <div id="email-validation"></div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="faida_id">Faida ID *</label>
                            <input type="text" id="faida_id" name="faida_id" class="form-control" 
                                   value="<?php echo isset($_POST['faida_id']) ? htmlspecialchars($_POST['faida_id']) : ''; ?>" 
                                   required placeholder="BTW-XXXX-XXXX" pattern="BTW-\d{4}-\d{4}">
                            <small class="form-text text-muted">Format: BTW-1234-5678</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="phone_number">Phone Number</label>
                            <input type="tel" id="phone_number" name="phone_number" class="form-control" 
                                   value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>" 
                                   placeholder="+251-XXX-XXXXXX">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="age">Age *</label>
                            <input type="number" id="age" name="age" class="form-control" 
                                   value="<?php echo isset($_POST['age']) ? htmlspecialchars($_POST['age']) : ''; ?>" 
                                   min="18" max="120" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="gender">Gender *</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="marital_status">Marital Status</label>
                            <select id="marital_status" name="marital_status" class="form-control">
                                <option value="">Select Status</option>
                                <option value="Single" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                                <option value="Married" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                                <option value="Divorced" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                <option value="Widowed" <?php echo (isset($_POST['marital_status']) && $_POST['marital_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="kebele_name">Kebele *</label>
                            <select id="kebele_name" name="kebele_name" class="form-control" required>
                                <option value="">Select Kebele</option>
                                <option value="Bole Central" <?php echo (isset($_POST['kebele_name']) && $_POST['kebele_name'] == 'Bole Central') ? 'selected' : ''; ?>>Bole Central</option>
                                <option value="Bole Bulbula" <?php echo (isset($_POST['kebele_name']) && $_POST['kebele_name'] == 'Bole Bulbula') ? 'selected' : ''; ?>>Bole Bulbula</option>
                                <option value="Bole Arabsa" <?php echo (isset($_POST['kebele_name']) && $_POST['kebele_name'] == 'Bole Arabsa') ? 'selected' : ''; ?>>Bole Arabsa</option>
                                <option value="Bole Mikael" <?php echo (isset($_POST['kebele_name']) && $_POST['kebele_name'] == 'Bole Mikael') ? 'selected' : ''; ?>>Bole Mikael</option>
                                <option value="Bole Medhanialem" <?php echo (isset($_POST['kebele_name']) && $_POST['kebele_name'] == 'Bole Medhanialem') ? 'selected' : ''; ?>>Bole Medhanialem</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="role">Register As *</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="">Select Role</option>
                                <option value="Farmer" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Farmer') ? 'selected' : ''; ?>>Farmer</option>
                                <option value="Trader" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Trader') ? 'selected' : ''; ?>>Trader</option>
                                <option value="Visitor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Visitor') ? 'selected' : ''; ?>>Visitor</option>
                                <option value="Traveler" <?php echo (isset($_POST['role']) && $_POST['role'] == 'Traveler') ? 'selected' : ''; ?>>Traveler</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="btn btn-outline-primary next-step" data-next="2">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            
            <!-- Step 2: Account Details -->
            <div class="form-step" id="step2">
                <h3><i class="fas fa-lock"></i> Account Security</h3>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="password">Password *</label>
                            <input type="password" id="password" name="password" class="form-control" required>
                            <div class="password-strength">
                                <span id="strengthText">Password strength: Weak</span>
                                <div class="strength-bar">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                            </div>
                            <small class="form-text text-muted">
                                Password must be at least 8 characters with one uppercase letter and one number
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                            <div id="password-match"></div>
                        </div>
                    </div>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="button" class="btn btn-outline-primary next-step" data-next="3">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            
            <!-- Step 3: Role Specific Information -->
            <div class="form-step" id="step3">
                <h3 id="role-specific-title"><i class="fas fa-user-tag"></i> Role Information</h3>
                
                <!-- Farmer Fields -->
                <div id="farmer-fields" class="role-fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="farm_size">Farm Size (hectares)</label>
                                <input type="number" step="0.01" id="farm_size" name="farm_size" class="form-control" 
                                       value="<?php echo isset($_POST['farm_size']) ? htmlspecialchars($_POST['farm_size']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="farm_type">Farm Type</label>
                                <select id="farm_type" name="farm_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="Irrigation" <?php echo (isset($_POST['farm_type']) && $_POST['farm_type'] == 'Irrigation') ? 'selected' : ''; ?>>Irrigation</option>
                                    <option value="Rain-fed" <?php echo (isset($_POST['farm_type']) && $_POST['farm_type'] == 'Rain-fed') ? 'selected' : ''; ?>>Rain-fed</option>
                                    <option value="Mixed" <?php echo (isset($_POST['farm_type']) && $_POST['farm_type'] == 'Mixed') ? 'selected' : ''; ?>>Mixed</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="farm_location">Farm Location</label>
                        <textarea id="farm_location" name="farm_location" class="form-control" rows="2"><?php echo isset($_POST['farm_location']) ? htmlspecialchars($_POST['farm_location']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Trader Fields -->
                <div id="trader-fields" class="role-fields" style="display: none;">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="business_name">Business Name *</label>
                                <input type="text" id="business_name" name="business_name" class="form-control" 
                                       value="<?php echo isset($_POST['business_name']) ? htmlspecialchars($_POST['business_name']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="business_type">Business Type</label>
                                <select id="business_type" name="business_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="Retail" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'Retail') ? 'selected' : ''; ?>>Retail</option>
                                    <option value="Wholesale" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'Wholesale') ? 'selected' : ''; ?>>Wholesale</option>
                                    <option value="Export" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'Export') ? 'selected' : ''; ?>>Export</option>
                                    <option value="Import" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'Import') ? 'selected' : ''; ?>>Import</option>
                                    <option value="Processing" <?php echo (isset($_POST['business_type']) && $_POST['business_type'] == 'Processing') ? 'selected' : ''; ?>>Processing</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="business_address">Business Address</label>
                        <textarea id="business_address" name="business_address" class="form-control" rows="2"><?php echo isset($_POST['business_address']) ? htmlspecialchars($_POST['business_address']) : ''; ?></textarea>
                    </div>
                </div>
                
                <!-- Visitor/Traveler Fields -->
                <div id="visitor-fields" class="role-fields" style="display: none;">
                    <div class="form-group">
                        <label for="purpose_of_visit">Purpose of Visit</label>
                        <textarea id="purpose_of_visit" name="purpose_of_visit" class="form-control" rows="3"><?php echo isset($_POST['purpose_of_visit']) ? htmlspecialchars($_POST['purpose_of_visit']) : ''; ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="expected_duration">Expected Duration (days)</label>
                                <input type="number" id="expected_duration" name="expected_duration" class="form-control" 
                                       value="<?php echo isset($_POST['expected_duration']) ? htmlspecialchars($_POST['expected_duration']) : ''; ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="accommodation_type">Accommodation Type</label>
                                <select id="accommodation_type" name="accommodation_type" class="form-control">
                                    <option value="">Select Type</option>
                                    <option value="Hotel" <?php echo (isset($_POST['accommodation_type']) && $_POST['accommodation_type'] == 'Hotel') ? 'selected' : ''; ?>>Hotel</option>
                                    <option value="Guest House" <?php echo (isset($_POST['accommodation_type']) && $_POST['accommodation_type'] == 'Guest House') ? 'selected' : ''; ?>>Guest House</option>
                                    <option value="Family Stay" <?php echo (isset($_POST['accommodation_type']) && $_POST['accommodation_type'] == 'Family Stay') ? 'selected' : ''; ?>>Family Stay</option>
                                    <option value="Other" <?php echo (isset($_POST['accommodation_type']) && $_POST['accommodation_type'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="button" class="btn btn-outline-primary next-step" data-next="4">Next <i class="fas fa-arrow-right"></i></button>
                </div>
            </div>
            
            <!-- Step 4: Confirmation -->
            <div class="form-step" id="step4">
                <h3><i class="fas fa-check-circle"></i> Review & Confirm</h3>
                
                <div class="confirmation-summary">
                    <h4>Please review your information:</h4>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="summary-section">
                                <h5><i class="fas fa-user"></i> Personal Information</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Full Name:</strong></td><td id="summary-full-name"></td></tr>
                                    <tr><td><strong>Email:</strong></td><td id="summary-email"></td></tr>
                                    <tr><td><strong>Faida ID:</strong></td><td id="summary-faida-id"></td></tr>
                                    <tr><td><strong>Phone:</strong></td><td id="summary-phone"></td></tr>
                                    <tr><td><strong>Kebele:</strong></td><td id="summary-kebele"></td></tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="summary-section">
                                <h5><i class="fas fa-user-tag"></i> Account Information</h5>
                                <table class="table table-sm">
                                    <tr><td><strong>Role:</strong></td><td id="summary-role"></td></tr>
                                    <tr><td><strong>Age:</strong></td><td id="summary-age"></td></tr>
                                    <tr><td><strong>Gender:</strong></td><td id="summary-gender"></td></tr>
                                    <tr><td><strong>Marital Status:</strong></td><td id="summary-marital"></td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <div id="role-summary" class="summary-section"></div>
                    
                    <div class="terms-and-conditions">
                        <div class="form-check">
                            <input type="checkbox" id="agree_terms" name="agree_terms" class="form-check-input" required>
                            <label class="form-check-label" for="agree_terms">
                                I agree to the <a href="terms.php" target="_blank">Terms of Service</a> and <a href="privacy.php" target="_blank">Privacy Policy</a>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-navigation">
                    <button type="button" class="btn btn-outline-secondary prev-step" data-prev="3"><i class="fas fa-arrow-left"></i> Previous</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Complete Registration
                    </button>
                </div>
            </div>
        </form>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <p>Already have an account? <a href="login.php">Login here</a></p>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="<?php echo SITE_URL; ?>/js/registration.js"></script>
</body>
</html>