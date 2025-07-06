<?php
// Encabezado para indicar que se envía JSON
header('Content-Type: application/json; charset=utf-8');

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "bdmascotas");

// Verificar si hay error de conexión
if ($conexion->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Error de conexión: " . $conexion->connect_error]);
    exit;
}

// Consulta SQL para obtener los lotes de medicamentos
$sql = "SELECT idlotes, codigos_lotes FROM lotes_medicinas ORDER BY codigos_lotes ASC";
$resultado = $conexion->query($sql);

// Verificar si la consulta fue exitosa
if (!$resultado) {
    http_response_code(500);
    echo json_encode(["error" => "Error en la consulta: " . $conexion->error]);
    $conexion->close();
    exit;
}

// Construir el arreglo con los lotes
$lotes = [];
while ($fila = $resultado->fetch_assoc()) {
    $lotes[] = [
        "idlotes" => $fila["idlotes"],
        "codigos_lotes" => $fila["codigos_lotes"]
    ];
}

// Enviar resultado en formato JSON
echo json_encode($lotes, JSON_UNESCAPED_UNICODE);

// Cerrar conexión
$conexion->close();
?>
