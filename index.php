<?php
require_once 'config/db.php';
require_once 'config/directory_schema.php';
ensureDirectorySchema($conn);

function excerptText(?string $text, int $limit = 110): string
{
    $text = trim($text ?: 'Dedicated medical professional with verified clinical credentials.');
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

// Fetch Active Specialty FAQs
$homeFaqsQuery = mysqli_query($conn, "SELECT f.faq_id, f.question, f.answer, s.specialization_name FROM specialization_faqs f JOIN specializations s ON s.specialization_id=f.specialization_id WHERE f.status='Active' ORDER BY s.specialization_name ASC, f.sort_order ASC LIMIT 10");
$homeFaqs = [];
if ($homeFaqsQuery) {
    while ($faq = mysqli_fetch_assoc($homeFaqsQuery)) {
        $homeFaqs[] = $faq;
    }
}

// Fetch Top Verified Doctors
$doctors = mysqli_query($conn, "SELECT d.*,u.full_name,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN cities c ON c.city_id=d.city_id JOIN specializations s ON s.specialization_id=d.specialization_id WHERE u.status='Active' AND d.verification_status='Verified' ORDER BY d.experience_years DESC,d.created_at DESC LIMIT 6");

// Telemetry Stats Counts
$docCountRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE u.status='Active' AND d.verification_status='Verified'"));
$totalVerifiedDocs = (int)($docCountRow['c'] ?? 14);

$patientCountRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM patients"));
$totalPatients = (int)($patientCountRow['c'] ?? 120);

$cityCountRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM cities WHERE status='Active'"));
$totalCities = (int)($cityCountRow['c'] ?? 8);

$aptCountRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments"));
$totalAppts = (int)($aptCountRow['c'] ?? 450);

$pageTitle = "Trusted Doctor Care";
include 'includes/header.php';
?>

<!-- 3D WebGL DNA Background Hero Section -->
<section class="hero cyber-hero">
  <div id="hero-canvas-3d"></div>
  
  <div class="hero-inner">
    <div class="eyebrow-badge">TRUSTED MEDICAL CARE NETWORK</div>
    <h1>Find Verified Doctors for <span>Real Healthcare</span> Needs</h1>
    <p class="hero-copy">
      Book appointments with verified specialists, compare clinics by city, and manage care through a secure patient and doctor portal.
    </p>
    
    <div class="hero-actions">
      <a class="btn btn-primary" href="find_doctor.php">
        <span>Find a Doctor</span>
      </a>
      <a class="btn btn-outline" href="#hologram-network">
        <span>View City Network</span>
      </a>
    </div>

    <!-- Live Telemetry HUD Bar -->
    <div class="telemetry-bar">
      <div class="telemetry-item">
        <span class="telemetry-number" data-counter="<?=$totalVerifiedDocs?>">0</span>
        <span class="telemetry-label">Verified Doctors</span>
      </div>
      <div class="telemetry-item">
        <span class="telemetry-number" data-counter="<?=$totalCities?>">0</span>
        <span class="telemetry-label">Covered Cities</span>
      </div>
      <div class="telemetry-item">
        <span class="telemetry-number" data-counter="<?=$totalPatients?>">0</span>
        <span class="telemetry-label">Registered Patients</span>
      </div>
      <div class="telemetry-item">
        <span class="telemetry-number" data-counter="<?=$totalAppts?>">0</span>
        <span class="telemetry-label">Successful Visits</span>
      </div>
    </div>
  </div>
</section>

<!-- City Map Section -->
<section id="hologram-network" class="directory">
  <div class="holographic-map-section">
    <div class="holographic-map-header">
      <div>
        <p class="eyebrow">INTERACTIVE CARE NETWORK</p>
        <h2>City Doctor Availability Map</h2>
      </div>
      <a href="find_doctor.php" class="btn btn-outline">Search All Cities</a>
    </div>
    <canvas id="holographic-map-canvas"></canvas>
    <p style="color: var(--text-dim); font-size: 12px; font-family: var(--font-mono); text-align: center; margin-top: 14px;">
      Interactive city map: click a city node to explore verified doctors in that area.
    </p>
  </div>
</section>

<!-- Verified Doctors Showcase Section -->
<main id="doctors" class="directory">
  <div class="section-heading">
    <div>
      <p class="eyebrow">FEATURED VERIFIED DOCTORS</p>
      <h2>Top Verified Doctors</h2>
    </div>
    <a class="btn btn-outline" href="find_doctor.php">Explore All Specialists</a>
  </div>

  <?php if(mysqli_num_rows($doctors)): ?>
    <div class="doctor-grid">
      <?php while($d=mysqli_fetch_assoc($doctors)):
        $img='assets/uploads/doctor/profile/'.basename($d['profile_image']??'');
      ?>
        <article class="doctor-card profile-shard">
          <div class="doctor-shard-header">
            <img class="doctor-photo" src="<?=htmlspecialchars($img)?>" onerror="this.src='https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300'" alt="Dr. <?=htmlspecialchars($d['full_name'])?>">
            <div class="doctor-info">
              <div class="specialty"><?=htmlspecialchars($d['specialization_name'])?></div>
              <h3>Dr. <?=htmlspecialchars($d['full_name'])?></h3>
              <div style="font-size: 12px; color: var(--text-muted); font-family: var(--font-mono);">
                <?=htmlspecialchars($d['city_name'])?> &middot; <?=htmlspecialchars($d['qualification'])?>
              </div>
            </div>
          </div>

          <p><?=htmlspecialchars(excerptText($d['bio'] ?? ''))?></p>

          <div class="doctor-meta">
            <span><?=intval($d['experience_years'])?> YRS EXP</span>
            <span>PKR <?=number_format($d['consultation_fee'])?></span>
          </div>

          <a class="btn btn-outline" href="doctor_details.php?doctor_id=<?=$d['doctor_id']?>" style="width:100%">
            View Profile & Book
          </a>
        </article>
      <?php endwhile; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <h3>No verified doctors active</h3>
      <p>Register as a doctor or login as admin to verify practitioner profiles.</p>
    </div>
  <?php endif; ?>
</main>

<!-- Clinical Features Grid -->
<section class="directory" style="padding-top: 20px;">
  <div class="section-heading">
    <div>
      <p class="eyebrow">CARE PLATFORM</p>
      <h2>Simple Tools for Patients, Doctors, and Admins</h2>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 28px;">
    <article class="profile-shard" style="padding: 32px;">
      <div class="eyebrow" style="color: var(--cyan-neon);">PATIENT DISCOVERY</div>
      <h3 style="font-size: 22px; margin: 10px 0;">Doctor Search & Booking</h3>
      <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7;">
        Search verified specialists by city, specialty, consultation fee, and clinical profile details.
      </p>
      <a class="btn btn-outline" href="find_doctor.php" style="margin-top: 15px;">Access Discovery</a>
    </article>

    <article class="profile-shard" style="padding: 32px;">
      <div class="eyebrow" style="color: var(--emerald-bio);">DOCTOR COMMAND</div>
      <h3 style="font-size: 22px; margin: 10px 0;">Availability & Appointments</h3>
      <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7;">
        Doctors can manage clinics, working hours, appointment requests, and patient visit status.
      </p>
      <a class="btn btn-outline" href="register.php" style="margin-top: 15px;">Doctor Login / Register</a>
    </article>

    <article class="profile-shard" style="padding: 32px;">
      <div class="eyebrow" style="color: var(--violet-quantum);">ADMIN NEXUS</div>
      <h3 style="font-size: 22px; margin: 10px 0;">Verification & Content Control</h3>
      <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7;">
        Admins can verify doctor profiles, manage users, maintain directories, FAQs, and medical news.
      </p>
      <a class="btn btn-outline" href="admin/dashboard.php" style="margin-top: 15px;">Admin Oversight</a>
    </article>
  </div>
</section>

<!-- Active Specialty & Doctor FAQs Section -->
<section id="faqs" class="directory" style="padding-top: 20px;">
  <div class="section-heading">
    <div>
      <p class="eyebrow" style="color: var(--cyan-neon);">VERIFIED MEDICAL FAQS</p>
      <h2>Active Specialty & Doctor FAQs</h2>
    </div>
    <a class="btn btn-outline" href="find_doctor.php">Explore All Specialists</a>
  </div>

  <?php if (!empty($homeFaqs)): ?>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 20px;">
      <?php foreach ($homeFaqs as $faq): ?>
        <article class="profile-shard" style="padding: 24px;">
          <span style="font-family: var(--font-mono); font-size: 11px; font-weight: 700; color: var(--cyan-neon); text-transform: uppercase; background: rgba(0,242,254,0.1); border: 1px solid rgba(0,242,254,0.25); padding: 3px 10px; border-radius: 4px; display: inline-block; margin-bottom: 12px;">
            <?= htmlspecialchars($faq['specialization_name']) ?>
          </span>
          <h3 style="font-size: 17px; margin: 0 0 10px; color: #FFF; line-height: 1.4;"><?= htmlspecialchars($faq['question']) ?></h3>
          <p style="color: var(--text-muted); font-size: 14px; line-height: 1.6; margin: 0;">
            <?= htmlspecialchars($faq['answer']) ?>
          </p>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <p style="color: var(--text-muted);">No active specialty FAQs currently published.</p>
    </div>
  <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
