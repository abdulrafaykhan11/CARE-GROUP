<?php
require_once '../config/db.php';

// Fix "Dr. " in names
mysqli_query($conn, "UPDATE users SET full_name = REPLACE(full_name, 'Dr. Dr. ', '') WHERE role = 'Doctor'");
mysqli_query($conn, "UPDATE users SET full_name = REPLACE(full_name, 'Dr. ', '') WHERE role = 'Doctor'");

// Ensure every doctor has at least one clinic availability
$res = mysqli_query($conn, "SELECT doctor_id, city_id FROM doctors");
$count = 0;

while($doc = mysqli_fetch_assoc($res)) {
    $doc_id = $doc['doctor_id'];
    $city_id = $doc['city_id'];
    
    // Check if they have availability
    $avail_res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM doctor_availability WHERE doctor_id = $doc_id");
    $avail_row = mysqli_fetch_assoc($avail_res);
    
    if ($avail_row['cnt'] == 0) {
        // Find a clinic in their city
        $clinic_res = mysqli_query($conn, "SELECT clinic_id FROM clinics WHERE city_id = $city_id LIMIT 1");
        if(mysqli_num_rows($clinic_res) > 0) {
            $clinic_row = mysqli_fetch_assoc($clinic_res);
            $clinic_id = $clinic_row['clinic_id'];
        } else {
            // Create a generic clinic for this city
            mysqli_query($conn, "INSERT INTO clinics (city_id, clinic_name, phone, address, status) VALUES ($city_id, 'CARE Nexus City Clinic', '0300-1112223', 'Central Medical Block', 'Active')");
            $clinic_id = mysqli_insert_id($conn);
        }
        
        // Add availability
        $days = ['Monday', 'Wednesday', 'Friday'];
        foreach($days as $day) {
            mysqli_query($conn, "INSERT INTO doctor_availability (doctor_id, clinic_id, day, start_time, end_time, slot_duration, status) 
            VALUES ($doc_id, $clinic_id, '$day', '14:00:00', '18:00:00', 30, 'Active')");
        }
        $count++;
    }
}

echo "Fixed names and assigned clinics to $count doctors.";
?>
