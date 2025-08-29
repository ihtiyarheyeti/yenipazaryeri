<?php
// Auth route'larını test et
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload
spl_autoload_register(function($class){
  $prefix = 'App\\'; 
  $base = __DIR__ . '/../src/';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));
  $file = $base . str_replace('\\','/',$rel) . '.php';
  if (file_exists($file)) {
    require $file;
    error_log("Loaded: " . $file);
  } else {
    error_log("File not found: " . $file);
  }
});

use App\Router;
use App\Controllers\AuthController;

$router = new Router();

// Auth route'larını ekle
$router->post('/auth/login', [AuthController::class, 'login']);

// Route'ları kontrol et
echo "<h2>Auth Router Test</h2>";
echo "<pre>";
echo "POST Routes: " . json_encode($router->getRoutes('POST'), JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Test dispatch
echo "<h2>Test Auth Dispatch</h2>";
$response = $router->dispatch('POST', '/auth/login');
echo "POST Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
?>
