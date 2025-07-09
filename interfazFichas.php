<?php
include "bd.php";

$sql = "SELECT * FROM vsta_mascotas";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Mascotas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4 text-center">Listado de Mascotas</h2>
    <div class="table-responsive">
        <table id="tablaMascotas" class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th hidden>Color</th>
                    <th hidden >Edad</th>
                    <th>Especie</th>
                    <th hidden>Género</th>
                    <th hidden>Comportamiento</th>
                    <th hidden>Foto</th>
                    <th>Propietario</th>
                    <th>DNI</th>
                    <th hidden>Dirección</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nombreMascota']) ?></td>
                            <td hidden><?= htmlspecialchars($row['color']) ?></td>
                            <td hidden><?= htmlspecialchars($row['edad_mascotas']) ?></td>
                            <td><?= htmlspecialchars($row['nombre_especie']) ?></td>
                            <td hidden><?= htmlspecialchars($row['nombre_genero_mascotas']) ?></td>
                            <td hidden><?= htmlspecialchars($row['tipo_comportamiento']) ?></td>
                            <td hidden>
                                <?php if (!empty($row['foto'])): ?>
                                    <img src="ruta_a_imagenes/<?= htmlspecialchars($row['foto']) ?>" alt="foto" width="50">
                                <?php else: ?>
                                    Sin foto
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($row['Propietario']) ?></td>
                            <td><?= htmlspecialchars($row['dni']) ?></td>
                            <td hidden><?= htmlspecialchars($row['direccion']) ?></td>
                            <td>
                                <a href="carnet_mascota.php?id=<?= $row['idMascota'] ?>" class="btn btn-sm btn-primary" target = "_blank">
                                    Ver ficha
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" class="text-center">No se encontraron resultados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
    $(document).ready(function () {
        $('#tablaMascotas').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    });
</script>
</body>
</html>