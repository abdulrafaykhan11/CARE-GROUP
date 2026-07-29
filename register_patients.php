<?php
include 'config/db.php';
require_once 'config/upload_cleanup.php';
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header("Location: login.php?error=please_login");
    exit();
}
$error = '';
if (isset($_POST['register_patient'])) {
    $phonePattern = "/^((\+92)|(0092)|(0))?3[0-9]{2}[-?\s]?[0-9]{7}$/";
    if (
        strlen($_POST["full_address"]) > 7 &&
        strlen($_POST["emergency_contact_name"]) > 3 &&
        preg_match($phonePattern, $_POST["emergency_contact_number"]) &&
        !empty($_POST["gender"]) && !empty($_POST["dob"]) &&
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
                header("Location: patient/dashboard.php");
                exit();
            }
            else{
                deleteUploadedProfileFile($targetpath, 'patient');
                $error = "Error saving to database.";
            }
        }
        else{
            $error = "Error uploading image.";
        }
    }
    else{
        $error = "Fields can't be empty and must be valid.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Registration | Care Connect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card" style="width: min(600px, 100%);">
        <a class="brand" href="index.php">care<span>connect</span></a>
        <h1>Patient Registration</h1>
        
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="post" enctype="multipart/form-data">
            <div class="field">
                <label>FULL ADDRESS</label>
                <input type="text" name="full_address" required>
            </div>
            
            <div class="form-row">
                <div class="field">
                    <label>CITY</label>
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
                    <label>GENDER</label>
                    <select name="gender" required>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>DATE OF BIRTH</label>
                    <input type="date" name="dob" required>
                </div>
                <div class="field">
                    <label>BLOOD GROUP</label>
                    <select name="blood_group" required>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="field">
                    <label>EMERGENCY CONTACT NAME</label>
                    <input type="text" name="emergency_contact_name" required>
                </div>
                <div class="field">
                    <label>EMERGENCY CONTACT NUMBER</label>
                    <input type="text" name="emergency_contact_number" required placeholder="0300-1234567">
                </div>
            </div>

            <div class="field">
                <label>PROFILE PICTURE</label>
                <input type="file" name="profile_picture" required style="border:none; padding:0; padding-top:10px;">
            </div>
            
            <button class="btn btn-primary" type="submit" name="register_patient" style="width:100%; margin-top:20px;">Complete Registration</button>
        </form>
    </main>
</body>
</html>
