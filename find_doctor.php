<?php
require_once 'config/db.php';
require_once 'config/directory_schema.php';
ensureDirectorySchema($conn);

function shortText(?string $text, int $limit = 115): string
{
    $text = trim($text ?: 'Dedicated medical professional.');
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
$perPage = 5;
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
    $faqResult = mysqli_query($conn, "SELECT question,answer FROM specialization_faqs WHERE specialization_id=$spec AND status='Active' ORDER BY sort_order ASC, faq_id ASC LIMIT 8");
    while ($faq = mysqli_fetch_assoc($faqResult)) {
        $faqs[] = $faq;
    }
}

$guideName = $selectedSpec['specialization_name'] ?? 'Find Doctor';
$overview = $guide['overview'] ?? ($selectedSpec['description'] ?? 'Choose a specialty to see doctors, compare experience and fees, then book an available clinic slot.');
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
        ['question' => 'What happens after booking?', 'answer' => 'Your request is sent to the doctor and appears in your dashboard.']
    ];
}

$topWhere = $spec ? "WHERE d.specialization_id=$spec AND u.status='Active' AND d.verification_status='Verified'" : "WHERE u.status='Active' AND d.verification_status='Verified'";
$topDoctors = mysqli_query($conn, "SELECT d.doctor_id,d.experience_years,d.consultation_fee,u.full_name,s.specialization_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id $topWhere ORDER BY d.experience_years DESC LIMIT 3");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Find Doctor | Care Connect</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.php">care<span>connect</span></a>
    <nav>
      <a class="active" href="find_doctor.php">Find doctor</a>
      <a href="index.php#doctors">Top doctors</a>
      <?php if(!empty($_SESSION['user_id'])): ?>
        <a href="<?=($_SESSION['role']==='Doctor'?'doctor/dashboard.php':(($_SESSION['role'] ?? '')==='Admin'?'admin/dashboard.php':'patient/dashboard.php'))?>">My dashboard</a>
        <a class="btn btn-outline" href="logout.php">Sign out</a>
      <?php else: ?>
        <a href="login.php">Sign in</a>
        <a class="btn btn-primary" href="register.php">Create account</a>
      <?php endif; ?>
    </nav>
  </header>

  <section class="find-hero">
    <p class="eyebrow">FIND DOCTOR</p>
    <h1>Choose the specialty you need.</h1>
    <p>Search neurologists, cardiologists, gynecologists, pediatricians, dermatologists, and more.</p>
  </section>

  <form class="search-card find-search" method="get">
    <div class="filter-grid">
      <div class="field">
        <label>Search by doctor or keyword</label>
        <input name="search" value="<?=htmlspecialchars($search)?>" placeholder="e.g. migraine, Dr. Samia">
      </div>
      <div class="field">
        <label>City</label>
        <select name="city">
          <option value="">All cities</option>
          <?php while($x=mysqli_fetch_assoc($cities)): ?>
            <option value="<?=$x['city_id']?>" <?=$city===(int)$x['city_id']?'selected':''?>><?=htmlspecialchars($x['city_name'])?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="field">
        <label>Specialty</label>
        <select name="spec">
          <option value="">All specialties</option>
          <?php foreach($specs as $x): ?>
            <option value="<?=$x['specialization_id']?>" <?=$spec===(int)$x['specialization_id']?'selected':''?>><?=htmlspecialchars($x['specialization_name'])?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Gender</label>
        <select name="gender">
          <option value="">Any</option>
          <?php foreach(['Male','Female','Other'] as $g): ?>
            <option <?=$gender===$g?'selected':''?>><?=$g?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-primary" type="submit">Search</button>
    </div>
  </form>

  <main class="directory find-layout">
    <section>
      <div class="section-heading">
        <div>
          <p class="eyebrow">MATCHING DOCTORS</p>
          <h2><?=htmlspecialchars($guideName)?></h2>
        </div>
        <span><?=$totalDoctors?> doctors found</span>
      </div>

      <?php if(mysqli_num_rows($doctors)): ?>
        <div class="doctor-grid">
          <?php while($d=mysqli_fetch_assoc($doctors)):
            $img='assets/uploads/doctor/profile/'.basename($d['profile_image']??'');
          ?>
            <article class="doctor-card">
              <img class="doctor-photo" src="<?=htmlspecialchars($img)?>" onerror="this.style.display='none'" alt="">
              <div class="specialty"><?=htmlspecialchars($d['specialization_name'])?></div>
              <h3>Dr. <?=htmlspecialchars($d['full_name'])?></h3>
              <p><?=htmlspecialchars($d['qualification'])?> &middot; <?=htmlspecialchars($d['city_name'])?></p>
              <p><?=htmlspecialchars(shortText($d['bio'] ?? ''))?></p>
              <div class="doctor-meta">
                <span><?=intval($d['experience_years'])?> yrs experience</span>
                <span>PKR <?=number_format($d['consultation_fee'])?></span>
              </div>
              <a class="btn btn-outline" href="doctor_details.php?doctor_id=<?=$d['doctor_id']?>">View & book</a>
            </article>
          <?php endwhile; ?>
        </div>

        <?php if($limit < $totalDoctors):
          $next = $_GET;
          $next['page'] = $page + 1;
        ?>
          <div class="load-more-wrap">
            <a class="btn btn-primary" href="find_doctor.php?<?=http_build_query($next)?>">Load more doctors</a>
            <p class="note">Showing <?=min($limit, $totalDoctors)?> of <?=$totalDoctors?> doctors.</p>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="empty-state">
          <h3>No matching doctors</h3>
          <p>Try another specialty, city, or search term.</p>
        </div>
      <?php endif; ?>
    </section>

    <section class="specialty-guide">
      <div>
        <p class="eyebrow">ABOUT <?=htmlspecialchars($guideName)?></p>
        <h2><?=htmlspecialchars($guideName)?> guide</h2>
        <p><?=htmlspecialchars($overview)?></p>
      </div>

      <div class="guide-grid">
        <article>
          <h3>When to book</h3>
          <ul>
            <?php foreach($whenToBook as $item): ?>
              <li><?=htmlspecialchars($item)?></li>
            <?php endforeach; ?>
          </ul>
        </article>
        <article>
          <h3>Care tips</h3>
          <ul>
            <?php foreach($carePoints as $item): ?>
              <li><?=htmlspecialchars($item)?></li>
            <?php endforeach; ?>
          </ul>
        </article>
        <article>
          <h3>Top doctors</h3>
          <?php if(mysqli_num_rows($topDoctors)): ?>
            <?php while($t=mysqli_fetch_assoc($topDoctors)): ?>
              <p><a href="doctor_details.php?doctor_id=<?=$t['doctor_id']?>">Dr. <?=htmlspecialchars($t['full_name'])?></a><br><span><?=htmlspecialchars($t['specialization_name'])?> &middot; <?=intval($t['experience_years'])?> yrs &middot; PKR <?=number_format($t['consultation_fee'])?></span></p>
            <?php endwhile; ?>
          <?php else: ?>
            <p>No top doctors available for this specialty yet.</p>
          <?php endif; ?>
        </article>
      </div>

      <div class="faq-list">
        <h3>FAQs</h3>
        <?php foreach($faqs as $faq): ?>
          <details>
            <summary><?=htmlspecialchars($faq['question'])?></summary>
            <p><?=htmlspecialchars($faq['answer'])?></p>
          </details>
        <?php endforeach; ?>
      </div>
    </section>
  </main>
</body>
</html>
