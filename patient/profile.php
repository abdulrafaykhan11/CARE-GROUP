<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/upload_cleanup.php';

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Patient') {
    header('Location: ../login.php');
    exit;
}

$uid = (int) $_SESSION['user_id'];
$msg = '';
$msgType = 'success';

$patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*,u.full_name,u.email,u.phone FROM patients p JOIN users u ON u.user_id=p.user_id WHERE p.user_id=$uid"));
if (!$patient) {
    header('Location: ../register_patients.php');
    exit;
}

if (isset($_POST['update_profile'])) {
    $name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['full_address'] ?? '');
    $cityId = (int) ($_POST['city_id'] ?? 0);
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['date_of_birth'] ?? '';
    $blood = $_POST['blood_group'] ?? '';
    $emergencyName = trim($_POST['emergency_contact_name'] ?? '');
    $emergencyPhone = trim($_POST['emergency_contact_phone'] ?? '');
    $newImagePath = '';
    $oldImage = $patient['profile_image'] ?? '';

    if ($name === '' || $email === '' || $phone === '' || $address === '' || !$cityId || $gender === '' || $dob === '' || $blood === '') {
        $msg = 'Please fill all required fields.';
        $msgType = 'error';
    } elseif (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) {
            $msg = 'Profile image must be JPG, PNG, or WEBP.';
            $msgType = 'error';
        } else {
            $dir = __DIR__ . '/../assets/uploads/patients/';
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            $filename = 'patient_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $diskPath = $dir . $filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $diskPath)) {
                $newImagePath = 'assets/uploads/patients/' . $filename;
            } else {
                $msg = 'Could not upload the new profile image.';
                $msgType = 'error';
            }
        }
    }

    if ($msgType !== 'error') {
        $stmt = mysqli_prepare($conn, "UPDATE users SET full_name=?, email=?, phone=? WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $phone, $uid);
        mysqli_stmt_execute($stmt);

        if ($newImagePath !== '') {
            $stmt = mysqli_prepare($conn, "UPDATE patients SET city_id=?, full_address=?, gender=?, date_of_birth=?, blood_group=?, emergency_contact_name=?, emergency_contact_phone=?, profile_image=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, 'isssssssi', $cityId, $address, $gender, $dob, $blood, $emergencyName, $emergencyPhone, $newImagePath, $uid);
        } else {
            $stmt = mysqli_prepare($conn, "UPDATE patients SET city_id=?, full_address=?, gender=?, date_of_birth=?, blood_group=?, emergency_contact_name=?, emergency_contact_phone=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, 'issssssi', $cityId, $address, $gender, $dob, $blood, $emergencyName, $emergencyPhone, $uid);
        }

        if (mysqli_stmt_execute($stmt)) {
            if ($newImagePath !== '' && $oldImage !== $newImagePath) {
                deleteUploadedProfileFile($oldImage, 'patient');
            }
            $_SESSION['full_name'] = $name;
            $msg = 'Profile updated successfully.';
            $patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*,u.full_name,u.email,u.phone FROM patients p JOIN users u ON u.user_id=p.user_id WHERE p.user_id=$uid"));
        } else {
            if ($newImagePath !== '') {
                deleteUploadedProfileFile($newImagePath, 'patient');
            }
            $msg = 'Could not save profile changes.';
            $msgType = 'error';
        }
    }
}

$cities = mysqli_query($conn, "SELECT city_id,city_name FROM cities WHERE status='Active' ORDER BY city_name");
$img = !empty($patient['profile_image']) ? '../' . $patient['profile_image'] : '';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Patient Profile | Care Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="../index.php">care<span>connect</span></a>
        <p class="side-label">PATIENT PORTAL</p>
        <a href="dashboard.php">Overview</a>
        <a href="../find_doctor.php">Find doctors</a>
        <a class="active" href="profile.php">Profile</a>
        <a href="../logout.php">Sign out</a>
    </aside>
    <main class="dashboard-main">
        <header class="dash-header">
            <div><p class="eyebrow">ACCOUNT SETTINGS</p><h1>My Profile</h1></div>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=htmlspecialchars($msg)?></div><?php endif; ?>

        <section class="panel" style="max-width: 820px;">
            <form method="post" enctype="multipart/form-data" class="booking-form">
                <div class="profile-image-editor">
                    <?php if($img): ?><img src="<?=htmlspecialchars($img)?>" onerror="this.style.display='none'" alt=""><?php endif; ?>
                    <div class="field">
                        <label>PROFILE PICTURE</label>
                        <input type="file" name="profile_picture" accept="image/*" style="border:none; padding-top:10px;">
                    </div>
                </div>

                <div class="form-row">
                    <div class="field"><label>FULL NAME</label><input name="full_name" value="<?=htmlspecialchars($patient['full_name'])?>" required></div>
                    <div class="field"><label>EMAIL</label><input type="email" name="email" value="<?=htmlspecialchars($patient['email'])?>" required></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>PHONE</label><input name="phone" value="<?=htmlspecialchars($patient['phone'])?>" required></div>
                    <div class="field"><label>CITY</label><select name="city_id" required><?php while($city = mysqli_fetch_assoc($cities)): ?><option value="<?=$city['city_id']?>" <?=$patient['city_id']==$city['city_id']?'selected':''?>><?=htmlspecialchars($city['city_name'])?></option><?php endwhile; ?></select></div>
                </div>
                <div class="field"><label>FULL ADDRESS</label><input name="full_address" value="<?=htmlspecialchars($patient['full_address'])?>" required></div>
                <div class="form-row">
                    <div class="field"><label>GENDER</label><select name="gender" required><?php foreach(['Male','Female','Other'] as $g): ?><option <?=$patient['gender']===$g?'selected':''?>><?=$g?></option><?php endforeach; ?></select></div>
                    <div class="field"><label>DATE OF BIRTH</label><input type="date" name="date_of_birth" value="<?=htmlspecialchars($patient['date_of_birth'])?>" required></div>
                </div>
                <div class="form-row">
                    <div class="field"><label>BLOOD GROUP</label><select name="blood_group" required><?php foreach(['A+','A-','B+','B-','AB+','AB-','O+','O-'] as $b): ?><option <?=$patient['blood_group']===$b?'selected':''?>><?=$b?></option><?php endforeach; ?></select></div>
                    <div class="field"><label>EMERGENCY CONTACT</label><input name="emergency_contact_name" value="<?=htmlspecialchars($patient['emergency_contact_name'])?>"></div>
                </div>
                <div class="field"><label>EMERGENCY PHONE</label><input name="emergency_contact_phone" value="<?=htmlspecialchars($patient['emergency_contact_phone'])?>"></div>
                <button class="btn btn-primary" name="update_profile" style="width:100%">Save Profile</button>
            </form>
        </section>
    </main>
</body>
</html>
