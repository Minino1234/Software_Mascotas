<?php
$conexion = new mysqli("localhost", "root", "", "bdmascotas");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$id = $_GET['id'];
$sql = "SELECT * FROM medicinas WHERE idmedicinas = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$data = $result->fetch_assoc();

echo json_encode($data);
$stmt->close();
$conexion->close();
?>
