<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/appointment_schema.php';
ensureAppointmentChangeSchema($conn);

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Patient') {
    header('Location: ../login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*,u.full_name,u.email FROM patients p JOIN users u ON u.user_id=p.user_id WHERE p.user_id=$uid"));
if (!$patient) {
    header('Location: ../register_patients.php');
    exit;
}

$pid = (int)$patient['patient_id'];
$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total, SUM(status='Pending') pending, SUM(status='Confirmed') confirmed, SUM(status='Completed') completed FROM appointments WHERE patient_id=$pid"));
$apps = mysqli_query($conn, "SELECT a.*,u.full_name doctor_name,s.specialization_name,c.clinic_name FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN clinics c ON c.clinic_id=a.clinic_id WHERE a.patient_id=$pid ORDER BY a.appointment_date ASC,a.appointment_time ASC LIMIT 6");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>My Care Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="../index.php">care<span>connect</span></a>
        <p class="side-label">PATIENT PORTAL</p>
        <a class="active" href="dashboard.php">Overview</a>
        <a href="../find_doctor.php">Find doctors</a>
        <a href="../logout.php">Sign out</a>
    </aside>
    <main class="dashboard-main">
        <header class="dash-header">
            <div>
                <p class="eyebrow">YOUR HEALTH, ORGANISED</p>
                <h1>Good to see you, <?=htmlspecialchars(explode(' ', $patient['full_name'])[0])?>.</h1>
            </div>
            <a class="btn btn-primary" href="../find_doctor.php">Find a doctor</a>
        </header>

        <section class="stats-grid">
            <article><span>Appointments</span><strong><?=$stats['total'] ?? 0?></strong><small>All time</small></article>
            <article><span>Awaiting approval</span><strong><?=$stats['pending'] ?? 0?></strong><small>Doctor review pending</small></article>
            <article><span>Confirmed</span><strong><?=$stats['confirmed'] ?? 0?></strong><small>Upcoming visits</small></article>
            <article><span>Completed</span><strong><?=$stats['completed'] ?? 0?></strong><small>Care history</small></article>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">APPOINTMENTS</p>
                    <h2>Your care timeline</h2>
                </div>
                <a href="../find_doctor.php">Book another</a>
            </div>
            <?php if(mysqli_num_rows($apps)): ?>
                <div class="appointment-list">
                    <?php while($a = mysqli_fetch_assoc($apps)): ?>
                        <article class="appointment-card">
                            <div class="date-block">
                                <b><?=date('d', strtotime($a['appointment_date']))?></b>
                                <span><?=date('M', strtotime($a['appointment_date']))?></span>
                            </div>
                            <div>
                                <h3>Dr. <?=htmlspecialchars($a['doctor_name'])?></h3>
                                <p><?=htmlspecialchars($a['specialization_name'])?> &middot; <?=htmlspecialchars($a['clinic_name'])?></p>
                                <?php if(!empty($a['reschedule_reason'])): ?>
                                    <p class="change-note">Changed by <?=htmlspecialchars($a['rescheduled_by'])?>: <?=htmlspecialchars($a['reschedule_reason'])?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <b><?=date('h:i A', strtotime($a['appointment_time']))?></b>
                                <p><?=htmlspecialchars($a['reason'])?></p>
                            </div>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                                <span class="status status-<?=$a['status']?>"><?=$a['status']?></span>
                                <?php if(in_array($a['status'], ['Pending', 'Confirmed'], true)): ?>
                                    <a href="../edit_appointment.php?id=<?=$a['appointment_id']?>" class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;">Edit Details</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No visits booked yet</h3>
                    <p>Find a trusted specialist and book your first consultation.</p>
                    <a class="btn btn-primary" href="../find_doctor.php">Explore doctors</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
