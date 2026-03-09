<?php
require 'db.php';
$res = $conn->query("SHOW COLUMNS FROM attendance");
while($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
