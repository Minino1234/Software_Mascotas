<?php
header('Content-Type: application/json');

$idPersona = $_GET['id'] ?? null;
$rol = $_GET['rol'] ?? null;

$mysqli = new mysqli("localhost", "root", "", "bdmascotas");
if ($mysqli->connect_error || !$idPersona || !$rol) {
    echo json_encode([]);
    exit;
}

if ($rol === 'propietario') {
    $sql = "SELECT direccion, barrios_id FROM propietarios WHERE personas_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    // Obtener nombre del barrio
    $nombre = '';
    if ($res && isset($res['barrios_id'])) {
        $stmt = $mysqli->prepare("SELECT nombres_barrios FROM barrios WHERE idbarrios = ?");
        $stmt->bind_param("i", $res['barrios_id']);
        $stmt->execute();
        $nombreBarrio = $stmt->get_result()->fetch_assoc();
        $nombre = $nombreBarrio['nombres_barrios'] ?? '';
    }

    echo json_encode([
        'direccion' => $res['direccion'] ?? '',
        'barrio' => $nombre
    ]);
} elseif ($rol === 'veterinario') {
    $sql = "SELECT fecha_contratacion, plazo_contrato FROM veterinarios WHERE personas_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $idPersona);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    echo json_encode([
        'fecha_contratacion' => $res['fecha_contratacion'] ?? '',
        'plazo_contrato' => $res['plazo_contrato'] ?? ''
    ]);
} else {
    echo json_encode([]);
}
