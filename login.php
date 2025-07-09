<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "bd.php";

if (!isset($_SESSION['intentos'])) {
    $_SESSION['intentos'] = 3;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = $_POST["usuario"];
    $clave = $_POST["clave"];

    // Usando hash seguro (opcional pero recomendado)
    $stmt = $conn->prepare("SELECT id, NombreUsuario, Contraseña FROM usuarios WHERE NombreUsuario = ?");
    if (!$stmt) {
        echo "Error en la consulta: " . $conn->error;
        exit;
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();

        if ($clave === $row['Contraseña']) { // ⚠️ Aquí mejor usar password_verify si usas hash
            $_SESSION['intentos'] = 3;

            // 🚩 Aquí se guarda la bandera de sesión
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['nombre_usuario'] = $row['NombreUsuario'];

            echo "success"; // El JS redireccionará
        } else {
            $_SESSION['intentos']--;
            if ($_SESSION['intentos'] > 0) {
                echo "❌ Contraseña incorrecta. Te quedan " . $_SESSION['intentos'] . " intento(s).";
            } else {
                echo "🚫 Has agotado los intentos. Intenta más tarde.";
            }
        }
    } else {
        $_SESSION['intentos']--;
        if ($_SESSION['intentos'] > 0) {
            echo "❌ Usuario no encontrado. Te quedan " . $_SESSION['intentos'] . " intento(s).";
        } else {
            echo "🚫 Has agotado los intentos. Intenta más tarde.";
        }
    }
}

