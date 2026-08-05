<?php
/**
 * CARE mail settings. Keep SMTP credentials in environment variables when possible.
 */
const MAIL_HOST = 'smtp.gmail.com';
const MAIL_PORT = 587;
const MAIL_USERNAME = 'arifrafay551@gmail.com';
const MAIL_PASSWORD = 'mmyjwwlefcbpotjd';
const MAIL_FROM = 'arifrafay551@gmail.com';
const MAIL_FROM_NAME = 'Care Connect';

function mailAutoloadReady(): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload) || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
        return false;
    }
    require_once $autoload;
    return true;
}

function makeCareMailer(): ?PHPMailer\PHPMailer\PHPMailer
{
    if (!mailAutoloadReady()) {
        return null;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = MAIL_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = MAIL_PORT;
    $mail->CharSet = 'UTF-8';
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->isHTML(true);
    return $mail;
}

function sendCareMail(array $to, string $subject, string $body, string $altBody = '', array $cc = []): bool
{
    try {
        $mail = makeCareMailer();
        if (!$mail) {
            return false;
        }

        foreach ($to as $recipient) {
            if (!empty($recipient['email'])) {
                $mail->addAddress($recipient['email'], $recipient['name'] ?? '');
            }
        }
        foreach ($cc as $recipient) {
            if (!empty($recipient['email'])) {
                $mail->addCC($recipient['email'], $recipient['name'] ?? '');
            }
        }
        if (count($mail->getToAddresses()) === 0) {
            return false;
        }

        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body));
        return $mail->send();
    } catch (Throwable $e) {
        return false;
    }
}

function careEmailShell(string $title, string $content): string
{
    return '<div style="font-family:Arial,sans-serif;color:#17324d;max-width:640px;margin:auto;background:#ffffff;border:1px solid #dbeafe;border-radius:12px;overflow:hidden">'
        . '<div style="background:linear-gradient(135deg,#0f766e,#0ea5e9);color:#fff;padding:22px 26px"><div style="font-size:11px;letter-spacing:1.4px;text-transform:uppercase;font-weight:700">CARE Nexus</div><h2 style="margin:8px 0 0;font-size:24px">' . htmlspecialchars($title) . '</h2></div>'
        . '<div style="padding:24px 26px;line-height:1.65;font-size:14px">' . $content . '</div>'
        . '<div style="padding:14px 26px;background:#f8fafc;color:#64748b;font-size:12px">This is an automated CARE Nexus notification.</div>'
        . '</div>';
}

function adminRecipients(mysqli $conn): array
{
    $admins = [];
    $result = @mysqli_query($conn, "SELECT full_name,email FROM users WHERE role='Admin' AND status='Active'");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $admins[] = ['email' => $row['email'], 'name' => $row['full_name']];
        }
    }
    if (!$admins && MAIL_FROM !== '') {
        $admins[] = ['email' => MAIL_FROM, 'name' => 'CARE Admin'];
    }
    return $admins;
}

function getAppointmentMailDetails(mysqli $conn, int $appointmentId): ?array
{
    $row = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT a.*, d.consultation_fee, u_pat.full_name patient_name, u_pat.email patient_email,
               u_doc.full_name doctor_name, u_doc.email doctor_email, cl.clinic_name
        FROM appointments a
        JOIN patients p ON p.patient_id = a.patient_id
        JOIN users u_pat ON u_pat.user_id = p.user_id
        JOIN doctors d ON d.doctor_id = a.doctor_id
        JOIN users u_doc ON u_doc.user_id = d.user_id
        JOIN clinics cl ON cl.clinic_id = a.clinic_id
        WHERE a.appointment_id = $appointmentId
        LIMIT 1
    "));
    if (!$row) {
        return null;
    }
    $row['admin_recipients'] = adminRecipients($conn);
    return $row;
}

function appointmentSummaryHtml(array $appointment): string
{
    $payment = !empty($appointment['payment_method']) ? '<br><b>Payment:</b> ' . htmlspecialchars($appointment['payment_method']) : '';
    $fee = isset($appointment['consultation_fee']) ? '<br><b>Fee:</b> PKR ' . number_format((float)$appointment['consultation_fee']) : '';
    return '<div style="background:#f3f8f7;padding:16px;border-radius:10px;margin:14px 0">'
        . '<b>Patient:</b> ' . htmlspecialchars($appointment['patient_name'] ?? '') . '<br>'
        . '<b>Doctor:</b> Dr. ' . htmlspecialchars($appointment['doctor_name'] ?? '') . '<br>'
        . '<b>Date:</b> ' . htmlspecialchars(date('d M Y', strtotime($appointment['appointment_date'] ?? 'now'))) . '<br>'
        . '<b>Time:</b> ' . htmlspecialchars(date('h:i A', strtotime($appointment['appointment_time'] ?? 'now'))) . '<br>'
        . '<b>Clinic:</b> ' . htmlspecialchars($appointment['clinic_name'] ?? '')
        . $fee . $payment
        . '</div>';
}

function sendRegistrationCongratsEmail(array $user): bool
{
    $role = htmlspecialchars($user['role'] ?? 'Member');
    $name = htmlspecialchars($user['full_name'] ?? 'there');
    $body = careEmailShell('Registration successful', "<p>Congratulations, <strong>$name</strong>.</p><p>Your CARE Nexus $role account has been created successfully. You can now complete your profile and start using the portal.</p>");
    return sendCareMail([['email' => $user['email'] ?? '', 'name' => $user['full_name'] ?? '']], 'Congratulations - CARE Nexus registration successful', $body);
}

function sendProfileCompletedEmail(mysqli $conn, array $user, string $role): bool
{
    $name = htmlspecialchars($user['full_name'] ?? 'there');
    $body = careEmailShell("$role profile completed", "<p>Congratulations, <strong>$name</strong>.</p><p>Your $role profile has been completed on CARE Nexus.</p>");
    $ok = sendCareMail([['email' => $user['email'] ?? '', 'name' => $user['full_name'] ?? '']], "CARE Nexus $role profile completed", $body);

    $adminBody = careEmailShell("New $role registration", "<p><strong>$name</strong> has completed a $role profile on CARE Nexus.</p><p>Email: " . htmlspecialchars($user['email'] ?? '') . "</p>");
    sendCareMail(adminRecipients($conn), "New $role registration on CARE Nexus", $adminBody);
    return $ok;
}

function sendAppointmentEmail(array $appointment): bool
{
    $body = careEmailShell('Appointment request received',
        '<p>Hello ' . htmlspecialchars($appointment['patient_name']) . ',</p>'
        . '<p>Your appointment request has been sent to <strong>Dr. ' . htmlspecialchars($appointment['doctor_name']) . '</strong>.</p>'
        . '<div style="background:#f3f8f7;padding:18px;border-radius:12px"><b>Date:</b> ' . htmlspecialchars($appointment['date']) . '<br><b>Time:</b> ' . htmlspecialchars($appointment['time']) . '<br><b>Clinic:</b> ' . htmlspecialchars($appointment['clinic']) . '</div>'
        . '<p>We will email you when the doctor confirms it.</p>'
    );

    $to = [['email' => $appointment['patient_email'], 'name' => $appointment['patient_name']]];
    $cc = [];
    if (!empty($appointment['doctor_email'])) {
        $cc[] = ['email' => $appointment['doctor_email'], 'name' => $appointment['doctor_name']];
    }
    if (!empty($appointment['admin_recipients'])) {
        $cc = array_merge($cc, $appointment['admin_recipients']);
    }
    return sendCareMail($to, 'Appointment request received | Care Connect', $body, '', $cc);
}

function sendAppointmentConfirmationEmail(array $appointment): bool
{
    $body = careEmailShell('Appointment confirmed',
        '<p>Hello ' . htmlspecialchars($appointment['patient_name']) . ',</p>'
        . '<p><strong>Dr. ' . htmlspecialchars($appointment['doctor_name']) . '</strong> has confirmed your appointment.</p>'
        . '<div style="background:#f3f8f7;padding:18px;border-radius:12px"><b>Date:</b> ' . htmlspecialchars($appointment['date']) . '<br><b>Time:</b> ' . htmlspecialchars($appointment['time']) . '<br><b>Clinic:</b> ' . htmlspecialchars($appointment['clinic']) . '</div>'
        . '<p>Please arrive 10 minutes early.</p>'
    );
    $to = [['email' => $appointment['patient_email'], 'name' => $appointment['patient_name']]];
    $cc = [];
    if (!empty($appointment['doctor_email'])) {
        $cc[] = ['email' => $appointment['doctor_email'], 'name' => $appointment['doctor_name']];
    }
    if (!empty($appointment['admin_recipients'])) {
        $cc = array_merge($cc, $appointment['admin_recipients']);
    }
    return sendCareMail($to, 'Appointment confirmed | Care Connect', $body, '', $cc);
}

function sendAppointmentStatusEmail(mysqli $conn, int $appointmentId, string $status, string $actor = 'System'): bool
{
    $appointment = getAppointmentMailDetails($conn, $appointmentId);
    if (!$appointment) {
        return false;
    }

    $extra = '';
    if ($status === 'Completed') {
        $extra = '<p>The visit was marked completed. Revenue has been recorded as PKR ' . number_format((float)$appointment['consultation_fee']) . ' via ' . htmlspecialchars($appointment['payment_method'] ?? 'Cash') . '.</p>';
    }

    $body = careEmailShell('Appointment status updated',
        '<p>The appointment status is now <strong>' . htmlspecialchars($status) . '</strong>.</p>'
        . '<p>Updated by: ' . htmlspecialchars($actor) . '</p>'
        . appointmentSummaryHtml($appointment)
        . $extra
    );

    $to = [
        ['email' => $appointment['patient_email'], 'name' => $appointment['patient_name']],
        ['email' => $appointment['doctor_email'], 'name' => $appointment['doctor_name']],
    ];
    return sendCareMail($to, "Appointment $status | Care Connect", $body, '', $appointment['admin_recipients']);
}

function sendAppointmentUpdatedEmail(mysqli $conn, int $appointmentId, string $actor, string $changeReason = ''): bool
{
    $appointment = getAppointmentMailDetails($conn, $appointmentId);
    if (!$appointment) {
        return false;
    }

    $reason = $changeReason !== '' ? '<p><b>Change reason:</b> ' . htmlspecialchars($changeReason) . '</p>' : '';
    $body = careEmailShell('Appointment details updated',
        '<p>An appointment has been edited on CARE Nexus.</p>'
        . '<p>Updated by: ' . htmlspecialchars($actor) . '</p>'
        . $reason
        . appointmentSummaryHtml($appointment)
    );

    $to = [
        ['email' => $appointment['patient_email'], 'name' => $appointment['patient_name']],
        ['email' => $appointment['doctor_email'], 'name' => $appointment['doctor_name']],
    ];
    return sendCareMail($to, 'Appointment details updated | Care Connect', $body, '', $appointment['admin_recipients']);
}
