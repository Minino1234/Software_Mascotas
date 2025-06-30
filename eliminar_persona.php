<?php
header('Content-Type: application/json');

$entrada = json_decode(file_get_contents("php://input"), true);
$idPersona = $entrada['idPersona'] ?? null;
$rol = $entrada['rol'] ?? null;

$mysqli = new mysqli("localhost", "root", "", "bdmascotas");
if ($mysqli->connect_error || !$idPersona || !$rol) {
    echo json_encode(['success' => false, 'message' => 'Error de entrada']);
    exit;
}

if ($rol === 'propietario') {
    $mysqli->query("DELETE FROM propietarios WHERE personas_id = $idPersona");
} elseif ($rol === 'veterinario') {
    $mysqli->query("DELETE FROM veterinarios WHERE personas_id = $idPersona");
}

$mysqli->query("DELETE FROM personas WHERE idPersona = $idPersona");

echo json_encode(['success' => true, 'message' => 'Eliminado correctamente']);
