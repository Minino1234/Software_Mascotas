<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once "bd.php";

$accion = $_GET["action"] ?? $_POST["action"] ?? "";

if ($accion === "buscar_dni") {
    $dni = $_GET["dni"] ?? "";
    $stmt = $conn->prepare("SELECT idPersona AS id, nombres, apell1, apell2, dni FROM personas WHERE dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_assoc();
    echo json_encode($resultado ?: null);
    exit;
}

if ($accion === "autocompletar") {
    $nombre = $_GET["nombre"] ?? "";
    $like = "%" . $nombre . "%";
    $stmt = $conn->prepare("SELECT idPersona AS id, dni, CONCAT(nombres, ' ', apell1, ' ', apell2) AS nombre_completo 
                            FROM personas 
                            WHERE CONCAT(nombres, ' ', apell1, ' ', apell2) LIKE ?");
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($resultado);
    exit;
}
if ($accion === "buscar_dni_parcial") {
    $dni = $_GET["dni"] ?? "";
    $stmt = $conn->prepare("SELECT idPersona AS id, dni, CONCAT(nombres, ' ', apell1, ' ', apell2) AS nombre_completo,
                                   nombres, apell1, apell2
                            FROM personas 
                            WHERE dni LIKE CONCAT(?, '%') LIMIT 10");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($resultado);
    exit;
}
if ($accion === "mascotas") {
    $dni = $_GET["dni"] ?? "";

    $stmt = $conn->prepare("
        SELECT 
            m.idMascota AS id,
            m.nombreMascota AS nombre,
            e.nombre_especie AS especie,
            m.edad_mascotas AS edad
        FROM personas p
        INNER JOIN propietarios pr ON pr.personas_id = p.idPersona
        INNER JOIN propietarios_has_mascotas phm ON phm.propietarios_idPropietarios = pr.idPropietarios
        INNER JOIN mascotas m ON m.idMascota = phm.mascotas_idMascota
        INNER JOIN especie e ON e.idEspecie = m.idEspecie
        WHERE p.dni = ?
    ");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($accion === "campanias") {
    $q = $_GET["q"] ?? "";
    $like = "%" . $q . "%";
    $stmt = $conn->prepare("SELECT idCampanas AS id, nombre_campana AS nombre, lugar_campana AS lugar, fecha_campana AS fecha 
                            FROM campanas 
                            WHERE nombre_campana LIKE ? OR lugar_campana LIKE ? OR fecha_campana LIKE ? 
                            ORDER BY fecha_campana DESC LIMIT 5");
    $stmt->bind_param("sss", $like, $like, $like);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($resultado);
    exit;
}

if ($accion === "procedimientos") {
    $idCampania = $_GET["id"] ?? 0;

    $stmt = $conn->prepare("
        SELECT p.idprocedimientos AS id, p.nombre_procedimiento AS nombre
        FROM campanas_has_procedimientos chp
        INNER JOIN procedimientos p ON p.idprocedimientos = chp.procedimientos_idprocedimientos
        WHERE chp.campanas_idCampanas = ?
    ");
    $stmt->bind_param("i", $idCampania);
    $stmt->execute();
    $res = $stmt->get_result();
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($accion === "medicinas") {
    $q = $_GET["q"] ?? "";
    $like = "%" . $q . "%";
    $stmt = $conn->prepare("SELECT m.idmedicinas AS id, m.nom_medicina AS nombre, 
                               l.codigos_lotes AS lote, m.fec_vencimiento AS vencimiento 
                        FROM medicinas m
                        INNER JOIN lotes_medicinas l ON m.idlotes = l.idlotes
                        WHERE m.nom_medicina LIKE ? OR l.codigos_lotes LIKE ?");
    $stmt->bind_param("ss", $like, $like);
    $stmt->execute();
    $resultado = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    echo json_encode($resultado);
    exit;
}

if ($accion === "guardar") {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $idPropietario = $input["propietario_id"] ?? null;
    $mascotas = $input["mascotas"] ?? [];
    $campania = $input["campania"] ?? null;
    $procedimientos = $input["procedimientos"] ?? [];
    $medicinas = $input["medicinas"] ?? [];

    if (!$idPropietario || !$mascotas || !$campania || !$procedimientos || !$medicinas) {
        echo json_encode(["success" => false, "message" => "Datos incompletos."]);
        exit;
    }
    

    $conn->begin_transaction();
    try {
        foreach ($mascotas as $idMascota) {
            foreach ($procedimientos as $idProc) {
                foreach ($medicinas as $idMed) {
                    $stmt = $conn->prepare("INSERT INTO registro_ficha (idCampanas, mascotas_idMascota, observaciones, procedimientos_idprocedimientos, idmedicinas)
                                            VALUES (?, ?, '', ?, ?)");
                    $stmt->bind_param("iiii", $campania, $idMascota, $idProc, $idMed);
                    $stmt->execute();
                }
            }
        }
        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Error al guardar: " . $e->getMessage()]);
    }
    exit;
}

if ($accion === "registros") {
    $sql = "SELECT rf.idregistro_ficha AS id,
                   m.nombreMascota AS mascota,
                   CONCAT(p.nombres, ' ', p.apell1) AS propietario,
                   c.nombre_campana AS campania,
                   pr.nombre_procedimiento AS procedimiento,
                   md.nom_medicina AS medicina
            FROM registro_ficha rf
            INNER JOIN mascotas m ON rf.mascotas_idMascota = m.idMascota
            INNER JOIN personas p ON m.idPersona = p.idPersona
            INNER JOIN campanas c ON rf.idCampanas = c.idCampanas
            INNER JOIN procedimientos pr ON rf.procedimientos_idprocedimientos = pr.idprocedimientos
            INNER JOIN medicinas md ON rf.idmedicinas = md.idmedicinas
            ORDER BY rf.idregistro_ficha DESC";

    $resultado = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
    $agrupado = [];

    foreach ($resultado as $fila) {
        $id = $fila["id"];
        if (!isset($agrupado[$id])) {
            $agrupado[$id] = [
                "id" => $id,
                "mascota" => $fila["mascota"],
                "propietario" => $fila["propietario"],
                "campania" => $fila["campania"],
                "procedimientos" => [],
                "medicinas" => []
            ];
        }
        $agrupado[$id]["procedimientos"][] = $fila["procedimiento"];
        $agrupado[$id]["medicinas"][] = $fila["medicina"];
    }

    echo json_encode(array_values($agrupado));
    exit;
}

if ($accion === "eliminar") {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input["id"] ?? "";
    if (!$id) {
        echo json_encode(["success" => false, "message" => "ID no recibido"]);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM registro_ficha WHERE idregistro_ficha = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Ficha eliminada"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error al eliminar"]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Acción no válida"]);
?>

