<?php
/** Adds appointment reschedule audit fields once for existing installations. */
function ensureAppointmentChangeSchema(mysqli $conn): void
{
    $column = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'reschedule_reason'");
    if (!$column || mysqli_num_rows($column) === 0) {
        mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN reschedule_reason VARCHAR(255) NULL AFTER notes");
    }

    $column = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'rescheduled_by'");
    if (!$column || mysqli_num_rows($column) === 0) {
        mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN rescheduled_by ENUM('Patient','Doctor') NULL AFTER reschedule_reason");
    }

    $column = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'rescheduled_at'");
    if (!$column || mysqli_num_rows($column) === 0) {
        mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN rescheduled_at TIMESTAMP NULL AFTER rescheduled_by");
    }
}
