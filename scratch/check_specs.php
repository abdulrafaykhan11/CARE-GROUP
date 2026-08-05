<?php
require_once '../config/db.php';
$res = mysqli_query($conn, "SELECT s.specialization_name, COUNT(d.doctor_id) as cnt FROM doctors d JOIN specializations s ON d.specialization_id=s.specialization_id GROUP BY s.specialization_id");
while($row = mysqli_fetch_assoc($res)) {
    echo $row['specialization_name'] . ": " . $row['cnt'] . " doctors\n";
}
?>
