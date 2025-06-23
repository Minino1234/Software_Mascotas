<?php
session_start();
include "bd.php";

if (!isset($_SESSION['intentos'])) {
    $_SESSION['intentos'] = 3;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $clave = $_POST["clave"];

    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE NombreUsuario = ? AND Contraseña = ?");
    if (!$stmt) {
        echo "Error en la consulta: " . $conn->error;
        exit;
    }

   $stmt->bind_param("ss", $usuario, $clave);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $_SESSION['intentos'] = 3;
        echo "success"; // El JS redireccionará
    } else {
        $_SESSION['intentos']--;

        if ($_SESSION['intentos'] > 0) {
            echo "❌ Usuario o contraseña incorrecta. Te quedan " . $_SESSION['intentos'] . " intento(s).";
        } else {
            echo "🚫 Has agotado los intentos. Intenta más tarde.";
        }
    }
}
