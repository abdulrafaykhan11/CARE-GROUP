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
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Patient Discovery HUD | CARE Nexus</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
</head>
<body>
    <div class="dash-container">
        <!-- Patient Sidebar -->
        <aside class="dash-sidebar">
            <a class="brand" href="../index.php">
                CARE <span>NEXUS</span>
            </a>
            <div class="eyebrow" style="color: var(--cyan-neon); margin-bottom: 24px;">PATIENT DISCOVERY HUD</div>
            
            <nav class="dash-nav">
                <a class="active" href="dashboard.php">❖ My Care HUD</a>
                <a href="../find_doctor.php">❖ Holographic Search</a>
                <a href="profile.php">❖ Patient Telemetry</a>
                <a href="../logout.php" style="margin-top: auto; color: var(--rose-danger);">❖ Sign Out</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="dash-content">
            <header class="section-heading">
                <div>
                    <p class="eyebrow">PATIENT CARE TIMELINE</p>
                    <h2>Welcome back, <?=htmlspecialchars(explode(' ', $patient['full_name'])[0])?></h2>
                </div>
                <a class="btn btn-primary" href="../find_doctor.php">Book New Doctor Shard</a>
            </header>

            <!-- Stats HUD -->
            <section class="hud-grid">
                <article class="hud-metric">
                    <label>TOTAL APPOINTMENTS</label>
                    <div class="value"><?=$stats['total'] ?? 0?></div>
                    <span class="subtext">❖ All-time clinical visits</span>
                </article>

                <article class="hud-metric">
                    <label>AWAITING CONFIRMATION</label>
                    <div class="value" style="color: var(--amber-flux);"><?=$stats['pending'] ?? 0?></div>
                    <span class="subtext" style="color: var(--amber-flux);">❖ Doctor review pending</span>
                </article>

                <article class="hud-metric">
                    <label>CONFIRMED VISITS</label>
                    <div class="value" style="color: var(--cyan-neon);"><?=$stats['confirmed'] ?? 0?></div>
                    <span class="subtext" style="color: var(--cyan-neon);">❖ Scheduled upcoming</span>
                </article>

                <article class="hud-metric">
                    <label>COMPLETED CARE</label>
                    <div class="value" style="color: var(--emerald-bio);"><?=$stats['completed'] ?? 0?></div>
                    <span class="subtext" style="color: var(--emerald-bio);">❖ Care delivered</span>
                </article>
            </section>

            <!-- Appointments Table -->
            <section class="cyber-table-wrap">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <div>
                        <p class="eyebrow">CARE TIMELINE</p>
                        <h3 style="margin: 0; font-size: 20px; color: var(--text-main);">Your Clinical Visit Requests</h3>
                    </div>
                    <a href="../find_doctor.php" class="btn btn-outline">Find More Doctors</a>
                </div>

                <?php if(mysqli_num_rows($apps)): ?>
                    <table class="cyber-table">
                        <thead>
                            <tr>
                                <th>DATE & TIME</th>
                                <th>PRACTITIONER</th>
                                <th>SPECIALTY & CLINIC</th>
                                <th>REASON</th>
                                <th>STATUS</th>
                                <th>ACTIONS</th>
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
                                        <strong style="color: var(--text-main);">Dr. <?=htmlspecialchars($a['doctor_name'])?></strong>
                                        <?php if(!empty($a['reschedule_reason'])): ?>
                                            <div style="color: var(--rose-danger); font-size: 11px; margin-top: 4px;">
                                                Changed by <?=htmlspecialchars($a['rescheduled_by'])?>: <?=htmlspecialchars($a['reschedule_reason'])?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="color: var(--text-muted);">
                                        <?=htmlspecialchars($a['specialization_name'])?><br>
                                        <small><?=htmlspecialchars($a['clinic_name'])?></small>
                                    </td>
                                    <td><?=htmlspecialchars($a['reason'])?></td>
                                    <td>
                                        <span class="status-pill status-<?=strtolower($a['status'])?>">
                                            <?=$a['status']?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if(in_array($a['status'], ['Pending', 'Confirmed'], true)): ?>
                                            <a href="../edit_appointment.php?id=<?=$a['appointment_id']?>" class="btn btn-outline" style="padding: 6px 12px; font-size: 11px;">
                                                Edit Slot
                                            </a>
                                        <?php else: ?>
                                            <span style="font-family: var(--font-mono); font-size: 12px; color: var(--text-dim);">Completed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <h3>No clinical visits requested yet</h3>
                        <p>Discover top verified doctors and book your first consultation.</p>
                        <a class="btn btn-primary" href="../find_doctor.php" style="margin-top: 15px;">Launch Doctor Discovery</a>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
