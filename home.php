<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Bole Town -Where You Get Digital Services Porta.l</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        .hero {
            background: linear-gradient(rgba(44, 90, 160, 0.9), rgba(44, 90, 160, 0.8)), url('assets/images/bole-town.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }
        .stats {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            margin: 50px 0;
        }
        .stat-item {
            text-align: center;
            margin: 20px;
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c5aa0;
        }
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }
        .service-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            transition: transform 0.3s;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .service-icon {
            font-size: 2.5rem;
            color: #2c5aa0;
            margin-bottom: 15px;
        }
        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
            margin: 30px 0;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <section class="hero">
        <div class="container">
            <h1>Welcome to Bole Town Digital Portal</h1>
            <p class="lead">Access government services, connect with farmers and traders, and explore our vibrant community - all in one place.</p>
            <div class="cta-buttons">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-primary btn-lg">Go to Dashboard</a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/register.php" class="btn btn-primary btn-lg">Get Started</a>
                    <a href="<?php echo SITE_URL; ?>/login.php" class="btn btn-outline-light btn-lg">Login</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/about.php" class="btn btn-outline-light btn-lg">Learn More</a>
            </div>
        </div>
    </section>

    <div class="container">
        <!-- Statistics -->
        <div class="stats">
            <?php
            $stats_query = "SELECT 
                (SELECT COUNT(*) FROM users WHERE status = 'Active') as total_users,
                (SELECT COUNT(*) FROM farmers) as total_farmers,
                (SELECT COUNT(*) FROM traders) as total_traders,
                (SELECT COUNT(*) FROM service_transactions WHERE status = 'Completed') as total_transactions";
            $stats_result = $conn->query($stats_query);
            $stats = $stats_result->fetch_assoc();
            ?>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Registered Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_farmers']; ?></div>
                <div class="stat-label">Active Farmers</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_traders']; ?></div>
                <div class="stat-label">Verified Traders</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $stats['total_transactions']; ?></div>
                <div class="stat-label">Services Delivered</div>
            </div>
        </div>

        <!-- Services Grid -->
        <h2 class="text-center">Our Services</h2>
        <div class="services-grid">
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-tint"></i>
                </div>
                <h3>Water Service</h3>
                <p>Pay water bills, check consumption, and report issues online</p>
                <a href="services/wtr.php" class="btn btn-outline-primary">Learn More</a>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-hospital"></i>
                </div>
                <h3>Clinic Service</h3>
                <p>Book medical appointments and access healthcare services</p>
                <a href="services/cln.php" class="btn btn-outline-primary">Learn More</a>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-birthday-cake"></i>
                </div>
                <h3>Birthday Card</h3>
                <p>Request personalized digital birthday cards for your loved ones</p>
                <a href="services/bday.php" class="btn btn-outline-primary">Learn More</a>
            </div>
            <div class="service-card">
                <div class="service-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h3>Tax Service</h3>
                <p>Pay taxes online and access tax information</p>
                <a href="services/tax.php" class="btn btn-outline-primary">Learn More</a>
            </div>
        </div>

        <!-- Interactive Map -->
        <h2 class="text-center">Explore Bole Town</h2>
        <div id="map"></div>

        <!-- Announcements -->
        <div class="announcements">
            <h2><i class="fas fa-bullhorn"></i> Latest Announcements</h2>
            <?php
            $announcements_query = "SELECT * FROM notifications WHERE notification_type = 'Announcement' AND is_important = TRUE ORDER BY created_at DESC LIMIT 5";
            $announcements = $conn->query($announcements_query);
            
            if($announcements->num_rows > 0): ?>
                <div class="announcement-list">
                    <?php while($announcement = $announcements->fetch_assoc()): ?>
                        <div class="announcement-item">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <p><?php echo htmlspecialchars($announcement['message']); ?></p>
                            <small><?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></small>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p class="text-center">No announcements at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        const map = L.map('map').setView([9.0054, 38.7578], 12); // Bole coordinates

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // Add markers for important locations
        const locations = [
            {name: "Bole Town Hall", lat: 9.0054, lng: 38.7578},
            {name: "Bole Hospital", lat: 9.0100, lng: 38.7600},
            {name: "Main Market", lat: 9.0000, lng: 38.7500},
            {name: "Agriculture Office", lat: 9.0080, lng: 38.7550},
            {name: "Water Office", lat: 9.0030, lng: 38.7530}
        ];

        locations.forEach(location => {
            L.marker([location.lat, location.lng])
                .addTo(map)
                .bindPopup(`<b>${location.name}</b>`);
        });
    </script>
</body>

</html>
