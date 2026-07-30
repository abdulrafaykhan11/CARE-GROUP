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
$pageTitle = 'Edit Appointment Shard';
include 'includes/header.php';
?>

<div class="auth-page" style="min-height: calc(100vh - 120px); padding: 50px 20px;">
    <main class="auth-card" style="width: min(780px, 100%); border-color: var(--border-cyber-glow);">
        <div class="eyebrow-badge">APPOINTMENT RE-FLUX HUD</div>
        <h1>Modify Visit Details</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
            Update visit reason, select a new date and time slot, or review existing appointment telemetry.
        </p>

        <!-- Telemetry Summary Shard -->
        <div style="background: rgba(4, 8, 20, 0.85); border: 1px solid var(--border-cyber); border-radius: var(--radius-md); padding: 20px; margin-bottom: 28px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div>
                <span style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 4px;">PATIENT NAME</span>
                <strong style="color: #FFF; font-size: 15px;"><?=htmlspecialchars($app['patient_name'])?></strong>
            </div>
            <div>
                <span style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 4px;">SPECIALIST DOCTOR</span>
                <strong style="color: var(--cyan-neon); font-size: 15px;">Dr. <?=htmlspecialchars($app['doctor_name'])?></strong>
                <small style="display: block; color: var(--text-muted); font-size: 11px; font-family: var(--font-mono);"><?=htmlspecialchars($app['specialization_name'])?></small>
            </div>
            <div>
                <span style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 4px;">CLINIC LOCATION</span>
                <strong style="color: #FFF; font-size: 15px;"><?=htmlspecialchars($app['clinic_name'])?></strong>
            </div>
            <div>
                <span style="font-family: var(--font-mono); font-size: 11px; color: var(--text-muted); text-transform: uppercase; display: block; margin-bottom: 6px;">VISIT STATUS</span>
                <span class="status status-<?=$app['status']?>"><?=$app['status']?></span>
            </div>
        </div>

        <?php if(!empty($app['reschedule_reason'])): ?>
            <div class="alert alert-error" style="background: rgba(0, 242, 254, 0.08); border-color: rgba(0, 242, 254, 0.3); color: var(--cyan-neon); margin-bottom: 24px;">
                <strong>Last Reschedule Audit:</strong> <?=htmlspecialchars($app['reschedule_reason'])?>
                <span style="display: block; font-size: 11px; opacity: 0.8; margin-top: 4px;">Modified by <?=htmlspecialchars($app['rescheduled_by'])?> at <?=date('d M Y, h:i A', strtotime($app['rescheduled_at']))?></span>
            </div>
        <?php endif; ?>

        <?=$msg?>

        <form method="post" style="display: grid; gap: 18px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 18px;">
                <div class="field">
                    <label>APPOINTMENT DATE <span style="color: var(--cyan-neon);">*</span></label>
                    <input id="date" type="date" name="date" min="<?=date('Y-m-d')?>" value="<?=$app['appointment_date']?>" required>
                </div>
                <div class="field">
                    <label>AVAILABLE TIME SLOT <span style="color: var(--cyan-neon);">*</span></label>
                    <select id="time" name="time" required>
                        <option value="">Select date first</option>
                    </select>
                </div>
            </div>
            
            <p id="scheduleHint" style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon); margin-top: -6px;">Checking clinic schedule...</p>

            <div class="field">
                <label>REASON FOR VISIT <span style="color: var(--cyan-neon);">*</span></label>
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
                <input name="reason" id="reason_input" maxlength="200" style="display:none; margin-top:12px;" placeholder="Please specify your custom reason" disabled>
            </div>

            <div class="field" id="changeReasonWrap" style="display:none; background: rgba(245, 158, 11, 0.06); border: 1px dashed rgba(245, 158, 11, 0.4); padding: 16px; border-radius: var(--radius-sm);">
                <label style="color: #FDE047;">REASON FOR CHANGING DATE / TIME <span style="color: var(--cyan-neon);">*</span></label>
                <textarea name="change_reason" id="changeReason" rows="3" maxlength="255" placeholder="Explain why this appointment date or time is being moved..."></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-top: 10px;">
                <a class="btn btn-outline" href="<?=$returnUrl?>" style="width:100%;">
                    <span>Back to Dashboard</span>
                </a>
                <button class="btn btn-primary" name="update_appointment" type="submit" style="width:100%;">
                    <span>❖ Commit Changes</span>
                </button>
            </div>
        </form>
    </main>
</div>

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
        time.innerHTML = '<option value="">Select a time slot</option>';
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
            time.options[0].text = 'Doctor unavailable on ' + localDay(date.value);
            hint.textContent = '✕ Available days: ' + [...new Set(availability.map(x=>x.day))].join(', ');
        } else {
            hint.textContent = '❖ Unbooked availability slots loaded for ' + localDay(date.value) + '.';
        }
        updateChangeReasonRequirement();
    }

    date.addEventListener('change', slots);
    time.addEventListener('change', updateChangeReasonRequirement);
    toggleOtherReason();
    slots();
</script>

<?php include 'includes/footer.php'; ?>

