<?php
declare(strict_types=1);

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

header("Content-Type: application/json; charset=utf-8");

// Autoload
spl_autoload_register(function($class){
  $prefix = 'App\\'; 
  $base = __DIR__ . '/../src/';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));
  $file = $base . str_replace('\\','/',$rel) . '.php';
  if (file_exists($file)) require $file;
});

use App\Router;
use App\Controllers\HealthController;
use App\Controllers\AuthController;

// Router oluştur
$router = new Router();

// Test route'ları
$router->get('/health', [HealthController::class, 'index']);
$router->post('/auth/login', [AuthController::class, 'login']);
$router->get('/auth/me', [AuthController::class, 'me']);

// Debug
error_log("Router created successfully");
error_log("Routes registered: " . json_encode($router->getRoutes('POST')));

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = $_SERVER['REQUEST_URI'] ?? '/';

error_log("DEBUG: Method=$method, Path=$path");

$response = $router->dispatch($method, $path);
?>
