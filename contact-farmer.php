<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth_check.php';

require_login();

if (!isset($_GET['id'])) {
    redirect('farmers.php');
}

$farmer_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Get farmer details
$query = "SELECT u.full_name, u.user_id as farmer_user_id 
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

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    
    $insert_query = "INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("iiss", $user_id, $farmer['farmer_user_id'], $subject, $message);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Your message has been sent to " . $farmer['full_name'];
        redirect('farmer-details.php?id=' . $farmer_id);
    } else {
        $error = "Error sending message: " . $conn->error;
    }
}

$page_title = 'Contact ' . $farmer['full_name'];
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-paper-plane"></i> Send Message to <?php echo htmlspecialchars($farmer['full_name']); ?></h3>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST">
                        <div class="form-group mb-3">
                            <label for="subject">Subject *</label>
                            <input type="text" name="subject" id="subject" class="form-control" placeholder="What is this regarding?" required>
                        </div>

                        <div class="form-group mb-4">
                            <label for="message">Message *</label>
                            <textarea name="message" id="message" class="form-control" rows="6" placeholder="Type your message here..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="farmer-details.php?id=<?php echo $farmer_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-send"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
