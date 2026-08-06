<?php
require_once __DIR__ . '/../config/db.php';

$res = $conn->query("SELECT * FROM notifications");
if ($res) {
    echo "Total rows: " . $res->num_rows . "\n";
    while($row = $res->fetch_assoc()) {
        print_r($row);
    }
}
