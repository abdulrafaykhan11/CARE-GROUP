<?php
require_once __DIR__ . '/../config/db.php';
$res = $conn->query("SELECT * FROM notifications WHERE status = 'Approved'");
echo "Approved count: " . $res->num_rows . "\n";
while($row = $res->fetch_assoc()) {
    print_r($row);
}
