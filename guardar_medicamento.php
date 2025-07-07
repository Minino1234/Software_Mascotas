<?php
$conexion = new mysqli("localhost", "root", "", "bdmascotas"); // Cambia contraseña si es necesario
if ($conexion->connect_error) {
    die("Conexión fallida: " . $conexion->connect_error);
}

$nom_medicina = $_POST['nom_medicina'];
$descripcion = $_POST['descripcion'];
$fec_vencimiento = $_POST['fec_vencimiento'];
$tipo_lote = $_POST['tipo_lote'];

if ($tipo_lote === "nuevo") {
    $nuevo_lote = trim($_POST['nuevo_lote']);

    if ($nuevo_lote === "") {
        echo "El código del nuevo lote no puede estar vacío.";
        exit;
    }

    // Verificar si el lote ya existe
    $check = $conexion->prepare("SELECT idlotes FROM lotes_medicinas WHERE codigos_lotes = ?");
    $check->bind_param("s", $nuevo_lote);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $check->bind_result($idloteExistente);
        $check->fetch();
        $idlote = $idloteExistente;
        $check->close();
    } else {
        // Crear nuevo lote
        $stmt = $conexion->prepare("INSERT INTO lotes_medicinas (codigos_lotes) VALUES (?)");
        $stmt->bind_param("s", $nuevo_lote);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $idlote = $stmt->insert_id;
        } else {
            echo "Error al crear el nuevo lote.";
            exit;
        }
        $stmt->close();
    }
} else {
    // Lote registrado
    $idlote = $_POST['idlotes'];
    if (empty($idlote)) {
        echo "Debe seleccionar un lote registrado.";
        exit;
    }
}

// Insertar medicamento
$stmt2 = $conexion->prepare("INSERT INTO medicinas (nom_medicina, descripcion, fec_vencimiento, idlotes) VALUES (?, ?, ?, ?)");
$stmt2->bind_param("sssi", $nom_medicina, $descripcion, $fec_vencimiento, $idlote);
$stmt2->execute();

if ($stmt2->affected_rows > 0) {
    echo "Medicamento registrado correctamente.";
} else {
    echo "Error al registrar el medicamento.";
}

$stmt2->close();
$conexion->close();
?>
