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

    ensureAppointmentVisualSchema($conn);
}

/** Adds optional symptom photo storage for appointment records. */
function ensureAppointmentVisualSchema(mysqli $conn): void
{
    $column = mysqli_query($conn, "SHOW COLUMNS FROM appointments LIKE 'symptom_photo_path'");
    if (!$column || mysqli_num_rows($column) === 0) {
        mysqli_query($conn, "ALTER TABLE appointments ADD COLUMN symptom_photo_path VARCHAR(255) NULL AFTER notes");
    }
}

/**
 * Stores an optional symptom photo from file upload or webcam canvas data.
 * Returns [relative path|null, validation error|null].
 */
function saveAppointmentSymptomPhoto(array $files, array $post, string $projectRoot): array
{
    $uploadDir = rtrim($projectRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'appointments';
    $relativeDir = 'assets/uploads/appointments';
    $maxBytes = 5 * 1024 * 1024;
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return [null, 'Could not prepare secure appointment upload storage.'];
    }

    if (!empty($post['symptom_photo_data'])) {
        $dataUrl = trim($post['symptom_photo_data']);
        if (!preg_match('/^data:(image\/(?:jpeg|png|webp));base64,([A-Za-z0-9+\/=\s]+)$/', $dataUrl, $matches)) {
            return [null, 'Captured symptom photo format is not supported.'];
        }

        $mime = $matches[1];
        $binary = base64_decode(str_replace(' ', '+', $matches[2]), true);
        if ($binary === false || strlen($binary) > $maxBytes) {
            return [null, 'Captured symptom photo must be under 5MB.'];
        }
        if (function_exists('getimagesizefromstring') && getimagesizefromstring($binary) === false) {
            return [null, 'Captured symptom photo could not be verified as an image.'];
        }

        $filename = 'symptom_' . time() . '_' . random_int(1000, 9999) . '.' . $allowed[$mime];
        $target = $uploadDir . DIRECTORY_SEPARATOR . $filename;
        if (file_put_contents($target, $binary) === false) {
            return [null, 'Could not save the captured symptom photo.'];
        }

        return [$relativeDir . '/' . $filename, null];
    }

    if (empty($files['symptom_photo']) || ($files['symptom_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];
    }

    $file = $files['symptom_photo'];
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return [null, 'The symptom photo upload did not complete. Please try again.'];
    }
    if (($file['size'] ?? 0) > $maxBytes) {
        return [null, 'Symptom photo must be under 5MB.'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']) ?: '';
            finfo_close($finfo);
        }
    }
    if (!$mime && function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']) ?: '';
    }
    if (!isset($allowed[$mime])) {
        return [null, 'Please attach a JPG, PNG, or WEBP symptom photo.'];
    }
    if (function_exists('getimagesize') && getimagesize($file['tmp_name']) === false) {
        return [null, 'Uploaded symptom photo could not be verified as an image.'];
    }

    $filename = 'symptom_' . time() . '_' . random_int(1000, 9999) . '.' . $allowed[$mime];
    $target = $uploadDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return [null, 'Could not save the uploaded symptom photo.'];
    }

    return [$relativeDir . '/' . $filename, null];
}
