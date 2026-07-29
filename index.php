<?php
require_once 'config/db.php';

function excerptText(?string $text, int $limit = 110): string
{
    $text = trim($text ?: 'Dedicated medical professional.');
    return strlen($text) > $limit ? substr($text, 0, $limit - 3) . '...' : $text;
}

$doctors = mysqli_query($conn, "SELECT d.*,u.full_name,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN cities c ON c.city_id=d.city_id JOIN specializations s ON s.specialization_id=d.specialization_id WHERE u.status='Active' AND d.verification_status='Verified' ORDER BY d.experience_years DESC,d.created_at DESC LIMIT 6");
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Care Connect | Trusted doctors</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.php">care<span>connect</span></a>
    <nav>
      <a href="find_doctor.php">Find doctor</a>
      <a href="#doctors">Top doctors</a>
      <?php if(!empty($_SESSION['user_id'])): ?>
        <a href="<?=($_SESSION['role']==='Doctor'?'doctor/dashboard.php':(($_SESSION['role'] ?? '')==='Admin'?'admin/dashboard.php':'patient/dashboard.php'))?>">My dashboard</a>
        <a class="btn btn-outline" href="logout.php">Sign out</a>
      <?php else: ?>
        <a href="login.php">Sign in</a>
        <a class="btn btn-primary" href="register.php">Create account</a>
      <?php endif; ?>
    </nav>
  </header>

  <section class="hero">
    <div class="hero-inner">
      <p class="eyebrow">ELEVATED CARE</p>
      <h1>The right doctor is <span>closer</span> than you think.</h1>
      <p class="hero-copy">Start with our featured doctors, or search by specialty to find the exact care you need.</p>
      <div class="hero-actions">
        <a class="btn btn-primary" href="find_doctor.php">Find doctor by specialty</a>
      </div>
    </div>
  </section>

  <main id="doctors" class="directory">
    <div class="section-heading">
      <div>
        <p class="eyebrow">FEATURED DOCTORS</p>
        <h2>Top doctors to start with</h2>
      </div>
      <a class="btn btn-outline" href="find_doctor.php">View all doctors</a>
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
            <p><?=htmlspecialchars(excerptText($d['bio'] ?? ''))?></p>
            <div class="doctor-meta">
              <span><?=intval($d['experience_years'])?> yrs experience</span>
              <span>PKR <?=number_format($d['consultation_fee'])?></span>
            </div>
            <a class="btn btn-outline" href="doctor_details.php?doctor_id=<?=$d['doctor_id']?>">View & book</a>
          </article>
        <?php endwhile; ?>
      </div>
    <?php else: ?>
      <div class="empty-state">
        <h3>No doctors available yet</h3>
        <p>Add doctors from the registration flow, then they will appear here.</p>
      </div>
    <?php endif; ?>
  </main>
</body>
</html>
