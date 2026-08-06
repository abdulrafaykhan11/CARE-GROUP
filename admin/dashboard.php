<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    (SELECT COUNT(*) FROM users) users_total,
    (SELECT COUNT(*) FROM users WHERE status='Active') users_active,
    (SELECT COUNT(*) FROM users WHERE status='Suspended') users_suspended,
    (SELECT COUNT(*) FROM doctors) doctors_total,
    (SELECT COUNT(*) FROM doctors WHERE verification_status='Pending') doctors_pending,
    (SELECT COUNT(*) FROM doctors WHERE verification_status='Verified') doctors_verified,
    (SELECT COUNT(*) FROM patients) patients_total,
    (SELECT COUNT(*) FROM appointments) appointments_total,
    (SELECT COUNT(*) FROM appointments WHERE status='Pending') appointments_pending,
    (SELECT COUNT(*) FROM clinics WHERE status='Active') clinics_active,
    (SELECT COUNT(*) FROM specializations WHERE status='Active') specs_active,
    (SELECT COALESCE(SUM(d.consultation_fee),0) FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id WHERE a.status='Completed') completed_value
"));

$appointmentStatus = [];
$r = mysqli_query($conn, "SELECT status, COUNT(*) total FROM appointments GROUP BY status");
while ($row = mysqli_fetch_assoc($r)) {
    $appointmentStatus[$row['status']] = (int) $row['total'];
}

$weekdays = ['Monday'=>0,'Tuesday'=>0,'Wednesday'=>0,'Thursday'=>0,'Friday'=>0,'Saturday'=>0,'Sunday'=>0];
$r = mysqli_query($conn, "SELECT DAYNAME(appointment_date) day_name, COUNT(*) total FROM appointments GROUP BY DAYOFWEEK(appointment_date), DAYNAME(appointment_date)");
while ($row = mysqli_fetch_assoc($r)) {
    $weekdays[$row['day_name']] = (int) $row['total'];
}

$paymentRevenue = ['Cash' => 0, 'Card' => 0];
$r = mysqli_query($conn, "SELECT COALESCE(a.payment_method,'Cash') payment_method, COALESCE(SUM(d.consultation_fee),0) total FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id WHERE a.status='Completed' GROUP BY COALESCE(a.payment_method,'Cash')");
while ($row = mysqli_fetch_assoc($r)) {
    $paymentRevenue[$row['payment_method']] = (float) $row['total'];
}

$monthlyRevenue = [];
$r = mysqli_query($conn, "SELECT DATE_FORMAT(COALESCE(a.completed_at,a.updated_at,a.created_at),'%b %Y') month_label, COALESCE(SUM(d.consultation_fee),0) total FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id WHERE a.status='Completed' GROUP BY YEAR(COALESCE(a.completed_at,a.updated_at,a.created_at)), MONTH(COALESCE(a.completed_at,a.updated_at,a.created_at)), month_label ORDER BY YEAR(COALESCE(a.completed_at,a.updated_at,a.created_at)), MONTH(COALESCE(a.completed_at,a.updated_at,a.created_at)) LIMIT 12");
while ($row = mysqli_fetch_assoc($r)) {
    $monthlyRevenue[$row['month_label']] = (float) $row['total'];
}
$maxRevenue = max($monthlyRevenue ? max($monthlyRevenue) : 0, 1);

$maxWeekday = max(max($weekdays), 1);
$totalAppointments = max(1, (int) $stats['appointments_total']);
$completionRate = round(((int) ($appointmentStatus['Completed'] ?? 0) / $totalAppointments) * 100);
$frictionRate = round((((int) ($appointmentStatus['Cancelled'] ?? 0) + (int) ($appointmentStatus['NoShow'] ?? 0)) / $totalAppointments) * 100);

$pendingDoctors = mysqli_query($conn, "SELECT d.doctor_id,d.profile_image,d.qualification,d.experience_years,d.pmdc_registration_number,d.verification_status,u.full_name,u.email,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id WHERE d.verification_status='Pending' ORDER BY d.created_at DESC LIMIT 6");
$recentAppointments = mysqli_query($conn, "SELECT a.appointment_id,a.appointment_date,a.appointment_time,a.status,u_doc.full_name doctor_name,u_pat.full_name patient_name,cl.clinic_name FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u_doc ON u_doc.user_id=d.user_id JOIN patients p ON p.patient_id=a.patient_id JOIN users u_pat ON u_pat.user_id=p.user_id JOIN clinics cl ON cl.clinic_id=a.clinic_id ORDER BY a.updated_at DESC LIMIT 5");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Nexus Oversight | CARE Platform</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <!-- Admin Sidebar -->
        <aside class="dash-sidebar">
            <a class="brand" href="../index.php">
                CARE <span>NEXUS</span>
            </a>
            <div class="eyebrow" style="color: var(--violet-quantum); margin-bottom: 24px;">ADMIN OVERSIGHT NEXUS</div>
            
            <nav class="dash-nav">
                <a class="active" href="dashboard.php">❖ Control HUD</a>
                <a href="doctors.php">❖ Doctor Approval Queue</a>
                <a href="users.php">❖ User Telemetry</a>
                <a href="appointments.php">❖ Appointments Nexus</a>
                <a href="directory.php">❖ City Nodes & Specs</a>
                <a href="notifications.php">❖ Notification Manager</a>
                <a href="../logout.php" style="margin-top: auto; color: var(--rose-danger);">❖ Exit Control</a>
            </nav>
        </aside>

        <!-- Admin Nexus Content -->
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">ADMIN NEXUS CONTROL</p>
                    <h2>Centralized System Oversight</h2>
                </div>
                <a class="btn btn-primary" href="doctors.php?status=Pending">Review Doctor Approvals</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <!-- Metrics Cards Grid -->
            <section class="hud-grid">
                <a class="hud-metric" href="users.php">
                    <label>REGISTERED USERS</label>
                    <div class="value"><?=$stats['users_total']?></div>
                    <span class="subtext">❖ <?=$stats['users_active']?> active / <?=$stats['users_suspended']?> suspended</span>
                </a>

                <a class="hud-metric" href="doctors.php?status=Pending" style="border-color: rgba(245, 158, 11, 0.4);">
                    <label>PENDING DOCTORS</label>
                    <div class="value" style="color: var(--amber-flux);"><?=$stats['doctors_pending']?></div>
                    <span class="subtext" style="color: var(--amber-flux);">❖ <?=$stats['doctors_verified']?> verified live</span>
                </a>

                <a class="hud-metric" href="appointments.php">
                    <label>APPOINTMENT NEXUS</label>
                    <div class="value" style="color: var(--cyan-neon);"><?=$stats['appointments_total']?></div>
                    <span class="subtext" style="color: var(--cyan-neon);">❖ <?=$stats['appointments_pending']?> pending review</span>
                </a>

                <a class="hud-metric" href="appointments.php?status=Completed" style="border-color: rgba(5, 150, 105, 0.4);">
                    <label>TOTAL REVENUE</label>
                    <div class="value" style="color: var(--emerald-bio); font-size: clamp(24px, 3vw, 34px);">PKR <?=number_format((float)$stats['completed_value'])?></div>
                    <span class="subtext" style="color: var(--emerald-bio);">Completed appointments only</span>
                </a>

                <a class="hud-metric" href="directory.php">
                    <label>DIRECTORY NETWORK</label>
                    <div class="value" style="color: var(--emerald-bio);"><?=$stats['clinics_active']?></div>
                    <span class="subtext" style="color: var(--emerald-bio);">❖ <?=$stats['specs_active']?> active specialties</span>
                </a>
            </section>

            <!-- Funnel & Pressure Charts -->
            <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 28px; margin-bottom: 36px;">
                <article class="cyber-table-wrap" style="margin:0;">
                    <div class="section-heading" style="margin-bottom: 20px;">
                        <div>
                            <p class="eyebrow">STATUS FUNNEL</p>
                            <h3 style="margin:0; font-size: 20px; color: var(--text-main);">Appointment Distribution</h3>
                        </div>
                        <span style="font-family: var(--font-mono); color: var(--rose-danger); font-size: 12px; font-weight: 700;"><?=$frictionRate?>% FRICTION</span>
                    </div>
                    <div style="display: grid; gap: 14px;">
                        <?php foreach(['Pending','Confirmed','Completed','Cancelled','NoShow'] as $status):
                            $value = (int)($appointmentStatus[$status] ?? 0);
                            $width = max(8, round(($value / $totalAppointments) * 100));
                        ?>
                            <div style="display: grid; grid-template-columns: 100px 1fr 40px; gap: 12px; align-items: center; font-family: var(--font-mono); font-size: 12px;">
                                <span style="color: var(--text-muted);"><?=$status?></span>
                                <div style="height: 10px; background: rgba(226,232,240,0.9); border-radius: 999px; overflow: hidden;">
                                    <div style="width: <?=$width?>%; height: 100%; background: linear-gradient(90deg, var(--cyan-neon), var(--violet-quantum));"></div>
                                </div>
                                <b style="color: var(--text-main); text-align: right;"><?=$value?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>

                <article class="cyber-table-wrap" style="margin:0;">
                    <div class="section-heading" style="margin-bottom: 20px;">
                        <div>
                            <p class="eyebrow">DEMAND MATRIX</p>
                            <h3 style="margin:0; font-size: 20px; color: var(--text-main);">Weekday Telemetry</h3>
                        </div>
                        <span style="font-family: var(--font-mono); color: var(--emerald-bio); font-size: 12px; font-weight: 700;"><?=$completionRate?>% COMPLETE</span>
                    </div>
                    <div class="weekday-chart" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; height: 180px; align-items: end;">
                        <?php foreach($weekdays as $day => $value):
                            $height = max(12, round(($value / $maxWeekday) * 100));
                        ?>
                            <div style="height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center; gap: 6px;">
                                <div style="width: 100%; height: <?=$height?>%; background: linear-gradient(180deg, var(--cyan-neon), var(--emerald-bio)); border-radius: 4px;"></div>
                                <span style="font-family: var(--font-mono); font-size: 10px; color: var(--text-muted);"><?=substr($day, 0, 3)?></span>
                                <b style="font-family: var(--font-mono); font-size: 11px; color: var(--text-main);"><?=$value?></b>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </article>
            </section>

            <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 28px; margin-bottom: 36px;">
                <article class="cyber-table-wrap" style="margin:0;">
                    <div class="section-heading" style="margin-bottom: 20px;">
                        <div>
                            <p class="eyebrow">REVENUE FLOW</p>
                            <h3 style="margin:0; font-size: 20px; color: var(--text-main);">Cash vs Card</h3>
                        </div>
                        <span style="font-family: var(--font-mono); color: var(--emerald-bio); font-size: 12px; font-weight: 700;">PKR <?=number_format((float)$stats['completed_value'])?></span>
                    </div>
                    <?php
                        $totalRevenue = max((float)$stats['completed_value'], 1);
                        foreach(['Cash','Card'] as $method):
                            $amount = (float)($paymentRevenue[$method] ?? 0);
                            $width = $amount > 0 ? max(8, round(($amount / $totalRevenue) * 100)) : 0;
                    ?>
                        <div style="display:grid;grid-template-columns:70px 1fr 120px;gap:12px;align-items:center;margin-bottom:14px;font-family:var(--font-mono);font-size:12px;">
                            <span style="color:var(--text-muted);"><?=$method?></span>
                            <div style="height:12px;background:rgba(226,232,240,0.9);border-radius:999px;overflow:hidden;">
                                <div style="width:<?=$width?>%;height:100%;background:linear-gradient(90deg,var(--emerald-bio),var(--cyan-neon));"></div>
                            </div>
                            <b style="color:var(--text-main);text-align:right;">PKR <?=number_format($amount)?></b>
                        </div>
                    <?php endforeach; ?>
                </article>

                <article class="cyber-table-wrap" style="margin:0;">
                    <div class="section-heading" style="margin-bottom: 20px;">
                        <div>
                            <p class="eyebrow">REVENUE CHART</p>
                            <h3 style="margin:0; font-size: 20px; color: var(--text-main);">Completed Revenue</h3>
                        </div>
                        <span style="font-family: var(--font-mono); color: var(--cyan-neon); font-size: 12px; font-weight: 700;"><?=count($monthlyRevenue)?> MONTHS</span>
                    </div>
                    <?php if($monthlyRevenue): ?>
                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(72px,1fr));gap:12px;height:220px;align-items:end;">
                            <?php foreach($monthlyRevenue as $month => $amount):
                                $height = max(12, round(($amount / $maxRevenue) * 100));
                            ?>
                                <div style="height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:7px;">
                                    <b style="font-family:var(--font-mono);font-size:11px;color:var(--text-main);">PKR <?=number_format($amount)?></b>
                                    <div title="<?=h($month)?> revenue" style="width:100%;height:<?=$height?>%;background:linear-gradient(180deg,var(--emerald-bio),var(--cyan-neon));border-radius:5px 5px 2px 2px;"></div>
                                    <span style="font-family:var(--font-mono);font-size:10px;color:var(--text-muted);text-align:center;"><?=h($month)?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 30px;">
                            <h4 style="margin:0;color:var(--text-main);">No completed revenue yet</h4>
                            <p style="margin:6px 0 0;font-size:13px;">Revenue appears after an appointment is completed with Cash or Card payment.</p>
                        </div>
                    <?php endif; ?>
                </article>
            </section>

            <!-- Pending Approvals & Activity -->
            <section style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 28px;">
                <article class="cyber-table-wrap" style="margin:0;">
                    <div class="section-heading" style="margin-bottom: 16px;">
                        <div>
                            <p class="eyebrow">VERIFICATION QUEUE</p>
                            <h3 style="margin:0; font-size: 18px; color: var(--text-main);">Doctors Waiting Verification</h3>
                        </div>
                        <a href="doctors.php?status=Pending" style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon);">Review All</a>
                    </div>
                    <?php if(mysqli_num_rows($pendingDoctors)): ?>
                        <div style="display: grid; gap: 12px;">
                            <?php while($d = mysqli_fetch_assoc($pendingDoctors)):
                                $photo = doctorFileUrl($d['profile_image'], 'profile');
                            ?>
                                <a href="doctor_detail.php?id=<?=$d['doctor_id']?>" style="display: flex; gap: 14px; align-items: center; padding: 12px; border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); background: var(--bg-card);">
                                    <img src="<?=h($photo ?: 'https://images.unsplash.com/photo-1622253692010-333f2da6031d?w=300')?>" style="width: 48px; height: 48px; border-radius: 12px; object-fit: cover;" alt="">
                                    <div style="flex-grow: 1;">
                                        <strong style="color: var(--text-main); display: block;">Dr. <?=h($d['full_name'])?></strong>
                                        <span style="font-size: 12px; color: var(--text-muted);"><?=h($d['specialization_name'])?> &middot; <?=h($d['city_name'])?></span>
                                    </div>
                                    <span class="btn btn-outline" style="padding: 6px 12px; font-size: 11px;">Review</span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state" style="padding: 30px;">
                            <h4 style="margin:0; color: var(--text-main);">No pending doctor approvals</h4>
                            <p style="margin:6px 0 0; font-size: 13px;">All practitioner credentials verified.</p>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="cyber-table-wrap" style="margin:0;">
                    <div class="section-heading" style="margin-bottom: 16px;">
                        <div>
                            <p class="eyebrow">SYSTEM LOGS</p>
                            <h3 style="margin:0; font-size: 18px; color: var(--text-main);">Recent Appointment Activity</h3>
                        </div>
                        <a href="appointments.php" style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon);">Manage Nexus</a>
                    </div>
                    <div style="display: grid; gap: 12px;">
                        <?php while($a = mysqli_fetch_assoc($recentAppointments)): ?>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; border: 1px solid var(--border-cyber); border-radius: var(--radius-sm); font-size: 13px; background: var(--bg-card);">
                                <div>
                                    <strong style="font-family: var(--font-mono); font-size: 12px; color: var(--cyan-neon);">
                                        <?=date('d M Y', strtotime($a['appointment_date']))?> - <?=date('h:i A', strtotime($a['appointment_time']))?>
                                    </strong>
                                    <div style="color: var(--text-muted); margin-top: 4px;">
                                        Dr. <?=h($a['doctor_name'])?> &middot; Patient: <?=h($a['patient_name'])?>
                                    </div>
                                </div>
                                <span class="status-pill status-<?=strtolower($a['status'])?>"><?=h($a['status'])?></span>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </article>
            </section>
        </main>
    </div>
    <?php include_once __DIR__ . '/../includes/chatbot.php'; ?>
</body>
</html>
