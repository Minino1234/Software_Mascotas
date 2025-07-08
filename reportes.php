<?php
ob_start();
?>

<?php
include "bd.php";

$sql = "SELECT * FROM `vsta_mascotas`;";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Carnet de Información Animal</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        
        .card-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: center;
        }
        
        .pet-card {
            width: 800px;
            height: 400px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            display: flex;
            position: relative;
        }
        
        .pet-info {
            padding: 25px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .pet-image {
            width: 300px;
            height: 100%;
            background-size: cover;
            background-position: center;
            border-left: 1px solid #eee;
        }
        
        .card-title {
            color: #333;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        .info-row {
            margin-bottom: 15px;
            display: flex;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            color: #555;
        }
        
        .info-value {
            flex: 1;
            color: #333;
        }
        
        .footer-bar {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background-color: #3498db;
            color: white;
            padding: 10px;
            text-align: center;
            font-size: 14px;
        }
        
        .footer-text {
            margin: 0;
            line-height: 1.4;
        }
    </style>
</head>

<body>
    <div class="card-container">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $fotoPath = !empty($row['foto']) ? $row['foto'] : 'ruta/a/imagen/por/defecto.jpg';
                
                echo '<div class="pet-card">';
                echo '<div class="pet-info">';
                echo '<div class="card-title">Carnet de Información Animal</div>';
                
                echo '<div class="info-row">';
                echo '<span class="info-label">Nombre:</span>';
                echo '<span class="info-value">' . htmlspecialchars($row['nombreMascota']) . '</span>';
                echo '</div>';
                
                echo '<div class="info-row">';
                echo '<span class="info-label">Sexo:</span>';
                echo '<span class="info-value">' . htmlspecialchars($row['nombre_genero_mascotas']) . '</span>';
                echo '</div>';
                
                echo '<div class="info-row">';
                echo '<span class="info-label">Raza:</span>';
                echo '<span class="info-value">' . htmlspecialchars($row['nombre_especie']) . '</span>';
                echo '</div>';
                
                echo '<div class="info-row">';
                echo '<span class="info-label">Comportamiento:</span>';
                echo '<span class="info-value">' . htmlspecialchars($row['tipo_comportamiento']) . '</span>';
                echo '</div>';
                
                echo '<div class="info-row">';
                echo '<span class="info-label">Propietario:</span>';
                echo '<span class="info-value">' . htmlspecialchars($row['Propietario']) . '</span>';
                echo '</div>';
                
                echo '<div class="info-row">';
                echo '<span class="info-label">Dirección:</span>';
                echo '<span class="info-value">' . htmlspecialchars($row['direccion']) . '</span>';
                echo '</div>';
                
                echo '</div>'; // cierre pet-info
                
                echo '<div class="pet-image" style="background-image: url(\'' . htmlspecialchars($fotoPath) . '\')"></div>';
                
                echo '<div class="footer-bar">';
                echo '<p class="footer-text">Municipalidad Provincial de Huaraz<br>';
                echo 'Gerencia de Servicios Públicos<br>';
                echo 'Sub Gerencia de Sanidad y Salubridad Pública</p>';
                echo '</div>';
                
                echo '</div>'; // cierre pet-card
            }
        } else {
            echo '<p>No hay mascotas registradas.</p>';
        }
        ?>
    </div>
</body>

</html>

<?php
$conn->close();
?>

<?php
$html = ob_get_clean();

require_once 'C:\xampp\htdocs\Software_Mascotas\dompdf\autoload.inc.php';
use Dompdf\Dompdf;
$dompdf = new Dompdf();

$options = $dompdf->getOptions();
$options->set(array('isRemoteEnabled' => true));
$dompdf->setOptions($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A3', 'landscape'); // Cambiado a horizontal
$dompdf->render();
$dompdf->stream("carnet_mascotas.pdf", array("Attachment" => false));
?>