<?php
include "config/db.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <form action="" method="post">
        FULLNAME : <input type="text" name="full_name"><br>
        EMAIL : <input type="email" name="email"><br>
        PHONE : <input type="phone" name="phone"><br>
        PASSWORD : <input type="password" name="password"><br>
        ROLE :
        <select name="role" required>
            <option value="Patient">Patient</option>
            <option value="Doctor">Doctor</option>
        </select><br><br>
        <input type="submit" name="register" value="Register">
    </form>
</body>

</html>
<?php
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
                echo "<script>window.location.href = 'register_docotr.php'</script>";
                exit();
            }
            if ($_SESSION["role"] === "Patient") {
                $_SESSION['full_name'] = $patient_name;
                echo "<script>window.location.href = 'register_patients.php'</script>";
                exit();
            }
        } else {
            echo "error username alredy exist or phone number alredy exist";
        }
    }
}
?>