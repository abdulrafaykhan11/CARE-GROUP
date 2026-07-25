<?php
/** Adds clinic-level scheduling support once for existing installations. */
function ensureClinicAvailabilitySchema(mysqli $conn): void
{
    $column = mysqli_query($conn, "SHOW COLUMNS FROM doctor_availability LIKE 'clinic_id'");
    if (!$column || mysqli_num_rows($column) === 0) {
        mysqli_query($conn, "ALTER TABLE doctor_availability ADD COLUMN clinic_id INT NULL AFTER doctor_id");
        // Legacy availability belonged to a doctor only. Assign it to the primary clinic once.
        mysqli_query($conn, "UPDATE doctor_availability da SET clinic_id=(SELECT dc.clinic_id FROM doctor_clinic dc WHERE dc.doctor_id=da.doctor_id ORDER BY dc.is_primary DESC,dc.doctor_clinic_id ASC LIMIT 1) WHERE da.clinic_id IS NULL");
        mysqli_query($conn, "ALTER TABLE doctor_availability ADD INDEX idx_doctor_clinic (doctor_id, clinic_id)");
    }
}
