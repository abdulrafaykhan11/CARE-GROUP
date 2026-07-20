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
                // DB me SIRF FILENAME save hoga (Index page ke compatibility ke liye)
                $image_query_part = ", profile_image = '$new_image_name'";
            }
        } else {
            $msg .= "<p style='color: orange;'>Image extension not allowed (Only JPG, PNG, WEBP allowed).</p>";
        }
    }

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
        $msg .= "<p style='color: green; background: #e8f8f0; padding: 10px; border-radius: 4px;'>Profile & Picture updated successfully!</p>";
    } else {
        $msg .= "<p style='color: red; background: #fde8e8; padding: 10px; border-radius: 4px;'>SQL Error: " . mysqli_error($conn) . "</p>";
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
    <title>Doctor Profile Settings</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; }
        .profile-card { max-width: 600px; margin: auto; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-weight: bold; margin-bottom: 5px; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        .btn-submit { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; width: 100%; }
        .btn-submit:hover { background-color: #218838; }
        .profile-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin-bottom: 10px; border: 3px solid #007bff; }
    </style>
</head>
<body>

<div class="profile-card">
    <h2>Doctor Profile Settings</h2>
    <?php echo $msg; ?>

    <form action="" method="POST" enctype="multipart/form-data">
        
        <h3>Personal Details</h3>

        <!-- Profile Image Display Logic -->
        <div class="form-group" style="text-align: center;">
            <?php 
                $raw_img = $doc_data['profile_image'] ?? '';
                
                // Agar DB me sirf filename ho (e.g. doctor_profile_123.png)
                if (!empty($raw_img)) {
                    // Clean path extraction
                    $file_only = basename($raw_img); 
                    $img_src = "../assets/uploads/doctor/profile/" . $file_only;
                } else {
                    $img_src = "https://via.placeholder.com/120?text=No+Image";
                }
            ?>
            
            <img src="<?php echo $img_src; ?>" class="profile-preview" alt="Profile Picture"><br>
            <label>Change Profile Image:</label>
            <input type="file" name="profile_image" accept="image/*">
        </div>

        <div class="form-group">
            <label>Full Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user_data['full_name'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>Email Address:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>Phone Number:</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" required>
        </div>

        <hr style="margin: 20px 0;">

        <h3>Professional Details</h3>
        
        <div class="form-group">
            <label>Specialization:</label>
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

        <div class="form-group">
            <label>City:</label>
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

        <div class="form-group">
            <label>Qualification:</label>
            <input type="text" name="qualification" value="<?php echo htmlspecialchars($doc_data['qualification'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
            <label>Experience (Years):</label>
            <input type="number" name="experience_years" value="<?php echo htmlspecialchars($doc_data['experience_years'] ?? '0'); ?>" min="0" required>
        </div>

        <div class="form-group">
            <label>Consultation Fee (PKR):</label>
            <input type="number" name="consultation_fee" value="<?php echo htmlspecialchars($doc_data['consultation_fee'] ?? '0'); ?>" step="0.01" min="0" required>
        </div>

        <div class="form-group">
            <label>Full Clinic/Hospital Address:</label>
            <input type="text" name="full_address" value="<?php echo htmlspecialchars($doc_data['full_address'] ?? ''); ?>">
        </div>

        <div class="form-group">
            <label>Bio / About Me:</label>
            <textarea name="bio" rows="4"><?php echo htmlspecialchars($doc_data['bio'] ?? ''); ?></textarea>
        </div>

        <button type="submit" name="update_profile_btn" class="btn-submit">Save Profile</button>
    </form>
</div>

</body>
</html>