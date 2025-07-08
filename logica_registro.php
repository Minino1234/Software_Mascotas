<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");

require_once "bd.php";

$accion = $_GET["action"] ?? $_POST["action"] ?? "";

if ($accion === "buscar_dni") {
    $dni = $_GET["dni"] ?? "";
    $sql = "SELECT idPersona AS id, nombres, apell1, apell2 FROM personas WHERE dni = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$dni]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC) ?: null);
    exit;
}

if ($accion === "autocompletar") {
    $nombre = $_GET["nombre"] ?? "";
    $sql = "SELECT idPersona AS id, dni, CONCAT(nombres, ' ', apell1, ' ', apell2) AS nombre_completo 
            FROM personas 
            WHERE CONCAT(nombres, ' ', apell1, ' ', apell2) LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$nombre%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($accion === "mascotas") {
    $id = $_GET["id"] ?? "";
    $sql = "SELECT idMascota AS id, nombreMascota AS nombre, idEspecie AS especie, color AS raza 
            FROM mascotas WHERE idPersona = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($accion === "campanias") {
    $q = $_GET["q"] ?? "";
    $sql = "SELECT idCampanas AS id, nombre_campana AS nombre, lugar_campana AS lugar, fecha_campana AS fecha 
            FROM campanas 
            WHERE nombre_campana LIKE ? OR lugar_campana LIKE ? OR fecha_campana LIKE ? 
            ORDER BY fecha_campana DESC LIMIT 5";
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$q%", "%$q%", "%$q%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($accion === "procedimientos") {
    $idCamp = $_GET["id"] ?? "";
    $sql = "SELECT idprocedimientos AS id, nombre_procedimiento AS nombre 
            FROM procedimientos WHERE idCampanas = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$idCamp]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($accion === "medicinas") {
    $q = $_GET["q"] ?? "";
    $sql = "SELECT idmedicinas AS id, nom_medicina AS nombre, idlotes AS lote, fec_vencimiento AS vencimiento 
            FROM medicinas 
            WHERE nom_medicina LIKE ? OR idlotes LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute(["%$q%", "%$q%"]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
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

    try {
        $conn->beginTransaction();

        foreach ($mascotas as $idMascota) {
            foreach ($procedimientos as $idProc) {
                foreach ($medicinas as $idMed) {
                    $stmt = $conn->prepare("
                        INSERT INTO registro_ficha (idCampanas, mascotas_idMascota, observaciones, procedimientos_idprocedimientos, idmedicinas) 
                        VALUES (?, ?, '', ?, ?)
                    ");
                    $stmt->execute([$campania, $idMascota, $idProc, $idMed]);
                }
            }
        }

        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
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

    $registros = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $resultado = [];

    foreach ($registros as $fila) {
        $id = $fila["id"];
        if (!isset($resultado[$id])) {
            $resultado[$id] = [
                "id" => $id,
                "mascota" => $fila["mascota"],
                "propietario" => $fila["propietario"],
                "campania" => $fila["campania"],
                "procedimientos" => [],
                "medicinas" => []
            ];
        }
        $resultado[$id]["procedimientos"][] = $fila["procedimiento"];
        $resultado[$id]["medicinas"][] = $fila["medicina"];
    }

    echo json_encode(array_values($resultado));
    exit;
}

if ($accion === "eliminar") {
    $input = json_decode(file_get_contents("php://input"), true);
    $id = $input["id"] ?? "";
    try {
        $stmt = $conn->prepare("DELETE FROM registro_ficha WHERE idregistro_ficha = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true, "message" => "Ficha eliminada"]);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
    exit;
}

echo json_encode(["success" => false, "message" => "Acción no válida"]);
?>

