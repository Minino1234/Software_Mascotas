<?php
$conn = new mysqli("localhost", "root", "", "bdmascotas");
$accion = $_REQUEST['accion'] ?? '';

// 1) Buscar propietario por DNI
if ($accion === "buscar_propietario") {
    $dni = $_GET["dni"];
    $stmt = $conn->prepare(
        "SELECT pe.apell1, pe.apell2, pe.nombres, pe.dni, b.nombres_barrios
         FROM personas pe
         JOIN propietarios p ON pe.idPersona = p.personas_id
         JOIN barrios b ON b.idbarrios = p.barrios_id
         WHERE pe.dni = ?"
    );
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    if ($stmt->error) {
  error_log("⚠️ Error SQL: " . $stmt->error);
} else {
  error_log("✅ Consulta ejecutada correctamente");
}
    $res = $stmt->get_result();
    if ($fila = $res->fetch_assoc()) {
        echo json_encode([
            "encontrado" => true,
            "apellidos"  => $fila["apell1"] . " " . $fila["apell2"],
            "nombres"    => $fila["nombres"],
            "dni"        => $fila["dni"],
            "barrio"     => $fila["nombres_barrios"]
        ]);
    } else {
        echo json_encode(["encontrado" => false]);
    }
    exit;
}

// 2) Listar select options
if ($accion === "listar_comportamientos") {
    $res = $conn->query(
        "SELECT idcomportamientos AS id, tipo_comportamiento AS nombre FROM comportamientos"
    );
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}
if ($accion === "listar_especies") {
    $res = $conn->query(
        "SELECT idEspecie AS id, nombre_especie AS nombre FROM especie"
    );
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}
if ($accion === "listar_generos") {
    $res = $conn->query(
        "SELECT idGenero AS id, nombre_genero_mascotas AS nombre FROM genero_mascotas"
    );
    echo json_encode($res->fetch_all(MYSQLI_ASSOC));
    exit;
}

// 3) Crear o editar mascota
if ($accion === "crear" || $accion === "editar") {
    $id             = intval($_POST["idMascota"] ?? 0);
    $nombre         = $_POST['nombreMascota'];
    $especie        = intval($_POST['idEspecie']);
    $genero         = intval($_POST['idGenero']);
    $edad           = intval($_POST['edad_mascotas']);
    $color          = $_POST['color'];
    $comportamiento = intval($_POST['idcomportamientos']);
    $dni            = $_POST['dni_propietario'];
    $obs            = $_POST['observaciones'] ?? "";
    // 📸 Procesar imagen
    $foto = "";
    error_log("🧪 Valores enviados: id=$id, nombre=$nombre, especie=$especie, genero=$genero, edad=$edad, color=$color, comportamiento=$comportamiento, foto=$foto");
    if (!empty($_FILES["fotoMascota"]["name"])) {
        $ruta = __DIR__ . "/imagenes_mascotas/";
        if (!is_dir($ruta)) mkdir($ruta, 0755, true);

        if ($accion === "editar") {
            $resFoto      = $conn->query("SELECT foto FROM mascotas WHERE idMascota = $id");
            $fotoAnterior = $resFoto->fetch_assoc()["foto"] ?? "";
            $pathAnt      = __DIR__ . "/" . $fotoAnterior;
            if ($fotoAnterior && file_exists($pathAnt)) {
                unlink($pathAnt);
            }
        }

        $nombreArchivo = uniqid() . "_" . basename($_FILES["fotoMascota"]["name"]);
        $destino       = $ruta . $nombreArchivo;
        if (move_uploaded_file($_FILES["fotoMascota"]["tmp_name"], $destino)) {
            $foto = "imagenes_mascotas/" . $nombreArchivo;
        } else {
            error_log("⚠️ No se pudo mover la imagen a $destino");
        }
    }

    // 🔍 Obtener id del propietario
    $stmt = $conn->prepare("SELECT p.idPropietarios FROM propietarios p JOIN personas pe ON pe.idPersona = p.personas_id WHERE pe.dni = ?");
    $stmt->bind_param("s", $dni);
    $stmt->execute();
    if ($stmt->error) {
        error_log("⚠️ Error al buscar propietario: " . $stmt->error);
    } else {
        error_log("✅ Propietario encontrado");
    }
    $res = $stmt->get_result();
    $idProp = $res->fetch_assoc()["idPropietarios"] ?? null;
    if (!$idProp) {
        echo "❌ Propietario no encontrado.";
        exit;
    }

    error_log("🧪 Datos recibidos → id=$id, nombre=$nombre, especie=$especie, genero=$genero, edad=$edad, color=$color, comportamiento=$comportamiento, foto=$foto");

    if ($accion === "crear") {
        error_log("🆕 Creando nueva mascota...");
        $stmt = $conn->prepare("INSERT INTO mascotas (nombreMascota, idEspecie, idGenero, edad_mascotas, color, idcomportamientos, foto) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("siiisss", $nombre, $especie, $genero, $edad, $color, $comportamiento, $foto);
        $stmt->execute();
        if ($stmt->error) {
            error_log("⚠️ Error al insertar mascota: " . $stmt->error);
        } else {
            error_log("✅ Mascota registrada correctamente");
        }
        $idMascota = $conn->insert_id;
        $conn->query("INSERT INTO propietarios_has_mascotas (mascotas_idMascota, propietarios_idPropietarios) VALUES ($idMascota, $idProp)");
    } else {
        error_log("✏️ Editando mascota ID: $id");
        if ($foto !== "") {
            $stmt = $conn->prepare("UPDATE mascotas SET nombreMascota=?, idEspecie=?, idGenero=?, edad_mascotas=?, color=?, idcomportamientos=?, foto=? WHERE idMascota=?");
            $stmt->bind_param("siiisssi", $nombre, $especie, $genero, $edad, $color, $comportamiento, $foto, $id);
        } else {
            $stmt = $conn->prepare("UPDATE mascotas SET nombreMascota=?, idEspecie=?, idGenero=?, edad_mascotas=?, color=?, idcomportamientos=? WHERE idMascota=?");
            $stmt->bind_param("siiissi", $nombre, $especie, $genero, $edad, $color, $comportamiento, $id);
        }
        $stmt->execute();
        if ($stmt->error) {
            error_log("⚠️ Error al actualizar mascota: " . $stmt->error);
        } else {
            error_log("✅ Mascota actualizada correctamente");
        }

        $conn->query("DELETE FROM propietarios_has_mascotas WHERE mascotas_idMascota = $id");
        $conn->query("INSERT INTO propietarios_has_mascotas (mascotas_idMascota, propietarios_idPropietarios) VALUES ($id, $idProp)");
    }

    exit;
}
// 4) Obtener datos de una mascota (para editar)
if ($accion === "obtener") {
    $id  = intval($_GET["id"]);
    $res = $conn->query("SELECT * FROM mascotas WHERE idMascota = $id");
    $m   = $res->fetch_assoc();

    // Traer DNI del propietario
    $res2 = $conn->query(
        "SELECT pe.dni
         FROM propietarios_has_mascotas pm
         JOIN propietarios p ON p.idPropietarios = pm.propietarios_idPropietarios
         JOIN personas pe ON pe.idPersona = p.personas_id
         WHERE pm.mascotas_idMascota = $id"
    );
    $m["dni"] = $res2->fetch_assoc()["dni"] ?? "";

    // 🐾 Verificar valor de color antes de enviar
    error_log("🐾 Color enviado para edición: " . $m["color"]);

    echo json_encode($m);
    exit;
}


// 5) Eliminar mascota + foto
if ($accion === "eliminar") {
    $id   = intval($_POST["id"]);
    $res  = $conn->query("SELECT foto FROM mascotas WHERE idMascota = $id");
    $foto = $res->fetch_assoc()["foto"] ?? "";
    if ($foto) {
        $path = __DIR__ . "/" . $foto;
        if (file_exists($path)) unlink($path);
    }
    $conn->query("DELETE FROM propietarios_has_mascotas WHERE mascotas_idMascota = $id");
    $conn->query("DELETE FROM mascotas WHERE idMascota = $id");
    exit;
}

// 6) Listar últimos 10 registros + búsqueda en toda la tabla
if ($accion === "listar") {
    $busqueda    = $_GET["busqueda"] ?? "";
    $orden       = $_GET["orden"]    ?? "nombreMascota";
    $ordenCampo  = ($orden === "propietario")
                   ? "pe.apell1"
                   : "m.nombreMascota";

    $sql = 
      "SELECT m.idMascota,
              m.nombreMascota,
              m.edad_mascotas,
              m.color,
              e.nombre_especie       AS especie,
              g.nombre_genero_mascotas AS genero,
              c.tipo_comportamiento  AS comportamiento,
              CONCAT(pe.apell1,' ',pe.apell2,' ',pe.nombres) AS propietario
       FROM mascotas m
       JOIN especie e        ON e.idEspecie = m.idEspecie
       JOIN genero_mascotas g ON g.idGenero = m.idGenero
       JOIN comportamientos c ON c.idcomportamientos = m.idcomportamientos
       JOIN propietarios_has_mascotas pm
         ON pm.mascotas_idMascota = m.idMascota
       JOIN propietarios p   ON p.idPropietarios = pm.propietarios_idPropietarios
       JOIN personas pe      ON pe.idPersona = p.personas_id
       WHERE pe.dni           LIKE CONCAT('%', ?, '%')
          OR pe.nombres       LIKE CONCAT('%', ?, '%')
          OR pe.apell1        LIKE CONCAT('%', ?, '%')
          OR pe.apell2        LIKE CONCAT('%', ?, '%')
          OR m.nombreMascota  LIKE CONCAT('%', ?, '%')
          OR m.color          LIKE CONCAT('%', ?, '%')
       ORDER BY $ordenCampo ASC
       LIMIT 10";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
      "ssssss",
      $busqueda, $busqueda, $busqueda,
      $busqueda, $busqueda, $busqueda
    );
    $stmt->execute();
    if ($stmt->error) {
  error_log("⚠️ Error SQL: " . $stmt->error);
} else {
  error_log("✅ Consulta ejecutada correctamente");
}
    $res = $stmt->get_result();

    echo "<table border='1' width='100%'>
            <thead>
              <tr>
                <th>Nombre</th>
                <th>Especie</th>
                <th>Género</th>
                <th>Edad</th>
                <th>Color</th>
                <th>Comportamiento</th>
                <th>Propietario</th>
                <th>Acciones</th>
              </tr>
            </thead>
            <tbody>";
    while ($row = $res->fetch_assoc()) {
        $id            = $row["idMascota"];
        $n             = htmlspecialchars($row["nombreMascota"]);
        $e             = htmlspecialchars($row["especie"]);
        $g_            = htmlspecialchars($row["genero"]);
        $ed            = htmlspecialchars($row["edad_mascotas"]);
        $col           = htmlspecialchars($row["color"]);
        $comp          = htmlspecialchars($row["comportamiento"]);
        $propietario   = htmlspecialchars($row["propietario"]);

        echo "<tr>
                <td>$n</td>
                <td>$e</td>
                <td>$g_</td>
                <td>$ed</td>
                <td>$col</td>
                <td>$comp</td>
                <td>$propietario</td>
                <td>
                  <button class='editar-btn'   onclick='editarMascota($id)'>✏️</button>
                  <button class='eliminar-btn' onclick='eliminarMascota($id, \"$n\")'>🗑️</button>
                </td>
              </tr>";
    }
    echo "</tbody></table>";
    exit;
}

$conn->close();
?>
