<?php
session_start();
header('Content-Type: application/json');

// Puedes verificar cualquier dato que guardes al iniciar sesión. Ejemplo:
if (isset($_SESSION['usuario_id'])) {
  echo json_encode(['autenticado' => true]);
} else {
  echo json_encode(['autenticado' => false]);
}