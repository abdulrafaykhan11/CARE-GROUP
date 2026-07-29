<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/appointment_schema.php';
require_once __DIR__ . '/../config/directory_schema.php';
ensureAppointmentChangeSchema($conn);
ensureDirectorySchema($conn);

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$adminId = (int) $_SESSION['user_id'];
$msg = '';
$msgType = 'success';

function h($value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function postInt(string $key): int { return (int) ($_POST[$key] ?? 0); }
function oneCount(mysqli $conn, string $sql): int
{
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    return (int) ($row['total'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'set_user_status') {
        $userId = postInt('user_id');
        $status = $_POST['status'] ?? '';
        if ($userId === $adminId && $status !== 'Active') {
            $msg = 'You cannot disable the admin account you are using.';
            $msgType = 'error';
        } elseif (in_array($status, ['Active', 'Inactive', 'Suspended'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET status=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $userId);
            mysqli_stmt_execute($stmt);
            $msg = 'User status updated.';
        }
    }

    if ($action === 'delete_user') {
        $userId = postInt('user_id');
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM users WHERE user_id=$userId"));
        if (!$user) {
            $msg = 'User not found.';
            $msgType = 'error';
        } elseif ($userId === $adminId || $user['role'] === 'Admin') {
            $msg = 'Admin accounts cannot be deleted from this panel.';
            $msgType = 'error';
        } else {
            $linkedAppointments = 0;
            if ($user['role'] === 'Doctor') {
                $linkedAppointments = oneCount($conn, "SELECT COUNT(*) total FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id WHERE d.user_id=$userId");
            }
            if ($user['role'] === 'Patient') {
                $linkedAppointments = oneCount($conn, "SELECT COUNT(*) total FROM appointments a JOIN patients p ON p.patient_id=a.patient_id WHERE p.user_id=$userId");
            }
            if ($linkedAppointments > 0) {
                mysqli_query($conn, "UPDATE users SET status='Suspended' WHERE user_id=$userId");
                $msg = 'Account has appointment history, so it was suspended and removed from the website.';
            } else {
                mysqli_query($conn, "DELETE FROM users WHERE user_id=$userId");
                $msg = 'Account deleted permanently.';
            }
        }
    }

    if ($action === 'set_doctor_verification') {
        $doctorId = postInt('doctor_id');
        $status = $_POST['verification_status'] ?? '';
        if (in_array($status, ['Pending', 'Verified', 'Rejected'], true)) {
            if ($status === 'Verified') {
                mysqli_query($conn, "UPDATE doctors SET verification_status='Verified', verified_by=$adminId, verified_at=NOW() WHERE doctor_id=$doctorId");
            } else {
                $safe = mysqli_real_escape_string($conn, $status);
                mysqli_query($conn, "UPDATE doctors SET verification_status='$safe', verified_by=NULL, verified_at=NULL WHERE doctor_id=$doctorId");
            }
            $msg = 'Doctor verification updated.';
        }
    }

    if ($action === 'set_clinic_status') {
        $clinicId = postInt('clinic_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE clinics SET status=? WHERE clinic_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $clinicId);
            mysqli_stmt_execute($stmt);
            $msg = 'Clinic website visibility updated.';
        }
    }

    if ($action === 'delete_clinic') {
        $clinicId = postInt('clinic_id');
        $refs = oneCount($conn, "SELECT (SELECT COUNT(*) FROM appointments WHERE clinic_id=$clinicId) + (SELECT COUNT(*) FROM doctor_clinic WHERE clinic_id=$clinicId) total");
        if ($refs > 0) {
            mysqli_query($conn, "UPDATE clinics SET status='Inactive' WHERE clinic_id=$clinicId");
            $msg = 'Clinic has linked records, so it was made inactive instead of hard-deleted.';
        } else {
            mysqli_query($conn, "DELETE FROM clinics WHERE clinic_id=$clinicId");
            $msg = 'Clinic deleted permanently.';
        }
    }

    if ($action === 'add_specialization') {
        $name = trim($_POST['specialization_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $overview = trim($_POST['overview'] ?? '');
        if ($name === '' || $description === '') {
            $msg = 'Specialization name and description are required.';
            $msgType = 'error';
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO specializations (specialization_name,description,status) VALUES (?,?,'Active')");
            mysqli_stmt_bind_param($stmt, 'ss', $name, $description);
            if (mysqli_stmt_execute($stmt)) {
                $specId = mysqli_insert_id($conn);
                if ($overview !== '') {
                    $when = trim($_POST['when_to_book'] ?? '');
                    $tips = trim($_POST['care_points'] ?? '');
                    $stmt = mysqli_prepare($conn, "INSERT INTO specialization_guides (specialization_id,overview,when_to_book,care_points) VALUES (?,?,?,?)");
                    mysqli_stmt_bind_param($stmt, 'isss', $specId, $overview, $when, $tips);
                    mysqli_stmt_execute($stmt);
                }
                $msg = 'Specialization added.';
            } else {
                $msg = 'Could not add specialization. It may already exist.';
                $msgType = 'error';
            }
        }
    }

    if ($action === 'set_specialization_status') {
        $specId = postInt('specialization_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE specializations SET status=? WHERE specialization_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $specId);
            mysqli_stmt_execute($stmt);
            $msg = 'Specialization status updated.';
        }
    }

    if ($action === 'delete_specialization') {
        $specId = postInt('specialization_id');
        $refs = oneCount($conn, "SELECT COUNT(*) total FROM doctors WHERE specialization_id=$specId");
        if ($refs > 0) {
            mysqli_query($conn, "UPDATE specializations SET status='Inactive' WHERE specialization_id=$specId");
            $msg = 'Specialization has doctors attached, so it was made inactive.';
        } else {
            mysqli_query($conn, "DELETE FROM specializations WHERE specialization_id=$specId");
            $msg = 'Specialization deleted permanently.';
        }
    }

    if ($action === 'add_city') {
        $city = trim($_POST['city_name'] ?? '');
        $state = trim($_POST['state'] ?? '');
        if ($city === '') {
            $msg = 'City name is required.';
            $msgType = 'error';
        } else {
            $stmt = mysqli_prepare($conn, "INSERT INTO cities (city_name,state,country,status) VALUES (?,?,'Pakistan','Active')");
            mysqli_stmt_bind_param($stmt, 'ss', $city, $state);
            mysqli_stmt_execute($stmt);
            $msg = 'City added.';
        }
    }

    if ($action === 'set_city_status') {
        $cityId = postInt('city_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE cities SET status=? WHERE city_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $cityId);
            mysqli_stmt_execute($stmt);
            $msg = 'City status updated.';
        }
    }

    if ($action === 'set_appointment_status') {
        $appointmentId = postInt('appointment_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'NoShow'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=? WHERE appointment_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $appointmentId);
            mysqli_stmt_execute($stmt);
            $msg = 'Appointment status updated.';
        }
    }

    if ($action === 'set_availability_status') {
        $availabilityId = postInt('availability_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE doctor_availability SET status=? WHERE availability_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $availabilityId);
            mysqli_stmt_execute($stmt);
            $msg = 'Doctor availability status updated.';
        }
    }

    if ($action === 'set_news_status') {
        $newsId = postInt('news_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Draft', 'Published', 'Archived'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE medical_news SET status=?, published_at=IF(?='Published', COALESCE(published_at, NOW()), published_at) WHERE news_id=?");
            mysqli_stmt_bind_param($stmt, 'ssi', $status, $status, $newsId);
            mysqli_stmt_execute($stmt);
            $msg = 'Medical news status updated.';
        }
    }

    if ($action === 'set_faq_status') {
        $faqId = postInt('faq_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE specialization_faqs SET status=? WHERE faq_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $faqId);
            mysqli_stmt_execute($stmt);
            $msg = 'FAQ status updated.';
        }
    }
}

$stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT
    (SELECT COUNT(*) FROM users) users_total,
    (SELECT COUNT(*) FROM users WHERE status='Active') users_active,
    (SELECT COUNT(*) FROM users WHERE status='Suspended') users_suspended,
    (SELECT COUNT(*) FROM doctors) doctors_total,
    (SELECT COUNT(*) FROM doctors WHERE verification_status='Pending') doctors_pending,
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

$specialtyCoverage = [];
$r = mysqli_query($conn, "SELECT s.specialization_name, COUNT(d.doctor_id) total FROM specializations s LEFT JOIN doctors d ON d.specialization_id=s.specialization_id GROUP BY s.specialization_id ORDER BY total DESC, s.specialization_name ASC LIMIT 8");
while ($row = mysqli_fetch_assoc($r)) {
    $specialtyCoverage[] = $row;
}
$maxSpecialty = 1;
foreach ($specialtyCoverage as $row) {
    $maxSpecialty = max($maxSpecialty, (int) $row['total']);
}

$allDoctors = mysqli_query($conn, "SELECT d.doctor_id,d.qualification,d.experience_years,d.verification_status,u.full_name,u.email,u.phone,u.status user_status,s.specialization_name,c.city_name FROM doctors d JOIN users u ON u.user_id=d.user_id JOIN specializations s ON s.specialization_id=d.specialization_id JOIN cities c ON c.city_id=d.city_id ORDER BY FIELD(d.verification_status,'Pending','Rejected','Verified'), d.created_at DESC");
$users = mysqli_query($conn, "SELECT user_id,full_name,email,phone,role,status,created_at FROM users ORDER BY created_at DESC");
$clinics = mysqli_query($conn, "SELECT cl.clinic_id,cl.clinic_name,cl.status,c.city_name,(SELECT COUNT(*) FROM doctor_clinic dc WHERE dc.clinic_id=cl.clinic_id) doctors_count FROM clinics cl JOIN cities c ON c.city_id=cl.city_id ORDER BY cl.updated_at DESC, cl.clinic_id DESC");
$specializations = mysqli_query($conn, "SELECT s.specialization_id,s.specialization_name,s.status,(SELECT COUNT(*) FROM doctors d WHERE d.specialization_id=s.specialization_id) doctors_count FROM specializations s ORDER BY s.status ASC, s.specialization_name ASC");
$cities = mysqli_query($conn, "SELECT city_id,city_name,state,status FROM cities ORDER BY status ASC, city_name ASC");
$appointments = mysqli_query($conn, "SELECT a.appointment_id,a.appointment_date,a.appointment_time,a.status,a.reason,u_doc.full_name doctor_name,u_pat.full_name patient_name,cl.clinic_name FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id JOIN users u_doc ON u_doc.user_id=d.user_id JOIN patients p ON p.patient_id=a.patient_id JOIN users u_pat ON u_pat.user_id=p.user_id JOIN clinics cl ON cl.clinic_id=a.clinic_id ORDER BY a.updated_at DESC");
$availabilityRows = mysqli_query($conn, "SELECT da.availability_id,da.day,da.start_time,da.end_time,da.slot_duration,da.status,u.full_name doctor_name,cl.clinic_name FROM doctor_availability da JOIN doctors d ON d.doctor_id=da.doctor_id JOIN users u ON u.user_id=d.user_id LEFT JOIN clinics cl ON cl.clinic_id=da.clinic_id ORDER BY da.updated_at DESC, da.availability_id DESC");
$newsRows = mysqli_query($conn, "SELECT news_id,title,status,created_at FROM medical_news ORDER BY updated_at DESC, news_id DESC");
$faqRows = mysqli_query($conn, "SELECT f.faq_id,f.question,f.status,s.specialization_name FROM specialization_faqs f JOIN specializations s ON s.specialization_id=f.specialization_id ORDER BY f.status ASC, s.specialization_name ASC, f.sort_order ASC");

$done = (int) ($appointmentStatus['Completed'] ?? 0);
$cancelled = (int) ($appointmentStatus['Cancelled'] ?? 0) + (int) ($appointmentStatus['NoShow'] ?? 0);
$totalAppointments = max(1, (int) $stats['appointments_total']);
$completionRate = round(($done / $totalAppointments) * 100);
$frictionRate = round(($cancelled / $totalAppointments) * 100);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Admin Panel | Care Connect</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="app-body admin-body">
    <aside class="sidebar">
        <a class="brand" href="../index.php">care<span>connect</span></a>
        <p class="side-label">ADMIN CONTROL</p>
        <a class="active" href="#overview">Overview</a>
        <a href="#verification">Doctor approvals</a>
        <a href="#users">Users</a>
        <a href="#directory">Directory</a>
        <a href="#appointments">Appointments</a>
        <a href="../logout.php">Sign out</a>
    </aside>

    <main class="dashboard-main admin-main" id="overview">
        <header class="dash-header admin-hero-panel">
            <div>
                <p class="eyebrow">COMMAND CENTER</p>
                <h1>Admin dashboard.</h1>
                <p>Control users, doctors, clinics, specializations, cities, appointments, and website visibility from one place.</p>
            </div>
            <div class="admin-health-ring" style="--score: <?=$completionRate?>;">
                <strong><?=$completionRate?>%</strong>
                <span>completion</span>
            </div>
        </header>

        <?php if($msg): ?><div class="alert alert-<?=$msgType?>"><?=h($msg)?></div><?php endif; ?>

        <section class="admin-metric-grid">
            <article><span>Active users</span><strong><?=$stats['users_active']?></strong><small><?=$stats['users_total']?> total accounts</small></article>
            <article><span>Pending doctors</span><strong><?=$stats['doctors_pending']?></strong><small><?=$stats['doctors_total']?> doctor profiles</small></article>
            <article><span>Appointments</span><strong><?=$stats['appointments_total']?></strong><small><?=$stats['appointments_pending']?> awaiting action</small></article>
            <article><span>Directory</span><strong><?=$stats['clinics_active']?></strong><small><?=$stats['specs_active']?> active specialties</small></article>
            <article><span>Completed value</span><strong>PKR <?=number_format((float) $stats['completed_value'])?></strong><small>from completed visits</small></article>
        </section>

        <section class="admin-insight-grid">
            <article class="panel">
                <div class="panel-head"><div><p class="eyebrow">CARE FLOW</p><h2>Status funnel</h2></div><span class="status status-Confirmed"><?=$frictionRate?>% friction</span></div>
                <div class="funnel-bars">
                    <?php foreach(['Pending','Confirmed','Completed','Cancelled','NoShow'] as $status):
                        $value = (int) ($appointmentStatus[$status] ?? 0);
                        $width = max(8, round(($value / $totalAppointments) * 100));
                    ?>
                        <div class="funnel-row"><span><?=$status?></span><div><i style="width: <?=$width?>%"></i></div><b><?=$value?></b></div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head"><div><p class="eyebrow">DEMAND MAP</p><h2>Weekday pressure</h2></div></div>
                <div class="weekday-chart">
                    <?php foreach($weekdays as $day => $value):
                        $height = max(10, round(($value / $maxWeekday) * 100));
                    ?>
                        <div><span style="height: <?=$height?>%"></span><small><?=substr($day, 0, 3)?></small><b><?=$value?></b></div>
                    <?php endforeach; ?>
                </div>
            </article>

            <article class="panel">
                <div class="panel-head"><div><p class="eyebrow">COVERAGE</p><h2>Specialty depth</h2></div></div>
                <div class="coverage-list">
                    <?php foreach($specialtyCoverage as $row):
                        $width = max(8, round(((int) $row['total'] / $maxSpecialty) * 100));
                    ?>
                        <div><span><?=h($row['specialization_name'])?></span><i><b style="width: <?=$width?>%"></b></i><em><?=$row['total']?></em></div>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="panel admin-table-panel" id="verification">
            <div class="panel-head"><div><p class="eyebrow">DOCTOR APPROVALS</p><h2>All doctor verification</h2></div></div>
            <?php if(mysqli_num_rows($allDoctors)): ?>
                <div class="admin-table">
                    <?php while($d = mysqli_fetch_assoc($allDoctors)): ?>
                        <article>
                            <div><strong>Dr. <?=h($d['full_name'])?></strong><span><?=h($d['specialization_name'])?>, <?=h($d['city_name'])?> - <?=h($d['qualification'])?> - <?=intval($d['experience_years'])?> yrs</span><small><?=h($d['email'])?> - <?=h($d['phone'])?> - Account <?=h($d['user_status'])?></small></div>
                            <form method="post" class="inline-admin-form">
                                <input type="hidden" name="action" value="set_doctor_verification">
                                <input type="hidden" name="doctor_id" value="<?=$d['doctor_id']?>">
                                <select name="verification_status"><?php foreach(['Pending','Verified','Rejected'] as $status): ?><option <?=$d['verification_status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                                <button class="btn btn-primary">Update</button>
                            </form>
                        </article>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state"><h3>No pending doctors</h3><p>New doctor profiles will appear here for approval.</p></div>
            <?php endif; ?>
        </section>

        <section class="panel admin-table-panel" id="users">
            <div class="panel-head"><div><p class="eyebrow">ACCOUNTS</p><h2>Users and access</h2></div></div>
            <div class="admin-table">
                <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <article>
                        <div><strong><?=h($u['full_name'])?></strong><span><?=h($u['role'])?> - <?=h($u['email'])?> - <?=h($u['phone'])?></span><small>Joined <?=date('d M Y', strtotime($u['created_at']))?></small></div>
                        <form method="post" class="inline-admin-form">
                            <input type="hidden" name="action" value="set_user_status">
                            <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                            <select name="status"><?php foreach(['Active','Inactive','Suspended'] as $status): ?><option <?=$u['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                            <button class="btn btn-primary">Save</button>
                        </form>
                        <?php if($u['role'] !== 'Admin'): ?>
                            <form method="post" onsubmit="return confirm('Remove this account? Accounts with history will be suspended.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?=$u['user_id']?>">
                                <button class="btn btn-outline danger-btn">Remove</button>
                            </form>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="admin-split" id="directory">
            <article class="panel">
                <div class="panel-head"><div><p class="eyebrow">ADD SPECIALTY</p><h2>Specialization</h2></div></div>
                <form method="post" class="booking-form">
                    <input type="hidden" name="action" value="add_specialization">
                    <div class="field"><label>NAME</label><input name="specialization_name" required></div>
                    <div class="field"><label>DESCRIPTION</label><textarea name="description" rows="3" required></textarea></div>
                    <div class="field"><label>GUIDE OVERVIEW</label><textarea name="overview" rows="3"></textarea></div>
                    <div class="field"><label>WHEN TO BOOK (ONE PER LINE)</label><textarea name="when_to_book" rows="4"></textarea></div>
                    <div class="field"><label>CARE TIPS (ONE PER LINE)</label><textarea name="care_points" rows="4"></textarea></div>
                    <button class="btn btn-primary">Add specialization</button>
                </form>
            </article>
            <article class="panel">
                <div class="panel-head"><div><p class="eyebrow">ADD CITY</p><h2>City coverage</h2></div></div>
                <form method="post" class="booking-form">
                    <input type="hidden" name="action" value="add_city">
                    <div class="field"><label>CITY</label><input name="city_name" required></div>
                    <div class="field"><label>STATE / PROVINCE</label><input name="state"></div>
                    <button class="btn btn-primary">Add city</button>
                </form>
            </article>
        </section>

        <section class="admin-split">
            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">CLINICS</p><h2>Website locations</h2></div></div>
                <div class="admin-table compact">
                    <?php while($c = mysqli_fetch_assoc($clinics)): ?>
                        <article>
                            <div><strong>#<?=$c['clinic_id']?> - <?=h($c['clinic_name'])?></strong><span><?=h($c['city_name'])?> - <?=$c['doctors_count']?> doctors</span></div>
                            <form method="post" class="inline-admin-form">
                                <input type="hidden" name="action" value="set_clinic_status">
                                <input type="hidden" name="clinic_id" value="<?=$c['clinic_id']?>">
                                <select name="status"><?php foreach(['Active','Inactive'] as $status): ?><option <?=$c['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                                <button class="btn btn-primary">Save</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Remove this clinic from website?');"><input type="hidden" name="action" value="delete_clinic"><input type="hidden" name="clinic_id" value="<?=$c['clinic_id']?>"><button class="btn btn-outline danger-btn">Remove</button></form>
                        </article>
                    <?php endwhile; ?>
                </div>
            </article>
            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">SPECIALIZATIONS</p><h2>Public categories</h2></div></div>
                <div class="admin-table compact">
                    <?php while($s = mysqli_fetch_assoc($specializations)): ?>
                        <article>
                            <div><strong><?=h($s['specialization_name'])?></strong><span><?=$s['doctors_count']?> doctors</span></div>
                            <form method="post" class="inline-admin-form">
                                <input type="hidden" name="action" value="set_specialization_status">
                                <input type="hidden" name="specialization_id" value="<?=$s['specialization_id']?>">
                                <select name="status"><?php foreach(['Active','Inactive'] as $status): ?><option <?=$s['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                                <button class="btn btn-primary">Save</button>
                            </form>
                            <form method="post" onsubmit="return confirm('Remove this specialization from search?');"><input type="hidden" name="action" value="delete_specialization"><input type="hidden" name="specialization_id" value="<?=$s['specialization_id']?>"><button class="btn btn-outline danger-btn">Remove</button></form>
                        </article>
                    <?php endwhile; ?>
                </div>
            </article>
        </section>

        <section class="panel admin-table-panel">
            <div class="panel-head"><div><p class="eyebrow">CITIES</p><h2>City visibility</h2></div></div>
            <div class="admin-table compact">
                <?php while($city = mysqli_fetch_assoc($cities)): ?>
                    <article>
                        <div><strong><?=h($city['city_name'])?></strong><span><?=h($city['state'])?></span></div>
                        <form method="post" class="inline-admin-form">
                            <input type="hidden" name="action" value="set_city_status">
                            <input type="hidden" name="city_id" value="<?=$city['city_id']?>">
                            <select name="status"><?php foreach(['Active','Inactive'] as $status): ?><option <?=$city['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                            <button class="btn btn-primary">Save</button>
                        </form>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="panel admin-table-panel" id="appointments">
            <div class="panel-head"><div><p class="eyebrow">APPOINTMENTS</p><h2>Global appointment control</h2></div></div>
            <div class="admin-table">
                <?php while($a = mysqli_fetch_assoc($appointments)): ?>
                    <article>
                        <div><strong><?=date('d M Y', strtotime($a['appointment_date']))?> at <?=date('h:i A', strtotime($a['appointment_time']))?></strong><span>Dr. <?=h($a['doctor_name'])?> with <?=h($a['patient_name'])?> - <?=h($a['clinic_name'])?></span><small><?=h($a['reason'])?></small></div>
                        <form method="post" class="inline-admin-form">
                            <input type="hidden" name="action" value="set_appointment_status">
                            <input type="hidden" name="appointment_id" value="<?=$a['appointment_id']?>">
                            <select name="status"><?php foreach(['Pending','Confirmed','Completed','Cancelled','NoShow'] as $status): ?><option <?=$a['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                            <button class="btn btn-primary">Save</button>
                        </form>
                    </article>
                <?php endwhile; ?>
            </div>
        </section>

        <section class="admin-split">
            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">AVAILABILITY</p><h2>Doctor schedules</h2></div></div>
                <?php if(mysqli_num_rows($availabilityRows)): ?>
                    <div class="admin-table compact">
                        <?php while($row = mysqli_fetch_assoc($availabilityRows)): ?>
                            <article>
                                <div><strong>Dr. <?=h($row['doctor_name'])?></strong><span><?=h($row['clinic_name'] ?? 'Clinic not assigned')?> - <?=$row['day']?> - <?=date('h:i A', strtotime($row['start_time']))?> to <?=date('h:i A', strtotime($row['end_time']))?></span><small><?=$row['slot_duration']?> minute slots</small></div>
                                <form method="post" class="inline-admin-form">
                                    <input type="hidden" name="action" value="set_availability_status">
                                    <input type="hidden" name="availability_id" value="<?=$row['availability_id']?>">
                                    <select name="status"><?php foreach(['Active','Inactive'] as $status): ?><option <?=$row['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                                    <button class="btn btn-primary">Save</button>
                                </form>
                            </article>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state"><h3>No schedules yet</h3><p>Doctor availability will appear here.</p></div>
                <?php endif; ?>
            </article>

            <article class="panel admin-table-panel">
                <div class="panel-head"><div><p class="eyebrow">CONTENT STATUS</p><h2>News and FAQs</h2></div></div>
                <div class="admin-table compact">
                    <?php if(mysqli_num_rows($newsRows)): ?>
                        <?php while($row = mysqli_fetch_assoc($newsRows)): ?>
                            <article>
                                <div><strong><?=h($row['title'])?></strong><span>Medical news</span><small><?=date('d M Y', strtotime($row['created_at']))?></small></div>
                                <form method="post" class="inline-admin-form">
                                    <input type="hidden" name="action" value="set_news_status">
                                    <input type="hidden" name="news_id" value="<?=$row['news_id']?>">
                                    <select name="status"><?php foreach(['Draft','Published','Archived'] as $status): ?><option <?=$row['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                                    <button class="btn btn-primary">Save</button>
                                </form>
                            </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <?php if(mysqli_num_rows($faqRows)): ?>
                        <?php while($row = mysqli_fetch_assoc($faqRows)): ?>
                            <article>
                                <div><strong><?=h($row['question'])?></strong><span><?=h($row['specialization_name'])?> FAQ</span></div>
                                <form method="post" class="inline-admin-form">
                                    <input type="hidden" name="action" value="set_faq_status">
                                    <input type="hidden" name="faq_id" value="<?=$row['faq_id']?>">
                                    <select name="status"><?php foreach(['Active','Inactive'] as $status): ?><option <?=$row['status']===$status?'selected':''?>><?=$status?></option><?php endforeach; ?></select>
                                    <button class="btn btn-primary">Save</button>
                                </form>
                            </article>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    <?php if(!mysqli_num_rows($newsRows) && !mysqli_num_rows($faqRows)): ?>
                        <article><div><strong>No content rows yet</strong><span>Medical news and specialty FAQs will appear here.</span></div></article>
                    <?php endif; ?>
                </div>
            </article>
        </section>
    </main>
</body>
</html>
