<?php
include 'config/db.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <h1>Patient Registration</h1>
    <form action="" method="post" enctype="multipart/form-data">
        FULLADDRESS : <input type="text" name="full_address"><br>
        GENDER : <select name="gender" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select><br><br>
        CITY : <select name="city_id" required>
            <option value="">Select City</option>
            <?php
                $cityQuery = "SELECT city_id,city_name FROM cities";
                $cityResult = mysqli_query($conn,$cityQuery);
                while($city = mysqli_fetch_assoc($cityResult)){
                    echo "<option value='".$city['city_id']."'>".$city['city_name']."</option>";
                }
            ?>
        </select>
        DATE OF BIRTH : <input type="date" name="dob"><br>
        BLOOD GROUP : <select name="blood_group" required>
            <option value="A+">A+</option>
            <option value="A-">A-</option>
            <option value="B+">B+</option>
            <option value="B-">B-</option>
            <option value="AB+">AB+</option>
            <option value="AB-">AB-</option>
            <option value="O+">O+</option>
            <option value="O-">O-</option>
        </select><br>
        EMERGENCY CONTACT NAME : <input type="text" name="emergency_contact_name"><br>
        EMERGENCY CONTACT NUMBER : <input type="text" name="emergency_contact_number"><br>
        PROFILE PICTURE : <input type="file" name="profile_picture"><br>
        <input type="submit" name="register_patient" value="Register">
    </form>
</body>

</html>
<?php
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
                echo "Profile created successfull";
                echo "<script>window.location.href = 'login.php'</script>";
                exit();
            }
            else{
                echo "Error";
            }
        }
        else{
            echo "error in uploading image";
        }
    }
    else{
        echo "Field's can't be empty or register yourself";
    }
}
?>