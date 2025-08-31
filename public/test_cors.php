<?php
// CORS test dosyası

// Output buffering başlat
ob_start();

// AGGRESIF CORS AYARLARI
header("Access-Control-Allow-Origin: https://panel.woontegra.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, X-Requested-With");

// OPTIONS preflight istekleri için
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: https://panel.woontegra.com");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, Cache-Control, X-Requested-With");
    
    http_response_code(204);
    ob_end_flush();
    exit;
}

header("Content-Type: application/json; charset=utf-8");

// Test response
echo json_encode([
    'ok' => true,
    'message' => 'CORS test başarılı!',
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers' => getallheaders(),
    'timestamp' => date('Y-m-d H:i:s')
]);

ob_end_flush();
