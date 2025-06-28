<?php
include "bd.php";

$sql = "SELECT nom_genero_personas FROM genero_personas";
$result = $conn->query($sql);

$genero_personas = [];

while ($row = $result->fetch_assoc()) {
    $genero_personas[] = $row['nom_genero_personas'];
}

header('Content-Type: application/json');
echo json_encode($genero_personas);
?>