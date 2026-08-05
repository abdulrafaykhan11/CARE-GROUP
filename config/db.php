<?php
// Session warning fix
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$conn = mysqli_connect("localhost", "root", "", "care");
// ... baki db connection code
