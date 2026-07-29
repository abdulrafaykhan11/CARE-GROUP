<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/appointment_schema.php';
ensureAppointmentChangeSchema($conn);

if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Doctor'){
    header('Location: ../login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$doc = mysqli_fetch_assoc(mysqli_query($conn, "SELECT d.*,u.full_name FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.user_id=$uid"));
if(!$doc){
    header('Location: ../register_doctor.php');
    exit;
}

$id = (int)$doc['doctor_id'];
$msg = '';
$allowed = ['Confirmed','Cancelled','Completed','NoShow'];
if(isset($_POST['status']) && in_array($_POST['status'], $allowed, true)){
    $aid = (int)$_POST['appointment_id'];
    $status = $_POST['status'];
    mysqli_query($conn, "UPDATE appointments SET status='$status' WHERE appointment_id=$aid AND doctor_id=$id AND status IN ('Pending','Confirmed')");
    $msg = '<div class="alert alert-success">Appointment status updated.</div>';

    if($status === 'Confirmed'){
        require_once __DIR__.'/../config/mail.php';
        $appDetails = mysqli_fetch_assoc(mysqli_query($conn, "SELECT a.appointment_date, a.appointment_time, u_pat.full_name patient_name, u_pat.email patient_email, u_doc.full_name doctor_name, u_doc.email doctor_email, c.clinic_name FROM appointments a JOIN patients p ON a.patient_id = p.patient_id JOIN users u_pat ON p.user_id = u_pat.user_id JOIN doctors d ON a.doctor_id = d.doctor_id JOIN users u_doc ON d.user_id = u_doc.user_id JOIN clinics c ON a.clinic_id = c.clinic_id WHERE a.appointment_id = $aid"));
        if($appDetails){
            sendAppointmentConfirmationEmail([
                'patient_email' => $appDetails['patient_email'],
                'patient_name' => $appDetails['patient_name'],
                'doctor_email' => $appDetails['doctor_email'],
                'doctor_name' => $appDetails['doctor_name'],
                'date' => date('d M Y', strtotime($appDetails['appointment_date'])),
                'time' => date('h:i A', strtotime($appDetails['appointment_time'])),
                'clinic' => $appDetails['clinic_name']
            ]);
        }
    }
}

$st = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) total,SUM(status='Pending') pending,SUM(status='Confirmed') confirmed,SUM(status='Completed') completed, SUM(status='NoShow') noshow FROM appointments WHERE doctor_id=$id"));
$apps = mysqli_query($conn, "SELECT a.*,u.full_name patient_name,c.clinic_name FROM appointments a JOIN patients p ON p.patient_id=a.patient_id JOIN users u ON u.user_id=p.user_id JOIN clinics c ON c.clinic_id=a.clinic_id WHERE a.doctor_id=$id ORDER BY a.appointment_date ASC,a.appointment_time ASC LIMIT 6");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctor Dashboard | Care Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .btn-sm { padding: 8px 12px; font-size: 11px; border-radius: 6px; }
        .actions-col { display: flex; gap: 6px; flex-wrap: wrap; }
    </style>
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="../index.php">care<span>connect</span></a>
        <p class="side-label">DOCTOR PORTAL</p>
        <a class="active" href="dashboard.php">Overview</a>
        <a href="appointments.php">Appointments</a>
        <a href="availability.php">Availability</a>
        <a href="clinics.php">Clinics</a>
        <a href="profile.php">Profile</a>
        <a href="../logout.php">Sign out</a>
    </aside>
    <main class="dashboard-main">
        <header class="dash-header">
            <div>
                <p class="eyebrow">PRACTICE OVERVIEW</p>
                <h1>Hello, Dr. <?=htmlspecialchars(explode(' ', $doc['full_name'])[0])?>.</h1>
            </div>
            <a class="btn btn-primary" href="appointments.php">Manage appointments</a>
        </header>

        <?=$msg?>

        <section class="stats-grid">
            <article><span>Awaiting action</span><strong><?=$st['pending'] ?? 0?></strong><small>Needs your decision</small></article>
            <article><span>Confirmed</span><strong><?=$st['confirmed'] ?? 0?></strong><small>Upcoming consultations</small></article>
            <article><span>Completed</span><strong><?=$st['completed'] ?? 0?></strong><small>Care delivered</small></article>
            <article><span>No Shows</span><strong style="color: var(--danger);"><?=$st['noshow'] ?? 0?></strong><small>Missed appointments</small></article>
        </section>

        <section class="panel">
            <div class="panel-head">
                <div>
                    <p class="eyebrow">SCHEDULE</p>
                    <h2>Upcoming appointments</h2>
                </div>
                <a href="appointments.php">View all</a>
            </div>
            <?php if(mysqli_num_rows($apps)): ?>
                <div class="appointment-list">
                    <?php while($a = mysqli_fetch_assoc($apps)): ?>
                        <article class="appointment-card" style="grid-template-columns: 60px 1.2fr 1.2fr auto;">
                            <div class="date-block">
                                <b><?=date('d', strtotime($a['appointment_date']))?></b>
                                <span><?=date('M', strtotime($a['appointment_date']))?></span>
                            </div>
                            <div>
                                <h3><?=htmlspecialchars($a['patient_name'])?></h3>
                                <p><?=htmlspecialchars($a['clinic_name'])?></p>
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
                                <?php if(in_array($a['status'], ['Pending','Confirmed'], true)): ?>
                                    <div style="display:flex; gap:6px; margin-bottom: 4px;">
                                        <a href="../edit_appointment.php?id=<?=$a['appointment_id']?>" class="btn btn-outline btn-sm">Edit</a>
                                    </div>
                                    <form method="post" class="actions-col">
                                        <input type="hidden" name="appointment_id" value="<?=$a['appointment_id']?>">
                                        <?php if($a['status'] === 'Pending'): ?>
                                            <button class="btn btn-primary btn-sm" name="status" value="Confirmed">Confirm</button>
                                        <?php else: ?>
                                            <button class="btn btn-primary btn-sm" name="status" value="Completed">Complete</button>
                                            <button class="btn btn-outline btn-sm" name="status" value="NoShow" style="border-color:var(--danger); color:var(--danger);">No Show</button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline btn-sm" name="status" value="Cancelled">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Your schedule is clear</h3>
                    <p>Add your availability so patients can request a suitable time.</p>
                    <a class="btn btn-primary" href="availability.php">Set availability</a>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
