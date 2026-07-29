<?php
require_once 'config/db.php';
require_once 'config/appointment_schema.php';
ensureAppointmentChangeSchema($conn);

if(empty($_SESSION['user_id'])){
    header('Location: login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$aid = (int)($_GET['id'] ?? 0);

if(!$aid){
    die('Invalid appointment ID.');
}

$appQuery = "SELECT a.*, d.user_id as doctor_user_id, p.user_id as patient_user_id, u_doc.full_name as doctor_name, u_pat.full_name as patient_name, s.specialization_name, c.clinic_name
             FROM appointments a
             JOIN doctors d ON a.doctor_id = d.doctor_id
             JOIN patients p ON a.patient_id = p.patient_id
             JOIN users u_doc ON d.user_id = u_doc.user_id
             JOIN users u_pat ON p.user_id = u_pat.user_id
             JOIN specializations s ON d.specialization_id = s.specialization_id
             JOIN clinics c ON a.clinic_id = c.clinic_id
             WHERE a.appointment_id = $aid";
$app = mysqli_fetch_assoc(mysqli_query($conn, $appQuery));

if(!$app){
    die('Appointment not found.');
}
if($role === 'Doctor' && $app['doctor_user_id'] != $uid){
    die('Access denied.');
}
if($role === 'Patient' && $app['patient_user_id'] != $uid){
    die('Access denied.');
}
if(!in_array($app['status'], ['Pending', 'Confirmed'], true)){
    die('Only pending or confirmed appointments can be edited.');
}

$doctorId = (int)$app['doctor_id'];
$clinicId = (int)$app['clinic_id'];
$availability = [];
$q = mysqli_query($conn, "SELECT da.*, cl.clinic_name FROM doctor_availability da JOIN clinics cl ON cl.clinic_id=da.clinic_id WHERE da.doctor_id=$doctorId AND da.clinic_id=$clinicId AND da.status='Active'");
while($r = mysqli_fetch_assoc($q)){
    $availability[] = $r;
}

$reserved = [];
$q = mysqli_query($conn, "SELECT appointment_date,appointment_time FROM appointments WHERE doctor_id=$doctorId AND appointment_date>=CURDATE() AND status NOT IN ('Cancelled','NoShow') AND appointment_id != $aid");
while($r = mysqli_fetch_assoc($q)){
    $reserved[] = $r;
}

$msg = '';
if(isset($_POST['update_appointment'])){
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $reason = trim($_POST['reason'] ?? $_POST['reason_dropdown'] ?? '');
    $changeReason = trim($_POST['change_reason'] ?? '');
    $dateTimeChanged = $date !== $app['appointment_date'] || $time !== $app['appointment_time'];

    $valid = false;
    foreach($availability as $a){
        if(date('l', strtotime($date)) === $a['day'] && $time >= $a['start_time'] && $time < $a['end_time']){
            $valid = true;
            break;
        }
    }

    if(!$valid || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)){
        $msg = '<div class="alert alert-error">Please choose an available date and time slot.</div>';
    } elseif($reason === ''){
        $msg = '<div class="alert alert-error">Please add a reason for the visit.</div>';
    } elseif($dateTimeChanged && $changeReason === ''){
        $msg = '<div class="alert alert-error">Please add a reason for changing the appointment date or time.</div>';
    } else {
        $safeDate = mysqli_real_escape_string($conn, $date);
        $safeTime = mysqli_real_escape_string($conn, $time);
        $exists = mysqli_query($conn, "SELECT appointment_id FROM appointments WHERE doctor_id=$doctorId AND appointment_date='$safeDate' AND appointment_time='$safeTime' AND status NOT IN ('Cancelled','NoShow') AND appointment_id != $aid");
        if(mysqli_num_rows($exists)){
            $msg = '<div class="alert alert-error">That slot is already booked. Please choose another time.</div>';
        } else {
            if($dateTimeChanged){
                $stmt = mysqli_prepare($conn, "UPDATE appointments SET appointment_date=?, appointment_time=?, reason=?, reschedule_reason=?, rescheduled_by=?, rescheduled_at=NOW() WHERE appointment_id=?");
                mysqli_stmt_bind_param($stmt, 'sssssi', $date, $time, $reason, $changeReason, $role, $aid);
            } else {
                $stmt = mysqli_prepare($conn, "UPDATE appointments SET reason=? WHERE appointment_id=?");
                mysqli_stmt_bind_param($stmt, 'si', $reason, $aid);
            }
            if(mysqli_stmt_execute($stmt)){
                $msg = '<div class="alert alert-success">Appointment updated successfully.</div>';
                $app['appointment_date'] = $date;
                $app['appointment_time'] = $time;
                $app['reason'] = $reason;
                if($dateTimeChanged){
                    $app['reschedule_reason'] = $changeReason;
                    $app['rescheduled_by'] = $role;
                    $app['rescheduled_at'] = date('Y-m-d H:i:s');
                }
            } else {
                $msg = '<div class="alert alert-error">Could not save changes. Try again.</div>';
            }
        }
    }
}

$returnUrl = $role === 'Doctor' ? 'doctor/dashboard.php' : 'patient/dashboard.php';
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Edit Appointment | Care Connect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
    <main class="dashboard-main" style="max-width: 680px; margin: 50px auto; padding: 0 20px;">
        <header class="dash-header">
            <div>
                <p class="eyebrow">APPOINTMENT DETAILS</p>
                <h1>Edit Appointment</h1>
            </div>
            <a class="btn btn-outline" href="<?=$returnUrl?>">Back to dashboard</a>
        </header>

        <section class="panel">
            <div class="panel-head" style="flex-direction: column; align-items: flex-start; gap: 10px;">
                <p><strong>Patient:</strong> <?=htmlspecialchars($app['patient_name'])?></p>
                <p><strong>Doctor:</strong> Dr. <?=htmlspecialchars($app['doctor_name'])?> (<?=htmlspecialchars($app['specialization_name'])?>)</p>
                <p><strong>Clinic:</strong> <?=htmlspecialchars($app['clinic_name'])?></p>
                <p><strong>Status:</strong> <span class="status status-<?=$app['status']?>"><?=$app['status']?></span></p>
                <?php if(!empty($app['reschedule_reason'])): ?>
                    <p><strong>Last schedule change:</strong> <?=htmlspecialchars($app['reschedule_reason'])?> <span class="note">by <?=htmlspecialchars($app['rescheduled_by'])?></span></p>
                <?php endif; ?>
            </div>

            <?=$msg?>

            <form method="post" class="booking-form" style="margin-top: 20px;">
                <div class="form-row">
                    <div class="field">
                        <label>DATE</label>
                        <input id="date" type="date" name="date" min="<?=date('Y-m-d')?>" value="<?=$app['appointment_date']?>" required>
                    </div>
                    <div class="field">
                        <label>AVAILABLE TIME</label>
                        <select id="time" name="time" required>
                            <option value="">Select date first</option>
                        </select>
                    </div>
                </div>
                <p class="note" id="scheduleHint" style="margin-bottom: 25px;">Checking schedule...</p>

                <div class="field">
                    <label>REASON FOR VISIT</label>
                    <select name="reason_dropdown" id="reason_dropdown" required onchange="toggleOtherReason()">
                        <option value="">Select a reason</option>
                        <option value="General Checkup">General Checkup</option>
                        <option value="Fever / Cold">Fever / Cold</option>
                        <option value="Skin Issues">Skin Issues</option>
                        <option value="Orthopedic Pain">Orthopedic Pain</option>
                        <option value="Headache / Migraine">Headache / Migraine</option>
                        <option value="Heart / Blood Pressure">Heart / Blood Pressure</option>
                        <option value="Pregnancy / Gynecology">Pregnancy / Gynecology</option>
                        <option value="Follow-up">Follow-up</option>
                        <option value="Other">Other</option>
                    </select>
                    <input name="reason" id="reason_input" maxlength="200" style="display:none; margin-top:10px; border-bottom: 2px solid var(--gold);" placeholder="Please specify your reason" disabled>
                </div>

                <div class="field" id="changeReasonWrap" style="display:none;">
                    <label>REASON FOR CHANGING DATE / TIME</label>
                    <textarea name="change_reason" id="changeReason" rows="3" maxlength="255" placeholder="Explain why this appointment needs to be moved"></textarea>
                </div>

                <button class="btn btn-primary" name="update_appointment" style="width:100%">Save Changes</button>
            </form>
        </section>
    </main>

    <script>
        const existingReason = <?=json_encode($app['reason'])?>;
        const originalDate = <?=json_encode($app['appointment_date'])?>;
        const originalTime = <?=json_encode($app['appointment_time'])?>;
        const drop = document.getElementById('reason_dropdown');
        const inp = document.getElementById('reason_input');

        let found = false;
        for(let i=0; i<drop.options.length; i++){
            if(drop.options[i].value === existingReason){
                drop.selectedIndex = i;
                found = true;
                break;
            }
        }
        if(!found && existingReason){
            drop.value = 'Other';
            inp.style.display = 'block';
            inp.disabled = false;
            inp.required = true;
            inp.value = existingReason;
        }

        function toggleOtherReason() {
            const isOther = drop.value === 'Other';
            inp.style.display = isOther ? 'block' : 'none';
            inp.disabled = !isOther;
            inp.required = isOther;
            if(!isOther) inp.value = '';
        }

        const availability = <?=json_encode($availability)?>;
        const reserved = <?=json_encode($reserved)?>;
        const date = document.querySelector('#date');
        const time = document.querySelector('#time');
        const hint = document.querySelector('#scheduleHint');
        const changeReasonWrap = document.getElementById('changeReasonWrap');
        const changeReason = document.getElementById('changeReason');
        const days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

        function localDay(v){ return days[new Date(v+'T00:00:00').getDay()]; }

        function updateChangeReasonRequirement(){
            const changed = date.value !== originalDate || time.value !== originalTime;
            changeReasonWrap.style.display = changed ? 'block' : 'none';
            changeReason.required = changed;
            if(!changed) changeReason.value = '';
        }

        function slots(){
            time.innerHTML = '<option value="">Select a time</option>';
            if(!date.value) return;
            let added = 0;
            availability.filter(x => x.day === localDay(date.value)).forEach(x => {
                let s = x.start_time.split(':').map(Number);
                let e = x.end_time.split(':').map(Number);
                let cur = s[0]*60 + s[1];
                let end = e[0]*60 + e[1];
                let duration = Number(x.slot_duration);

                for(; cur + duration <= end; cur += duration){
                    let h = String(Math.floor(cur/60)).padStart(2,'0');
                    let m = String(cur%60).padStart(2,'0');
                    let v = h + ':' + m + ':00';
                    if(reserved.some(r => r.appointment_date === date.value && r.appointment_time === v)) continue;
                    let label = new Date('2000-01-01T'+v).toLocaleTimeString([],{hour:'numeric',minute:'2-digit'});
                    let opt = new Option(label, v);
                    if(date.value === originalDate && v === originalTime){
                        opt.selected = true;
                    }
                    time.add(opt);
                    added++;
                }
            });

            if(!added){
                time.options[0].text = 'Doctor is unavailable on ' + localDay(date.value);
                hint.textContent = 'Please choose one of these days: ' + [...new Set(availability.map(x=>x.day))].join(', ');
            } else {
                hint.textContent = 'Available unbooked slots loaded for ' + localDay(date.value) + '.';
            }
            updateChangeReasonRequirement();
        }

        date.addEventListener('change', slots);
        time.addEventListener('change', updateChangeReasonRequirement);
        toggleOtherReason();
        slots();
    </script>
</body>
</html>
