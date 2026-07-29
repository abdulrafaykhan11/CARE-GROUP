<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$status = $_GET['status'] ?? '';
$valid = ['Pending','Verified','Rejected'];
$where = in_array($status, $valid, true) ? "WHERE d.verification_status='".mysqli_real_escape_string($conn, $status)."'" : '';
$doctors = mysqli_query($conn, "SELECT d.*,u.full_name,u.email,u.phone,u.status user_status,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id $where ORDER BY FIELD(d.verification_status,'Pending','Rejected','Verified'), d.created_at DESC");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctors | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('doctors'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div><p class="eyebrow">DOCTOR APPROVALS</p><h1>Doctors</h1><p>Open a doctor profile to verify PMDC, CNIC, license, degree, and profile details before approving.</p></div>
            <a class="btn btn-outline" href="dashboard.php">Overview</a>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <nav class="admin-tabs">
            <a class="<?=!$status?'active':''?>" href="doctors.php">All</a>
            <?php foreach($valid as $s): ?><a class="<?=$status===$s?'active':''?>" href="doctors.php?status=<?=$s?>"><?=$s?></a><?php endforeach; ?>
        </nav>

        <section class="admin-doctor-grid">
            <?php while($d = mysqli_fetch_assoc($doctors)):
                $photo = doctorFileUrl($d['profile_image'], 'profile');
            ?>
                <article class="admin-doctor-card">
                    <a class="admin-doctor-card-main" href="doctor_detail.php?id=<?=$d['doctor_id']?>">
                        <?php if($photo): ?><img src="<?=h($photo)?>" onerror="this.style.display='none'" alt=""><?php endif; ?>
                        <div>
                            <span class="status status-<?=h($d['verification_status'])?>"><?=h($d['verification_status'])?></span>
                            <h2>Dr. <?=h($d['full_name'])?></h2>
                            <p><?=h($d['specialization_name'])?> - <?=h($d['city_name'])?></p>
                            <small><?=h($d['qualification'])?> - <?=intval($d['experience_years'])?> yrs - PMDC <?=h($d['pmdc_registration_number'])?></small>
                        </div>
                    </a>
                    <div class="admin-card-actions">
                        <a class="btn btn-outline" href="doctor_detail.php?id=<?=$d['doctor_id']?>">Full profile</a>
                        <form method="post" class="inline-admin-form">
                            <input type="hidden" name="action" value="set_doctor_verification">
                            <input type="hidden" name="doctor_id" value="<?=$d['doctor_id']?>">
                            <select name="verification_status">
                                <?php foreach($valid as $s): ?><option <?=$d['verification_status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
                            </select>
                            <button class="btn btn-primary">Save</button>
                        </form>
                    </div>
                </article>
            <?php endwhile; ?>
        </section>
    </main>
</body>
</html>
