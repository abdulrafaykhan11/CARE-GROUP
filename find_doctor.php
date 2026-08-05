<?php
require_once 'config/db.php';
require_once 'config/directory_schema.php';
require_once 'config/content_templates.php';
ensureDirectorySchema($conn);

function shortText(?string $text, int $limit = 130): string
{
    $text = trim($text ?: 'Dedicated medical professional with verified clinical credentials.');
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

function linesFromDb(?string $text): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $text ?? ''))));
}

$search = trim($_GET['search'] ?? '');
$city = (int)($_GET['city'] ?? 0);
$spec = (int)($_GET['spec'] ?? 0);
$gender = $_GET['gender'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 6;
$limit = $page * $perPage;

$cities = mysqli_query($conn, "SELECT city_id,city_name FROM cities WHERE status='Active' ORDER BY city_name");
$specsResult = mysqli_query($conn, "SELECT specialization_id,specialization_name,description FROM specializations WHERE status='Active' ORDER BY specialization_name");
$specs = [];
$selectedSpec = null;
while ($row = mysqli_fetch_assoc($specsResult)) {
    $specs[] = $row;
    if ((int)$row['specialization_id'] === $spec) {
        $selectedSpec = $row;
    }
}

$where = ["u.status='Active'", "d.verification_status='Verified'"];
if ($search !== '') {
    $safe = mysqli_real_escape_string($conn, $search);
    $where[] = "(u.full_name LIKE '%$safe%' OR s.specialization_name LIKE '%$safe%' OR c.city_name LIKE '%$safe%' OR d.qualification LIKE '%$safe%')";
}
if ($city) {
    $where[] = "d.city_id=$city";
}
if ($spec) {
    $where[] = "d.specialization_id=$spec";
}
if (in_array($gender, ['Male','Female','Other'], true)) {
    $where[] = "d.gender='".mysqli_real_escape_string($conn, $gender)."'";
}
$whereSql = implode(' AND ', $where);

$countRow = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN cities c ON c.city_id=d.city_id JOIN specializations s ON s.specialization_id=d.specialization_id WHERE $whereSql"));
$totalDoctors = (int)($countRow['total'] ?? 0);
$doctors = mysqli_query($conn, "SELECT d.*,u.full_name,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN cities c ON c.city_id=d.city_id JOIN specializations s ON s.specialization_id=d.specialization_id WHERE $whereSql ORDER BY d.experience_years DESC,d.created_at DESC LIMIT $limit");

$guide = null;
$faqs = [];
if ($selectedSpec) {
    $guide = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM specialization_guides WHERE specialization_id=$spec LIMIT 1"));
    $faqResult = mysqli_query($conn, "SELECT question,answer FROM specialization_faqs WHERE specialization_id=$spec AND status='Active' ORDER BY sort_order ASC, faq_id ASC LIMIT 10");
    while ($faq = mysqli_fetch_assoc($faqResult)) {
        $faqs[] = $faq;
    }
}

$guideName = $selectedSpec['specialization_name'] ?? 'Medical Specialty';
$overview = $guide['overview'] ?? ($selectedSpec['description'] ?? 'Choose a specialty to see verified doctors, compare experience and fees, then book an available clinic slot.');
$whenToBook = linesFromDb($guide['when_to_book'] ?? '');
if (!$whenToBook) {
    $whenToBook = ['Symptoms are not improving', 'You need a second opinion', 'You need follow-up care', 'You want preventive screening', 'You need specialist treatment'];
}
$carePoints = linesFromDb($guide['care_points'] ?? '');
if (!$carePoints) {
    $carePoints = ['Compare experience, fee, and location before booking.', 'Bring previous reports, prescriptions, and test results.', 'Use notes to share important symptoms before the visit.'];
}
if (!$faqs) {
    $faqs = [
        ['question' => 'How do I choose the right doctor?', 'answer' => 'Start with specialty, then compare experience, fee, location, and availability.'],
        ['question' => 'Why are only verified doctors shown?', 'answer' => 'Care Connect only shows doctors after admin verification, so patients do not book unreviewed profiles.'],
        ['question' => 'What happens after booking?', 'answer' => 'Your request is sent to the doctor and appears in your dashboard.']
    ];
}
$sections = deepSpecialtySections($guideName, $overview, $whenToBook, $carePoints);

$topWhere = $spec ? "WHERE d.specialization_id=$spec AND u.status='Active' AND d.verification_status='Verified'" : "WHERE u.status='Active' AND d.verification_status='Verified'";
$topDoctors = mysqli_query($conn, "SELECT d.doctor_id,d.experience_years,d.consultation_fee,u.full_name,s.specialization_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id $topWhere ORDER BY d.experience_years DESC LIMIT 5");

$pageTitle = "Find Doctor & Profile Shards";
include 'includes/header.php';
?>

<div class="directory" style="padding-top: 40px;">
  <!-- Search Hero Header -->
  <div style="text-align: center; max-width: 800px; margin: 0 auto 40px;">
    <div class="eyebrow-badge">INTERACTIVE PATIENT DISCOVERY</div>
    <h1 style="font-size: 42px; margin-bottom: 16px;">Holographic Doctor Search</h1>
    <p style="color: var(--text-muted); font-size: 17px; line-height: 1.7;">
      Filter by city network node, specialty field, or keyword. Select profiles below to view bio-metric telemetry and availability flux.
    </p>
  </div>

  <!-- Holographic City Network Map Canvas -->
  <div class="holographic-map-section">
    <div class="holographic-map-header">
      <div>
        <p class="eyebrow">CYBER MAP RADAR</p>
        <h3 style="font-size: 20px; margin: 0; color: var(--text-main);">Holographic City Network Grid</h3>
      </div>
      <div style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon);">
        <?=$totalDoctors?> VERIFIED SHARDS ACTIVE
      </div>
    </div>
    <canvas id="holographic-map-canvas"></canvas>
  </div>

  <!-- Cybernetic Filter Panel -->
  <form class="search-card" method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Search Doctor / Keyword</label>
        <input name="search" value="<?=htmlspecialchars($search)?>" placeholder="e.g. Dr. Samia, Cardiology">
      </div>
      
      <div class="field">
        <label>City Node</label>
        <select name="city">
          <option value="">All Cities</option>
          <?php while($x=mysqli_fetch_assoc($cities)): ?>
            <option value="<?=$x['city_id']?>" <?=$city===(int)$x['city_id']?'selected':''?>><?=htmlspecialchars($x['city_name'])?></option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="field">
        <label>Specialty Field</label>
        <select name="spec">
          <option value="">All Specialties</option>
          <?php foreach($specs as $x): ?>
            <option value="<?=$x['specialization_id']?>" <?=$spec===(int)$x['specialization_id']?'selected':''?>><?=htmlspecialchars($x['specialization_name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label>Gender</label>
        <select name="gender">
          <option value="">Any Gender</option>
          <?php foreach(['Male','Female','Other'] as $g): ?>
            <option <?=$gender===$g?'selected':''?>><?=$g?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <button class="btn btn-primary" type="submit">
        <span>❖ Execute Search</span>
      </button>
    </div>
  </form>

  <!-- Doctor Results Shards Grid -->
  <section class="doctor-results-section">
    <div class="section-heading">
      <div>
        <p class="eyebrow">VERIFIED DOCTOR SHARDS</p>
        <h2><?=htmlspecialchars($guideName)?></h2>
      </div>
      <span style="font-family: var(--font-mono); color: var(--cyan-neon); font-weight: 700; font-size: 14px;">
        <?=$totalDoctors?> Specialists Found
      </span>
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

            <p><?=htmlspecialchars(shortText($d['bio'] ?? ''))?></p>

            <div class="doctor-meta">
              <span><?=intval($d['experience_years'])?> YRS EXP</span>
              <span>PKR <?=number_format($d['consultation_fee'])?></span>
            </div>

            <a class="btn btn-outline" href="doctor_details.php?doctor_id=<?=$d['doctor_id']?>" style="width:100%">
              View Full Shard Profile
            </a>
          </article>
        <?php endwhile; ?>
      </div>

      <?php if($limit < $totalDoctors):
        $next = $_GET;
        $next['page'] = $page + 1;
      ?>
        <div style="text-align: center; margin-top: 40px;">
          <a class="btn btn-primary" href="find_doctor.php?<?=http_build_query($next)?>">
            Load More Doctor Shards
          </a>
          <p style="color: var(--text-dim); font-size: 13px; font-family: var(--font-mono); margin-top: 10px;">
            Showing <?=min($limit, $totalDoctors)?> of <?=$totalDoctors?> specialists.
          </p>
        </div>
      <?php endif; ?>
    <?php else: ?>
      <div class="empty-state">
        <h3>No verified doctor shards found</h3>
        <p>Try modifying your city node, specialty filter, or search keywords.</p>
      </div>
    <?php endif; ?>
  </section>

  <!-- Deep Specialty Archive Guide Shards -->
  <section style="margin-top: 80px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 60px;">
    <div class="section-heading">
      <div>
        <p class="eyebrow">CLINICAL DATABASE ARCHIVE</p>
        <h2>Understanding <?=htmlspecialchars($guideName)?></h2>
      </div>
    </div>

    <div style="background: var(--bg-card); border: 1px solid var(--border-cyber); border-radius: var(--radius-lg); padding: 32px; margin-bottom: 30px; backdrop-filter: blur(20px);">
      <p style="color: var(--text-muted); font-size: 18px; line-height: 1.8; margin: 0;">
        <?=htmlspecialchars($overview)?>
      </p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
      <?php foreach($sections as $title => $paragraphs): ?>
        <article class="profile-shard" style="padding: 28px;">
          <h3 style="font-size: 20px; color: var(--cyan-neon); margin-bottom: 14px;"><?=htmlspecialchars($title)?></h3>
          <?php foreach($paragraphs as $paragraph): ?>
            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7; margin-bottom: 10px;"><?=htmlspecialchars($paragraph)?></p>
          <?php endforeach; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<?php include 'includes/footer.php'; ?>
