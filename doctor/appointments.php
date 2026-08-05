<?php
require_once __DIR__.'/../config/db.php';
require_once __DIR__.'/../config/appointment_actions.php';
ensureAppointmentChangeSchema($conn);

if(empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Doctor'){
    header('Location: ../login.php');
    exit;
}

$uid = (int)$_SESSION['user_id'];
$d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT doctor_id FROM doctors WHERE user_id=$uid"));
if(!$d){
    header('Location: ../register_doctor.php');
    exit;
}

$id = (int)$d['doctor_id'];
$msg = '';
$allowed = ['Confirmed','Cancelled','Completed','NoShow'];
if(isset($_POST['status']) && in_array($_POST['status'], $allowed, true)){
    $aid = (int)$_POST['appointment_id'];
    $status = $_POST['status'];
    [$text, $type] = updateAppointmentStatusWithNotifications($conn, $aid, $status, 'Doctor', $id);
    $msg = '<div class="alert alert-' . ($type === 'success' ? 'success' : 'error') . '">' . htmlspecialchars($text) . '</div>';
}

$filter = $_GET['status'] ?? '';
$validStatuses = ['Pending','Confirmed','Completed','Cancelled','NoShow'];
$where = in_array($filter, $validStatuses, true) ? " AND a.status='".mysqli_real_escape_string($conn, $filter)."'" : '';
$apps = mysqli_query($conn, "SELECT a.*,u.full_name patient_name,u.phone,c.clinic_name FROM appointments a JOIN patients p ON p.patient_id=a.patient_id JOIN users u ON u.user_id=p.user_id JOIN clinics c ON c.clinic_id=a.clinic_id WHERE a.doctor_id=$id $where ORDER BY a.appointment_date ASC,a.appointment_time ASC");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Appointments</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body">
    <aside class="sidebar">
        <a class="brand" href="dashboard.php">care<span>connect</span></a>
        <p class="side-label">DOCTOR PORTAL</p>
        <a href="dashboard.php">Overview</a>
        <a class="active" href="appointments.php">Appointments</a>
        <a href="availability.php">Availability</a>
        <a href="clinics.php">Clinics</a>
        <a href="profile.php">Profile</a>
    </aside>
    <main class="dashboard-main">
        <header class="dash-header">
            <div>
                <p class="eyebrow">PATIENT CARE</p>
                <h1>Appointments.</h1>
            </div>
        </header>
        <?=$msg?>
        <section class="panel">
            <div class="panel-head">
                <div>
                    <?php foreach(array_merge(['All'], $validStatuses) as $x): ?>
                        <a class="btn <?=$filter===$x || ($x==='All' && !$filter) ? 'btn-primary' : 'btn-outline'?>" style="padding:8px 11px;margin-right:5px" href="appointments.php<?=$x==='All' ? '' : '?status='.$x?>"><?=$x?></a>
                    <?php endforeach; ?>
                </div>
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
                                <h3><?=htmlspecialchars($a['patient_name'])?></h3>
                                <p><?=htmlspecialchars($a['clinic_name'])?> &middot; <?=htmlspecialchars($a['phone'])?></p>
                                <?php if(!empty($a['reschedule_reason'])): ?>
                                    <p class="change-note">Changed by <?=htmlspecialchars($a['rescheduled_by'])?>: <?=htmlspecialchars($a['reschedule_reason'])?></p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <b><?=date('h:i A', strtotime($a['appointment_time']))?></b>
                                <p><?=htmlspecialchars($a['reason'])?></p>
                                <?php if(!empty($a['symptom_photo_path'])): ?>
                                    <a class="appointment-photo-link" href="../<?=htmlspecialchars($a['symptom_photo_path'])?>" target="_blank" rel="noopener">View Symptom Photo</a>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; flex-direction:column; align-items:flex-end; gap:8px;">
                                <span class="status status-<?=$a['status']?>"><?=$a['status']?></span>
                                <?php if(in_array($a['status'], ['Pending','Confirmed'], true)): ?>
                                    <div style="display:flex; gap:6px; margin-bottom:4px;">
                                        <a href="../edit_appointment.php?id=<?=$a['appointment_id']?>" class="btn btn-outline" style="padding: 6px 10px; font-size: 11px;">Edit Details</a>
                                    </div>
                                    <form method="post" style="display:flex; gap:6px; flex-wrap:wrap; justify-content:flex-end;">
                                        <input type="hidden" name="appointment_id" value="<?=$a['appointment_id']?>">
                                        <?php if($a['status'] === 'Pending'): ?>
                                            <button class="btn btn-primary" style="padding: 8px 12px; font-size: 11px;" name="status" value="Confirmed">Confirm</button>
                                        <?php else: ?>
                                            <select name="payment_method" style="padding: 8px 10px; font-size: 11px;">
                                                <option value="">Payment?</option>
                                                <option value="Cash">Cash</option>
                                                <option value="Card">Card</option>
                                            </select>
                                            <button class="btn btn-primary" style="padding: 8px 12px; font-size: 11px;" name="status" value="Completed">Complete</button>
                                            <button class="btn btn-outline" style="padding: 8px 12px; font-size: 11px; border-color:var(--danger); color:var(--danger);" name="status" value="NoShow">No Show</button>
                                        <?php endif; ?>
                                        <button class="btn btn-outline" style="padding: 8px 12px; font-size: 11px;" name="status" value="Cancelled">Cancel</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No appointments here</h3>
                    <p>Appointments from patients will appear once they book an available slot.</p>
                </div>
            <?php endif; ?>
        </section>
    </main>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
