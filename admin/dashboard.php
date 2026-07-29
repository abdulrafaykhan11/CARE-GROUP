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
$maxWeekday = max(max($weekdays), 1);
$totalAppointments = max(1, (int) $stats['appointments_total']);
$completionRate = round(((int) ($appointmentStatus['Completed'] ?? 0) / $totalAppointments) * 100);
$frictionRate = round((((int) ($appointmentStatus['Cancelled'] ?? 0) + (int) ($appointmentStatus['NoShow'] ?? 0)) / $totalAppointments) * 100);

$pendingDoctors = mysqli_query($conn, "SELECT d.doctor_id,d.profile_image,d.qualification,d.experience_years,d.pmdc_registration_number,d.verification_status,u.full_name,u.email,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id WHERE d.verification_status='Pending' ORDER BY d.created_at DESC LIMIT 6");
$recentAppointments = mysqli_query($conn, "SELECT a.appointment_id,a.appointment_date,a.appointment_time,a.status,u_doc.full_name doctor_name,u_pat.full_name patient_name,cl.clinic_name FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u_doc ON u_doc.user_id=d.user_id JOIN patients p ON p.patient_id=a.patient_id JOIN users u_pat ON u_pat.user_id=p.user_id JOIN clinics cl ON cl.clinic_id=a.clinic_id ORDER BY a.updated_at DESC LIMIT 5");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Overview | Care Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('overview'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div>
                <p class="eyebrow">ADMIN OVERVIEW</p>
                <h1>Control room</h1>
                <p>High-level health of users, approvals, appointments, and directory coverage.</p>
            </div>
            <a class="btn btn-primary" href="doctors.php?status=Pending">Review doctors</a>
        </header>

        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <section class="admin-card-grid">
            <a class="admin-stat-card" href="users.php"><span>Users</span><strong><?=$stats['users_total']?></strong><small><?=$stats['users_active']?> active, <?=$stats['users_suspended']?> suspended</small></a>
            <a class="admin-stat-card urgent" href="doctors.php?status=Pending"><span>Pending doctors</span><strong><?=$stats['doctors_pending']?></strong><small><?=$stats['doctors_verified']?> verified doctors live</small></a>
            <a class="admin-stat-card" href="appointments.php"><span>Appointments</span><strong><?=$stats['appointments_total']?></strong><small><?=$stats['appointments_pending']?> pending requests</small></a>
            <a class="admin-stat-card" href="directory.php"><span>Directory</span><strong><?=$stats['clinics_active']?></strong><small><?=$stats['specs_active']?> active specialties</small></a>
            <a class="admin-stat-card" href="appointments.php?status=Completed"><span>Completed value</span><strong>PKR <?=number_format((float)$stats['completed_value'])?></strong><small>from completed appointments</small></a>
        </section>

        <section class="admin-overview-grid">
            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">CARE FLOW</p><h2>Status funnel</h2></div><span class="status status-Confirmed"><?=$frictionRate?>% friction</span></div>
                <div class="funnel-bars">
                    <?php foreach(['Pending','Confirmed','Completed','Cancelled','NoShow'] as $status):
                        $value = (int)($appointmentStatus[$status] ?? 0);
                        $width = max(8, round(($value / $totalAppointments) * 100));
                    ?>
                        <div class="funnel-row"><span><?=$status?></span><div><i style="width: <?=$width?>%"></i></div><b><?=$value?></b></div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">DEMAND MAP</p><h2>Weekday pressure</h2></div><span class="status status-Confirmed"><?=$completionRate?>% complete</span></div>
                <div class="weekday-chart">
                    <?php foreach($weekdays as $day => $value):
                        $height = max(10, round(($value / $maxWeekday) * 100));
                    ?>
                        <div><span style="height: <?=$height?>%"></span><small><?=substr($day, 0, 3)?></small><b><?=$value?></b></div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="admin-overview-grid">
            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">APPROVAL QUEUE</p><h2>Doctors waiting</h2></div><a href="doctors.php?status=Pending">View all</a></div>
                <?php if(mysqli_num_rows($pendingDoctors)): ?>
                    <div class="admin-doctor-mini-list">
                        <?php while($d = mysqli_fetch_assoc($pendingDoctors)):
                            $photo = doctorFileUrl($d['profile_image'], 'profile');
                        ?>
                            <a href="doctor_detail.php?id=<?=$d['doctor_id']?>">
                                <?php if($photo): ?><img src="<?=h($photo)?>" onerror="this.style.display='none'" alt=""><?php endif; ?>
                                <div><strong>Dr. <?=h($d['full_name'])?></strong><span><?=h($d['specialization_name'])?> - <?=h($d['city_name'])?></span><small>PMDC <?=h($d['pmdc_registration_number'])?></small></div>
                                <em>Review</em>
                            </a>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>No pending doctors</h3><p>All doctor profiles are already reviewed.</p></div>
                <?php endif; ?>
            </article>

            <article class="panel admin-clean-panel">
                <div class="panel-head"><div><p class="eyebrow">RECENT ACTIVITY</p><h2>Appointments</h2></div><a href="appointments.php">Manage</a></div>
                <div class="admin-simple-list">
                    <?php while($a = mysqli_fetch_assoc($recentAppointments)): ?>
                        <a href="appointments.php">
                            <strong><?=date('d M Y', strtotime($a['appointment_date']))?> - <?=date('h:i A', strtotime($a['appointment_time']))?></strong>
                            <span>Dr. <?=h($a['doctor_name'])?> with <?=h($a['patient_name'])?> at <?=h($a['clinic_name'])?></span>
                            <small><?=h($a['status'])?></small>
                        </a>
                    <?php endwhile; ?>
                </div>
            </article>
        </section>
    </main>
</body>
</html>
