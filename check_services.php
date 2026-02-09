<?php
require_once 'config/database.php';

// Check for birthday card service
$check = $conn->query("SELECT * FROM services WHERE service_code = 'BDAY'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO services (service_name, service_code, description, icon_class, is_active) 
                  VALUES ('Birthday Card', 'BDAY', 'Request a personalized digital birthday card from the Bole Town administration.', 'fas fa-birthday-cake', 1)");
    echo "Added Birthday Card service.\n";
} else {
    echo "Birthday Card service already exists.\n";
}

// Check other codes used in my task summary (BDAY, WTR, CLN, TAX)
$all = $conn->query("SELECT service_code FROM services");
echo "Current services: ";
while ($row = $all->fetch_assoc()) {
    echo $row['service_code'] . " ";
}
echo "\n";
?>
