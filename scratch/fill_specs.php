<?php
require_once '../config/db.php';

$base_dir = '../assets/uploads/doctor/';
$firstNames = ["Faisal", "Noman", "Kashif", "Asad", "Tariq", "Imran", "Sobia", "Nazia", "Mona"];
$lastNames = ["Iqbal", "Farooq", "Sheikh", "Qureshi", "Malik", "Rehman", "Abbas", "Raza"];

// Find specializations with < 5 doctors
$res = mysqli_query($conn, "
    SELECT s.specialization_id, s.specialization_name, COUNT(d.doctor_id) as cnt 
    FROM specializations s 
    LEFT JOIN doctors d ON s.specialization_id=d.specialization_id 
    GROUP BY s.specialization_id 
    HAVING cnt < 5
");

$added = 0;
while($row = mysqli_fetch_assoc($res)) {
    $needed = 5 - $row['cnt'];
    $spec_id = $row['specialization_id'];
    
    // Pick a random city
    $city_res = mysqli_query($conn, "SELECT city_id FROM cities ORDER BY RAND() LIMIT 1");
    $city_row = mysqli_fetch_assoc($city_res);
    $city_id = $city_row['city_id'];
    
    // Find or create clinic
    $clinic_res = mysqli_query($conn, "SELECT clinic_id FROM clinics WHERE city_id = $city_id LIMIT 1");
    if($clinic_res && mysqli_num_rows($clinic_res) > 0) {
        $clinic_row = mysqli_fetch_assoc($clinic_res);
        $clinic_id = $clinic_row['clinic_id'];
    } else {
        mysqli_query($conn, "INSERT INTO clinics (city_id, clinic_name, phone, address, status) VALUES ($city_id, 'CARE Nexus City Clinic', '0300-1112223', 'Central Medical Block', 'Active')");
        $clinic_id = mysqli_insert_id($conn);
    }

    for($i=0; $i<$needed; $i++) {
        $gender = rand(0,1) ? 'Male' : 'Female';
        $gender_str = $gender == 'Male' ? 'men' : 'women';
        $fname = $firstNames[array_rand($firstNames)];
        $lname = $lastNames[array_rand($lastNames)];
        $name = $fname . " " . $lname;
        $email = strtolower($fname) . "." . strtolower($lname) . rand(1000,9999) . "@care.com";
        $phone = "03" . rand(10, 49) . "-" . rand(1000000, 9999999);
        $password = password_hash("password123", PASSWORD_DEFAULT);
        
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
            VALUES ($user_id, $spec_id, $city_id, '123 Medical Avenue, Clinic Block', $exp, 'MBBS, FCPS', '$pmdc', '$cnic', '$target_license', '$target_degree', $fee, '$gender', '1980-01-01', '$profile_name', 'Experienced practitioner committed to patient care.', 'Verified', 1, NOW())";
            mysqli_query($conn, $q2);
            $doc_id = mysqli_insert_id($conn);

            $days = ['Monday', 'Wednesday', 'Friday'];
            foreach($days as $day) {
                mysqli_query($conn, "INSERT INTO doctor_availability (doctor_id, clinic_id, day, start_time, end_time, slot_duration, status) 
                VALUES ($doc_id, $clinic_id, '$day', '14:00:00', '18:00:00', 30, 'Active')");
            }
            $added++;
        }
    }
}
echo "Added $added more doctors to fill specializations.";
?>
