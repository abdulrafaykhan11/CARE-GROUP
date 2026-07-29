<?php
require_once 'config/db.php';
require_once 'config/availability_schema.php';
require_once 'config/content_templates.php';
ensureClinicAvailabilitySchema($conn);

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Patient') {
  header('Location: login.php');
  exit;
}

$uid = (int) $_SESSION['user_id'];
$patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT patient_id FROM patients WHERE user_id=$uid"));
$doctorId = (int) ($_GET['doctor_id'] ?? 0);
$doctor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT d.*,u.full_name,u.email,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id WHERE d.doctor_id=$doctorId AND u.status='Active' AND d.verification_status='Verified'"));

if (!$patient) {
  header('Location: register_patients.php');
  exit;
}
if (!$doctor) {
  http_response_code(404);
  exit('Doctor not found.');
}

$availability = [];
$q = mysqli_query($conn, "SELECT da.*,cl.clinic_name FROM doctor_availability da JOIN clinics cl ON cl.clinic_id=da.clinic_id WHERE da.doctor_id=$doctorId AND da.status='Active' AND cl.status='Active' AND da.clinic_id IS NOT NULL");
while ($r = mysqli_fetch_assoc($q)) {
  $availability[] = $r;
}

$clinicNames = [];
$clinicRows = mysqli_query($conn, "SELECT cl.clinic_name,c.city_name,dc.is_primary FROM doctor_clinic dc JOIN clinics cl ON cl.clinic_id=dc.clinic_id JOIN cities c ON c.city_id=cl.city_id WHERE dc.doctor_id=$doctorId AND cl.status='Active' ORDER BY dc.is_primary DESC,cl.clinic_name");
while ($clinicRow = mysqli_fetch_assoc($clinicRows)) {
  $clinicNames[] = $clinicRow['clinic_name'] . ' - ' . $clinicRow['city_name'];
}

$reserved = [];
$q = mysqli_query($conn, "SELECT appointment_date,appointment_time FROM appointments WHERE doctor_id=$doctorId AND appointment_date>=CURDATE() AND status NOT IN ('Cancelled','NoShow')");
while ($r = mysqli_fetch_assoc($q)) {
  $reserved[] = $r;
}

$msg = '';
if (isset($_POST['book'])) {
  $clinic = (int) $_POST['clinic_id'];
  $date = $_POST['date'] ?? '';
  $time = $_POST['time'] ?? '';
  $reason = trim($_POST['reason'] ?? $_POST['reason_dropdown'] ?? '');
  $notes = trim($_POST['notes'] ?? '');
  $valid = false;

  foreach ($availability as $a) {
    if ($a['clinic_id'] == $clinic && date('l', strtotime($date)) === $a['day'] && $time >= $a['start_time'] && $time < $a['end_time']) {
      $valid = true;
      break;
    }
  }

  if (!$valid || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
    $msg = '<div class="alert alert-error">Please choose an available date and time slot.</div>';
  } elseif ($reason === '') {
    $msg = '<div class="alert alert-error">Please add a reason for your visit.</div>';
  } else {
    $safeDate = mysqli_real_escape_string($conn, $date);
    $safeTime = mysqli_real_escape_string($conn, $time);
    $exists = mysqli_query($conn, "SELECT appointment_id FROM appointments WHERE doctor_id=$doctorId AND appointment_date='$safeDate' AND appointment_time='$safeTime' AND status NOT IN ('Cancelled','NoShow')");
    if (mysqli_num_rows($exists)) {
      $msg = '<div class="alert alert-error">This slot was just booked. Please choose another time.</div>';
    } else {
      $stmt = mysqli_prepare($conn, 'INSERT INTO appointments (doctor_id,patient_id,clinic_id,appointment_date,appointment_time,reason,notes) VALUES (?,?,?,?,?,?,?)');
      mysqli_stmt_bind_param($stmt, 'iiissss', $doctorId, $patient['patient_id'], $clinic, $date, $time, $reason, $notes);
      if (mysqli_stmt_execute($stmt)) {
        require_once 'config/mail.php';
        $clinicName = '';
        foreach ($availability as $a) {
          if ($a['clinic_id'] == $clinic) {
            $clinicName = $a['clinic_name'];
            break;
          }
        }
        $pemail = mysqli_fetch_assoc(mysqli_query($conn, "SELECT email FROM users WHERE user_id=$uid"))['email'];
        $mailOk = sendAppointmentEmail(['patient_email' => $pemail, 'patient_name' => $_SESSION['full_name'], 'doctor_email' => $doctor['email'], 'doctor_name' => $doctor['full_name'], 'date' => date('d M Y', strtotime($date)), 'time' => date('h:i A', strtotime($time)), 'clinic' => $clinicName]);
        $msg = '<div class="alert alert-success">Appointment request sent successfully.' . ($mailOk ? ' Confirmation email sent.' : '') . '</div>';
      } else {
        $msg = '<div class="alert alert-error">Could not save your request.</div>';
      }
    }
  }
}

$img = 'assets/uploads/doctor/profile/' . basename($doctor['profile_image'] ?? '');
$profileSections = doctorLongProfileSections($doctor, $clinicNames, $availability);
$profileFaqs = doctorFaqs($doctor);
?><!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Book Dr. <?= htmlspecialchars($doctor['full_name']) ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.php">care<span>connect</span></a>
    <nav>
      <a href="patient/dashboard.php">My dashboard</a>
      <a class="btn btn-outline" href="find_doctor.php">Back to doctors</a>
    </nav>
  </header>

  <main class="doctor-profile-page">
    <section class="doctor-profile-hero">
      <div class="doctor-profile-photo">
        <img src="<?= htmlspecialchars($img) ?>" onerror="this.style.display='none'" alt="">
      </div>
      <div class="doctor-profile-intro">
        <p class="eyebrow"><?= htmlspecialchars($doctor['specialization_name']) ?></p>
        <h1>Dr. <?= htmlspecialchars($doctor['full_name']) ?></h1>
        <p><?= htmlspecialchars($doctor['qualification']) ?> &middot; <?= intval($doctor['experience_years']) ?> years experience &middot; <?= htmlspecialchars($doctor['city_name']) ?></p>
        <div class="doctor-profile-metrics">
          <article><span>Fee</span><strong>PKR <?= number_format($doctor['consultation_fee']) ?></strong></article>
          <article><span>Status</span><strong>Verified</strong></article>
          <article><span>Clinics</span><strong><?= count($clinicNames) ?></strong></article>
        </div>
      </div>
    </section>

    <section class="doctor-profile-layout">
      <div class="doctor-content-sections">
        <section class="doctor-story-panel">
          <p class="eyebrow">PROFILE OVERVIEW</p>
          <h2>About Dr. <?= htmlspecialchars($doctor['full_name']) ?></h2>
          <p><?= htmlspecialchars(trim($doctor['bio'] ?? '') ?: 'This verified doctor profile gives patients a clearer view of qualification, experience, clinic timing, fee, and booking expectations before they request an appointment.') ?></p>
        </section>

        <?php foreach ($profileSections as $title => $paragraphs): ?>
          <section class="doctor-story-panel">
            <h2><?= htmlspecialchars($title) ?></h2>
            <?php foreach ($paragraphs as $paragraph): ?>
              <p><?= htmlspecialchars($paragraph) ?></p>
            <?php endforeach; ?>
          </section>
        <?php endforeach; ?>

        <section class="doctor-story-panel">
          <div class="section-heading compact-heading">
            <div><p class="eyebrow">CLINICS & TIMINGS</p><h2>Where this doctor is available</h2></div>
          </div>
          <div class="doctor-schedule-grid">
            <?php if ($availability): ?>
              <?php foreach ($availability as $a): ?>
                <article>
                  <strong><?= htmlspecialchars($a['clinic_name']) ?></strong>
                  <span><?= htmlspecialchars($a['day']) ?></span>
                  <small><?= date('h:i A', strtotime($a['start_time'])) ?> - <?= date('h:i A', strtotime($a['end_time'])) ?> &middot; <?= intval($a['slot_duration']) ?> min slots</small>
                </article>
              <?php endforeach; ?>
            <?php else: ?>
              <article><strong>No active schedule yet</strong><span>Check again later or choose another verified doctor.</span></article>
            <?php endif; ?>
          </div>
        </section>

        <section class="doctor-story-panel doctor-faqs">
          <p class="eyebrow">PATIENT QUESTIONS</p>
          <h2>FAQs about this profile</h2>
          <?php foreach ($profileFaqs as $question => $answer): ?>
            <details><summary><?= htmlspecialchars($question) ?></summary><p><?= htmlspecialchars($answer) ?></p></details>
          <?php endforeach; ?>
        </section>
      </div>

      <aside class="doctor-booking-panel">
        <section class="booking-form">
          <p class="eyebrow">REQUEST A VISIT</p>
          <h2>Choose a convenient time</h2><?= $msg ?>
          <form method="post">
            <div class="field"><label>CLINIC</label><select name="clinic_id" id="clinic" required>
                <option value="">Choose clinic</option><?php foreach ($availability as $a): ?>
                  <option value="<?= $a['clinic_id'] ?>"><?= htmlspecialchars($a['clinic_name']) ?> &middot; <?= $a['day'] ?> (<?= date('h:i A', strtotime($a['start_time'])) ?>-<?= date('h:i A', strtotime($a['end_time'])) ?>)</option>
                <?php endforeach; ?>
              </select></div>
            <div class="form-row">
              <div class="field"><label>DATE</label><input id="date" type="date" name="date" min="<?= date('Y-m-d') ?>" required></div>
              <div class="field"><label>AVAILABLE TIME</label><select id="time" name="time" required><option value="">Choose a clinic and date</option></select></div>
            </div>
            <p class="note" id="scheduleHint" style="margin-bottom: 25px;">Choose a clinic and date to see slots.</p>
            <div class="field"><label>REASON FOR VISIT</label><select name="reason_dropdown" id="reasonDropdown" required>
                <option value="">Select a reason</option>
                <option>General Checkup</option>
                <option>Fever / Cold</option>
                <option>Skin Issues</option>
                <option>Orthopedic Pain</option>
                <option>Headache / Migraine</option>
                <option>Heart / Blood Pressure</option>
                <option>Pregnancy / Gynecology</option>
                <option>Follow-up</option>
                <option value="Other">Other</option>
              </select><input name="reason" id="otherReason" maxlength="200" style="display:none;margin-top:10px" placeholder="Please specify your reason" disabled></div>
            <div class="field"><label>NOTES (OPTIONAL)</label><textarea name="notes" rows="3" maxlength="1000"></textarea></div>
            <button class="btn btn-primary" name="book" style="width:100%">Request appointment</button>
          </form>
        </section>
      </aside>
    </section>
  </main>

  <script>
    const availability = <?= json_encode($availability) ?>, reserved = <?= json_encode($reserved) ?>, clinic = document.querySelector('#clinic'), date = document.querySelector('#date'), time = document.querySelector('#time'), hint = document.querySelector('#scheduleHint'), days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    const dayFor = v => days[new Date(v + 'T00:00:00').getDay()], ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'), schedules = () => availability.filter(x => String(x.clinic_id) === String(clinic.value));
    function slots() {
      time.innerHTML = '<option value="">Select a time</option>';
      if (!clinic.value || !date.value) return;
      let added = 0;
      schedules().filter(x => x.day === dayFor(date.value)).forEach(x => {
        let s = x.start_time.split(':').map(Number), e = x.end_time.split(':').map(Number), cur = s[0] * 60 + s[1], end = e[0] * 60 + e[1], dur = Number(x.slot_duration);
        for (; cur + dur <= end; cur += dur) {
          let v = String(Math.floor(cur / 60)).padStart(2, '0') + ':' + String(cur % 60).padStart(2, '0') + ':00';
          if (reserved.some(r => r.appointment_date === date.value && r.appointment_time === v)) continue;
          time.add(new Option(new Date('2000-01-01T' + v).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }), v));
          added++;
        }
      });
      hint.textContent = added ? 'Only unbooked slots are shown.' : 'No unbooked slots remain for this date.';
    }
    function chooseNextDate() {
      let active = schedules().map(x => x.day), d = new Date();
      d.setHours(0, 0, 0, 0);
      for (let i = 0; i < 14; i++, d.setDate(d.getDate() + 1)) if (active.includes(days[d.getDay()])) { date.value = ymd(d); break }
    }
    clinic.addEventListener('change', () => { chooseNextDate(); slots() });
    date.addEventListener('change', slots);
    document.querySelector('#reasonDropdown').addEventListener('change', e => {
      let o = document.querySelector('#otherReason'), other = e.target.value === 'Other';
      o.style.display = other ? 'block' : 'none';
      o.disabled = !other;
      o.required = other;
      if (!other) o.value = '';
    });
  </script>
</body>
</html>
