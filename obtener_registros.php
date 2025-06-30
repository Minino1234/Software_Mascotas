<?php
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "root", "", "bdmascotas");
if ($mysqli->connect_error) {
    echo json_encode([]);
    exit;
}

$sql = "
    SELECT 
        p.idPersona,
        p.nombres,
        p.apell1,
        p.apell2,
        p.dni,
        p.telefono,
        g.nom_genero_personas AS genero,
        IF(v.idveterinarios IS NOT NULL, 'veterinario', 'propietario') AS rol
    FROM personas p
    LEFT JOIN veterinarios v ON p.idPersona = v.personas_id
    LEFT JOIN propietarios pr ON p.idPersona = pr.personas_id
    LEFT JOIN genero_personas g ON p.genero_personas_id = g.idgenero_personas
    WHERE v.idveterinarios IS NOT NULL OR pr.idPropietarios IS NOT NULL
    ORDER BY p.idPersona DESC
";

$res = $mysqli->query($sql);
$data = [];

while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode($data);
