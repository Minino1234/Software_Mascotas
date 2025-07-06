<?php
include "bd.php";
$accion = $_REQUEST['accion'] ?? '';

function estadoCampania($fecha) {
  $hoy = date("Y-m-d");
  if ($fecha == $hoy) return ["Activa", "estado-activa"];
  elseif ($fecha > $hoy) return ["Pendiente", "estado-pendiente"];
  else return ["Ejecutada", "estado-ejecutada"];
}

switch ($accion) {
  case "listar_procedimientos":
    $res = $conn->query("SELECT idprocedimientos AS id, nombre_procedimiento AS nombre FROM procedimientos");
    $datos = [];
    while ($row = $res->fetch_assoc()) $datos[] = $row;
    echo json_encode($datos);
    break;

  case "crear":
  $nombre = $_POST["nombre"] ?? "";
  $lugar = $_POST["lugar"] ?? "";
  $fecha = $_POST["fecha"] ?? "";
  $tratamientos = json_decode($_POST["tratamientos_txt"] ?? "[]");

  if ($nombre && $lugar && $fecha) {
    $stmt = $conn->prepare("INSERT INTO campanas (nombre_campana, lugar_campana, fecha_campana) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $lugar, $fecha);
    $stmt->execute();
    $campanaId = $conn->insert_id;

    foreach ($tratamientos as $nombreProc) {
      $result = $conn->query("SELECT idprocedimientos FROM procedimientos WHERE nombre_procedimiento = '$nombreProc'");
      if ($fila = $result->fetch_assoc()) {
        $idProc = $fila["idprocedimientos"];
        $conn->query("INSERT INTO campanas_has_procedimientos (campanas_idcampanas, procedimientos_idprocedimientos) VALUES ($campanaId, $idProc)");
      }
    }
  }
  break;

  case "editar":
  $id = intval($_POST["id"] ?? 0);
  $nombre = $_POST["nombre"] ?? "";
  $lugar = $_POST["lugar"] ?? "";
  $fecha = $_POST["fecha"] ?? "";
  $tratamientos = json_decode($_POST["tratamientos_txt"] ?? "[]");

  if ($id && $nombre && $lugar && $fecha) {
    $stmt = $conn->prepare("UPDATE campanas SET nombre_campana = ?, lugar_campana = ?, fecha_campana = ? WHERE idCampanas = ?");
    $stmt->bind_param("sssi", $nombre, $lugar, $fecha, $id);
    $stmt->execute();

    $conn->query("DELETE FROM campanas_has_procedimientos WHERE campanas_idcampanas = $id");
    foreach ($tratamientos as $nombreProc) {
      $result = $conn->query("SELECT idprocedimientos FROM procedimientos WHERE nombre_procedimiento = '$nombreProc'");
      if ($fila = $result->fetch_assoc()) {
        $idProc = $fila["idprocedimientos"];
        $conn->query("INSERT INTO campanas_has_procedimientos (campanas_idcampanas, procedimientos_idprocedimientos) VALUES ($id, $idProc)");
      }
    }
  }
  break;

  case "eliminar":
    $id = intval($_POST["id"] ?? 0);
    if ($id) {
      $conn->query("DELETE FROM campanas_has_procedimientos WHERE campanas_idcampanas = $id");
      $stmt = $conn->prepare("DELETE FROM campanas WHERE idCampanas = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
    }
    break;

  case "obtener":
  $id = intval($_GET["id"] ?? 0);
  $stmt = $conn->prepare("SELECT idCampanas AS id, nombre_campana AS nombre, lugar_campana AS lugar, fecha_campana AS fecha FROM campanas WHERE idCampanas = ?");
  $stmt->bind_param("i", $id);
  $stmt->execute();
  $camp = $stmt->get_result()->fetch_assoc();

  $nombres = [];
  $res = $conn->query("SELECT p.nombre_procedimiento FROM campanas_has_procedimientos chp JOIN procedimientos p ON chp.procedimientos_idprocedimientos = p.idprocedimientos WHERE chp.campanas_idcampanas = $id");
  while ($row = $res->fetch_assoc()) {
    $nombres[] = $row["nombre_procedimiento"];
  }

  $camp["tratamientos_nombres"] = $nombres;
  echo json_encode($camp);
  break;

  case "listar":
  $busqueda = $conn->real_escape_string($_GET["busqueda"] ?? "");
  $orden = $_GET["orden"] ?? "fecha";

  $query = "SELECT * FROM campanas";
  if ($busqueda) {
    $query .= " WHERE nombre_campana LIKE '%$busqueda%' OR lugar_campana LIKE '%$busqueda%'";
  }

  // Lógica de ordenamiento
  switch ($orden) {
    case "nombre":
      $query .= " ORDER BY nombre_campana ASC";
      break;
    case "fecha":
      $query .= " ORDER BY fecha_campana ASC";
      break;
    case "estado":
      $query .= " ORDER BY fecha_campana ASC"; // la lógica de estado es calculada
      break;
    default:
      $query .= " ORDER BY fecha_campana ASC";
  }

  $result = $conn->query($query);

  echo "<table>
    <tr>
      <th>Nombre de la campaña</th>
      <th>Lugar</th>
      <th>Fecha</th>
      <th>Estado</th>
      <th>Tratamientos aplicados</th>
      <th>Acciones</th>
    </tr>";

  while ($row = $result->fetch_assoc()) {
    list($estadoTexto, $estadoClase) = estadoCampania($row["fecha_campana"]);

    // Solo si está activa se marca visualmente
    $claseFila = $estadoTexto === "Activa" ? "fila-activa" : "";

    $idCampana = $row['idCampanas'];

    // Obtener tratamientos
    $trs = [];
    $procRes = $conn->query("SELECT p.nombre_procedimiento FROM campanas_has_procedimientos chp JOIN procedimientos p ON chp.procedimientos_idprocedimientos = p.idprocedimientos WHERE chp.campanas_idcampanas = $idCampana");
    while ($procRow = $procRes->fetch_assoc()) {
      $trs[] = $procRow["nombre_procedimiento"];
    }
    $procsStr = implode(", ", $trs);

    echo "<tr class='$claseFila'>
      <td>{$row['nombre_campana']}</td>
      <td>{$row['lugar_campana']}</td>
      <td>{$row['fecha_campana']}</td>
      <td>$estadoTexto</td>
      <td>$procsStr</td>
      <td>
        <button onclick='editarCampania($idCampana)'>Editar</button>
        <button onclick='eliminarCampania($idCampana)'>Eliminar</button>
      </td>
    </tr>";
  }

  echo "</table>";
  break;


  default:
    echo "<p>⚠️ Acción no válida.</p>";
}

$conn->close();
?>
