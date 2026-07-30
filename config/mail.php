<?php
/**
 * Booking mail settings. Add your SMTP credentials here (or environment variables)
 * after running `composer install` in this folder.
 */
const MAIL_HOST = 'smtp.gmail.com';
const MAIL_PORT = 587;
const MAIL_USERNAME = 'arifrafay551@gmail.com'; // e.g. clinic@gmail.com
const MAIL_PASSWORD = 'mmyjwwlefcbpotjd'; // Gmail App Password, never your normal password
const MAIL_FROM = 'arifrafay551@gmail.com';
const MAIL_FROM_NAME = 'Care Connect';

function sendAppointmentEmail(array $appointment): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload) || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
        return false; // Booking remains successful; configure SMTP to enable mail.
    }
    require_once $autoload;
    try {
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
        $mail->addAddress($appointment['patient_email'], $appointment['patient_name']);
        if (!empty($appointment['doctor_email'])) $mail->addCC($appointment['doctor_email'], $appointment['doctor_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Appointment request received | Care Connect';
        $mail->Body = '<div style="font-family:Arial;color:#17324d;max-width:600px;margin:auto"><h2>Appointment request received</h2><p>Hello '.htmlspecialchars($appointment['patient_name']).',</p><p>Your appointment request has been sent to <strong>Dr. '.htmlspecialchars($appointment['doctor_name']).'</strong>.</p><div style="background:#f3f8f7;padding:18px;border-radius:12px"><b>Date:</b> '.htmlspecialchars($appointment['date']).'<br><b>Time:</b> '.htmlspecialchars($appointment['time']).'<br><b>Clinic:</b> '.htmlspecialchars($appointment['clinic']).'</div><p>We will email you when the doctor confirms it.</p></div>';
        $mail->AltBody = 'Your appointment request with Dr. '.$appointment['doctor_name'].' for '.$appointment['date'].' at '.$appointment['time'].' has been received.';
        return $mail->send();
    } catch (Throwable $e) { return false; }
}

function sendAppointmentConfirmationEmail(array $appointment): bool
{
    $autoload = __DIR__ . '/../vendor/autoload.php';
    if (!file_exists($autoload) || MAIL_USERNAME === '' || MAIL_PASSWORD === '') {
        return false;
    }
    require_once $autoload;
    try {
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
        $mail->addAddress($appointment['patient_email'], $appointment['patient_name']);
        if (!empty($appointment['doctor_email'])) $mail->addCC($appointment['doctor_email'], $appointment['doctor_name']);
        $mail->isHTML(true);
        $mail->Subject = 'Appointment Confirmed | Care Connect';
        $mail->Body = '<div style="font-family:Arial;color:#17324d;max-width:600px;margin:auto"><h2>Appointment Confirmed!</h2><p>Hello '.htmlspecialchars($appointment['patient_name']).',</p><p>Great news! <strong>Dr. '.htmlspecialchars($appointment['doctor_name']).'</strong> has confirmed your appointment.</p><div style="background:#f3f8f7;padding:18px;border-radius:12px"><b>Date:</b> '.htmlspecialchars($appointment['date']).'<br><b>Time:</b> '.htmlspecialchars($appointment['time']).'<br><b>Clinic:</b> '.htmlspecialchars($appointment['clinic']).'</div><p>Please arrive 10 minutes early. See you soon!</p></div>';
        $mail->AltBody = 'Great news! Your appointment with Dr. '.$appointment['doctor_name'].' on '.$appointment['date'].' at '.$appointment['time'].' has been confirmed.';
        return $mail->send();
    } catch (Throwable $e) { return false; }
}
