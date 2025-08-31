<?php
// Basit test dosyası

// CORS header'ları
header("Access-Control-Allow-Origin: https://panel.woontegra.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// OPTIONS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// JSON response
header("Content-Type: application/json; charset=utf-8");

echo json_encode([
    'ok' => true,
    'message' => 'Basit test başarılı!',
    'method' => $_SERVER['REQUEST_METHOD'],
    'timestamp' => date('Y-m-d H:i:s')
]);
