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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Doctor Command Center | CARE Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <!-- Command Center Sidebar -->
        <aside class="dash-sidebar">
            <a class="brand" href="../index.php">
                CARE <span>NEXUS</span>
            </a>
            <div class="eyebrow" style="color: var(--emerald-bio); margin-bottom: 24px;">DOCTOR COMMAND HUD</div>
            
            <nav class="dash-nav">
                <a class="active" href="dashboard.php">❖ Overview HUD</a>
                <a href="appointments.php">❖ Appointments Queue</a>
                <a href="availability.php">❖ Availability Flux</a>
                <a href="clinics.php">❖ Clinic Nodes</a>
                <a href="profile.php">❖ Profile Shard</a>
                <a href="../logout.php" style="margin-top: auto; color: var(--rose-danger);">❖ Sign Out</a>
            </nav>
        </aside>

        <!-- Command HUD Content -->
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">DOCTOR COMMAND CENTER</p>
                    <h2>Welcome, Dr. <?=htmlspecialchars(explode(' ', $doc['full_name'])[0])?></h2>
                </div>
                <a class="btn btn-primary" href="appointments.php">Full Appointments Queue</a>
            </header>

            <?=$msg?>

            <!-- Bio-Metric Telemetry Stats HUD -->
            <section class="hud-grid">
                <article class="hud-metric">
                    <label>AWAITING ACTION</label>
                    <div class="value"><?=$st['pending'] ?? 0?></div>
                    <span class="subtext">❖ Needs your confirmation</span>
                </article>

                <article class="hud-metric">
                    <label>CONFIRMED VISITS</label>
                    <div class="value"><?=$st['confirmed'] ?? 0?></div>
                    <span class="subtext" style="color: var(--cyan-neon);">❖ Upcoming schedule</span>
                </article>

                <article class="hud-metric">
                    <label>COMPLETED CARE</label>
                    <div class="value" style="color: var(--emerald-bio);"><?=$st['completed'] ?? 0?></div>
                    <span class="subtext" style="color: var(--emerald-bio);">❖ Successfully delivered</span>
                </article>

                <article class="hud-metric">
                    <label>MISSED / NO-SHOW</label>
                    <div class="value" style="color: var(--rose-danger);"><?=$st['noshow'] ?? 0?></div>
                    <span class="subtext" style="color: var(--rose-danger);">❖ Patient missed visit</span>
                </article>
            </section>

            <!-- Upcoming Appointments Queue Panel -->
            <section class="cyber-table-wrap">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <p class="eyebrow">SCHEDULE QUEUE</p>
                        <h3 style="margin: 0; font-size: 20px; color: var(--text-main);">Upcoming Patient Appointments</h3>
                    </div>
                    <a href="appointments.php" class="btn btn-outline">View All</a>
                </div>

                <?php if(mysqli_num_rows($apps)): ?>
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>DATE & TIME</th>
                                <th>PATIENT NAME</th>
                                <th>CLINIC NODE</th>
                                <th>REASON</th>
                                <th>STATUS</th>
                                <th>COMMAND ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($a = mysqli_fetch_assoc($apps)): ?>
                                <tr>
                                    <td style="font-family: var(--font-mono); font-weight: 700; color: var(--cyan-neon);">
                                        <?=date('d M Y', strtotime($a['appointment_date']))?><br>
                                        <small style="color: var(--text-muted);"><?=date('h:i A', strtotime($a['appointment_time']))?></small>
                                    </td>
                                    <td>
                                        <strong style="color: var(--text-main);"><?=htmlspecialchars($a['patient_name'])?></strong>
                                        <?php if(!empty($a['reschedule_reason'])): ?>
                                            <div style="color: var(--rose-danger); font-size: 11px; margin-top: 4px;">
                                                Rescheduled: <?=htmlspecialchars($a['reschedule_reason'])?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-muted);"><?=htmlspecialchars($a['clinic_name'])?></td>
                                    <td><?=htmlspecialchars($a['reason'])?></td>
                                    <td>
                                        <span class="status-pill status-<?=strtolower($a['status'])?>">
                                            <?=$a['status']?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(in_array($a['status'], ['Pending','Confirmed'], true)): ?>
                                            <form method="post" style="display: flex; gap: 8px;">
                                                <input type="hidden" name="appointment_id" value="<?=$a['appointment_id']?>">
                                                <?php if($a['status'] === 'Pending'): ?>
                                                    <button class="btn btn-primary" style="padding: 6px 14px; font-size: 11px;" name="status" value="Confirmed">Confirm</button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary" style="padding: 6px 14px; font-size: 11px;" name="status" value="Completed">Complete</button>
                                                    <button class="btn btn-outline" style="padding: 6px 14px; font-size: 11px; border-color: var(--rose-danger); color: var(--rose-danger);" name="status" value="NoShow">No Show</button>
                                                <?php endif; ?>
                                                <button class="btn btn-outline" style="padding: 6px 14px; font-size: 11px;" name="status" value="Cancelled">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span style="font-family: var(--font-mono); font-size: 12px; color: var(--text-dim);">No Actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>Your schedule queue is currently clear</h3>
                        <p>Configure availability flux so patients can book visits.</p>
                        <a class="btn btn-primary" href="availability.php" style="margin-top: 15px;">Configure Availability Flux</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
    <?php include_once __DIR__ . '/../includes/chatbot.php'; ?>
</body>
</html>
