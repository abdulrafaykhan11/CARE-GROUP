<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$status = $_GET['status'] ?? '';
$valid = ['Pending','Verified','Rejected'];
$where = in_array($status, $valid, true) ? "WHERE d.verification_status='".mysqli_real_escape_string($conn, $status)."'" : '';
$doctors = mysqli_query($conn, "SELECT d.*,u.full_name,u.email,u.phone,u.status user_status,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id $where ORDER BY FIELD(d.verification_status,'Pending','Rejected','Verified'), d.created_at DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctor Verification | Admin Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <?php adminSidebar('doctors'); ?>
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">DOCTOR CREDENTIAL TELEMETRY</p>
                    <h2>Practitioner Approvals Queue</h2>
                </div>
                <a class="btn btn-outline" href="dashboard.php">Overview HUD</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 30px;">
                <a class="btn <?=$status===''?'btn-primary':'btn-outline'?>" href="doctors.php">All Doctors</a>
                <?php foreach($valid as $s): ?>
                    <a class="btn <?=$status===$s?'btn-primary':'btn-outline'?>" href="doctors.php?status=<?=$s?>"><?=$s?> Queue</a>
                <?php endforeach; ?>
            </div>

            <section style="display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 24px;">
                <?php while($d = mysqli_fetch_assoc($doctors)):
                    $photo = doctorFileUrl($d['profile_image'], 'profile');
                ?>
                    <article class="profile-shard" style="padding: 24px;">
                        <a href="doctor_detail.php?id=<?=$d['doctor_id']?>" style="display: flex; gap: 16px; align-items: flex-start; text-decoration: none; color: inherit;">
                            <img src="<?=h($photo ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300')?>" style="width: 70px; height: 70px; border-radius: 16px; border: 2px solid var(--border-cyber); object-fit: cover;" alt="">
                            <div>
                                <span class="status-pill status-<?=strtolower($d['verification_status'])?>" style="margin-bottom: 6px;">
                                    <?=h($d['verification_status'])?>
                                </span>
                                <h3 style="font-size: 18px; margin: 4px 0; color: var(--text-main);">Dr. <?=h($d['full_name'])?></h3>
                                <div style="font-size: 13px; color: var(--text-muted);"><?=h($d['specialization_name'])?> &middot; <?=h($d['city_name'])?></div>
                                <div style="font-family: var(--font-mono); font-size: 11px; color: var(--cyan-neon); margin-top: 4px;">
                                    PMDC: <?=h($d['pmdc_registration_number'])?> &middot; <?=intval($d['experience_years'])?> YRS
                                </div>
                            </div>
                        </a>

                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 18px; padding-top: 14px; border-top: 1px solid var(--border-cyber);">
                            <a class="btn btn-outline" href="doctor_detail.php?id=<?=$d['doctor_id']?>" style="padding: 6px 14px; font-size: 11px;">
                                Review Credentials
                            </a>

                            <form method="post" style="display: flex; gap: 6px; align-items: center;">
                                <input type="hidden" name="action" value="set_doctor_verification">
                                <input type="hidden" name="doctor_id" value="<?=$d['doctor_id']?>">
                                <select name="verification_status" style="padding: 6px 10px; font-size: 11px; border-radius: var(--radius-sm);">
                                    <?php foreach($valid as $s): ?>
                                        <option <?=$d['verification_status']===$s?'selected':''?>><?=$s?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-primary" style="padding: 6px 12px; font-size: 11px;">Save</button>
                            </form>
                        </div>
                    </article>
                <?php endwhile; ?>
            </section>
        </main>
    </div>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
