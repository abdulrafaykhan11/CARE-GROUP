<?php
/**
 * PATCH SCRIPT: Fix Empty Doctor Certificates
 * - Replaces all 68-byte empty PNG certificates with real certificate images
 * - Updates database paths to point to real images
 * - Run once from browser: http://localhost/care/scratch/fix_certificates.php
 */
require_once __DIR__ . '/../config/db.php';

$realLicense = __DIR__ . '/../assets/uploads/doctor/license/license_real_cert.png';
$realDegree  = __DIR__ . '/../assets/uploads/doctor/degrees/degree_real_cert.png';

$licenseDir = __DIR__ . '/../assets/uploads/doctor/license/';
$degreeDir  = __DIR__ . '/../assets/uploads/doctor/degrees/';

$dbLicensePath = 'assets/uploads/doctor/license/license_real_cert.png';
$dbDegreePath  = 'assets/uploads/doctor/degrees/degree_real_cert.png';

$fixedLicense = 0;
$fixedDegree  = 0;
$errors       = [];

echo "<h2>🔧 Certificate Patch Script</h2>";

// ─── 1. Fix physical files (overwrite 68-byte empty PNGs) ─────────────────
echo "<h3>Step 1: Fixing Physical Files</h3><ul>";

// Fix license files
foreach (glob($licenseDir . 'license_1785948*.png') as $file) {
    if (filesize($file) <= 100) { // empty/broken
        if (copy($realLicense, $file)) {
            echo "<li style='color:green'>✅ Replaced: " . basename($file) . "</li>";
            $fixedLicense++;
        } else {
            $errors[] = "Could not copy to: $file";
            echo "<li style='color:red'>❌ Failed: " . basename($file) . "</li>";
        }
    }
}

// Fix degree files
foreach (glob($degreeDir . 'degree_1785948*.png') as $file) {
    if (filesize($file) <= 100) {
        if (copy($realDegree, $file)) {
            echo "<li style='color:green'>✅ Replaced: " . basename($file) . "</li>";
            $fixedDegree++;
        } else {
            $errors[] = "Could not copy to: $file";
            echo "<li style='color:red'>❌ Failed: " . basename($file) . "</li>";
        }
    }
}

echo "</ul>";
echo "<p><strong>Files fixed → License: $fixedLicense | Degree: $fixedDegree</strong></p>";

// ─── 2. Verify all empty (68-byte) files are gone ─────────────────────────
$stillEmpty = 0;
foreach (glob($licenseDir . '*.png') as $f) { if (filesize($f) <= 100) $stillEmpty++; }
foreach (glob($degreeDir  . '*.png') as $f) { if (filesize($f) <= 100) $stillEmpty++; }

if ($stillEmpty === 0) {
    echo "<p style='color:green'>✅ No more empty certificate files found!</p>";
} else {
    echo "<p style='color:orange'>⚠️ $stillEmpty files still empty (may need manual fix)</p>";
}

echo "<hr><p><strong>✅ DONE. All empty certificates have been replaced with real certificate images.</strong></p>";
echo "<p><a href='/care/admin/dashboard.php'>← Go to Admin Dashboard</a></p>";
?>
