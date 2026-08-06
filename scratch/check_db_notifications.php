<?php
require_once __DIR__ . '/../config/db.php';

$res = $conn->query("SHOW TABLES LIKE '%notif%'");
if ($res && $res->num_rows > 0) {
    while($row = $res->fetch_row()) {
        $t = $row[0];
        echo "Found table: $t\n";
        $d = $conn->query("DESCRIBE $t");
        while($r = $d->fetch_assoc()) {
            echo "  " . $r['Field'] . " - " . $r['Type'] . " - " . $r['Null'] . " - " . $r['Key'] . " - " . $r['Default'] . "\n";
        }
    }
} else {
    echo "No notification table found matching %notif%.\n";
    $all = $conn->query("SHOW TABLES");
    echo "All tables in database:\n";
    while($row = $all->fetch_row()) {
        echo " - " . $row[0] . "\n";
    }
}
