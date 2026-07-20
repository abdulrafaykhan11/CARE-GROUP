<?php
include "../config/db.php";
$user_id = $_SESSION['user_id'];
$doc_query = "SELECT doctor_id FROM doctors WHERE user_id = '$user_id'";
$doc_result = mysqli_query($conn,$doc_query);
if(mysqli_num_rows($doc_result) > 0){
    $row = mysqli_fetch_assoc($doc_result);
    $doc_id = $row['doctor_id'];
}
if(isset($_POST['submit'])){
    if(!empty($_POST['clinic']) && !empty($_POST['is_primary'])){
        $clinic_id = $_POST['clinic'];
        $is_primary = $_POST['is_primary'];
        $doctor_id = $_POST['doc_id'];
        $doc_clinic_query = "INSERT INTO doctor_clinic (doctor_id,clinic_id,is_primary) VALUES ('$doctor_id','$clinic_id','$is_primary')";
        $doc_clinic_result = mysqli_query($conn,$doc_clinic_query);
        if($doc_clinic_result){
            echo "<script>alert('clinic added successfully')</script>";
        }
        else{
            echo "error" . mysqli_error($conn);
        }
    }
    else{
        echo "Fileds can't be empty";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="post">
        <input type="hidden" name="doc_id" value="<?php echo $doc_id; ?>">
        Clinic Name : <select name="clinic" required>
            <option value="">Select</option>
            <?php
            $clinic_query = "SELECT clinic_id,clinic_name FROM clinics";
            $clinic_result = mysqli_query($conn,$clinic_query);
            if(mysqli_num_rows($clinic_result) > 0){
                while($cli = mysqli_fetch_assoc($clinic_result)){
                    echo "<option value='".$cli['clinic_id']."'>".$cli['clinic_name']."</option>";
                }
            }
            ?>
        </select><br><br>
        IS Primary : <select name="is_primary" id="" required>
            <option value="">Select</option>
            <option value="1">YES</option>
            <option value="0">NO</option>
        </select><br><br>
        <input type="submit" name="submit" value="Add clinic"><br>
    </form>
</body>
</html>