<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../config/db.php";
require_once __DIR__ . '/../config/upload_cleanup.php';

// 1. Auth Check
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Doctor') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msgType = "success";
$currentDoc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_image FROM doctors WHERE user_id = '$user_id'"));
$oldProfileImage = $currentDoc['profile_image'] ?? '';

// 2. FORM SUBMIT / UPDATE LOGIC
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

    // 1. Update Users Table
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
            $new_image_name = "doctor_profile_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
            $upload_path = "../assets/uploads/doctor/profile/" . $new_image_name;

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
            if (!empty($new_image_name) && !empty($oldProfileImage) && basename($oldProfileImage) !== $new_image_name) {
                deleteUploadedProfileFile($oldProfileImage, 'doctor');
            }
            $msg = "Profile telemetry updated successfully!";
            $msgType = "success";
        } else {
            if (!empty($new_image_name)) {
                deleteUploadedProfileFile($new_image_name, 'doctor');
            }
            $msg = "Database Error: " . mysqli_error($conn);
            $msgType = "error";
        }
    }
}

// 3. FETCH CURRENT DATA & DROPDOWNS
$user_res  = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$user_id'");
$user_data = mysqli_fetch_assoc($user_res);

$doc_res  = mysqli_query($conn, "SELECT * FROM doctors WHERE user_id = '$user_id'");
$doc_data = ($doc_res && mysqli_num_rows($doc_res) > 0) ? mysqli_fetch_assoc($doc_res) : [];

$specializations_list = mysqli_query($conn, "SELECT specialization_id, specialization_name FROM specializations WHERE status = 'Active'");
$cities_list          = mysqli_query($conn, "SELECT city_id, city_name FROM cities WHERE status = 'Active'");

$raw_img = $doc_data['profile_image'] ?? '';
if (!empty($raw_img)) {
    $file_only = basename($raw_img);
    $img_src = "../assets/uploads/doctor/profile/" . $file_only;
} else {
    $img_src = "";
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctor Profile Telemetry | CARE Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <aside class="dash-sidebar">
            <a class="brand" href="../index.php">CARE <span>NEXUS</span></a>
            <div class="eyebrow" style="color: var(--emerald-bio); margin-bottom: 24px;">DOCTOR COMMAND HUD</div>
            <nav class="dash-nav">
                <a href="dashboard.php">❖ Overview HUD</a>
                <a href="appointments.php">❖ Appointments Queue</a>
                <a href="availability.php">❖ Availability Flux</a>
                <a href="clinics.php">❖ Clinic Nodes</a>
                <a class="active" href="profile.php">❖ Profile Shard</a>
                <a href="../logout.php" style="margin-top: auto; color: var(--rose-danger);">❖ Sign Out</a>
            </nav>
        </aside>

        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">PRACTITIONER TELEMETRY SETTINGS</p>
                    <h2>Doctor Profile Configuration</h2>
                </div>
            </header>

            <?php if ($msg): ?>
                <div class="alert alert-<?=$msgType?>"><?=$msg?></div>
            <?php endif; ?>

            <section class="cyber-table-wrap" style="max-width: 900px;">
                <form action="" method="POST" enctype="multipart/form-data" style="display: grid; gap: 20px;">
                    
                    <!-- Profile Image Section -->
                    <div style="display: flex; align-items: center; gap: 24px; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.08);">
                        <?php if($img_src): ?>
                            <img src="<?=htmlspecialchars($img_src)?>" onerror="this.src='https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300'" style="width: 110px; height: 110px; border-radius: 20px; border: 3px solid var(--emerald-bio); object-fit: cover;" alt="Profile">
                        <?php else: ?>
                            <div style="width: 110px; height: 110px; border-radius: 20px; border: 3px solid var(--border-cyber); background: rgba(16,185,129,0.08); display: flex; align-items: center; justify-content: center; font-size: 36px; color: var(--emerald-bio);">❖</div>
                        <?php endif; ?>
                        <div style="flex: 1;">
                            <div class="field">
                                <label>CHANGE PROFILE SHARD PHOTO</label>
                                <input type="file" name="profile_image" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <!-- Account & Contact -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                        <div class="field">
                            <label>FULL NAME <span style="color: var(--cyan-neon);">*</span></label>
                            <input type="text" name="name" value="<?=htmlspecialchars($user_data['full_name'] ?? '')?>" required>
                        </div>
                        <div class="field">
                            <label>EMAIL ADDRESS <span style="color: var(--cyan-neon);">*</span></label>
                            <input type="email" name="email" value="<?=htmlspecialchars($user_data['email'] ?? '')?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                        <div class="field">
                            <label>PHONE NUMBER <span style="color: var(--cyan-neon);">*</span></label>
                            <input type="text" name="phone" value="<?=htmlspecialchars($user_data['phone'] ?? '')?>" required>
                        </div>
                        <div class="field">
                            <label>SPECIALIZATION FIELD <span style="color: var(--cyan-neon);">*</span></label>
                            <select name="specialization_id" required>
                                <option value="">Select Specialization</option>
                                <?php while ($spec = mysqli_fetch_assoc($specializations_list)): ?>
                                    <option value="<?=$spec['specialization_id']?>" 
                                        <?=(isset($doc_data['specialization_id']) && $doc_data['specialization_id'] == $spec['specialization_id']) ? 'selected' : ''?>>
                                        <?=htmlspecialchars($spec['specialization_name'])?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                        <div class="field">
                            <label>CITY NODE <span style="color: var(--cyan-neon);">*</span></label>
                            <select name="city_id" required>
                                <option value="">Select City</option>
                                <?php while ($city = mysqli_fetch_assoc($cities_list)): ?>
                                    <option value="<?=$city['city_id']?>" 
                                        <?=(isset($doc_data['city_id']) && $doc_data['city_id'] == $city['city_id']) ? 'selected' : ''?>>
                                        <?=htmlspecialchars($city['city_name'])?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="field">
                            <label>QUALIFICATION <span style="color: var(--cyan-neon);">*</span></label>
                            <input type="text" name="qualification" value="<?=htmlspecialchars($doc_data['qualification'] ?? '')?>" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px;">
                        <div class="field">
                            <label>EXPERIENCE YEARS <span style="color: var(--cyan-neon);">*</span></label>
                            <input type="number" name="experience_years" value="<?=htmlspecialchars($doc_data['experience_years'] ?? '0')?>" min="0" required>
                        </div>
                        <div class="field">
                            <label>CONSULTATION FEE (PKR) <span style="color: var(--cyan-neon);">*</span></label>
                            <input type="number" name="consultation_fee" value="<?=htmlspecialchars($doc_data['consultation_fee'] ?? '0')?>" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div class="field">
                        <label>CLINICAL / RESIDENTIAL ADDRESS</label>
                        <input type="text" name="full_address" value="<?=htmlspecialchars($doc_data['full_address'] ?? '')?>">
                    </div>

                    <div class="field">
                        <label>BIO & CARE PHILOSOPHY</label>
                        <textarea name="bio" rows="4" placeholder="Describe your clinical background, specialty focus, and patient care approach..."><?=htmlspecialchars($doc_data['bio'] ?? '')?></textarea>
                    </div>

                    <button type="submit" name="update_profile_btn" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                        <span>❖ Save Practitioner Telemetry Profile</span>
                    </button>
                </form>
            </section>
        </main>
    </div>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
