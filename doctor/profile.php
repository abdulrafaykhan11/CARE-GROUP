<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../config/db.php";

// 1. Auth Check
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msgType = "success";

// =======================================================
// 📥 2. FORM SUBMIT / UPDATE LOGIC
// =======================================================
if (isset($_POST['update_profile_btn'])) {
    
    $name              = mysqli_real_escape_string($conn, $_POST['name']);
    $email             = mysqli_real_escape_string($conn, $_POST['email']);
    $phone             = mysqli_real_escape_string($conn, $_POST['phone']);
    $specialization_id = intval($_POST['specialization_id']);
    $city_id           = intval($_POST['city_id']);
    $qualification     = mysqli_real_escape_string($conn, $_POST['qualification']);
    $experience        = intval($_POST['experience_years']);
    $fee               = floatval($_POST['consultation_fee']);
    $full_address      = mysqli_real_escape_string($conn, $_POST['full_address']);
    $bio               = mysqli_real_escape_string($conn, $_POST['bio']);

    // 1. Update Users Table (Name, Email, Phone)
    $user_update = "UPDATE users SET full_name = '$name', email = '$email', phone = '$phone' WHERE user_id = '$user_id'";
    mysqli_query($conn, $user_update);

    // 2. Handle Profile Image Upload
    $image_query_part = "";
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $file_name = $_FILES['profile_image']['name'];
        $file_tmp  = $_FILES['profile_image']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_ext = array('jpg', 'jpeg', 'png', 'webp');

        if (in_array($file_ext, $allowed_ext)) {
            // Unique Image Filename
            $new_image_name = "doctor_profile_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
            
            // Physical Server Disk Path
            $upload_path = "../assets/uploads/doctor/profile/" . $new_image_name;

            // Auto Create Folder
            if (!is_dir("../assets/uploads/doctor/profile/")) {
                mkdir("../assets/uploads/doctor/profile/", 0777, true);
            }

            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_query_part = ", profile_image = '$new_image_name'";
            }
        } else {
            $msg = "Image extension not allowed (Only JPG, PNG, WEBP allowed).";
            $msgType = "error";
        }
    }

    if (empty($msg)) {
        // 3. Check & Update Doctors Table
        $check_doc = mysqli_query($conn, "SELECT doctor_id FROM doctors WHERE user_id = '$user_id'");
        
        if ($check_doc && mysqli_num_rows($check_doc) > 0) {
            $doc_update = "UPDATE doctors 
                           SET specialization_id = '$specialization_id',
                               city_id = '$city_id',
                               qualification = '$qualification', 
                               experience_years = '$experience', 
                               consultation_fee = '$fee', 
                               full_address = '$full_address',
                               bio = '$bio'
                               $image_query_part
                           WHERE user_id = '$user_id'";
        } else {
            $img_val = !empty($new_image_name) ? $new_image_name : 'default.png';
            $doc_update = "INSERT INTO doctors (user_id, specialization_id, city_id, qualification, experience_years, consultation_fee, full_address, bio, profile_image, pmdc_registration_number, cnic) 
                           VALUES ('$user_id', '$specialization_id', '$city_id', '$qualification', '$experience', '$fee', '$full_address', '$bio', '$img_val', 'PENDING', '00000-0000000-0')";
        }

        if (mysqli_query($conn, $doc_update)) {
            $msg = "Profile & Picture updated successfully!";
            $msgType = "success";
        } else {
            $msg = "SQL Error: " . mysqli_error($conn);
            $msgType = "error";
        }
    }
}

// =======================================================
// 🔍 3. FETCH CURRENT DATA & DROPDOWNS
// =======================================================
$user_res  = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_res);

$doc_res  = mysqli_query($conn, "SELECT * FROM doctors WHERE user_id = '$user_id'");
$doc_data = ($doc_res && mysqli_num_rows($doc_res) > 0) ? mysqli_fetch_assoc($doc_res) : [];

$specializations_list = mysqli_query($conn, "SELECT specialization_id, specialization_name FROM specializations WHERE status = 'Active'");
$cities_list          = mysqli_query($conn, "SELECT city_id, city_name FROM cities WHERE status = 'Active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Profile Settings | Care Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="../index.php">care<span>connect</span></a>
        <p class="side-label">DOCTOR PORTAL</p>
        <a href="dashboard.php">⌂ Overview</a>
        <a href="appointments.php">▣ Appointments</a>
        <a href="availability.php">◷ Availability</a>
        <a href="clinics.php">⌖ Clinics</a>
        <a class="active" href="profile.php">⚙ Profile</a>
        <a href="../logout.php">↪ Sign out</a>
    </aside>

    <main class="dashboard-main">
        <header class="dash-header">
            <div>
                <p class="eyebrow">ACCOUNT SETTINGS</p>
                <h1>My Profile</h1>
            </div>
        </header>

        <section class="panel" style="max-width: 800px;">
            <div class="panel-head">
                <div>
                    <h2>Update Information</h2>
                </div>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-<?php echo $msgType; ?>"><?php echo $msg; ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="booking-form">
                
                <div style="margin-bottom: 30px; text-align: center;">
                    <?php 
                        $raw_img = $doc_data['profile_image'] ?? '';
                        if (!empty($raw_img)) {
                            $file_only = basename($raw_img); 
                            $img_src = "../assets/uploads/doctor/profile/" . $file_only;
                        } else {
                            $img_src = "https://via.placeholder.com/120?text=No+Image";
                        }
                    ?>
                    <img src="<?php echo $img_src; ?>" alt="Profile Picture" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; border: 3px solid var(--gold);"><br>
                    <div class="field" style="max-width: 300px; margin: auto;">
                        <label>Change Profile Image</label>
                        <input type="file" name="profile_image" accept="image/*" style="border:none; padding-top:10px;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label>FULL NAME</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
                    </div>
                    <div class="field">
                        <label>EMAIL ADDRESS</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label>PHONE NUMBER</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
                    </div>
                    <div class="field">
                        <label>SPECIALIZATION</label>
                        <select name="specialization_id" required>
                            <option value="">Select Specialization</option>
                            <?php while ($spec = mysqli_fetch_assoc($specializations_list)): ?>
                                <option value="<?php echo $spec['specialization_id']; ?>" 
                                    <?php echo (isset($doc_data['specialization_id']) && $doc_data['specialization_id'] == $spec['specialization_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($spec['specialization_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label>CITY</label>
                        <select name="city_id" required>
                            <option value="">Select City</option>
                            <?php while ($city = mysqli_fetch_assoc($cities_list)): ?>
                                <option value="<?php echo $city['city_id']; ?>" 
                                    <?php echo (isset($doc_data['city_id']) && $doc_data['city_id'] == $city['city_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($city['city_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>QUALIFICATION</label>
                        <input type="text" name="qualification" value="<?php echo htmlspecialchars($doc_data['qualification'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="field">
                        <label>EXPERIENCE (YEARS)</label>
                        <input type="number" name="experience_years" value="<?php echo htmlspecialchars($doc_data['experience_years'] ?? '0'); ?>" min="0" required>
                    </div>
                    <div class="field">
                        <label>CONSULTATION FEE (PKR)</label>
                        <input type="number" name="consultation_fee" value="<?php echo htmlspecialchars($doc_data['consultation_fee'] ?? '0'); ?>" step="0.01" min="0" required>
                    </div>
                </div>

                <div class="field">
                    <label>FULL CLINIC/HOSPITAL ADDRESS</label>
                    <input type="text" name="full_address" value="<?php echo htmlspecialchars($doc_data['full_address'] ?? ''); ?>">
                </div>

                <div class="field">
                    <label>BIO / ABOUT ME</label>
                    <textarea name="bio" rows="4" style="width:100%; border:0; border-bottom:2px solid var(--line); font-family:inherit; padding:10px 0; background:transparent;"><?php echo htmlspecialchars($doc_data['bio'] ?? ''); ?></textarea>
                </div>

                <button type="submit" name="update_profile_btn" class="btn btn-primary" style="width:100%;">Save Profile Settings</button>
            </form>
        </section>
    </main>
</body>
</html>