<?php
header('Content-Type: application/json');
$debug = [];

$mysqli = new mysqli("localhost", "root", "", "bdmascotas");
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Error de conexión', 'debug' => $mysqli->connect_error]);
    exit;
}

// ✅ Captura POST
$debug[] = ">>> POST:";
$debug[] = $_POST;
$debug[] = ">>> FILES:";
$debug[] = $_FILES;

$dni         = $_POST['dni'] ?? null;
$nombres     = $_POST['nombres'] ?? null;
$apellido1   = $_POST['apellido1'] ?? null;
$apellido2   = $_POST['apellido2'] ?? null;
$telefono    = $_POST['telefono'] ?? null;
$generoNombre = $_POST['genero'] ?? null;
$rol         = $_POST['rol'] ?? null;

$esEdicion = isset($_POST['editar']) && $_POST['editar'] == '1';
$idPersona = $_POST['idPersona'] ?? null;

if (!$dni || !$nombres || !$apellido1 || !$apellido2 || !$telefono || !$generoNombre || !$rol) {
    echo json_encode(['success' => false, 'message' => 'Faltan datos obligatorios', 'debug' => $debug]);
    exit;
}

// ✅ Obtener ID de género
$consultaGenero = "SELECT idgenero_personas FROM genero_personas WHERE nom_genero_personas = ?";
$stmt = $mysqli->prepare($consultaGenero);
$stmt->bind_param("s", $generoNombre);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $generoID = $row['idgenero_personas'];
} else {
    echo json_encode(['success' => false, 'message' => 'Género no encontrado', 'debug' => $debug]);
    exit;
}

// ✅ Insertar o actualizar personas
if ($esEdicion && $idPersona) {
    $sql = "UPDATE personas SET dni = ?, nombres = ?, apell1 = ?, apell2 = ?, telefono = ?, genero_personas_id = ? WHERE idPersona = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssssi", $dni, $nombres, $apellido1, $apellido2, $telefono, $generoID, $idPersona);
    $stmt->execute();
} else {
    $sql = "INSERT INTO personas (dni, nombres, apell1, apell2, telefono, genero_personas_id) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("sssssi", $dni, $nombres, $apellido1, $apellido2, $telefono, $generoID);
    $stmt->execute();
    $idPersona = $mysqli->insert_id;
}

// ✅ Según el rol
if ($rol === 'propietario') {
    $direccion = $_POST['direccion'] ?? null;
    $barrioNombre = $_POST['barrio'] ?? null;

    if (!$direccion || !$barrioNombre) {
        echo json_encode(['success' => false, 'message' => 'Faltan datos de propietario']);
        exit;
    }

    $stmt = $mysqli->prepare("SELECT idbarrios FROM barrios WHERE nombres_barrios = ?");
    $stmt->bind_param("s", $barrioNombre);
    $stmt->execute();
    $barrioID = $stmt->get_result()->fetch_assoc()['idbarrios'] ?? null;

    if (!$barrioID) {
        echo json_encode(['success' => false, 'message' => "Barrio no encontrado"]);
        exit;
    }

    if ($esEdicion) {
        // verificar si existe propietario primero
        $check = $mysqli->prepare("SELECT idPropietarios FROM propietarios WHERE personas_id = ?");
        $check->bind_param("i", $idPersona);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();

        if ($existe) {
            $sql = "UPDATE propietarios SET direccion = ?, barrios_id = ? WHERE personas_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sii", $direccion, $barrioID, $idPersona);
        } else {
            $sql = "INSERT INTO propietarios (direccion, personas_id, barrios_id) VALUES (?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("sii", $direccion, $idPersona, $barrioID);
        }
    } else {
        $sql = "INSERT INTO propietarios (direccion, personas_id, barrios_id) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("sii", $direccion, $idPersona, $barrioID);
    }

    $stmt->execute();

} elseif ($rol === 'veterinario') {
    $fecha_contratacion = $_POST['fecha_contratacion'] ?? null;
    $fecha_termino = $_POST['fecha_termino'] ?? null;

    if (!$fecha_contratacion || !$fecha_termino) {
        echo json_encode(['success' => false, 'message' => 'Faltan fechas de veterinario']);
        exit;
    }

    $contratoPDF = null;
    if (isset($_FILES['contrato']) && $_FILES['contrato']['error'] === 0) {
        $contratoPDF = 'contratos/' . uniqid() . '.pdf';
        move_uploaded_file($_FILES['contrato']['tmp_name'], $contratoPDF);
    }

    if ($esEdicion) {
        $check = $mysqli->prepare("SELECT idveterinarios FROM veterinarios WHERE personas_id = ?");
        $check->bind_param("i", $idPersona);
        $check->execute();
        $existe = $check->get_result()->fetch_assoc();

        if ($existe) {
            if ($contratoPDF) {
                $sql = "UPDATE veterinarios SET contrato_pdf = ?, fecha_contratacion = ?, plazo_contrato = ? WHERE personas_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("sssi", $contratoPDF, $fecha_contratacion, $fecha_termino, $idPersona);
            } else {
                $sql = "UPDATE veterinarios SET fecha_contratacion = ?, plazo_contrato = ? WHERE personas_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("ssi", $fecha_contratacion, $fecha_termino, $idPersona);
            }
        } else {
            $sql = "INSERT INTO veterinarios (personas_id, contrato_pdf, fecha_contratacion, plazo_contrato) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("isss", $idPersona, $contratoPDF, $fecha_contratacion, $fecha_termino);
        }
    } else {
        $sql = "INSERT INTO veterinarios (personas_id, contrato_pdf, fecha_contratacion, plazo_contrato) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("isss", $idPersona, $contratoPDF, $fecha_contratacion, $fecha_termino);
    }

    $stmt->execute();
}

echo json_encode([
    'success' => true,
    'message' => $esEdicion ? 'Actualización exitosa' : 'Registro exitoso',
    'idPersona' => $idPersona,
    'debug' => $debug
]);
?>
