<?php
$conexion = new mysqli("localhost", "root", "", "bdmascotas"); // Añade tu contraseña si corresponde
if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

$sql = "SELECT idlotes, codigos_lotes FROM lotes_medicinas ORDER BY idlotes DESC";
$resultado = $conexion->query($sql);

$lotes = array();
while ($fila = $resultado->fetch_assoc()) {
  $lotes[] = $fila;
}

echo json_encode($lotes);
$conexion->close();
?>
