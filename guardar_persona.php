<?php
header('Content-Type: application/json');

$debug = [];

$mysqli = new mysqli("localhost", "root", "", "bdmascotas");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión', 'debug' => $mysqli->connect_error]);
    exit;
}

// ✅ Captura todas las variables del POST
$debug[] = ">>> POST:";
$debug[] = $_POST;
$debug[] = ">>> FILES:";
$debug[] = $_FILES;

// --------------------
// ✅ VARIABLES DEL FORM
// --------------------
$dni           = $_POST['dni'] ?? null;
$nombres       = $_POST['nombres'] ?? null;
$apellido1     = $_POST['apellido1'] ?? null;
$apellido2     = $_POST['apellido2'] ?? null;
$telefono      = $_POST['telefono'] ?? null;
$generoNombre  = $_POST['genero'] ?? null;
$rol           = $_POST['rol'] ?? null;

// --------------------
// ✅ VERIFICAR CAMPOS
// --------------------
if (!$dni || !$nombres || !$apellido1 || !$apellido2 || !$telefono || !$generoNombre || !$rol) {
    echo json_encode([
        'success' => false,
        'message' => 'Faltan datos obligatorios en el POST',
        'debug' => $debug
    ]);
    exit;
}

$debug[] = "DATOS PERSONA: [$dni, $nombres, $apellido1, $apellido2, $telefono, $generoNombre, $rol]";

// --------------------
// ✅ OBTENER ID DE GÉNERO
// --------------------
$consultaGenero = "SELECT idgenero_personas FROM genero_personas WHERE nom_genero_personas = ?";
$debug[] = "SQL: $consultaGenero";

$stmt = $mysqli->prepare($consultaGenero);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error en prepare (género)', 'debug' => $mysqli->error]);
    exit;
}
$stmt->bind_param("s", $generoNombre);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $generoID = $row['idgenero_personas'];
    $debug[] = "Género ID encontrado: $generoID";
} else {
    echo json_encode(['success' => false, 'message' => "Género no encontrado: $generoNombre", 'debug' => $debug]);
    exit;
}

// --------------------
// ✅ INSERTAR EN PERSONAS
// --------------------
$sqlInsertPersona = "INSERT INTO personas (dni, nombres, apell1, apell2, telefono, genero_personas_id) VALUES (?, ?, ?, ?, ?, ?)";
$debug[] = "SQL: $sqlInsertPersona";

$stmt = $mysqli->prepare($sqlInsertPersona);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error en prepare (personas)', 'debug' => $mysqli->error]);
    exit;
}
$stmt->bind_param("sssssi", $dni, $nombres, $apellido1, $apellido2, $telefono, $generoID);
if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al ejecutar (personas)', 'debug' => $stmt->error]);
    exit;
}
$idPersona = $mysqli->insert_id;
$debug[] = "ID insertado en personas: $idPersona";

// --------------------
// ✅ INSERTAR SEGÚN ROL
// --------------------
if ($rol === 'propietario') {
    $direccion   = $_POST['direccion'] ?? null;
    $barrioNombre = $_POST['barrio'] ?? null;

    if (!$direccion || !$barrioNombre) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos de propietario', 'debug' => $debug]);
        exit;
    }

    $consultaBarrio = "SELECT idbarrios FROM barrios WHERE nombres_barrios = ?";
    $debug[] = "SQL: $consultaBarrio";

    $stmt = $mysqli->prepare($consultaBarrio);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en prepare (barrio)', 'debug' => $mysqli->error]);
        exit;
    }
    $stmt->bind_param("s", $barrioNombre);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $barrioID = $row['idbarrios'];
        $debug[] = "Barrio ID encontrado: $barrioID";
    } else {
        echo json_encode(['success' => false, 'message' => "Barrio no encontrado: $barrioNombre", 'debug' => $debug]);
        exit;
    }

    $sqlInsertProp = "INSERT INTO propietarios (direccion, personas_id, barrios_id) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sqlInsertProp);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en prepare (propietarios)', 'debug' => $mysqli->error]);
        exit;
    }
    $stmt->bind_param("sii", $direccion, $idPersona, $barrioID);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar (propietarios)', 'debug' => $stmt->error]);
        exit;
    }

} elseif ($rol === 'veterinario') {
    $fecha_contratacion = $_POST['fecha_contratacion'] ?? null;
    $fecha_termino      = $_POST['fecha_termino'] ?? null;

    if (!$fecha_contratacion || !$fecha_termino) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos de veterinario', 'debug' => $debug]);
        exit;
    }

    $contratoPDF = null;
    if (isset($_FILES['contrato']) && $_FILES['contrato']['error'] === 0) {
        $contratoPDF = 'contratos/' . uniqid() . '.pdf';
        if (!move_uploaded_file($_FILES['contrato']['tmp_name'], $contratoPDF)) {
            echo json_encode(['success' => false, 'message' => 'Error al mover el archivo PDF', 'debug' => $debug]);
            exit;
        }
    }

    $sqlInsertVet = "INSERT INTO veterinarios (personas_id, contrato_pdf, fecha_contratacion, plazo_contrato) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sqlInsertVet);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Error en prepare (veterinarios)', 'debug' => $mysqli->error]);
        exit;
    }
    $stmt->bind_param("isss", $idPersona, $contratoPDF, $fecha_contratacion, $fecha_termino);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al ejecutar (veterinarios)', 'debug' => $stmt->error]);
        exit;
    }
}

echo json_encode([
    'success' => true,
    'message' => 'Registro exitoso',
    'idPersona' => $idPersona,
    'debug' => $debug
]);
?>