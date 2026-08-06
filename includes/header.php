<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fetch dynamic verified doctor counts per city for Holographic Map
$cityMapCounts = [];
if (isset($conn) && $conn) {
    $qMap = mysqli_query($conn, "SELECT c.city_name, COUNT(d.doctor_id) AS verified_count FROM cities c LEFT JOIN doctors d ON d.city_id = c.city_id AND d.verification_status = 'Verified' GROUP BY c.city_id, c.city_name");
    if ($qMap) {
        while ($rMap = mysqli_fetch_assoc($qMap)) {
            $cityMapCounts[$rMap['city_name']] = (int)$rMap['verified_count'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="care-user-role" content="<?= htmlspecialchars(($_SESSION['role'] ?? 'All') === 'Patient' ? 'Patient' : 'All') ?>">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' | CARE Group' : 'CARE Group | Trusted Medical Care Platform' ?></title>
  
  <!-- Modern Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
  <?php if (!empty($extraStylesheets) && is_array($extraStylesheets)): ?>
    <?php foreach ($extraStylesheets as $stylesheet): ?>
      <link rel="stylesheet" href="<?=htmlspecialchars($stylesheet)?>">
    <?php endforeach; ?>
  <?php endif; ?>
  
  <!-- Three.js & GSAP 3 CDNs -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>

  <script>
    window.CITY_DOCTOR_COUNTS = <?= json_encode($cityMapCounts) ?>;
  </script>
</head>
<body>
  <header class="site-header">
    <a class="brand" href="index.php">
      CARE <span>NEXUS</span>
    </a>
    
    <button class="mobile-nav-toggle" id="mobileNavToggle" aria-label="Toggle navigation">
      <span></span>
      <span></span>
      <span></span>
    </button>

    <nav id="siteNav">
      <a href="index.php">Home</a>
      <a href="find_doctor.php">Find Doctor</a>
      <a href="eye-test.php">Eye Test</a>
      <a href="news.php">Medical News</a>
      <a href="index.php#hologram-network">City Map</a>
      
      <?php if(!empty($_SESSION['user_id'])): ?>
        <?php 
          $dashUrl = 'patient/dashboard.php';
          if(($_SESSION['role'] ?? '') === 'Doctor') $dashUrl = 'doctor/dashboard.php';
          else if(($_SESSION['role'] ?? '') === 'Admin') $dashUrl = 'admin/dashboard.php';
        ?>
        <a class="btn btn-outline" href="<?=$dashUrl?>">Dashboard</a>
        <a class="btn btn-primary" href="logout.php">Sign Out</a>
      <?php else: ?>
        <a href="login.php">Sign In</a>
        <a class="btn btn-primary" href="register.php">Create Account</a>
      <?php endif; ?>
    </nav>
  </header>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toggle = document.getElementById('mobileNavToggle');
      const nav = document.getElementById('siteNav');
      if (toggle && nav) {
        toggle.addEventListener('click', function() {
          toggle.classList.toggle('open');
          nav.classList.toggle('nav-open');
        });
      }
    });
  </script>
