<?php
include 'config/db.php';
require_once 'config/upload_cleanup.php';
require_once 'config/mail.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php?error=please_login");
    exit();
}
$error = '';
if (isset($_POST['register_patient'])) {
    $phonePattern = "/^((\+92)|(0092)|(0))?3[0-9]{2}[-?\s]?[0-9]{7}$/";
    // DOB age check — must be at least 16 years old
    $dobAge = 0;
    if (!empty($_POST['dob'])) {
        $dobAge = (int) date_diff(date_create($_POST['dob']), date_create('today'))->y;
    }
    if (
        strlen($_POST["full_address"]) > 7 &&
        strlen($_POST["emergency_contact_name"]) > 3 &&
        preg_match($phonePattern, $_POST["emergency_contact_number"]) &&
        !empty($_POST["gender"]) && !empty($_POST["dob"]) &&
        $dobAge >= 16 &&
        !empty($_POST["blood_group"]) &&
        !empty($_POST["city_id"]) &&
        !empty($_FILES['profile_picture']['name'])
    ) {
        $full_address = $_POST["full_address"];
        $gender = $_POST["gender"];
        $dob = $_POST["dob"];
        $blood_group = $_POST["blood_group"];
        $emergency_contact_name = $_POST["emergency_contact_name"];
        $emergency_contact_number = $_POST["emergency_contact_number"];
        $city_id = $_POST['city_id'];

        $img = $_FILES['profile_picture']['name'];
        $tmp_img = $_FILES['profile_picture']['tmp_name'];
        $image_extension = pathinfo($img,PATHINFO_EXTENSION);
        $newimgname = "patien_" . time() . "_" . rand(1000,9999) . "." . $image_extension;
        $targetfolder = "assets/uploads/patients/";
        $targetpath = $targetfolder . $newimgname;
        
        $userid = $_SESSION['user_id'];
        if(move_uploaded_file($tmp_img,$targetpath)){
            $query = "INSERT INTO patients (user_id,full_address,gender,city_id,date_of_birth,blood_group,emergency_contact_name,emergency_contact_phone,profile_image) VALUES ('$userid','$full_address','$gender','$city_id','$dob','$blood_group','$emergency_contact_name','$emergency_contact_number','$targetpath')";
            $result = mysqli_query($conn,$query);
            if($result){
                $_SESSION['patient_id'] = mysqli_insert_id($conn);
                $_SESSION['role'] = 'Patient';
                $userRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT full_name,email FROM users WHERE user_id=" . (int)$userid));
                if($userRow){
                    sendProfileCompletedEmail($conn, $userRow, 'Patient');
                }
                header("Location: patient/dashboard.php");
                exit();
            }
            else{
                deleteUploadedProfileFile($targetpath, 'patient');
                $error = "Error saving patient telemetry to database.";
            }
        }
        else{
            $error = "Error uploading profile image.";
        }
    }
    else{
        if (!empty($_POST['dob']) && $dobAge < 16) {
            $error = "You must be at least 16 years old to register as a patient.";
        } else {
            $error = "Mandatory fields cannot be empty and must be valid.";
        }
    }
}

$pageTitle = "Patient Telemetry Setup";
include 'includes/header.php';
?>

<div class="auth-page">
    <main class="auth-card" style="width: min(650px, 100%);">
        <div class="eyebrow-badge">PATIENT DISCOVERY SETUP</div>
        <h1>Complete Patient Telemetry</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
            Provide your address, city node, and emergency contact details for mandatory profile enforcement.
        </p>
        
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data" style="display: grid; gap: 18px;">
            <div class="field">
                <label>FULL RESIDENTIAL ADDRESS <span style="color: var(--cyan-neon);">*</span></label>
                <input type="text" name="full_address" required placeholder="House/Apartment #, Street, Area">
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>CITY NODE <span style="color: var(--cyan-neon);">*</span></label>
                    <select name="city_id" required>
                        <option value="">Select City</option>
                        <?php
                            $cityQuery = "SELECT city_id,city_name FROM cities";
                            $cityResult = mysqli_query($conn,$cityQuery);
                            while($city = mysqli_fetch_assoc($cityResult)){
                                echo "<option value='".$city['city_id']."'>".$city['city_name']."</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="field">
                    <label>GENDER <span style="color: var(--cyan-neon);">*</span></label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
<div class="field">
                    <label>DATE OF BIRTH <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="date" name="dob" id="patient_dob" required>
                    <small id="dob_age_msg" style="color: var(--cyan-neon); font-size: 12px; display:none;"></small>
                    <small id="dob_error_msg" style="color: #ff4f4f; font-size: 12px; display:none;">You must be at least 16 years old.</small>
                </div>
<div class="field">
                    <label>BLOOD GROUP <span style="color: var(--cyan-neon);">*</span></label>
                    <select name="blood_group" required>
                        <option value="">— Select Blood Group —</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="Unknown">🩸 Don't Know / Not Sure</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>EMERGENCY CONTACT NAME <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="text" name="emergency_contact_name" required placeholder="Contact Full Name">
                </div>
                <div class="field">
                    <label>EMERGENCY PHONE NUMBER <span style="color: var(--cyan-neon);">*</span></label>
                    <input type="text" name="emergency_contact_number" required placeholder="0300-1234567">
                </div>
            </div>

            <div class="field">
                <label>PROFILE PICTURE SHARD <span style="color: var(--cyan-neon);">*</span></label>
                <input type="file" name="profile_picture" required accept="image/*">
            </div>
            
            <button class="btn btn-primary" type="submit" name="register_patient" id="patient_submit_btn" style="width:100%; margin-top:10px;">
                <span>❖ Finalize Patient Registration</span>
            </button>
        </form>
    </main>
</div>

<script>
(function () {
    const dobInput = document.getElementById('patient_dob');
    const ageMsg   = document.getElementById('dob_age_msg');
    const errMsg   = document.getElementById('dob_error_msg');
    const submitBtn = document.getElementById('patient_submit_btn');

    // Set max date = today (can't pick a future date)
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm   = String(today.getMonth() + 1).padStart(2, '0');
    const dd   = String(today.getDate()).padStart(2, '0');
    dobInput.max = `${yyyy}-${mm}-${dd}`;

    function calcAge(dob) {
        const diff = Date.now() - new Date(dob).getTime();
        return Math.floor(diff / (1000 * 60 * 60 * 24 * 365.25));
    }

    dobInput.addEventListener('change', function () {
        if (!this.value) return;
        const age = calcAge(this.value);
        if (age < 16) {
            errMsg.style.display = 'block';
            ageMsg.style.display = 'none';
            submitBtn.disabled = true;
        } else {
            errMsg.style.display = 'none';
            ageMsg.textContent = `Age: ${age} years`;
            ageMsg.style.display = 'block';
            submitBtn.disabled = false;
        }
    });
})();
</script>

<?php include 'includes/footer.php'; ?>
