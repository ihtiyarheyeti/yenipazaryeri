<?php
// Route'ları test et
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

$router = new Router();

// Test route'ları ekle
$router->get('/test-get', function() { return ['ok' => true, 'method' => 'GET']; });
$router->post('/test-post', function() { return ['ok' => true, 'method' => 'POST']; });

// Route'ları kontrol et
echo "<h2>Router Test</h2>";
echo "<pre>";
echo "GET Routes: " . json_encode($router->getRoutes('GET'), JSON_PRETTY_PRINT) . "\n";
echo "POST Routes: " . json_encode($router->getRoutes('POST'), JSON_PRETTY_PRINT) . "\n";
echo "</pre>";

// Test dispatch
echo "<h2>Test Dispatch</h2>";
$response = $router->dispatch('GET', '/test-get');
echo "GET Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";

$response = $router->dispatch('POST', '/test-post');
echo "POST Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
?>
