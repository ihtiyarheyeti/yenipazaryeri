<?php
// Hata ayıklama dosyası

// Hataları göster
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

try {
    // PHP bilgilerini kontrol et
    $phpInfo = [
        'php_version' => PHP_VERSION,
        'extensions' => get_loaded_extensions(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'error_reporting' => error_reporting(),
        'display_errors' => ini_get('display_errors')
    ];
    
    echo json_encode([
        'ok' => true,
        'message' => 'Debug bilgileri',
        'php_info' => $phpInfo,
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
