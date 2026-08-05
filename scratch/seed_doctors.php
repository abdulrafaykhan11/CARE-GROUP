<?php
require_once '../config/db.php';

$base_dir = '../assets/uploads/doctor/';
@mkdir($base_dir . 'profile', 0777, true);
@mkdir($base_dir . 'degrees', 0777, true);
@mkdir($base_dir . 'license', 0777, true);

// Fetch cities
$cities = [];
$res = mysqli_query($conn, "SELECT city_id FROM cities WHERE status='Active'");
while($row = mysqli_fetch_assoc($res)) {
    $cities[] = $row['city_id'];
}
if (empty($cities)) {
    // create a few cities
    mysqli_query($conn, "INSERT INTO cities (city_name, state, country, status) VALUES ('Karachi', 'Sindh', 'Pakistan', 'Active')");
    $cities[] = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO cities (city_name, state, country, status) VALUES ('Lahore', 'Punjab', 'Pakistan', 'Active')");
    $cities[] = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO cities (city_name, state, country, status) VALUES ('Islamabad', 'Federal', 'Pakistan', 'Active')");
    $cities[] = mysqli_insert_id($conn);
}

// Fetch specializations
$specs = [];
$res = mysqli_query($conn, "SELECT specialization_id FROM specializations WHERE status='Active'");
while($row = mysqli_fetch_assoc($res)) {
    $specs[] = $row['specialization_id'];
}
if (empty($specs)) {
    // create specializations
    mysqli_query($conn, "INSERT INTO specializations (specialization_name, status) VALUES ('Cardiology', 'Active')");
    $specs[] = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO specializations (specialization_name, status) VALUES ('Neurology', 'Active')");
    $specs[] = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO specializations (specialization_name, status) VALUES ('Dermatology', 'Active')");
    $specs[] = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO specializations (specialization_name, status) VALUES ('Orthopedics', 'Active')");
    $specs[] = mysqli_insert_id($conn);
    mysqli_query($conn, "INSERT INTO specializations (specialization_name, status) VALUES ('Pediatrics', 'Active')");
    $specs[] = mysqli_insert_id($conn);
}

$firstNames = ["Ali", "Ahmed", "Hassan", "Usman", "Zain", "Omar", "Sara", "Fatima", "Ayesha", "Zahra", "Sana", "Hira", "Tariq", "Imran", "Kashif"];
$lastNames = ["Khan", "Ahmed", "Ali", "Hussain", "Raza", "Malik", "Shah", "Qureshi", "Tariq", "Javed", "Iqbal", "Farooq"];

$admin_id = 1; 
$doc_count = 0;
$spec_index = 0;
$gender_list = ["Male", "Female"];

foreach($cities as $city_id) {
    for($i = 0; $i < 10; $i++) {
        $gender = $gender_list[rand(0, 1)];
        $gender_str = strtolower($gender) == 'male' ? 'men' : 'women';
        $fname = $firstNames[array_rand($firstNames)];
        $lname = $lastNames[array_rand($lastNames)];
        $name = "Dr. " . $fname . " " . $lname;
        $email = strtolower($fname) . "." . strtolower($lname) . rand(1000,9999) . "@care.com";
        $phone = "03" . rand(10, 49) . "-" . rand(1000000, 9999999);
        $password = password_hash("password123", PASSWORD_DEFAULT);
        
        $spec_id = $specs[$spec_index % count($specs)];
        $spec_index++;

        $img_id = rand(1, 99);
        $profile_name = "doc_profile_" . time() . "_" . rand(10000, 99999) . ".jpg";
        $profile_path = $base_dir . 'profile/' . $profile_name;
        
        $opts = array('http'=>array('header'=>"User-Agent: Mozilla/5.0\r\n"));
        $context = stream_context_create($opts);
        $img_data = @file_get_contents("https://randomuser.me/api/portraits/{$gender_str}/{$img_id}.jpg", false, $context);
        
        if(!$img_data) {
            $img_data = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        }
        file_put_contents($profile_path, $img_data);

        // Dummy Certificates - using a small blank PNG base64 for real file presence
        $blank_png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=');
        $degree_name = "degree_" . time() . "_" . rand(10000, 99999) . ".png";
        $license_name = "license_" . time() . "_" . rand(10000, 99999) . ".png";
        file_put_contents($base_dir . 'degrees/' . $degree_name, $blank_png);
        file_put_contents($base_dir . 'license/' . $license_name, $blank_png);

        $q1 = "INSERT INTO users (full_name, email, phone, password, role, status) VALUES ('$name', '$email', '$phone', '$password', 'Doctor', 'Active')";
        if(mysqli_query($conn, $q1)) {
            $user_id = mysqli_insert_id($conn);
            $pmdc = rand(10000, 99999) . "-P";
            $cnic = rand(42101, 42999) . "-" . rand(1000000, 9999999) . "-" . rand(1, 9);
            $fee = rand(1000, 5000);
            $exp = rand(2, 20);
            
            $target_license = "assets/uploads/doctor/license/" . $license_name;
            $target_degree = "assets/uploads/doctor/degrees/" . $degree_name;
            
            $q2 = "INSERT INTO doctors (user_id, specialization_id, city_id, full_address, experience_years, qualification, pmdc_registration_number, cnic, license_certificate, degree_certificate, consultation_fee, gender, date_of_birth, profile_image, bio, verification_status, verified_by, verified_at) 
            VALUES ($user_id, $spec_id, $city_id, '123 Medical Avenue, Clinic Block', $exp, 'MBBS, FCPS', '$pmdc', '$cnic', '$target_license', '$target_degree', $fee, '$gender', '1980-01-01', '$profile_name', 'Experienced practitioner committed to patient care.', 'Verified', $admin_id, NOW())";
            mysqli_query($conn, $q2);
            $doc_count++;
        }
    }
}

echo "Successfully seeded $doc_count verified doctors across " . count($cities) . " cities and " . count($specs) . " specializations.\n";
?>
