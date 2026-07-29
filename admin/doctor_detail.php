<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$doctorId = (int)($_GET['id'] ?? $_POST['doctor_id'] ?? 0);
$doctor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT d.*,u.full_name,u.email,u.phone,u.status user_status,s.specialization_name,c.city_name,admin.full_name verified_by_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id LEFT JOIN users admin ON admin.user_id=d.verified_by WHERE d.doctor_id=$doctorId"));
if (!$doctor) {
    http_response_code(404);
    exit('Doctor not found.');
}

$photo = doctorFileUrl($doctor['profile_image'], 'profile');
$license = doctorFileUrl($doctor['license_certificate'], 'license');
$degree = doctorFileUrl($doctor['degree_certificate'], 'degrees');
$clinics = mysqli_query($conn, "SELECT cl.clinic_name,c.city_name,dc.is_primary FROM doctor_clinic dc JOIN clinics cl ON cl.clinic_id=dc.clinic_id JOIN cities c ON c.city_id=cl.city_id WHERE dc.doctor_id=$doctorId ORDER BY dc.is_primary DESC, cl.clinic_name");
$availability = mysqli_query($conn, "SELECT da.*,cl.clinic_name FROM doctor_availability da LEFT JOIN clinics cl ON cl.clinic_id=da.clinic_id WHERE da.doctor_id=$doctorId ORDER BY FIELD(da.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), da.start_time");
$appointmentsCount = oneCount($conn, "SELECT COUNT(*) total FROM appointments WHERE doctor_id=$doctorId");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctor Review | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('doctors'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div><p class="eyebrow">DOCTOR REVIEW</p><h1>Dr. <?=h($doctor['full_name'])?></h1><p>Verify identity, PMDC number, CNIC, license certificate, and degree before making this doctor visible to patients.</p></div>
            <a class="btn btn-outline" href="doctors.php">Back to doctors</a>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <section class="admin-profile-layout">
            <aside class="panel admin-profile-summary">
                <?php if($photo): ?><img src="<?=h($photo)?>" onerror="this.style.display='none'" alt=""><?php endif; ?>
                <span class="status status-<?=h($doctor['verification_status'])?>"><?=h($doctor['verification_status'])?></span>
                <h2>Dr. <?=h($doctor['full_name'])?></h2>
                <p><?=h($doctor['specialization_name'])?> - <?=h($doctor['city_name'])?></p>
                <dl>
                    <div><dt>Email</dt><dd><?=h($doctor['email'])?></dd></div>
                    <div><dt>Phone</dt><dd><?=h($doctor['phone'])?></dd></div>
                    <div><dt>Account</dt><dd><?=h($doctor['user_status'])?></dd></div>
                    <div><dt>Appointments</dt><dd><?=$appointmentsCount?></dd></div>
                </dl>
                <form method="post" class="admin-approval-box">
                    <input type="hidden" name="action" value="set_doctor_verification">
                    <input type="hidden" name="doctor_id" value="<?=$doctorId?>">
                    <label>Verification decision</label>
                    <select name="verification_status">
                        <?php foreach(['Pending','Verified','Rejected'] as $s): ?><option <?=$doctor['verification_status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?>
                    </select>
                    <button class="btn btn-primary">Save decision</button>
                </form>
            </aside>

            <section class="admin-profile-main">
                <article class="panel">
                    <div class="panel-head"><div><p class="eyebrow">IDENTITY</p><h2>Professional profile</h2></div></div>
                    <div class="admin-detail-grid">
                        <div><span>PMDC registration</span><strong><?=h($doctor['pmdc_registration_number'])?></strong></div>
                        <div><span>CNIC</span><strong><?=h($doctor['cnic'])?></strong></div>
                        <div><span>Qualification</span><strong><?=h($doctor['qualification'])?></strong></div>
                        <div><span>Experience</span><strong><?=intval($doctor['experience_years'])?> years</strong></div>
                        <div><span>Fee</span><strong>PKR <?=number_format((float)$doctor['consultation_fee'])?></strong></div>
                        <div><span>Gender</span><strong><?=h($doctor['gender'])?></strong></div>
                        <div><span>Date of birth</span><strong><?=h($doctor['date_of_birth'])?></strong></div>
                        <div><span>Verified by</span><strong><?=h($doctor['verified_by_name'] ?: 'Not verified')?></strong></div>
                    </div>
                    <div class="admin-long-text">
                        <span>Full address</span>
                        <p><?=h($doctor['full_address'])?></p>
                        <span>Bio</span>
                        <p><?=h($doctor['bio'])?></p>
                    </div>
                </article>

                <article class="panel">
                    <div class="panel-head"><div><p class="eyebrow">DOCUMENTS</p><h2>Certificates</h2></div></div>
                    <div class="admin-document-grid">
                        <?php foreach([['License certificate',$license],['Degree certificate',$degree]] as $doc): ?>
                            <div class="admin-document-card">
                                <h3><?=h($doc[0])?></h3>
                                <?php if($doc[1]):
                                    $ext = strtolower(pathinfo($doc[1], PATHINFO_EXTENSION));
                                ?>
                                    <?php if(in_array($ext, ['jpg','jpeg','png','webp'], true)): ?>
                                        <a href="<?=h($doc[1])?>" target="_blank"><img src="<?=h($doc[1])?>" alt=""></a>
                                    <?php else: ?>
                                        <iframe src="<?=h($doc[1])?>"></iframe>
                                    <?php endif; ?>
                                    <a class="btn btn-outline" href="<?=h($doc[1])?>" target="_blank">Open document</a>
                                <?php else: ?>
                                    <p>No document uploaded.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <section class="admin-split">
                    <article class="panel">
                        <div class="panel-head"><div><p class="eyebrow">CLINICS</p><h2>Linked clinics</h2></div></div>
                        <div class="admin-simple-list">
                            <?php while($c = mysqli_fetch_assoc($clinics)): ?>
                                <div><strong><?=h($c['clinic_name'])?></strong><span><?=h($c['city_name'])?> <?=((int)$c['is_primary'] ? '- Primary' : '')?></span></div>
                            <?php endwhile; ?>
                        </div>
                    </article>
                    <article class="panel">
                        <div class="panel-head"><div><p class="eyebrow">SCHEDULE</p><h2>Availability</h2></div></div>
                        <div class="admin-simple-list">
                            <?php while($a = mysqli_fetch_assoc($availability)): ?>
                                <div><strong><?=h($a['clinic_name'] ?? 'Clinic not assigned')?></strong><span><?=$a['day']?> - <?=date('h:i A', strtotime($a['start_time']))?> to <?=date('h:i A', strtotime($a['end_time']))?> - <?=h($a['status'])?></span></div>
                            <?php endwhile; ?>
                        </div>
                    </article>
                </section>
            </section>
        </section>
    </main>
</body>
</html>
