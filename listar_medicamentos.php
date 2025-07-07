<?php
$conexion = new mysqli("localhost", "root", "", "bdmascotas");
if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

$sql = "SELECT m.idmedicinas, m.nom_medicina, m.descripcion, m.fec_vencimiento, l.codigos_lotes
        FROM medicinas m
        JOIN lotes_medicinas l ON m.idlotes = l.idlotes
        ORDER BY m.idmedicinas DESC";
$resultado = $conexion->query($sql);

$medicamentos = array();
while ($row = $resultado->fetch_assoc()) {
  $medicamentos[] = $row;
}

echo json_encode($medicamentos);
$conexion->close();
?>
