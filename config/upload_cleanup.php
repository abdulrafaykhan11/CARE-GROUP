<?php
function deleteUploadedProfileFile(?string $storedPath, string $type): bool
{
    $storedPath = trim((string) $storedPath);
    if ($storedPath === '' || $storedPath === 'default.png') {
        return false;
    }

    $base = realpath(__DIR__ . '/..');
    if (!$base) {
        return false;
    }

    $allowedDir = $type === 'patient'
        ? $base . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'patients'
        : $base . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'doctor' . DIRECTORY_SEPARATOR . 'profile';

    $file = strpos($storedPath, 'assets/') === 0
        ? $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath)
        : $allowedDir . DIRECTORY_SEPARATOR . basename($storedPath);

    $realFile = realpath($file);
    $realAllowed = realpath($allowedDir);
    if (!$realFile || !$realAllowed || strpos($realFile, $realAllowed . DIRECTORY_SEPARATOR) !== 0 || !is_file($realFile)) {
        return false;
    }

    return unlink($realFile);
}

function deleteUploadedAssetFile(?string $storedPath): bool
{
    $storedPath = trim((string) $storedPath);
    if ($storedPath === '') {
        return false;
    }

    $base = realpath(__DIR__ . '/..');
    $uploads = $base ? realpath($base . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'uploads') : false;
    if (!$base || !$uploads || strpos($storedPath, '..') !== false) {
        return false;
    }

    $file = strpos($storedPath, 'assets/') === 0
        ? $base . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $storedPath)
        : $uploads . DIRECTORY_SEPARATOR . basename($storedPath);

    $realFile = realpath($file);
    if (!$realFile || strpos($realFile, $uploads . DIRECTORY_SEPARATOR) !== 0 || !is_file($realFile)) {
        return false;
    }

    return unlink($realFile);
}

function deleteUserProfileImage(mysqli $conn, int $userId): void
{
    $doctor = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_image FROM doctors WHERE user_id=$userId LIMIT 1"));
    if ($doctor && !empty($doctor['profile_image'])) {
        deleteUploadedProfileFile($doctor['profile_image'], 'doctor');
        mysqli_query($conn, "UPDATE doctors SET profile_image=NULL WHERE user_id=$userId");
    }

    $patient = mysqli_fetch_assoc(mysqli_query($conn, "SELECT profile_image FROM patients WHERE user_id=$userId LIMIT 1"));
    if ($patient && !empty($patient['profile_image'])) {
        deleteUploadedProfileFile($patient['profile_image'], 'patient');
        mysqli_query($conn, "UPDATE patients SET profile_image=NULL WHERE user_id=$userId");
    }
}
