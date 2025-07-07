<?php
$conexion = new mysqli("localhost", "root", "", "bdmascotas");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$id = $_POST['id'];
$stmt = $conexion->prepare("DELETE FROM medicinas WHERE idmedicinas = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

echo $stmt->affected_rows > 0 ? "Medicamento eliminado correctamente." : "No se pudo eliminar el medicamento.";
$stmt->close();
$conexion->close();
?>
