<?php
include "config/db.php"; 
require_once "config/upload_cleanup.php";
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php?error=please_login");
    exit();
}
$error = '';
if (isset($_POST["register_doctor"])) {
    $cnicPattern = "/^[0-9]{5}-[0-9]{7}-[0-9]{1}$/";
    $pmdcPattern = "/^[0-9]{4,7}-[A-Za-z]{1}$/";

    if (
        strlen($_POST["full_address"]) >= 8 &&
        !empty($_POST["experience"]) &&
        !empty($_POST["qualification"]) &&
        preg_match($pmdcPattern, $_POST["pmdc"]) &&
        preg_match($cnicPattern, $_POST["cnic"]) &&
        !empty($_POST["fee"]) &&
        !empty($_POST["specialization_id"]) &&
        !empty($_POST["city_id"]) &&
        !empty($_POST["gender"]) &&
        !empty($_POST["dob"]) &&
        !empty($_POST["bio"]) &&
        !empty($_FILES['img']['name']) &&
        !empty($_FILES['license']['name']) &&
        !empty($_FILES['degree']['name'])
    ) {
        $full_address = mysqli_real_escape_string($conn, $_POST["full_address"]);
        $experience = $_POST["experience"];
        $qualification = mysqli_real_escape_string($conn, $_POST["qualification"]);
        $pmdc = $_POST["pmdc"];
        $cnic = $_POST["cnic"];
        $fee = $_POST["fee"];
        $specialization_id = $_POST["specialization_id"];
        $city_id = $_POST["city_id"];
        $dob = $_POST["dob"];
        $bio = mysqli_real_escape_string($conn, $_POST["bio"]);
        $gender = $_POST["gender"];

        $allowedDocExts = array('pdf', 'jpg', 'jpeg', 'png');

        $degree = $_FILES['degree']['name'];
        $tmp_degree = $_FILES['degree']['tmp_name'];
        $degree_extension = strtolower(pathinfo($degree, PATHINFO_EXTENSION));
        $newdegreename = "degree_" . time() . "_" . rand(1000, 9999) . "." . $degree_extension;
        
        $targetdegree = "assets/uploads/doctor/degrees/" . $newdegreename;

        $license = $_FILES['license']['name'];
        $tmp_license = $_FILES['license']['tmp_name'];
        $license_extension = strtolower(pathinfo($license, PATHINFO_EXTENSION));
        $newlicensename = "license_" . time() . "_" . rand(1000, 9999) . "." . $license_extension;
        
        $targetlicense = "assets/uploads/doctor/license/" . $newlicensename;

        $img = $_FILES['img']['name'];
        $tmp_img = $_FILES['img']['tmp_name'];
        $img_extension = strtolower(pathinfo($img, PATHINFO_EXTENSION));
        $newimage = 'doctor_profile_' . time() . "_" . rand(1000, 9999) . "." . $img_extension;
        $targetProfileImg = "assets/uploads/doctor/profile/" . $newimage;

        if (in_array($degree_extension, $allowedDocExts) && in_array($license_extension, $allowedDocExts)) {

            $isImgMoved     = move_uploaded_file($tmp_img, $targetProfileImg);
            $isDegreeMoved  = move_uploaded_file($tmp_degree, $targetdegree);
            $isLicenseMoved = move_uploaded_file($tmp_license, $targetlicense);

            if ($isImgMoved && $isDegreeMoved && $isLicenseMoved) {
                
                $user_id = $_SESSION['user_id'];

                $query = "INSERT INTO doctors (user_id, specialization_id, city_id, full_address, experience_years, qualification, pmdc_registration_number, cnic, license_certificate, degree_certificate, consultation_fee, gender, date_of_birth, profile_image, bio) 
                          VALUES ('$user_id', '$specialization_id', '$city_id', '$full_address', '$experience', '$qualification', '$pmdc', '$cnic', '$targetlicense', '$targetdegree', '$fee', '$gender', '$dob', '$newimage', '$bio')";

                $result = mysqli_query($conn, $query);

                if ($result) {
                    $_SESSION['role'] = 'Doctor';
                    header("Location: doctor/dashboard.php");
                    exit();
                } else {
                    deleteUploadedProfileFile($newimage, 'doctor');
                    deleteUploadedAssetFile($targetlicense);
                    deleteUploadedAssetFile($targetdegree);
                    $error = "Database Error: " . mysqli_error($conn);
                }

            } else {
                deleteUploadedProfileFile($newimage, 'doctor');
                deleteUploadedAssetFile($targetlicense);
                deleteUploadedAssetFile($targetdegree);
                $error = "Error uploading document certificates. Please try again.";
            }

        } else {
            $error = "Degree and License certificates must be PDF, JPG, JPEG, or PNG files.";
        }

    } else {
        $error = "Error: Ensure all required fields, CNIC format, PMDC format, and document certificates are provided.";
    }
}

$pageTitle = "Doctor Practitioner Setup";
include 'includes/header.php';
?>

<div class="auth-page">
    <main class="auth-card" style="width: min(780px, 100%);">
        <div class="eyebrow-badge">DOCTOR COMMAND CENTER SETUP</div>
        <h1>Practitioner Credentials Telemetry</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
            Provide your PMDC credentials, medical degree certificates, and consultation fee for Admin Nexus verification.
        </p>
        
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" style="display: grid; gap: 18px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 18px;">
                <div class="field">
                    <label>CLINICAL / RESIDENTIAL ADDRESS <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="text" name="full_address" required placeholder="Full street address">
                </div>
                <div class="field">
                    <label>EXPERIENCE (YRS) <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="number" name="experience" min="0" required placeholder="5">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>QUALIFICATION <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="text" name="qualification" placeholder="e.g. MBBS, FCPS" required>
                </div>
                <div class="field">
                    <label>PMDC REGISTRATION # <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="text" name="pmdc" placeholder="12345-P / 98765-D" required>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>CNIC NUMBER <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="text" name="cnic" placeholder="42101-1234567-1" required>
                </div>
                <div class="field">
                    <label>CONSULTATION FEE (PKR) <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="number" name="fee" min="0" max="50000" required placeholder="2000">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>LICENSE CERTIFICATE <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="file" name="license" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
                <div class="field">
                    <label>DEGREE CERTIFICATE <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="file" name="degree" accept=".pdf,.jpg,.jpeg,.png" required>
                </div>
            </div>

            <div class="field">
                <label>PROFILE SHARD PHOTO <span style="color: var(--cyan-neon);">*</span></label>
                <input type="file" name="img" accept="image/*" required>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>SPECIALIZATION FIELD <span style="color: var(--cyan-neon);">*</span></label>
                    <select name="specialization_id" required>
                        <option value="">Select Specialty</option>
                        <?php
                        $spec_query = "SELECT specialization_id, specialization_name FROM specializations";
                        $spec_result = mysqli_query($conn, $spec_query);
                        while ($spec = mysqli_fetch_assoc($spec_result)) {
                            echo "<option value='" . $spec['specialization_id'] . "'>" . htmlspecialchars($spec['specialization_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="field">
                    <label>CITY NODE <span style="color: var(--cyan-neon);">*</span></label>
                    <select name="city_id" required>
                        <option value="">Select City</option>
                        <?php
                        $cityQuery = "SELECT city_id, city_name FROM cities";
                        $cityResult = mysqli_query($conn, $cityQuery);
                        while ($city = mysqli_fetch_assoc($cityResult)) {
                            echo "<option value='" . $city['city_id'] . "'>" . htmlspecialchars($city['city_name']) . "</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>GENDER <span style="color: var(--cyan-neon);">*</span></label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="field">
                    <label>DATE OF BIRTH <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="date" name="dob" required>
                </div>
            </div>

            <div class="field">
                <label>PROFESSIONAL BIO <span style="color: var(--cyan-neon);">*</span></label>
                <textarea name="bio" rows="4" placeholder="Detail your clinical background, experience, and patient care philosophy..." required></textarea>
            </div>
            
            <button class="btn btn-primary" type="submit" name="register_doctor" style="width:100%; margin-top:10px;">
                <span>❖ Submit Credentials For Verification</span>
            </button>
        </form>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
