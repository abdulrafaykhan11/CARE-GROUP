<?php
require_once 'config/db.php';
require_once 'config/availability_schema.php';
require_once 'config/appointment_schema.php';
require_once 'config/content_templates.php';
ensureClinicAvailabilitySchema($conn);
ensureAppointmentChangeSchema($conn);

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
  exit('Doctor shard profile not found.');
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
  $symptomPhotoPath = null;
  $photoError = null;

  foreach ($availability as $a) {
    if ($a['clinic_id'] == $clinic && date('l', strtotime($date)) === $a['day'] && $time >= $a['start_time'] && $time < $a['end_time']) {
      $valid = true;
      break;
    }
  }

  if (!$valid || !preg_match('/^\d{2}:\d{2}:\d{2}$/', $time)) {
    $msg = '<div class="alert alert-error">Please choose an active availability flux slot.</div>';
  } elseif ($reason === '') {
    $msg = '<div class="alert alert-error">Please select or enter your visit reason.</div>';
  } else {
    [$symptomPhotoPath, $photoError] = saveAppointmentSymptomPhoto($_FILES, $_POST, __DIR__);
    if ($photoError) {
      $msg = '<div class="alert alert-error">' . htmlspecialchars($photoError) . '</div>';
    } else {
    $safeDate = mysqli_real_escape_string($conn, $date);
    $safeTime = mysqli_real_escape_string($conn, $time);
    $exists = mysqli_query($conn, "SELECT appointment_id FROM appointments WHERE doctor_id=$doctorId AND appointment_date='$safeDate' AND appointment_time='$safeTime' AND status NOT IN ('Cancelled','NoShow')");
    if (mysqli_num_rows($exists)) {
      $msg = '<div class="alert alert-error">This slot was just booked by another patient. Choose another time slot.</div>';
    } else {
      $stmt = mysqli_prepare($conn, 'INSERT INTO appointments (doctor_id,patient_id,clinic_id,appointment_date,appointment_time,reason,notes,symptom_photo_path) VALUES (?,?,?,?,?,?,?,?)');
      mysqli_stmt_bind_param($stmt, 'iiisssss', $doctorId, $patient['patient_id'], $clinic, $date, $time, $reason, $notes, $symptomPhotoPath);
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
        $msg = '<div class="alert alert-success">Appointment request transmitted successfully.' . ($mailOk ? ' Confirmation email dispatched.' : '') . '</div>';
      } else {
        $msg = '<div class="alert alert-error">Transmission failed. Could not register appointment.</div>';
      }
    }
    }
  }
}

$img = 'assets/uploads/doctor/profile/' . basename($doctor['profile_image'] ?? '');
$profileSections = doctorLongProfileSections($doctor, $clinicNames, $availability);

$doctorSpecId = (int)($doctor['specialization_id'] ?? 0);
$dbFaqQuery = mysqli_query($conn, "SELECT question, answer FROM specialization_faqs WHERE specialization_id = $doctorSpecId AND status = 'Active' ORDER BY sort_order ASC, faq_id ASC");
$profileFaqs = [];
if ($dbFaqQuery) {
  while ($faqRow = mysqli_fetch_assoc($dbFaqQuery)) {
    $profileFaqs[$faqRow['question']] = $faqRow['answer'];
  }
}
if (empty($profileFaqs)) {
  $profileFaqs = doctorFaqs($doctor);
}

$pageTitle = 'Dr. ' . $doctor['full_name'] . ' Profile Shard';
include 'includes/header.php';
?>

<main class="doctor-profile-page">
  <!-- Doctor Hero Banner -->
  <section class="doctor-profile-hero">
    <div class="doctor-profile-photo">
      <img src="<?= htmlspecialchars($img) ?>" onerror="this.src='https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300'" alt="Dr. <?= htmlspecialchars($doctor['full_name']) ?>">
    </div>
    
    <div class="doctor-profile-intro">
      <div class="eyebrow" style="color: var(--cyan-neon);"><?= htmlspecialchars($doctor['specialization_name']) ?></div>
      <h1 style="font-size: 36px; margin: 6px 0;">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h1>
      <p style="color: var(--text-muted); font-size: 16px; margin: 0 0 16px;">
        <?= htmlspecialchars($doctor['qualification']) ?> &middot; <?= intval($doctor['experience_years']) ?> Years Experience &middot; <?= htmlspecialchars($doctor['city_name']) ?> Node
      </p>
      
      <div class="doctor-profile-metrics">
        <article>
          <span>CONSULTATION FEE</span>
          <strong>PKR <?= number_format($doctor['consultation_fee']) ?></strong>
        </article>
        <article>
          <span>VERIFICATION SEAL</span>
          <strong style="color: var(--emerald-bio);">VERIFIED ❖</strong>
        </article>
        <article>
          <span>CLINIC NODES</span>
          <strong><?= count($clinicNames) ?></strong>
        </article>
      </div>
    </div>
  </section>

  <!-- Layout: Story Panels & Cyber Booking HUD -->
  <section class="doctor-profile-layout">
    <div class="doctor-content-sections">
      <section class="doctor-story-panel">
        <p class="eyebrow">BIO-METRIC SUMMARY</p>
        <h2 style="font-size: 24px; margin-bottom: 14px;">About Dr. <?= htmlspecialchars($doctor['full_name']) ?></h2>
        <p style="color: var(--text-muted); font-size: 15px; line-height: 1.8;">
          <?= htmlspecialchars(trim($doctor['bio'] ?? '') ?: 'This verified practitioner profile gives patients transparent telemetry regarding experience, qualifications, clinic timings, and direct appointment booking.') ?>
        </p>
      </section>

      <?php foreach ($profileSections as $title => $paragraphs): ?>
        <section class="doctor-story-panel">
          <h2 style="font-size: 22px; color: var(--cyan-neon); margin-bottom: 14px;"><?= htmlspecialchars($title) ?></h2>
          <?php foreach ($paragraphs as $paragraph): ?>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7; margin-bottom: 10px;"><?= htmlspecialchars($paragraph) ?></p>
          <?php endforeach; ?>
        </section>
      <?php endforeach; ?>

      <!-- Availability Flux Schedule Timings -->
      <section class="doctor-story-panel">
        <p class="eyebrow">AVAILABILITY FLUX TIMELINE</p>
        <h2 style="font-size: 22px; margin-bottom: 18px;">Active Schedule Slots</h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
          <?php if ($availability): ?>
            <?php foreach ($availability as $a): ?>
              <div style="background: var(--bg-card); border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); padding: 16px;">
                <strong style="color: var(--text-main); font-size: 15px; display: block;"><?= htmlspecialchars($a['clinic_name']) ?></strong>
                <span style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon); font-weight: 700; text-transform: uppercase; display: block; margin: 4px 0;"><?= htmlspecialchars($a['day']) ?></span>
                <small style="color: var(--text-muted); font-family: var(--font-mono); font-size: 11px;">
                  <?= date('h:i A', strtotime($a['start_time'])) ?> - <?= date('h:i A', strtotime($a['end_time'])) ?> (<?= intval($a['slot_duration']) ?> min slots)
                </small>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p style="color: var(--text-muted);">No active availability schedule configured yet.</p>
          <?php endif; ?>
        </div>
      </section>

      <?php if (!empty($profileFaqs)): ?>
        <!-- Specialty & Practice FAQs Panel -->
        <section class="doctor-story-panel">
          <p class="eyebrow" style="color: var(--cyan-neon);">PATIENT CARE & PRACTICE FAQS</p>
          <h2 style="font-size: 22px; margin-bottom: 18px; color: var(--text-main);">Frequently Asked Questions</h2>
          <div class="faq-accordion" style="display: grid; gap: 12px;">
            <?php foreach ($profileFaqs as $q => $a): ?>
              <details style="background: var(--bg-card); border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); padding: 14px 18px; cursor: pointer;">
                <summary style="font-family: var(--font-heading); font-weight: 600; color: var(--text-main); font-size: 15px; outline: none; display: flex; justify-content: space-between; align-items: center;">
                  <span><?= htmlspecialchars($q) ?></span>
                  <span style="color: var(--cyan-neon); font-family: var(--font-mono); font-size: 18px;">+</span>
                </summary>
                <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7; margin-top: 12px; margin-bottom: 0;">
                  <?= htmlspecialchars($a) ?>
                </p>
              </details>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>
    </div>

    <!-- Booking Form HUD -->
    <aside class="doctor-booking-panel">
      <p class="eyebrow">PATIENT APPOINTMENT HUD</p>
      <h2 style="font-size: 24px; margin-bottom: 16px;">Schedule Visit</h2>
      <?= $msg ?>
      
      <form method="post" enctype="multipart/form-data" style="display: grid; gap: 16px;">
        <div class="field">
          <label>SELECT CLINIC NODE</label>
          <select name="clinic_id" id="clinic" required>
            <option value="">Choose Clinic</option>
            <?php foreach ($availability as $a): ?>
              <option value="<?= $a['clinic_id'] ?>"><?= htmlspecialchars($a['clinic_name']) ?> (<?= $a['day'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label>APPOINTMENT DATE</label>
          <input id="date" type="date" name="date" min="<?= date('Y-m-d') ?>" required>
        </div>

        <div class="field">
          <label>AVAILABILITY FLUX SLOT</label>
          <select id="time" name="time" required>
            <option value="">Select Clinic & Date First</option>
          </select>
        </div>
        <p style="font-family: var(--font-mono); font-size: 11px; color: var(--cyan-neon);" id="scheduleHint">Choose clinic and date to generate time slots.</p>

        <div class="field">
          <label>REASON FOR VISIT</label>
          <select name="reason_dropdown" id="reasonDropdown" required>
            <option value="">Select Reason</option>
            <option>General Checkup</option>
            <option>Fever / Cold</option>
            <option>Skin Issues</option>
            <option>Orthopedic Pain</option>
            <option>Headache / Migraine</option>
            <option>Heart / Blood Pressure</option>
            <option>Pregnancy / Gynecology</option>
            <option>Follow-up</option>
            <option value="Other">Other Reason</option>
          </select>
          <input name="reason" id="otherReason" maxlength="200" style="display:none; margin-top:10px" placeholder="Specify your medical reason" disabled>
        </div>

        <div class="field">
          <label>PATIENT NOTES (OPTIONAL)</label>
          <textarea name="notes" rows="3" placeholder="Share symptoms or prior records..."></textarea>
        </div>

        <section class="symptom-capture" data-symptom-capture>
          <div>
            <p class="eyebrow" style="margin-bottom: 10px;">ATTACH SYMPTOM PHOTO (OPTIONAL)</p>
            <p style="margin: 0 0 14px; color: var(--text-muted); font-size: 13px; line-height: 1.6;">Add a clear photo of a visible symptom so the doctor can review it before your visit.</p>
          </div>

          <div class="symptom-choice-grid">
            <button type="button" class="symptom-choice" data-capture-mode="camera">Capture via Webcam</button>
            <button type="button" class="symptom-choice" data-capture-mode="upload">Upload Image File</button>
          </div>

          <div class="symptom-panel" id="cameraPanel" hidden>
            <video id="symptomVideo" class="symptom-video" autoplay playsinline muted></video>
            <canvas id="symptomCanvas" hidden></canvas>
            <div class="symptom-actions">
              <button type="button" class="btn btn-primary" id="snapPhotoBtn">Snap Photo</button>
              <button type="button" class="btn btn-outline" id="stopCameraBtn">Close Camera</button>
            </div>
            <p class="form-hint" id="cameraStatus">Camera preview will appear here after permission is granted.</p>
          </div>

          <div class="symptom-panel" id="uploadPanel" hidden>
            <div class="field">
              <label>UPLOAD IMAGE FILE</label>
              <input type="file" name="symptom_photo" id="symptomFile" accept="image/jpeg,image/png,image/webp">
            </div>
            <p class="form-hint">Accepted formats: JPG, PNG, WEBP. Maximum size: 5MB.</p>
          </div>

          <figure class="symptom-preview" id="symptomPreviewWrap" hidden>
            <img id="symptomPreview" alt="Selected symptom preview">
            <figcaption>
              <span id="symptomPreviewLabel">Photo ready</span>
              <button type="button" class="btn btn-outline" id="retakePhotoBtn">Retake</button>
            </figcaption>
          </figure>

          <input type="hidden" name="symptom_photo_data" id="symptomPhotoData">
        </section>

        <button class="btn btn-primary" name="book" style="width:100%; margin-top: 10px;">
          <span>❖ Transmit Booking Request</span>
        </button>
      </form>
    </aside>
  </section>
</main>

<script>
  const availability = <?= json_encode($availability) ?>, reserved = <?= json_encode($reserved) ?>, clinic = document.querySelector('#clinic'), date = document.querySelector('#date'), time = document.querySelector('#time'), hint = document.querySelector('#scheduleHint'), days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  const dayFor = v => days[new Date(v + 'T00:00:00').getDay()], ymd = d => d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'), schedules = () => availability.filter(x => String(x.clinic_id) === String(clinic.value));
  function slots() {
    time.innerHTML = '<option value="">Select a time slot</option>';
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
    hint.textContent = added ? '❖ ' + added + ' Availability flux slots available.' : '✕ No unbooked slots remain for this date.';
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

  const symptomCapture = (() => {
    const root = document.querySelector('[data-symptom-capture]');
    if (!root) return;
    const cameraPanel = document.querySelector('#cameraPanel');
    const uploadPanel = document.querySelector('#uploadPanel');
    const video = document.querySelector('#symptomVideo');
    const canvas = document.querySelector('#symptomCanvas');
    const status = document.querySelector('#cameraStatus');
    const dataInput = document.querySelector('#symptomPhotoData');
    const fileInput = document.querySelector('#symptomFile');
    const previewWrap = document.querySelector('#symptomPreviewWrap');
    const preview = document.querySelector('#symptomPreview');
    const previewLabel = document.querySelector('#symptomPreviewLabel');
    const choices = root.querySelectorAll('[data-capture-mode]');
    let stream = null;
    let currentMode = '';

    const setStatus = message => {
      if (status) status.textContent = message;
    };

    const stopCamera = () => {
      if (stream) {
        stream.getTracks().forEach(track => track.stop());
        stream = null;
      }
      if (video) video.srcObject = null;
    };

    const showPreview = (src, label) => {
      preview.src = src;
      previewLabel.textContent = label;
      previewWrap.hidden = false;
    };

    const clearPreview = () => {
      preview.removeAttribute('src');
      previewWrap.hidden = true;
      dataInput.value = '';
      if (fileInput) fileInput.value = '';
    };

    const setMode = async mode => {
      currentMode = mode;
      choices.forEach(btn => btn.classList.toggle('is-active', btn.dataset.captureMode === mode));
      cameraPanel.hidden = mode !== 'camera';
      uploadPanel.hidden = mode !== 'upload';
      clearPreview();

      if (mode === 'camera') {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          setStatus('Your browser does not support webcam capture. Please upload an image file.');
          return;
        }
        try {
          stopCamera();
          stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false });
          video.srcObject = stream;
          setStatus('Camera ready. Frame the visible symptom clearly, then snap a photo.');
        } catch (error) {
          setStatus('Camera permission was blocked or unavailable. You can still upload an image file.');
        }
      } else {
        stopCamera();
      }
    };

    choices.forEach(btn => btn.addEventListener('click', () => setMode(btn.dataset.captureMode)));

    document.querySelector('#snapPhotoBtn').addEventListener('click', () => {
      if (!stream || !video.videoWidth) {
        setStatus('Camera is not ready yet.');
        return;
      }
      const maxWidth = 900;
      const ratio = Math.min(1, maxWidth / video.videoWidth);
      canvas.width = Math.round(video.videoWidth * ratio);
      canvas.height = Math.round(video.videoHeight * ratio);
      canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
      const dataUrl = canvas.toDataURL('image/jpeg', 0.86);
      dataInput.value = dataUrl;
      if (fileInput) fileInput.value = '';
      showPreview(dataUrl, 'Captured photo ready for doctor review');
      stopCamera();
      setStatus('Snapshot captured. Use Retake if you want another photo.');
    });

    document.querySelector('#stopCameraBtn').addEventListener('click', stopCamera);
    document.querySelector('#retakePhotoBtn').addEventListener('click', () => setMode(currentMode || 'camera'));

    if (fileInput) {
      fileInput.addEventListener('change', event => {
        dataInput.value = '';
        const file = event.target.files && event.target.files[0];
        if (!file) {
          clearPreview();
          return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
          fileInput.setCustomValidity('Please choose a JPG, PNG, or WEBP image under 5MB.');
          fileInput.reportValidity();
          clearPreview();
          fileInput.setCustomValidity('');
          return;
        }
        fileInput.setCustomValidity('');
        showPreview(URL.createObjectURL(file), 'Uploaded image ready for doctor review');
      });
    }

    window.addEventListener('beforeunload', stopCamera);
  })();
</script>

<?php include 'includes/footer.php'; ?>
