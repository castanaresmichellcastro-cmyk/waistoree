<?php
$conn = new mysqli("localhost:3307", "root", "", "waistore_db");
$res = $conn->query("DESCRIBE appearance_settings");
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>