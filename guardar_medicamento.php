<?php
// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "bdmascotas");

// Verificar la conexión
if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Obtener y validar los datos del formulario
$nombre = $_POST['nom_medicina'] ?? '';
$descripcion = $_POST['descripcion'] ?? '';
$fecha_vencimiento = $_POST['fec_vencimiento'] ?? '';
$idlote = $_POST['idlotes'] ?? '';

// Validar campos vacíos
if (empty($nombre) || empty($descripcion) || empty($fecha_vencimiento) || empty($idlote)) {
    echo "Por favor complete todos los campos.";
    exit;
}

// Insertar en la base de datos
$sql = "INSERT INTO medicinas (nom_medicina, descripcion, fec_vencimiento, idlotes)
        VALUES (?, ?, ?, ?)";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("sssi", $nombre, $descripcion, $fecha_vencimiento, $idlote);

if ($stmt->execute()) {
    echo "Medicamento registrado correctamente.";
} else {
    echo "Error al registrar el medicamento: " . $conexion->error;
}

$stmt->close();
$conexion->close();
?>
