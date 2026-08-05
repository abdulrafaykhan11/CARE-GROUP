<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/appointment_actions.php';
require_once __DIR__ . '/../config/directory_schema.php';
require_once __DIR__ . '/../config/upload_cleanup.php';
ensureAppointmentChangeSchema($conn);
ensureDirectorySchema($conn);

if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../login.php');
    exit;
}

$adminId = (int) $_SESSION['user_id'];

function h($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function postInt(string $key): int
{
    return (int) ($_POST[$key] ?? 0);
}

function oneCount(mysqli $conn, string $sql): int
{
    $row = mysqli_fetch_assoc(mysqli_query($conn, $sql));
    return (int) ($row['total'] ?? 0);
}

function adminPath(string $file): string
{
    return $file;
}

function doctorFileUrl(?string $path, string $fallbackDir): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (strpos($path, 'assets/') === 0) {
        return '../' . $path;
    }
    return '../assets/uploads/doctor/' . $fallbackDir . '/' . basename($path);
}

function adminSidebar(string $active): void
{
    $items = [
        'dashboard.php' => ['Overview HUD', 'overview'],
        'doctors.php' => ['Doctor Approvals', 'doctors'],
        'users.php' => ['User Telemetry', 'users'],
        'directory.php' => ['Directory Nodes', 'directory'],
        'appointments.php' => ['Appointments Nexus', 'appointments'],
        'content.php' => ['Content Shards', 'content'],
    ];
    echo '<aside class="dash-sidebar"><a class="brand" href="../index.php">CARE <span>NEXUS</span></a><div class="eyebrow" style="color: var(--violet-quantum); margin-bottom: 24px;">ADMIN OVERSIGHT</div><nav class="dash-nav">';
    foreach ($items as $href => $item) {
        [$label, $key] = $item;
        echo '<a class="' . ($active === $key ? 'active' : '') . '" href="' . $href . '">❖ ' . h($label) . '</a>';
    }
    echo '<a href="../logout.php" style="margin-top: auto; color: var(--rose-danger);">❖ Sign Out</a></nav></aside>';
}

function adminFlashFromPost(mysqli $conn, int $adminId): array
{
    $msg = '';
    $msgType = 'success';
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return [$msg, $msgType];
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'set_user_status') {
        $userId = postInt('user_id');
        $status = $_POST['status'] ?? '';
        if ($userId === $adminId && $status !== 'Active') {
            return ['You cannot disable the admin account you are using.', 'error'];
        }
        if (in_array($status, ['Active', 'Inactive', 'Suspended'], true)) {
            if ($status === 'Suspended') {
                deleteUserProfileImage($conn, $userId);
            }
            $stmt = mysqli_prepare($conn, "UPDATE users SET status=? WHERE user_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $userId);
            mysqli_stmt_execute($stmt);
            return ['User status updated.', 'success'];
        }
    }

    if ($action === 'delete_user') {
        $userId = postInt('user_id');
        $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT role FROM users WHERE user_id=$userId"));
        if (!$user) {
            return ['User not found.', 'error'];
        }
        if ($userId === $adminId || $user['role'] === 'Admin') {
            return ['Admin accounts cannot be deleted from this panel.', 'error'];
        }
        $linkedAppointments = 0;
        if ($user['role'] === 'Doctor') {
            $linkedAppointments = oneCount($conn, "SELECT COUNT(*) total FROM appointments a JOIN doctors d ON d.doctor_id=a.doctor_id WHERE d.user_id=$userId");
        }
        if ($user['role'] === 'Patient') {
            $linkedAppointments = oneCount($conn, "SELECT COUNT(*) total FROM appointments a JOIN patients p ON p.patient_id=a.patient_id WHERE p.user_id=$userId");
        }
        if ($linkedAppointments > 0) {
            deleteUserProfileImage($conn, $userId);
            mysqli_query($conn, "UPDATE users SET status='Suspended' WHERE user_id=$userId");
            return ['Account has appointment history, so it was suspended and removed from the website.', 'success'];
        }
        deleteUserProfileImage($conn, $userId);
        mysqli_query($conn, "DELETE FROM users WHERE user_id=$userId");
        return ['Account deleted permanently.', 'success'];
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
            $doctor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT u.full_name,u.email FROM doctors d JOIN users u ON u.user_id=d.user_id WHERE d.doctor_id=$doctorId LIMIT 1"));
            if ($doctor) {
                $body = careEmailShell('Doctor verification updated', '<p>Hello Dr. ' . h($doctor['full_name']) . ',</p><p>Your CARE Nexus verification status is now <strong>' . h($status) . '</strong>.</p>');
                sendCareMail([['email' => $doctor['email'], 'name' => $doctor['full_name']]], 'CARE Nexus doctor verification updated', $body, '', adminRecipients($conn));
            }
            return ['Doctor verification updated.', 'success'];
        }
    }

    if ($action === 'set_clinic_status') {
        $clinicId = postInt('clinic_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE clinics SET status=? WHERE clinic_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $clinicId);
            mysqli_stmt_execute($stmt);
            return ['Clinic visibility updated.', 'success'];
        }
    }

    if ($action === 'add_clinic') {
        $name = trim($_POST['clinic_name'] ?? '');
        $cityId = postInt('city_id');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name === '' || !$cityId || $address === '') {
            return ['Clinic name, city, and address are required.', 'error'];
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO clinics (city_id,clinic_name,phone,email,address,status) VALUES (?,?,?,?,?,'Active')");
        mysqli_stmt_bind_param($stmt, 'issss', $cityId, $name, $phone, $email, $address);
        if (mysqli_stmt_execute($stmt)) {
            return ['Clinic added to directory.', 'success'];
        }
        return ['Could not add clinic. Check duplicate names or city selection.', 'error'];
    }

    if ($action === 'delete_clinic') {
        $clinicId = postInt('clinic_id');
        $refs = oneCount($conn, "SELECT (SELECT COUNT(*) FROM appointments WHERE clinic_id=$clinicId) + (SELECT COUNT(*) FROM doctor_clinic WHERE clinic_id=$clinicId) total");
        if ($refs > 0) {
            mysqli_query($conn, "UPDATE clinics SET status='Inactive' WHERE clinic_id=$clinicId");
            return ['Clinic has linked records, so it was made inactive.', 'success'];
        }
        mysqli_query($conn, "DELETE FROM clinics WHERE clinic_id=$clinicId");
        return ['Clinic deleted permanently.', 'success'];
    }

    if ($action === 'add_specialization') {
        $name = trim($_POST['specialization_name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $overview = trim($_POST['overview'] ?? '');
        if ($name === '' || $description === '') {
            return ['Specialization name and description are required.', 'error'];
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO specializations (specialization_name,description,status) VALUES (?,?,'Active')");
        mysqli_stmt_bind_param($stmt, 'ss', $name, $description);
        if (!mysqli_stmt_execute($stmt)) {
            return ['Could not add specialization. It may already exist.', 'error'];
        }
        $specId = mysqli_insert_id($conn);
        if ($overview !== '') {
            $when = trim($_POST['when_to_book'] ?? '');
            $tips = trim($_POST['care_points'] ?? '');
            $stmt = mysqli_prepare($conn, "INSERT INTO specialization_guides (specialization_id,overview,when_to_book,care_points) VALUES (?,?,?,?)");
            mysqli_stmt_bind_param($stmt, 'isss', $specId, $overview, $when, $tips);
            mysqli_stmt_execute($stmt);
        }
        return ['Specialization added.', 'success'];
    }

    if ($action === 'set_specialization_status') {
        $specId = postInt('specialization_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE specializations SET status=? WHERE specialization_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $specId);
            mysqli_stmt_execute($stmt);
            return ['Specialization status updated.', 'success'];
        }
    }

    if ($action === 'delete_specialization') {
        $specId = postInt('specialization_id');
        $refs = oneCount($conn, "SELECT COUNT(*) total FROM doctors WHERE specialization_id=$specId");
        if ($refs > 0) {
            mysqli_query($conn, "UPDATE specializations SET status='Inactive' WHERE specialization_id=$specId");
            return ['Specialization has doctors attached, so it was made inactive.', 'success'];
        }
        mysqli_query($conn, "DELETE FROM specializations WHERE specialization_id=$specId");
        return ['Specialization deleted permanently.', 'success'];
    }

    if ($action === 'add_city') {
        $city = trim($_POST['city_name'] ?? '');
        $state = trim($_POST['state'] ?? '');
        if ($city === '') {
            return ['City name is required.', 'error'];
        }
        $stmt = mysqli_prepare($conn, "INSERT INTO cities (city_name,state,country,status) VALUES (?,?,'Pakistan','Active')");
        mysqli_stmt_bind_param($stmt, 'ss', $city, $state);
        mysqli_stmt_execute($stmt);
        return ['City added.', 'success'];
    }

    if ($action === 'set_city_status') {
        $cityId = postInt('city_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE cities SET status=? WHERE city_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $cityId);
            mysqli_stmt_execute($stmt);
            return ['City status updated.', 'success'];
        }
    }

    if ($action === 'set_appointment_status') {
        $appointmentId = postInt('appointment_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'NoShow'], true)) {
            return updateAppointmentStatusWithNotifications($conn, $appointmentId, $status, 'Admin');
        }
    }

    if ($action === 'set_availability_status') {
        $availabilityId = postInt('availability_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE doctor_availability SET status=? WHERE availability_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $availabilityId);
            mysqli_stmt_execute($stmt);
            return ['Doctor availability status updated.', 'success'];
        }
    }

    if ($action === 'set_news_status') {
        $newsId = postInt('news_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Draft', 'Published', 'Archived'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE medical_news SET status=?, published_at=IF(?='Published', COALESCE(published_at, NOW()), published_at) WHERE news_id=?");
            mysqli_stmt_bind_param($stmt, 'ssi', $status, $status, $newsId);
            mysqli_stmt_execute($stmt);
            return ['Medical news status updated.', 'success'];
        }
    }

    if ($action === 'set_faq_status') {
        $faqId = postInt('faq_id');
        $status = $_POST['status'] ?? '';
        if (in_array($status, ['Active', 'Inactive'], true)) {
            $stmt = mysqli_prepare($conn, "UPDATE specialization_faqs SET status=? WHERE faq_id=?");
            mysqli_stmt_bind_param($stmt, 'si', $status, $faqId);
            mysqli_stmt_execute($stmt);
            return ['FAQ status updated.', 'success'];
        }
    }

    return ['No valid admin action was submitted.', 'error'];
}
