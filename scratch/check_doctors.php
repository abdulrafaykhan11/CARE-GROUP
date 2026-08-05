<?php
require_once '../config/db.php';
$res = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM doctors");
$row = mysqli_fetch_assoc($res);
echo "Total doctors: " . $row['cnt'] . "\n";

$res2 = mysqli_query($conn, "SELECT c.city_name, COUNT(d.doctor_id) as cnt FROM doctors d JOIN cities c ON d.city_id=c.city_id GROUP BY c.city_id");
while($row2 = mysqli_fetch_assoc($res2)) {
    echo $row2['city_name'] . ": " . $row2['cnt'] . " doctors\n";
}
?>
