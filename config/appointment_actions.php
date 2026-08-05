<?php
require_once __DIR__ . '/appointment_schema.php';
require_once __DIR__ . '/mail.php';

function updateAppointmentStatusWithNotifications(mysqli $conn, int $appointmentId, string $status, string $actor, ?int $doctorId = null): array
{
    ensureAppointmentChangeSchema($conn);

    $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled', 'NoShow'];
    if (!in_array($status, $allowed, true)) {
        return ['Invalid appointment status.', 'error'];
    }

    $paymentMethod = null;
    if ($status === 'Completed') {
        $paymentMethod = $_POST['payment_method'] ?? '';
        if (!in_array($paymentMethod, ['Cash', 'Card'], true)) {
            return ['Please select whether the patient paid by Cash or Card before completing the appointment.', 'error'];
        }
    }

    $scope = $doctorId ? " AND doctor_id=" . (int)$doctorId : '';
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM appointments WHERE appointment_id=$appointmentId $scope LIMIT 1"));
    if (!$current) {
        return ['Appointment not found or access denied.', 'error'];
    }

    if ($doctorId && !in_array($current['status'], ['Pending', 'Confirmed'], true)) {
        return ['Only pending or confirmed appointments can be updated from the doctor portal.', 'error'];
    }

    if ($status === 'Completed') {
        $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=?, payment_method=?, completed_at=NOW() WHERE appointment_id=? $scope");
        mysqli_stmt_bind_param($stmt, 'ssi', $status, $paymentMethod, $appointmentId);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE appointments SET status=?, payment_method=IF(?='Completed', payment_method, NULL), completed_at=IF(?='Completed', completed_at, NULL) WHERE appointment_id=? $scope");
        mysqli_stmt_bind_param($stmt, 'sssi', $status, $status, $status, $appointmentId);
    }

    if (!mysqli_stmt_execute($stmt)) {
        return ['Could not update appointment status.', 'error'];
    }

    sendAppointmentStatusEmail($conn, $appointmentId, $status, $actor);
    return ['Appointment status updated and notifications were sent.', 'success'];
}
