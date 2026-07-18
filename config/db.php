<?php
$conn = mysqli_connect("localhost","root","","care");
if(mysqli_connect_errno()){
    echo "Error in database". mysqli_connect_error();
}
session_start();
?>