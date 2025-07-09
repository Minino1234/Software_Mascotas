<?php
// Conexión a la base de datos
include "bd.php";

// Al inicio del script carnet_mascota.php, después de include "bd.php";
$idMascota = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Modificar la consulta SQL para filtrar por ID:
$sql = "SELECT * FROM `vsta_mascotas` WHERE idMascota = $idMascota";
$result = $conn->query($sql);


// Incluimos la librería DOMPDF
require_once 'dompdf/autoload.inc.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// Configuración de DOMPDF
$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->setPaper('A7', 'landscape');

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Carnet de información animal</title>
    <style>
        @page {
            margin: 0;
            padding: 0;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
        }
        .carnet-container {
            width: 100%;
            height: 100%;
            // border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 0 5px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .header {
            background-color: #f8f9fa;
            padding: 4px;
            text-align: center;
            border-bottom: 1px solid #ddd;
            flex-shrink: 0;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 12px;
            font-weight: bold;
        }
        .content-wrapper {
            width: 100%;
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }
        .main-content {
            flex: 1;
            display: flex;
            padding: 5px;
            position: relative;
            overflow: hidden;
        }
        .info {
            flex: 1;
            padding-right: 30mm; /* Espacio reservado para la foto */
            font-size: 9px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            min-height: 0;
        }
        .info p {
            margin: 4px 0;
        }
        .info strong {
            color: #2c3e50;
        }
        .photo-container {
            position: absolute;
            right: 5px;
            top: 5px;
            width: 25mm;
            height: calc(100% - 10px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .photo {
            width: 25mm;
            height: 25mm;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 5px;
        }
        .photo img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .footer {
            background-color: #3498db;
            color: white;
            padding: 4px;
            text-align: center;
            font-size: 8px;
            height: 10mm;
            display: flex;
            flex-direction: column;
            justify-content: center;
            border-top: 1px solid #ddd;
            flex-shrink: 0;
            flex: 1;
        }
        .footer div {
            line-height: 1.2;
        }
    </style>
</head>
<body>';

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $html .= '
        <div class="carnet-container">
            <div class="header">
                <h1>Carnet de información animal</h1>
            </div>
            <div class="content-wrapper">
                <div class="main-content">
                    <div class="info">
                        <p><strong>Nombre:</strong> '.htmlspecialchars($row["nombreMascota"]).'</p>
                        <p><strong>Sexo:</strong> '.htmlspecialchars($row["nombre_genero_mascotas"]).'</p>
                        <p><strong>Especie:</strong> '.htmlspecialchars($row["nombre_especie"]).'</p>
                        <p><strong>Comportamiento:</strong> '.htmlspecialchars($row["tipo_comportamiento"]).'</p>
                        <p><strong>Propietario:</strong> '.htmlspecialchars($row["Propietario"]).'</p>
                        <p><strong>Dirección:</strong> '.htmlspecialchars($row["direccion"]).'</p>
                        <p><strong>fotodebug:</strong> '.htmlspecialchars($row["foto"]).'</p>
                    </div>
                    <div class="photo-container">
                        <div class="photo">
                            <img src="imagenes_mascotas\fotofuxion.png">
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer">
                <div>Municipalidad Provincial de Huaraz</div>
                <div>Gerencia de Servicios Públicos</div>
                <div>Sub Gerencia de Sanidad y Salubridad Pública</div>
            </div>
        </div>';
    }
} else {
    $html .= '<div class="carnet-container"><p>No se encontraron mascotas registradas.</p></div>';
}

$html .= '
</body>
</html>';

$dompdf->loadHtml($html);
$dompdf->render();
$dompdf->stream("carnet_mascota.pdf", [
    "Attachment" => false,
    "compress" => true
]);

$conn->close();
?>