<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Conexión
$conexion = new mysqli('localhost', 'root', '', 'bdmascotas');
$conexion->set_charset("utf8");

if ($conexion->connect_error) {
  die("Conexión fallida: " . $conexion->connect_error);
}

$dni = $_GET['dni'] ?? '';
$nombre = $_GET['nombre'] ?? '';
$apellido = $_GET['apellidos'] ?? '';

$response = [
  'propietario' => null,
  'mascotas' => []
];

if ($dni) {
  $sqlPropietario = "
    SELECT p.idPropietarios, pr.nombres, pr.apell1, pr.apell2, b.nombres_barrios AS barrio
    FROM propietarios p
    JOIN personas pr ON p.personas_id = pr.id
    JOIN barrios b ON p.barrios_id = b.id
    WHERE pr.dni = ?
  ";
  $stmt = $conexion->prepare($sqlPropietario);
  $stmt->bind_param("s", $dni);
} elseif ($nombre && $apellido) {
  $sqlPropietario = "
    SELECT p.idPropietarios, pr.nombres, pr.apell1, pr.apell2, b.nombres_barrios AS barrio
    FROM propietarios p
    JOIN personas pr ON p.personas_id = pr.id
    JOIN barrios b ON p.barrios_id = b.id
    WHERE pr.nombres LIKE ? AND pr.apell1 LIKE ?
  ";
  $stmt = $conexion->prepare($sqlPropietario);
  $n = '%' . $nombre . '%';
  $a = '%' . $apellido . '%';
  $stmt->bind_param("ss", $n, $a);
} else {
  echo json_encode($response);
  exit;
}

$stmt->execute();
$result = $stmt->get_result();
$propietario = $result->fetch_assoc();

if ($propietario) {
  $response['propietario'] = $propietario;

  $sqlMascotas = "
    SELECT nombre, especie, sexo
    FROM mascotas
    WHERE propietario_id = ?
  ";
  $stmtMascotas = $conexion->prepare($sqlMascotas);
  $stmtMascotas->bind_param("i", $propietario['idPropietarios']);
  $stmtMascotas->execute();
  $resultMascotas = $stmtMascotas->get_result();
  $response['mascotas'] = $resultMascotas->fetch_all(MYSQLI_ASSOC);
  $stmtMascotas->close();
}

$stmt->close();
$conexion->close();

header('Content-Type: application/json');
echo json_encode($response);
