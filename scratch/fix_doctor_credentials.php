<?php
/**
 * PATCH: Fix Doctor Emails & Passwords
 * Email  → firstname@gmail.com  (or firstname123@gmail.com if taken by another user)
 * Password → firstname123  (bcrypt hashed)
 * Run once: http://localhost/care/scratch/fix_doctor_credentials.php
 */
require_once __DIR__ . '/../config/db.php';

// ── Fetch ALL doctor users ────────────────────────────────────────────────
$allDoctors = [];
$res = mysqli_query($conn,
    "SELECT u.user_id, u.full_name, u.email
     FROM users u
     INNER JOIN doctors d ON d.user_id = u.user_id
     ORDER BY u.user_id"
);
while ($row = mysqli_fetch_assoc($res)) {
    $allDoctors[] = $row;
}

// ── Build a set of ALL existing emails in the users table ─────────────────
$existingEmails = [];
$eRes = mysqli_query($conn, "SELECT email FROM users");
while ($eRow = mysqli_fetch_assoc($eRes)) {
    $existingEmails[strtolower($eRow['email'])] = true;
}

// ── Helper: extract clean first name ─────────────────────────────────────
function getFirstName(string $fullName): string {
    // Remove titles like Dr., Prof., etc.
    $clean = preg_replace('/\b(Dr\.?|Prof\.?|Mr\.?|Mrs\.?|Ms\.?)\b/i', '', $fullName);
    $parts = preg_split('/\s+/', trim($clean));
    return strtolower(preg_replace('/[^a-z0-9]/i', '', $parts[0]));
}

// ── Process each doctor ───────────────────────────────────────────────────
$results = [];
$usedEmails = []; // track emails assigned in THIS run to avoid duplicates within the batch

foreach ($allDoctors as $doc) {
    $userId   = $doc['user_id'];
    $oldEmail = $doc['email'];
    $firstName = getFirstName($doc['full_name']);

    if (empty($firstName)) $firstName = 'doctor';

    // Determine email
    $baseEmail    = $firstName . '@gmail.com';
    $altEmail     = $firstName . '123@gmail.com';

    // Remove the doctor's OWN current email from conflict check
    $emailsWithoutSelf = $existingEmails;
    unset($emailsWithoutSelf[strtolower($oldEmail)]);

    if (!isset($emailsWithoutSelf[$baseEmail]) && !isset($usedEmails[$baseEmail])) {
        $newEmail = $baseEmail;
    } elseif (!isset($emailsWithoutSelf[$altEmail]) && !isset($usedEmails[$altEmail])) {
        $newEmail = $altEmail;
    } else {
        // Both taken — add counter
        $counter = 2;
        do {
            $newEmail = $firstName . $counter . '@gmail.com';
            $counter++;
        } while (isset($emailsWithoutSelf[$newEmail]) || isset($usedEmails[$newEmail]));
    }

    $usedEmails[$newEmail]    = true;
    $existingEmails[$newEmail] = true; // register it for next iterations

    // Password = firstname123
    $plainPassword = $firstName . '123';
    $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

    // Update DB
    $safeEmail    = mysqli_real_escape_string($conn, $newEmail);
    $safePassword = mysqli_real_escape_string($conn, $hashedPassword);

    $q = "UPDATE users SET email='$safeEmail', password='$safePassword' WHERE user_id='$userId'";
    $ok = mysqli_query($conn, $q);

    $results[] = [
        'user_id'   => $userId,
        'name'      => $doc['full_name'],
        'old_email' => $oldEmail,
        'new_email' => $newEmail,
        'password'  => $plainPassword,
        'ok'        => $ok,
    ];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Doctor Credentials Patch</title>
<style>
body{font-family:monospace;background:#0f172a;color:#e2e8f0;padding:24px;}
h2{color:#06b6d4;}h3{color:#a78bfa;}
table{width:100%;border-collapse:collapse;font-size:13px;}
th{background:#1e293b;color:#06b6d4;padding:8px;text-align:left;}
td{padding:7px 8px;border-bottom:1px solid #1e293b;}
tr:hover td{background:#1e293b;}
.ok{color:#34d399;}.fail{color:#f87171;}
.badge{display:inline-block;padding:2px 8px;border-radius:99px;font-size:11px;}
.gmail{background:rgba(6,182,212,0.15);color:#06b6d4;}
</style>
</head>
<body>
<h2>🔧 Doctor Credentials Patch — Complete</h2>
<h3>Total Doctors Updated: <?= count($results) ?></h3>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>ID</th>
      <th>Doctor Name</th>
      <th>Old Email</th>
      <th>New Email</th>
      <th>Password (plain)</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($results as $i => $r): ?>
    <tr>
      <td><?= $i+1 ?></td>
      <td><?= $r['user_id'] ?></td>
      <td><?= htmlspecialchars($r['name']) ?></td>
      <td style="color:#94a3b8;"><?= htmlspecialchars($r['old_email']) ?></td>
      <td><span class="badge gmail"><?= htmlspecialchars($r['new_email']) ?></span></td>
      <td style="color:#fbbf24;"><?= htmlspecialchars($r['password']) ?></td>
      <td class="<?= $r['ok'] ? 'ok' : 'fail' ?>"><?= $r['ok'] ? '✅ Updated' : '❌ Failed' ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<br>
<p style="color:#64748b;">✅ Script complete. You can delete this file after use.</p>
<p><a href="/care/admin/dashboard.php" style="color:#06b6d4;">← Admin Dashboard</a></p>
</body>
</html>
