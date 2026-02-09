<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$page_title = 'Contact Us';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields.';
    } elseif (!validate_email($email)) {
        $error = 'Invalid email address.';
    } else {
        // In a real application, you would save this to the database and/or send an email
        // Example: $conn->query("INSERT INTO contact_messages (name, email, subject, message) VALUES ('$name', '$email', '$subject', '$message')");
        
        $success = 'Thank you for your message. We will get back to you soon!';
        // Reset form fields
        $name = $email = $subject = $message = '';
    }
}

include 'includes/header.php';
?>

<section class="page-hero" style="background: linear-gradient(rgba(44, 90, 160, 0.8), rgba(44, 90, 160, 0.8)), url('<?php echo SITE_URL; ?>/assets/images/contact-hero.jpg'); background-size: cover; background-position: center; color: white; padding: 60px 0; text-align: center;">
    <div class="container">
        <h1>Contact Us</h1>
        <p class="lead">We're here to help. Reach out to us with any questions or feedback.</p>
    </div>
</section>

<div class="container my-5">
    <div class="row">
        <div class="col-md-6 mb-4">
            <h2>Get in Touch</h2>
            <p>Have questions about our services? Need technical support? Our team is ready to assist you.</p>
            
            <ul class="list-unstyled mt-4">
                <li class="mb-3">
                    <i class="fas fa-map-marker-alt text-primary mr-3"></i>
                    Bole Town Municipal Office, Main St, Bole
                </li>
                <li class="mb-3">
                    <i class="fas fa-phone text-primary mr-3"></i>
                    +251 11 XXX XXXX
                </li>
                <li class="mb-3">
                    <i class="fas fa-envelope text-primary mr-3"></i>
                    info@boletown.gov.et
                </li>
            </ul>

            <div class="social-links mt-4">
                <a href="#" class="btn btn-outline-primary btn-sm mr-2"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="btn btn-outline-primary btn-sm mr-2"><i class="fab fa-twitter"></i></a>
                <a href="#" class="btn btn-outline-primary btn-sm mr-2"><i class="fab fa-telegram-plane"></i></a>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h3>Send a Message</h3>
                    
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <?php if($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="name">Your Name</label>
                            <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="subject">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="message">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
