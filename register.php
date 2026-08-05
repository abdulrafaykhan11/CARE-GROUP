<?php
include "config/db.php";
require_once "config/mail.php";
$error = '';
if (isset($_POST["register"])) {
    $emailPattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    $phonePattern = "/^((\+92)|(0092)|(0))?3[0-9]{2}[-?\s]?[0-9]{7}$/";
    if (
        strlen($_POST["full_name"]) >= 3 &&
        preg_match($emailPattern, $_POST["email"]) &&
        preg_match($phonePattern, $_POST["phone"]) &&
        strlen($_POST["password"]) >= 8 &&
        !empty($_POST["role"])
    ) {
        $name = $_POST["full_name"];
        $email = $_POST["email"];
        $phone = $_POST["phone"];
        $password = $_POST["password"];
        $role = $_POST["role"];
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO users (full_name,email,phone,password,role) VALUES ('$name','$email','$phone','$hash','$role')";
        $result = mysqli_query($conn, $query);

        if ($result) {
            $_SESSION["role"] = $role;
            $_SESSION["user_id"] = mysqli_insert_id($conn);
            sendRegistrationCongratsEmail([
                'full_name' => $name,
                'email' => $email,
                'role' => $role
            ]);

            if ($_SESSION["role"] === "Doctor") {
                header("Location: register_doctor.php");
                exit();
            }
            if ($_SESSION["role"] === "Patient") {
                $_SESSION['full_name'] = $name;
                header("Location: register_patients.php");
                exit();
            }
        } else {
            $error = "Registration error: Username or phone number already registered.";
        }
    } else {
        $error = "Please complete all mandatory data fields correctly.";
    }
}

$pageTitle = "Create Cybernetic Account";
include 'includes/header.php';
?>

<div class="auth-page">
    <main class="auth-card">
        <div class="eyebrow-badge">MANDATORY DATA ENFORCEMENT</div>
        <h1>Create Cybernetic Account</h1>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 24px;">
            Enter your verified information to join the CARE Group Clinical Nexus.
        </p>

        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>

        <form action="" method="post" style="display: grid; gap: 18px;">
            <div class="field">
                <label>FULL NAME <span style="color: var(--cyan-neon);">*</span></label>
                <input type="text" name="full_name" required placeholder="e.g. Dr. Samia Khan / Ali Raza">
            </div>

            <div class="field">
                <label>EMAIL ADDRESS <span style="color: var(--cyan-neon);">*</span></label>
                <input type="email" name="email" required placeholder="name@domain.com">
            </div>

            <div class="field">
                <label>PHONE NUMBER <span style="color: var(--cyan-neon);">*</span></label>
                <input type="tel" name="phone" required placeholder="e.g. 0300-1234567">
            </div>

            <div class="field">
                <label>ENCRYPTED PASSWORD <span style="color: var(--cyan-neon);">*</span></label>
                <input type="password" name="password" minlength="8" required placeholder="Min 8 characters">
            </div>

            <div class="field">
                <label>PORTAL ROLE <span style="color: var(--cyan-neon);">*</span></label>
                <select name="role" required>
                    <option value="Patient">Patient Discovery</option>
                    <option value="Doctor">Doctor Practitioner</option>
                </select>
            </div>

            <button class="btn btn-primary" type="submit" name="register" style="width: 100%; margin-top: 10px;">
                <span>❖ Register Account & Proceed</span>
            </button>
        </form>

        <p style="color: var(--text-muted); font-size: 13px; text-align: center; margin-top: 24px;">
            Already have an active account? <a href="login.php" style="color: var(--cyan-neon); font-weight: 700;">Sign in here</a>
        </p>
    </main>
</div>

<?php include 'includes/footer.php'; ?>
