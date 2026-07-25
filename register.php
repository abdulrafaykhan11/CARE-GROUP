<?php
include "config/db.php";
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
            $error = "Error: username already exists or phone number already exists";
        }
    } else {
        $error = "Please fill all fields correctly.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Care Connect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-card">
        <a class="brand" href="index.php">care<span>connect</span></a>
        <h1>Create Account</h1>
        <?php if($error): ?><div class="alert alert-error"><?php echo $error; ?></div><?php endif; ?>
        <form action="" method="post">
            <div class="field">
                <label>FULLNAME</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="field">
                <label>EMAIL</label>
                <input type="email" name="email" required>
            </div>
            <div class="field">
                <label>PHONE</label>
                <input type="tel" name="phone" required placeholder="e.g. 0300-1234567">
            </div>
            <div class="field">
                <label>PASSWORD</label>
                <input type="password" name="password" minlength="8" required>
            </div>
            <div class="field">
                <label>ROLE</label>
                <select name="role" required>
                    <option value="Patient">Patient</option>
                    <option value="Doctor">Doctor</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit" name="register">Register Now</button>
        </form>
        <p class="note">Already have an account? <a href="login.php" style="color:var(--teal);font-weight:700">Sign in</a></p>
    </main>
</body>
</html>
