<?php
include "bd.php";

$accion = $_REQUEST['accion'] ?? '';

echo "<style>
  body { font-family: Arial, sans-serif; margin: 20px; background: #eaf6ff; color: #003366; }
  h1, h2 { color: #003366; }
  table { border-collapse: collapse; width: 100%; max-width: 600px; margin-top: 20px; }
  th, td { border: 1px solid #aaccee; padding: 8px; text-align: left; }
  th { background-color: #007acc; color: white; }
  tr:hover { background-color: #d6eaff; }
  a { color: #007acc; text-decoration: none; }
  a:hover { text-decoration: underline; }
  .mensaje { margin-top: 20px; font-weight: bold; }
</style>";

echo "<h1>Gestión de Campañas - Resultado</h1>";

switch ($accion) {
    case 'crear':
        $lugar = $_POST['lugar'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        if ($lugar && $fecha) {
            $stmt = $conn->prepare("INSERT INTO campanas (`lugar_campaña`, `fecha_campaña`) VALUES (?, ?)");
            if ($stmt === false) {
                echo "<p class='mensaje'>❌ Error en prepare: " . htmlspecialchars($conn->error) . "</p>";
                break;
            }
            $stmt->bind_param("ss", $lugar, $fecha);
            if ($stmt->execute()) {
                echo "<p class='mensaje'>✅ Campaña creada correctamente.</p>";
            } else {
                echo "<p class='mensaje'>❌ Error al crear campaña: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='mensaje'>❌ Datos incompletos.</p>";
        }
        break;

    case 'editar':
        $id = intval($_POST['id'] ?? 0);
        $lugar = $_POST['lugar'] ?? '';
        $fecha = $_POST['fecha'] ?? '';
        if ($id > 0 && $lugar && $fecha) {
            $stmt = $conn->prepare("UPDATE campanas SET `lugar_campaña` = ?, `fecha_campaña` = ? WHERE idCampanas = ?");
            if ($stmt === false) {
                echo "<p class='mensaje'>❌ Error en prepare: " . htmlspecialchars($conn->error) . "</p>";
                break;
            }
            $stmt->bind_param("ssi", $lugar, $fecha, $id);
            if ($stmt->execute()) {
                echo "<p class='mensaje'>✏️ Campaña actualizada correctamente.</p>";
            } else {
                echo "<p class='mensaje'>❌ Error al actualizar campaña: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='mensaje'>❌ Datos incompletos para editar.</p>";
        }
        break;

    case 'eliminar':
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM campanas WHERE idCampanas = ?");
            if ($stmt === false) {
                echo "<p class='mensaje'>❌ Error en prepare: " . htmlspecialchars($conn->error) . "</p>";
                break;
            }
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                echo "<p class='mensaje'>🗑️ Campaña eliminada correctamente.</p>";
            } else {
                echo "<p class='mensaje'>❌ Error al eliminar campaña: " . htmlspecialchars($stmt->error) . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p class='mensaje'>❌ ID no válido para eliminar.</p>";
        }
        break;

    case 'listar':
        $result = $conn->query("SELECT * FROM campanas ORDER BY idCampanas ASC");
        if ($result && $result->num_rows > 0) {
            echo "<h2>📋 Lista de campañas</h2>";
            echo "<table><tr><th>ID</th><th>Lugar</th><th>Fecha</th></tr>";
            while ($row = $result->fetch_assoc()) {
                $id = htmlspecialchars($row['idCampanas']);
                $lugar = htmlspecialchars($row['lugar_campaña']);
                $fecha = htmlspecialchars($row['fecha_campaña']);
                echo "<tr><td>$id</td><td>$lugar</td><td>$fecha</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No hay campañas registradas.</p>";
        }
        break;

    default:
        echo "<p>⚠️ Acción no reconocida o no especificada.</p>";
        break;
}

echo '<p><a href="interfaz.html">⬅ Volver a la interfaz</a></p>';

$conn->close();
?>
