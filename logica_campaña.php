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
  case "crear":
    $lugar = $_POST["lugar"] ?? "";
    $fecha = $_POST["fecha"] ?? "";
    if ($lugar && $fecha) {
      $stmt = $conn->prepare("INSERT INTO campañas (`lugar_campaña`, `fecha_campaña`) VALUES (?, ?)");
      $stmt->bind_param("ss", $lugar, $fecha);
      $stmt->execute();
      echo "<p>✅ Campaña creada correctamente.</p>";
    }
    break;

  case "editar":
    $id = intval($_POST["id"] ?? 0);
    $lugar = $_POST["lugar"] ?? "";
    $fecha = $_POST["fecha"] ?? "";
    if ($id && $lugar && $fecha) {
      $stmt = $conn->prepare("UPDATE campañas SET `lugar_campaña` = ?, `fecha_campaña` = ? WHERE idCampañas = ?");
      $stmt->bind_param("ssi", $lugar, $fecha, $id);
      $stmt->execute();
      echo "<p>✏️ Campaña actualizada.</p>";
    }
    break;

  case "eliminar":
    $id = intval($_POST["id"] ?? 0);
    if ($id) {
      $stmt = $conn->prepare("DELETE FROM campañas WHERE idCampañas = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      echo "<p>🗑️ Campaña eliminada.</p>";
    }
    break;

  case "listar":
    $result = $conn->query("SELECT * FROM campañas ORDER BY idCampañas");
    echo "<h2>📋 Lista de campañas</h2>";
    if ($result->num_rows > 0) {
      echo "<table><tr><th>ID</th><th>Lugar</th><th>Fecha</th><th>Estado</th></tr>";
      while ($row = $result->fetch_assoc()) {
        list($estado, $clase) = estadoCampania($row["fecha_campaña"]);
        echo "<tr class='$clase'><td>{$row['idCampañas']}</td><td>{$row['lugar_campaña']}</td><td>{$row['fecha_campaña']}</td><td>$estado</td></tr>";
      }
      echo "</table>";
    } else {
      echo "<p>No hay campañas registradas.</p>";
    }
    break;

  case "listar_combo":
    $result = $conn->query("SELECT idCampañas AS id, lugar_campaña AS lugar FROM campañas");
    $data = [];
    while ($row = $result->fetch_assoc()) {
      $data[] = $row;
    }
    echo json_encode($data);
    break;

  case "obtener":
    $id = intval($_GET["id"] ?? 0);
    $stmt = $conn->prepare("SELECT idCampañas AS id, lugar_campaña AS lugar, fecha_campaña AS fecha FROM campañas WHERE idCampañas = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    echo json_encode($res);
    break;

  default:
    echo "<p>⚠️ Acción no válida.</p>";
}

$conn->close();
?>
