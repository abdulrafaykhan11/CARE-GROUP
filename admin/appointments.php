<?php
require_once __DIR__ . '/_admin.php';
require_once __DIR__ . '/../config/appointment_schema.php';
ensureAppointmentChangeSchema($conn);
[$msg, $msgType] = adminFlashFromPost($conn, $adminId);

$status = $_GET['status'] ?? '';
$valid = ['Pending','Confirmed','Completed','Cancelled','NoShow'];
$where = in_array($status, $valid, true) ? "WHERE a.status='".mysqli_real_escape_string($conn, $status)."'" : '';
$appointments = mysqli_query($conn, "SELECT a.appointment_id,a.appointment_date,a.appointment_time,a.status,a.reason,a.symptom_photo_path,a.reschedule_reason,a.rescheduled_by,u_doc.full_name doctor_name,u_pat.full_name patient_name,cl.clinic_name FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u_doc ON u_doc.user_id=d.user_id JOIN patients p ON p.patient_id=a.patient_id JOIN users u_pat ON u_pat.user_id=p.user_id JOIN clinics cl ON cl.clinic_id=a.clinic_id $where ORDER BY a.appointment_date DESC,a.appointment_time DESC");
$availabilityRows = mysqli_query($conn, "SELECT da.availability_id,da.day,da.start_time,da.end_time,da.slot_duration,da.status,u.full_name doctor_name,cl.clinic_name FROM doctor_availability da JOIN doctors d ON d.doctor_id=da.doctor_id JOIN users u ON u.user_id=d.user_id LEFT JOIN clinics cl ON cl.clinic_id=da.clinic_id ORDER BY da.updated_at DESC, da.availability_id DESC");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Appointments Nexus | Admin Control</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <?php adminSidebar('appointments'); ?>
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">APPOINTMENTS & AVAILABILITY NEXUS</p>
                    <h2>System Appointments Queue</h2>
                </div>
                <a class="btn btn-outline" href="dashboard.php">Overview HUD</a>
            </header>

            <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

            <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 24px;">
                <a class="btn <?=$status===''?'btn-primary':'btn-outline'?>" href="appointments.php">All Appointments</a>
                <?php foreach($valid as $s): ?>
                    <a class="btn <?=$status===$s?'btn-primary':'btn-outline'?>" href="appointments.php?status=<?=$s?>"><?=$s?></a>
                <?php endforeach; ?>
            </div>

            <!-- Appointments Cyber Table -->
            <section class="cyber-table-wrap" style="margin-bottom: 40px;">
                <p class="eyebrow">VISITS TIMELINE</p>
                <h3 style="margin:0 0 16px; color: var(--text-main);">Clinical Appointments Log</h3>
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th>DATE & TIME</th>
                            <th>PATIENT & DOCTOR</th>
                            <th>CLINIC NODE</th>
                            <th>REASON</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($a = mysqli_fetch_assoc($appointments)): ?>
                            <tr>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: var(--cyan-neon);">
                                    <?=date('d M Y', strtotime($a['appointment_date']))?><br>
                                    <small style="color: var(--text-muted);"><?=date('h:i A', strtotime($a['appointment_time']))?></small>
                                </td>
                                <td>
                                    <strong style="color: var(--text-main);">Patient: <?=h($a['patient_name'])?></strong><br>
                                    <small style="color: var(--text-muted);">Doctor: Dr. <?=h($a['doctor_name'])?></small>
                                </td>
                                <td><?=h($a['clinic_name'])?></td>
                                <td>
                                    <?=h($a['reason'])?>
                                    <?php if(!empty($a['symptom_photo_path'])): ?>
                                        <a class="appointment-photo-link" href="../<?=h($a['symptom_photo_path'])?>" target="_blank" rel="noopener">View Symptom Photo</a>
                                    <?php endif; ?>
                                    <?php if(!empty($a['reschedule_reason'])): ?>
                                        <div style="color: var(--rose-danger); font-size: 11px; margin-top: 4px;">
                                            Changed by <?=h($a['rescheduled_by'])?>: <?=h($a['reschedule_reason'])?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-pill status-<?=strtolower($a['status'])?>"><?=h($a['status'])?></span>
                                </td>
                                <td>
                                    <form method="post" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="action" value="set_appointment_status">
                                        <input type="hidden" name="appointment_id" value="<?=$a['appointment_id']?>">
                                        <select name="status" style="padding: 4px 8px; font-size: 11px;">
                                            <?php foreach($valid as $s): ?>
                                                <option <?=$a['status']===$s?'selected':''?>><?=$s?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>

            <!-- Availability Schedules -->
            <section class="cyber-table-wrap">
                <p class="eyebrow">AVAILABILITY FLUX</p>
                <h3 style="margin:0 0 16px; color: var(--text-main);">Doctor Active Timings</h3>
                <table class="cyber-table">
                    <thead>
                        <tr>
                            <th>PRACTITIONER</th>
                            <th>CLINIC NODE</th>
                            <th>SCHEDULED DAY</th>
                            <th>TIMING WINDOW</th>
                            <th>SLOT DURATION</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($availabilityRows)): ?>
                            <tr>
                                <td><strong style="color: var(--text-main);">Dr. <?=h($row['doctor_name'])?></strong></td>
                                <td style="color: var(--text-muted);"><?=h($row['clinic_name'] ?? 'Unassigned')?></td>
                                <td style="font-family: var(--font-mono); font-weight: 700; color: var(--cyan-neon);"><?=$row['day']?></td>
                                <td style="font-family: var(--font-mono); font-size: 12px;">
                                    <?=date('h:i A', strtotime($row['start_time']))?> - <?=date('h:i A', strtotime($row['end_time']))?>
                                </td>
                                <td style="font-family: var(--font-mono);"><?=$row['slot_duration']?> mins</td>
                                <td><span class="status-pill status-<?=strtolower($row['status'])?>"><?=$row['status']?></span></td>
                                <td>
                                    <form method="post" style="display: flex; gap: 6px;">
                                        <input type="hidden" name="action" value="set_availability_status">
                                        <input type="hidden" name="availability_id" value="<?=$row['availability_id']?>">
                                        <select name="status" style="padding: 4px 8px; font-size: 11px;">
                                            <?php foreach(['Active','Inactive'] as $s): ?>
                                                <option <?=$row['status']===$s?'selected':''?>><?=$s?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn btn-primary" style="padding: 4px 10px; font-size: 11px;">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
    <script src="../assets/js/live_validation.js"></script>
</body>
</html>
