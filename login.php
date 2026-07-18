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
        EMAIL : <input type="email" name="email"><br>
        Password : <input type="password" name="password"><br>
        <input type="submit" name="submit" value="Login"><br>
    </form>
</body>

</html>
<?php
if (isset($_POST["submit"])) {
    $emailPattern = "/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/";
    if (preg_match($emailPattern, $_POST['email']) && strlen($_POST["password"]) >= 8) {
        $email = $_POST["email"];
        $password = $_POST["password"];

        $query = "SELECT * FROM users WHERE email = '$email'";
        $result = mysqli_query($conn, $query);
        if (mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            if (password_verify($password, $row['password'])) {
                $_SESSION['full_name'] = $row["full_name"];
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['role'] = $row['role'];

                if ($_SESSION['role'] === "Admin") {
                    echo "<script>window.location.href = 'index.php'</script>";
                }
                if ($_SESSION['role'] === "Doctor") {
                    echo "<script>window.location.href = 'index.php'</script>";
                }
                if ($_SESSION['role'] === "Patient") {
                    echo "<script>window.location.href = 'index.php'</script>";
                }
            } else {
                echo "wrong password";
            }
        } else {
            echo "error";
        }
    } else {
        echo "email or password incorrect";
    }
}
?>