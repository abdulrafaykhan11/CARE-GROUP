<?php
if (session_start() === PHP_SESSION_NONE) {
    session_start();
}
function checkLogin()
{
    if (!isset($_SESSION["user_id"])) {
        header("Location: ../login.php?error=please_login");
        exit();
    }
}

function checkRole($allowedRole)
{
    checkLogin();
    if ($_SESSION['role'] !== $allowedRole) {
        echo "Access denied";
        exit();
    }
}
?>