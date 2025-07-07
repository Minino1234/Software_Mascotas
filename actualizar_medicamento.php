<?php
$conexion = new mysqli("localhost", "root", "", "bdmascotas");
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$id = $_POST['idmedicinas'];
$nom = $_POST['nom_medicina'];
$desc = $_POST['descripcion'];
$fec = $_POST['fec_vencimiento'];
$idlote = $_POST['idlotes'];

$stmt = $conexion->prepare("UPDATE medicinas SET nom_medicina=?, descripcion=?, fec_vencimiento=?, idlotes=? WHERE idmedicinas=?");
$stmt->bind_param("sssii", $nom, $desc, $fec, $idlote, $id);
$stmt->execute();

echo $stmt->affected_rows > 0 ? "Medicamento actualizado correctamente." : "No se realizaron cambios.";
$stmt->close();
$conexion->close();
?>
