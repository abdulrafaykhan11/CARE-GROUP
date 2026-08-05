<?php
require_once '../config/db.php';
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM doctor_availability");
$row = mysqli_fetch_assoc($res);
echo "Total availabilities: " . $row['cnt'] . "\n";

$res2 = mysqli_query($conn, "SELECT full_name FROM users WHERE role = 'Doctor' LIMIT 5");
echo "Sample names:\n";
while($row2 = mysqli_fetch_assoc($res2)) {
    echo $row2['full_name'] . "\n";
}
?>
