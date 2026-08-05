<?php
include "../config/db.php";
if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Doctor'){
    header('Location: ../login.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$doc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT doctor_id FROM doctors WHERE user_id=$user_id"));
$doc_id = $doc ? (int)$doc['doctor_id'] : 0;
$msg = '';
$msgType = '';
$savedClinicId = 0;

if(isset($_POST['submit'])){
    $doctor_id = (int)($_POST['doc_id'] ?? 0);
    $is_primary = isset($_POST['is_primary']) && $_POST['is_primary'] !== '' ? (int)$_POST['is_primary'] : null;
    $clinic_id = (int)($_POST['clinic'] ?? 0);

    if($doctor_id !== $doc_id || $is_primary === null){
        $msg = "Fields can't be empty.";
        $msgType = 'error';
    } else {
        if($clinic_id === -1){
            $clinicName = trim($_POST['other_clinic_name'] ?? '');
            $cityId = (int)($_POST['other_city_id'] ?? 0);
            $address = trim($_POST['other_address'] ?? '');
            $phone = trim($_POST['other_phone'] ?? '');
            $email = trim($_POST['other_email'] ?? '');

            if($clinicName === '' || !$cityId || $address === ''){
                $msg = 'Please add clinic name, city, and address.';
                $msgType = 'error';
            } else {
                $safeName = mysqli_real_escape_string($conn, $clinicName);
                $existing = mysqli_fetch_assoc(mysqli_query($conn, "SELECT clinic_id FROM clinics WHERE clinic_name='$safeName' AND city_id=$cityId LIMIT 1"));
                if($existing){
                    $clinic_id = (int)$existing['clinic_id'];
                    $savedClinicId = $clinic_id;
                } else {
                    $stmt = mysqli_prepare($conn, "INSERT INTO clinics (city_id,clinic_name,phone,email,address,status) VALUES (?,?,?,?,?,'Active')");
                    mysqli_stmt_bind_param($stmt, 'issss', $cityId, $clinicName, $phone, $email, $address);
                    if(mysqli_stmt_execute($stmt)){
                        $clinic_id = mysqli_insert_id($conn);
                        $savedClinicId = $clinic_id;
                    } else {
                        $msg = 'Could not add new clinic: ' . mysqli_error($conn);
                        $msgType = 'error';
                    }
                }
            }
        }

        if($clinic_id > 0 && !$msg){
            $already = mysqli_fetch_assoc(mysqli_query($conn, "SELECT doctor_clinic_id FROM doctor_clinic WHERE doctor_id=$doctor_id AND clinic_id=$clinic_id LIMIT 1"));
            if($already){
                $msg = 'This clinic is already attached to your profile. Clinic DB ID: #' . $clinic_id;
                $msgType = 'success';
            } else {
                if($is_primary === 1){
                    mysqli_query($conn, "UPDATE doctor_clinic SET is_primary=0 WHERE doctor_id=$doctor_id");
                }
                $stmt = mysqli_prepare($conn, "INSERT INTO doctor_clinic (doctor_id,clinic_id,is_primary) VALUES (?,?,?)");
                mysqli_stmt_bind_param($stmt, 'iii', $doctor_id, $clinic_id, $is_primary);
                if(mysqli_stmt_execute($stmt)){
                    $msg = 'Clinic saved and linked successfully. Clinic DB ID: #' . $clinic_id;
                    $msgType = 'success';
                } else {
                    $msg = 'Error adding clinic: ' . mysqli_error($conn);
                    $msgType = 'error';
                }
            }
        }
    }
}

$cities = mysqli_query($conn, "SELECT city_id, city_name FROM cities WHERE status='Active' ORDER BY city_name");
$clinicCountRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM clinics WHERE status='Active'"));
$clinicCount = (int)($clinicCountRow['total'] ?? 0);
$clinics = mysqli_query($conn, "SELECT cl.clinic_id, cl.clinic_name, c.city_name FROM clinics cl JOIN cities c ON c.city_id=cl.city_id WHERE cl.status='Active' ORDER BY cl.clinic_id DESC");
$allClinics = mysqli_query($conn, "SELECT cl.clinic_id, cl.clinic_name, c.city_name, cl.address FROM clinics cl JOIN cities c ON c.city_id=cl.city_id WHERE cl.status='Active' ORDER BY cl.clinic_id DESC");
$myClinics = mysqli_query($conn, "SELECT dc.is_primary,cl.clinic_name,c.city_name FROM doctor_clinic dc JOIN clinics cl ON cl.clinic_id=dc.clinic_id JOIN cities c ON c.city_id=cl.city_id WHERE dc.doctor_id=$doc_id ORDER BY dc.is_primary DESC,cl.clinic_name");
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
        <a href="dashboard.php">Home</a>
        <a href="appointments.php">Appointments</a>
        <a href="availability.php">Availability</a>
        <a class="active" href="clinics.php">Clinics</a>
        <a href="profile.php">Profile</a>
        <a href="../logout.php">Sign out</a>
    </aside>
    <main class="dashboard-main">
        <header class="dash-header">
            <div>
                <p class="eyebrow">LOCATIONS</p>
                <h1>Manage Clinics</h1>
            </div>
        </header>

        <section class="panel" style="max-width: 760px;">
            <div class="panel-head">
                <div>
                    <h2>Add Clinic</h2>
                </div>
            </div>

            <?php if($msg): ?>
                <div class="alert alert-<?php echo $msgType; ?>">
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <form action="" method="post" class="booking-form">
                <input type="hidden" name="doc_id" value="<?php echo $doc_id; ?>">
                
                <div class="field">
                    <label>CLINIC NAME</label>
                    <select name="clinic" id="clinicSelect" required>
                        <option value="">Select Clinic</option>
                        <?php while($cli = mysqli_fetch_assoc($clinics)): ?>
                            <option value="<?=$cli['clinic_id']?>">#<?=$cli['clinic_id']?> - <?=htmlspecialchars($cli['clinic_name'])?> &middot; <?=htmlspecialchars($cli['city_name'])?></option>
                        <?php endwhile; ?>
                        <option value="-1">Other / Add my clinic</option>
                    </select>
                </div>

                <div id="otherClinicFields" style="display:none;">
                    <div class="form-row">
                        <div class="field">
                            <label>NEW CLINIC NAME</label>
                            <input type="text" name="other_clinic_name" id="otherClinicName" maxlength="150">
                        </div>
                        <div class="field">
                            <label>CITY</label>
                            <select name="other_city_id" id="otherCity">
                                <option value="">Select City</option>
                                <?php while($city = mysqli_fetch_assoc($cities)): ?>
                                    <option value="<?=$city['city_id']?>" <?=strtolower($city['city_name'])==='karachi'?'selected':''?>><?=htmlspecialchars($city['city_name'])?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label>ADDRESS</label>
                        <input type="text" name="other_address" id="otherAddress" maxlength="255">
                    </div>
                    <div class="form-row">
                        <div class="field">
                            <label>PHONE</label>
                            <input type="text" name="other_phone" maxlength="20">
                        </div>
                        <div class="field">
                            <label>EMAIL</label>
                            <input type="email" name="other_email" maxlength="150">
                        </div>
                    </div>
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

        <section class="panel" style="max-width: 760px; margin-top: 24px;">
            <div class="panel-head"><h2>Your Clinics</h2></div>
            <?php if(mysqli_num_rows($myClinics)): ?>
                <div class="appointment-list">
                    <?php while($row = mysqli_fetch_assoc($myClinics)): ?>
                        <article class="appointment-card" style="grid-template-columns: 1fr auto;">
                            <div>
                                <h3><?=htmlspecialchars($row['clinic_name'])?></h3>
                                <p><?=htmlspecialchars($row['city_name'])?></p>
                            </div>
                            <span class="status status-Confirmed"><?=$row['is_primary'] ? 'Primary' : 'Secondary'?></span>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No clinics added yet</h3>
                    <p>Add at least one clinic before setting availability.</p>
                </div>
            <?php endif; ?>
        </section>

        <section class="panel" style="max-width: 900px; margin-top: 24px;">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">DATABASE CLINICS</p>
                    <h2>All active clinics</h2>
                </div>
                <span class="status status-Confirmed"><?=$clinicCount?> total</span>
            </div>
            <?php if(mysqli_num_rows($allClinics)): ?>
                <div class="clinic-directory-list">
                    <?php while($row = mysqli_fetch_assoc($allClinics)): ?>
                        <article>
                            <strong>#<?=$row['clinic_id']?> - <?=htmlspecialchars($row['clinic_name'])?></strong>
                            <span><?=htmlspecialchars($row['city_name'])?></span>
                            <p><?=htmlspecialchars($row['address'])?></p>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No clinics in database</h3>
                    <p>Add a clinic from the form above.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script>
        const clinicSelect = document.getElementById('clinicSelect');
        const otherFields = document.getElementById('otherClinicFields');
        const otherRequired = ['otherClinicName', 'otherCity', 'otherAddress'].map(id => document.getElementById(id));

        function toggleOtherClinic() {
            const show = clinicSelect.value === '-1';
            otherFields.style.display = show ? 'block' : 'none';
            otherRequired.forEach(field => field.required = show);
        }

        clinicSelect.addEventListener('change', toggleOtherClinic);
        toggleOtherClinic();
    </script>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
