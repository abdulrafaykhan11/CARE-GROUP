<?php
require_once '../config/db.php';
function desc($table) {
    global $conn;
    $res = mysqli_query($conn, 'DESCRIBE ' . $table);
    if(!$res) { echo "No table $table\n"; return; }
    echo "Table: $table\n";
    while($row = mysqli_fetch_assoc($res)) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
    echo "\n";
}
desc('clinics');
desc('doctor_availability');

// Query existing clinics
$res = mysqli_query($conn, "SELECT * FROM clinics LIMIT 5");
echo "Existing clinics:\n";
while($row = mysqli_fetch_assoc($res)) {
    print_r($row);
}
?>
