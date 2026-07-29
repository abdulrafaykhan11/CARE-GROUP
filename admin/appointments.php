<?php
require_once __DIR__ . '/_admin.php';
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$status = $_GET['status'] ?? '';
$valid = ['Pending','Confirmed','Completed','Cancelled','NoShow'];
$where = in_array($status, $valid, true) ? "WHERE a.status='".mysqli_real_escape_string($conn, $status)."'" : '';
$appointments = mysqli_query($conn, "SELECT a.appointment_id,a.appointment_date,a.appointment_time,a.status,a.reason,a.reschedule_reason,a.rescheduled_by,u_doc.full_name doctor_name,u_pat.full_name patient_name,cl.clinic_name FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u_doc ON u_doc.user_id=d.user_id JOIN patients p ON p.patient_id=a.patient_id JOIN users u_pat ON u_pat.user_id=p.user_id JOIN clinics cl ON cl.clinic_id=a.clinic_id $where ORDER BY a.appointment_date DESC,a.appointment_time DESC");
$availabilityRows = mysqli_query($conn, "SELECT da.availability_id,da.day,da.start_time,da.end_time,da.slot_duration,da.status,u.full_name doctor_name,cl.clinic_name FROM doctor_availability da JOIN doctors d ON d.doctor_id=da.doctor_id JOIN users u ON u.user_id=d.user_id LEFT JOIN clinics cl ON cl.clinic_id=da.clinic_id ORDER BY da.updated_at DESC, da.availability_id DESC");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Appointments | Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <?php adminSidebar('appointments'); ?>
    <main class="dashboard-main admin-main">
        <header class="admin-page-head">
            <div><p class="eyebrow">APPOINTMENT CONTROL</p><h1>Appointments</h1><p>Change appointment status and turn doctor schedule slots on or off.</p></div>
            <a class="btn btn-outline" href="dashboard.php">Overview</a>
        </header>
        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <nav class="admin-tabs">
            <a class="<?=!$status?'active':''?>" href="appointments.php">All</a>
            <?php foreach($valid as $s): ?><a class="<?=$status===$s?'active':''?>" href="appointments.php?status=<?=$s?>"><?=$s?></a><?php endforeach; ?>
        </nav>

        <section class="panel admin-table-panel">
            <div class="panel-head"><div><p class="eyebrow">APPOINTMENTS</p><h2>Visits</h2></div></div>
            <div class="admin-table relaxed">
                <?php while($a = mysqli_fetch_assoc($appointments)): ?>
                    <article>
                        <div><strong><?=date('d M Y', strtotime($a['appointment_date']))?> at <?=date('h:i A', strtotime($a['appointment_time']))?></strong><span>Dr. <?=h($a['doctor_name'])?> with <?=h($a['patient_name'])?> - <?=h($a['clinic_name'])?></span><small><?=h($a['reason'])?><?=!empty($a['reschedule_reason']) ? ' - Changed by '.h($a['rescheduled_by']).': '.h($a['reschedule_reason']) : ''?></small></div>
                        <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_appointment_status"><input type="hidden" name="appointment_id" value="<?=$a['appointment_id']?>"><select name="status"><?php foreach($valid as $s): ?><option <?=$a['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="panel admin-table-panel">
            <div class="panel-head"><div><p class="eyebrow">AVAILABILITY</p><h2>Doctor schedules</h2></div></div>
            <div class="admin-table relaxed">
                <?php while($row = mysqli_fetch_assoc($availabilityRows)): ?>
                    <article>
                        <div><strong>Dr. <?=h($row['doctor_name'])?></strong><span><?=h($row['clinic_name'] ?? 'Clinic not assigned')?> - <?=$row['day']?> - <?=date('h:i A', strtotime($row['start_time']))?> to <?=date('h:i A', strtotime($row['end_time']))?></span><small><?=$row['slot_duration']?> minute slots</small></div>
                        <form method="post" class="inline-admin-form"><input type="hidden" name="action" value="set_availability_status"><input type="hidden" name="availability_id" value="<?=$row['availability_id']?>"><select name="status"><?php foreach(['Active','Inactive'] as $s): ?><option <?=$row['status']===$s?'selected':''?>><?=$s?></option><?php endforeach; ?></select><button class="btn btn-primary">Save</button></form>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>
    </main>
</body>
</html>
