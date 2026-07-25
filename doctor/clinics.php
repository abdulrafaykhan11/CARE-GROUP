<?php
include "../config/db.php";
if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Doctor'){
    header('Location: ../login.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$doc_query = "SELECT doctor_id FROM doctors WHERE user_id = '$user_id'";
$doc_result = mysqli_query($conn,$doc_query);
$doc_id = 0;
if(mysqli_num_rows($doc_result) > 0){
    $row = mysqli_fetch_assoc($doc_result);
    $doc_id = $row['doctor_id'];
}
$msg = '';
$msgType = '';
if(isset($_POST['submit'])){
    if(!empty($_POST['clinic']) && isset($_POST['is_primary']) && $_POST['is_primary'] !== ''){
        $clinic_id = (int)$_POST['clinic'];
        $is_primary = (int)$_POST['is_primary'];
        $doctor_id = (int)$_POST['doc_id'];
        $doc_clinic_query = "INSERT INTO doctor_clinic (doctor_id,clinic_id,is_primary) VALUES ('$doctor_id','$clinic_id','$is_primary')";
        $doc_clinic_result = mysqli_query($conn,$doc_clinic_query);
        if($doc_clinic_result){
            $msg = 'Clinic added successfully.';
            $msgType = 'success';
        }
        else{
            $msg = 'Error adding clinic: ' . mysqli_error($conn);
            $msgType = 'error';
        }
    }
    else{
        $msg = "Fields can't be empty.";
        $msgType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clinics | Care Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="../index.php">care<span>connect</span></a>
        <p class="side-label">DOCTOR PORTAL</p>
        <a href="dashboard.php">⌂ Overview</a>
        <a href="appointments.php">▣ Appointments</a>
        <a href="availability.php">◷ Availability</a>
        <a class="active" href="clinics.php">⌖ Clinics</a>
        <a href="profile.php">⚙ Profile</a>
        <a href="../logout.php">↪ Sign out</a>
    </aside>
    <main class="dashboard-main">
        <header class="dash-header">
            <div>
                <p class="eyebrow">LOCATIONS</p>
                <h1>Manage Clinics</h1>
            </div>
        </header>

        <section class="panel" style="max-width: 600px;">
            <div class="panel-head">
                <div>
                    <h2>Add New Clinic</h2>
                </div>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msgType; ?>">
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" class="booking-form">
                <input type="hidden" name="doc_id" value="<?php echo $doc_id; ?>">
                
                <div class="field">
                    <label>CLINIC NAME</label>
                    <select name="clinic" required>
                        <option value="">Select Clinic</option>
                        <?php
                        $clinic_query = "SELECT clinic_id, clinic_name FROM clinics";
                        $clinic_result = mysqli_query($conn, $clinic_query);
                        if(mysqli_num_rows($clinic_result) > 0){
                            while($cli = mysqli_fetch_assoc($clinic_result)){
                                echo "<option value='".$cli['clinic_id']."'>".htmlspecialchars($cli['clinic_name'])."</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div class="field">
                    <label>IS PRIMARY LOCATION?</label>
                    <select name="is_primary" required>
                        <option value="">Select</option>
                        <option value="1">YES</option>
                        <option value="0">NO</option>
                    </select>
                </div>

                <button type="submit" name="submit" class="btn btn-primary" style="margin-top: 15px;">Add Clinic</button>
            </form>
        </section>
    </main>
</body>
</html>