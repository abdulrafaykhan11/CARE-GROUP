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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Practitioner Credential Review | Admin Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <?php adminSidebar('doctors'); ?>
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">DOCTOR VERIFICATION TELEMETRY</p>
                    <h2>Dr. <?=h($doctor['full_name'])?> Credentials</h2>
                </div>
                <a class="btn btn-outline" href="doctors.php">Back to Doctors Queue</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <div style="display: grid; grid-template-columns: 320px 1fr; gap: 28px; align-items: flex-start;">
                <!-- Summary Card -->
                <aside class="profile-shard" style="padding: 24px; text-align: center;">
                    <img src="<?=h($photo ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300')?>" style="width: 120px; height: 120px; border-radius: 20px; border: 3px solid var(--cyan-neon); object-fit: cover; margin-bottom: 16px;" alt="">
                    
                    <span class="status-pill status-<?=strtolower($doctor['verification_status'])?>" style="margin-bottom: 12px; display: inline-block;">
                        <?=h($doctor['verification_status'])?>
                    </span>
                    <h3 style="font-size: 22px; color: var(--text-main); margin: 6px 0;">Dr. <?=h($doctor['full_name'])?></h3>
                    <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;"><?=h($doctor['specialization_name'])?> &middot; <?=h($doctor['city_name'])?></p>

                    <div style="text-align: left; background: var(--bg-card); border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); padding: 16px; font-size: 13px; margin-bottom: 20px;">
                        <div style="margin-bottom: 8px;"><strong style="color: var(--text-muted);">Email:</strong> <span style="color: var(--text-main); font-family: var(--font-mono);"><?=h($doctor['email'])?></span></div>
                        <div style="margin-bottom: 8px;"><strong style="color: var(--text-muted);">Phone:</strong> <span style="color: var(--text-main); font-family: var(--font-mono);"><?=h($doctor['phone'])?></span></div>
                        <div style="margin-bottom: 8px;"><strong style="color: var(--text-muted);">Account:</strong> <span style="color: var(--cyan-neon); font-weight: 700;"><?=h($doctor['user_status'])?></span></div>
                        <div><strong style="color: var(--text-muted);">Total Visits:</strong> <span style="color: var(--emerald-bio); font-family: var(--font-mono); font-weight: 700;"><?=$appointmentsCount?></span></div>
                    </div>

                    <form method="post" style="background: rgba(2, 132, 197, 0.05); border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); padding: 16px; text-align: left;">
                        <input type="hidden" name="action" value="set_doctor_verification">
                        <input type="hidden" name="doctor_id" value="<?=$doctorId?>">
                        <label style="font-size: 11px; font-family: var(--font-mono); color: var(--cyan-neon); font-weight: 700;">VERIFICATION DECISION</label>
                        <select name="verification_status" style="width: 100%; margin: 8px 0 14px; padding: 10px;">
                            <?php foreach(['Pending','Verified','Rejected'] as $s): ?>
                                <option <?=$doctor['verification_status']===$s?'selected':''?>><?=$s?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-primary" style="width: 100%;"><span>❖ Commit Decision</span></button>
                    </form>
                </aside>

                <!-- Detailed Information & Certificate Panels -->
                <section style="display: grid; gap: 28px;">
                    <!-- Identity Credentials Shard -->
                    <article class="cyber-table-wrap" style="margin: 0;">
                        <p class="eyebrow">BIO-METRIC & REGISTRATION TELEMETRY</p>
                        <h3 style="margin:0 0 20px; color: var(--text-main);">Credentials Verification</h3>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                            <div style="background: var(--bg-card); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">PMDC REGISTRATION #</span>
                                <strong style="display: block; font-size: 16px; color: var(--cyan-neon); margin-top: 4px; font-family: var(--font-mono);"><?=h($doctor['pmdc_registration_number'])?></strong>
                            </div>
                            <div style="background: var(--bg-card); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">CNIC NUMBER</span>
                                <strong style="display: block; font-size: 16px; color: var(--text-main); margin-top: 4px; font-family: var(--font-mono);"><?=h($doctor['cnic'])?></strong>
                            </div>
                            <div style="background: var(--bg-card); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">QUALIFICATION</span>
                                <strong style="display: block; font-size: 16px; color: var(--text-main); margin-top: 4px;"><?=h($doctor['qualification'])?></strong>
                            </div>
                            <div style="background: var(--bg-card); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">EXPERIENCE</span>
                                <strong style="display: block; font-size: 16px; color: var(--emerald-bio); margin-top: 4px;"><?=intval($doctor['experience_years'])?> Years</strong>
                            </div>
                            <div style="background: var(--bg-card); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">CONSULTATION FEE</span>
                                <strong style="display: block; font-size: 16px; color: var(--cyan-neon); margin-top: 4px;">PKR <?=number_format((float)$doctor['consultation_fee'])?></strong>
                            </div>
                            <div style="background: var(--bg-card); padding: 14px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                                <span style="font-size: 11px; font-family: var(--font-mono); color: var(--text-muted);">VERIFIED BY ADMIN</span>
                                <strong style="display: block; font-size: 16px; color: var(--text-main); margin-top: 4px;"><?=h($doctor['verified_by_name'] ?: 'Not Verified Yet')?></strong>
                            </div>
                        </div>

                        <div style="background: var(--bg-card); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-cyber);">
                            <strong style="color: var(--cyan-neon); font-size: 12px; font-family: var(--font-mono);">BIO & CARE PHILOSOPHY</strong>
                            <p style="color: var(--text-muted); font-size: 14px; line-height: 1.7; margin: 8px 0 0;"><?=h($doctor['bio'])?></p>
                        </div>
                    </article>

                    <!-- Document Certificates -->
                    <article class="cyber-table-wrap" style="margin: 0;">
                        <p class="eyebrow">UPLOADED CERTIFICATE ASSETS</p>
                        <h3 style="margin:0 0 20px; color: var(--text-main);">License & Degree Verification</h3>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                            <?php foreach([['PMDC License Certificate',$license],['Medical Degree Certificate',$degree]] as $doc): ?>
                                <div style="background: var(--bg-card); border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); padding: 18px;">
                                    <h4 style="margin: 0 0 14px; color: var(--text-main); font-size: 16px;"><?=h($doc[0])?></h4>
                                    <?php if($doc[1]):
                                        $ext = strtolower(pathinfo($doc[1], PATHINFO_EXTENSION));
                                    ?>
                                        <?php if(in_array($ext, ['jpg','jpeg','png','webp'], true)): ?>
                                            <a href="<?=h($doc[1])?>" target="_blank">
                                                <img src="<?=h($doc[1])?>" style="width: 100%; max-height: 220px; object-fit: contain; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 14px;" alt="">
                                            </a>
                                        <?php else: ?>
                                            <iframe src="<?=h($doc[1])?>" style="width: 100%; height: 220px; border: none; border-radius: 8px; margin-bottom: 14px;"></iframe>
                                        <?php endif; ?>
                                        <a class="btn btn-outline" href="<?=h($doc[1])?>" target="_blank" style="width: 100%; text-align: center;">Open Document File</a>
                                    <?php else: ?>
                                        <p style="color: var(--text-muted);">No document uploaded.</p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </article>
                </section>
            </div>
        </main>
    </div>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
