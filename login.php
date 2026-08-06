<?php 
require_once 'config/db.php';
$error = '';
if (isset($_POST['submit'])) {
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $r = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND status='Active' LIMIT 1");
    $u = mysqli_fetch_assoc($r);
    $passwordMatch = false;
    if ($u) {
        if (password_verify($_POST['password'], $u['password'])) {
            $passwordMatch = true;
        } elseif ($_POST['password'] === $u['password']) {
            // Plain-text fallback for manually inserted admin accounts — auto-hash for future
            $passwordMatch = true;
            $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE users SET password='" . mysqli_real_escape_string($conn, $hashed) . "' WHERE user_id=" . (int)$u['user_id']);
        }
    }
    if ($passwordMatch) {
        $_SESSION = [
            'user_id' => $u['user_id'],
            'full_name' => $u['full_name'],
            'role' => $u['role'],
            'just_logged_in' => true
        ];
        $to = $u['role'] === 'Doctor' ? 'doctor/dashboard.php' : ($u['role'] === 'Patient' ? 'patient/dashboard.php' : 'admin/dashboard.php');
        header("Location: $to");
        exit;
    }
    $error = 'Email address or password credentials incorrect.';
}

$pageTitle = "Sign In to Cybernetic Portal";
include 'includes/header.php';
?>

<div class="auth-page">
    <main class="auth-card">
        <div class="eyebrow-badge">SECURE TELEMETRY ACCESS</div>
        <h1>Sign In to Cybernetic Portal</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
            Access your patient discovery HUD, doctor command center, or admin nexus.
        </p>

        <?php if($error): ?><div class="alert alert-error"><?=$error?></div><?php endif; ?>

        <form method="post" style="display: grid; gap: 18px;">
            <div class="field">
                <label>REGISTERED EMAIL ADDRESS</label>
                <input type="email" name="email" required placeholder="name@domain.com">
            </div>

            <div class="field">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="margin: 0;">PASSWORD</label>
                    <label style="font-size: 12px; color: var(--text-muted); cursor: pointer; display: flex; align-items: center; gap: 6px; user-select: none;">
                        <input type="checkbox" id="togglePassword" style="cursor: pointer; width: 14px; height: 14px; accent-color: var(--cyan-neon);">
                        <span>Show Password</span>
                    </label>
                </div>
                <input type="password" name="password" id="passwordInput" minlength="8" required placeholder="••••••••">
            </div>

            <button class="btn btn-primary" name="submit" style="width: 100%; margin-top: 10px;">
                <span>❖ Sign In Securely</span>
            </button>
        </form>

        <script>
            document.getElementById('togglePassword')?.addEventListener('change', function() {
                const pwInput = document.getElementById('passwordInput');
                if (pwInput) pwInput.type = this.checked ? 'text' : 'password';
            });
        </script>

        <p style="color: var(--text-muted); font-size: 13px; text-align: center; margin-top: 24px;">
            New to CARE Group Platform? <a href="register.php" style="color: var(--cyan-neon); font-weight: 700;">Create account</a>
        </p>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
