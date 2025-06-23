<?php
include "bd.php";

$sql = "SELECT nombres_barrios FROM barrios";
$result = $conn->query($sql);

$barrios = [];

while ($row = $result->fetch_assoc()) {
    $barrios[] = $row['nombres_barrios'];
}

header('Content-Type: application/json');
echo json_encode($barrios);
?>
