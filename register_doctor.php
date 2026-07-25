<?php
include "config/db.php"; 
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
                    $error = "Database Error: " . mysqli_error($conn);
                }

            } else {
                $error = "Error in uploading files. Please try again.";
            }

        } else {
            $error = "Error: Degree and License files must be in PDF, JPG, JPEG, or PNG format.";
        }

    } else {
        $error = "Error: make sure all fields are filled correctly and files are uploaded.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Registration | Care Connect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card" style="width: min(700px, 100%);">
        <a class="brand" href="index.php">care<span>connect</span></a>
        <h1>Doctor Profile Registration</h1>
        
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-row">
                <div class="field">
                    <label>FULL ADDRESS</label>
                    <input type="text" name="full_address" required>
                </div>
                <div class="field">
                    <label>EXPERIENCE YEARS</label>
                    <input type="number" name="experience" min="0" required>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>QUALIFICATION</label>
                    <input type="text" name="qualification" placeholder="MBBS, FCPS" required>
                </div>
                <div class="field">
                    <label>PMDC REGISTRATION NUMBER</label>
                    <input type="text" name="pmdc" placeholder="12345-P / 98765-D" required>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>CNIC NUMBER</label>
                    <input type="text" name="cnic" placeholder="42101-1234567-1" required>
                </div>
                <div class="field">
                    <label>CONSULTATION FEE (PKR)</label>
                    <input type="number" name="fee" min="0" max="50000" required>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>LICENSE CERTIFICATE</label>
                    <input type="file" name="license" accept=".pdf,.jpg,.jpeg,.png" required style="border:none; padding:0; padding-top:10px;">
                </div>
                <div class="field">
                    <label>DEGREE CERTIFICATE</label>
                    <input type="file" name="degree" accept=".pdf,.jpg,.jpeg,.png" required style="border:none; padding:0; padding-top:10px;">
                </div>
            </div>

            <div class="field">
                <label>PROFILE IMAGE</label>
                <input type="file" name="img" accept="image/*" required style="border:none; padding:0; padding-top:10px;">
            </div>

            <div class="form-row">
                <div class="field">
                    <label>SPECIALIZATION</label>
                    <select name="specialization_id" required>
                        <option value="">SELECT</option>
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
                    <label>CITY</label>
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

            <div class="form-row">
                <div class="field">
                    <label>GENDER</label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="field">
                    <label>DATE OF BIRTH</label>
                    <input type="date" name="dob" required>
                </div>
            </div>

            <div class="field">
                <label>BIO</label>
                <textarea name="bio" rows="4" style="width:100%; border:0; border-bottom:2px solid var(--line); font-family:inherit; padding:10px 0; background:transparent;" required></textarea>
            </div>
            
            <button class="btn btn-primary" type="submit" name="register_doctor" style="width:100%; margin-top:20px;">Complete Registration</button>
        </form>
    </main>
</body>
</html>
