<?php
declare(strict_types=1);

// CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

header("Content-Type: application/json; charset=utf-8");

spl_autoload_register(function($class){
  $prefix = 'App\\'; $base = __DIR__ . '/../src/';
  if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
  $rel = substr($class, strlen($prefix));
  $file = $base . str_replace('\\','/',$rel) . '.php';
  if (file_exists($file)) require $file;
});

use App\Router;
use App\Controllers\HealthController;
use App\Controllers\ProductController;
use App\Controllers\VariantController;
use App\Controllers\MarketplaceController;
use App\Controllers\ConnectionController;
use App\Controllers\OptionController;
use App\Controllers\OptionValueController;
use App\Controllers\ProductOptionValueController;
use App\Controllers\ProductMarketplaceController;
use App\Controllers\AuthController;
use App\Controllers\IntegrationController;
use App\Controllers\LogController;
use App\Controllers\CategoryMappingController;
use App\Controllers\CsvController;
use App\Controllers\AuthzController;
use App\Controllers\UserController;
use App\Controllers\AuditController;
use App\Controllers\TwoFAController;
use App\Controllers\MailController;
use App\Middleware\AuditMiddleware;

$router = new Router();

// Health
$router->get('/health', [HealthController::class, 'index']);

// Auth
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/forgot', [AuthController::class, 'forgot']);
$router->post('/auth/reset', [AuthController::class, 'reset']);
$router->get('/auth/me', [AuthController::class, 'me']);
$router->post('/auth/2fa/setup', [TwoFAController::class, 'setup']);
$router->post('/auth/2fa/enable', [TwoFAController::class, 'enable']);
$router->post('/auth/2fa/disable', [TwoFAController::class, 'disable']);
$router->get('/auth/2fa/status', [TwoFAController::class, 'status']);

// Mail Test
$router->get('/mail/test', [MailController::class, 'test']);

// Products
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{id}', [ProductController::class, 'show']);
$router->post('/products', [ProductController::class, 'store']);
$router->put('/products/{id}', [ProductController::class, 'update']);
$router->delete('/products/{id}', [ProductController::class, 'destroy']);

// Variants
$router->get('/variants', [VariantController::class, 'index']);
$router->post('/variants', [VariantController::class, 'store']);
$router->get('/variants/{id}', [VariantController::class, 'show']);
$router->put('/variants/{id}', [VariantController::class, 'update']);
$router->delete('/variants/{id}', [VariantController::class, 'destroy']);

// Marketplaces
$router->get('/marketplaces', [MarketplaceController::class, 'index']);
$router->post('/marketplaces', [MarketplaceController::class, 'store']);

// Connections
$router->get('/connections', [ConnectionController::class, 'index']);
$router->post('/connections', [ConnectionController::class, 'store']);
$router->put('/connections/{id}', [ConnectionController::class, 'update']);
$router->delete('/connections/{id}', [ConnectionController::class, 'destroy']);
$router->get('/connections/ping/{id}', [ConnectionController::class, 'ping']);

// Options
$router->get('/options', [OptionController::class, 'index']);
$router->post('/options', [OptionController::class, 'store']);
$router->put('/options/{id}', [OptionController::class, 'update']);
$router->delete('/options/{id}', [OptionController::class, 'destroy']);

// Option Values
$router->get('/option-values', [OptionValueController::class, 'index']);
$router->post('/option-values', [OptionValueController::class, 'store']);
$router->put('/option-values/{id}', [OptionValueController::class, 'update']);
$router->delete('/option-values/{id}', [OptionValueController::class, 'destroy']);

// Product <-> OptionValue
$router->get('/product-option-values', [ProductOptionValueController::class, 'index']);
$router->post('/product-option-values', [ProductOptionValueController::class, 'attach']);
$router->delete('/product-option-values', [ProductOptionValueController::class, 'detach']);

// Product <-> Marketplace Mapping
$router->get('/product-mappings', [ProductMarketplaceController::class, 'index']);
$router->post('/product-mappings', [ProductMarketplaceController::class, 'attach']);
$router->delete('/product-mappings', [ProductMarketplaceController::class, 'detach']);

// Category Mappings
$router->get('/category-mappings', [CategoryMappingController::class, 'index']);
$router->post('/category-mappings', [CategoryMappingController::class, 'store']);
$router->delete('/category-mappings/{id}', [CategoryMappingController::class, 'destroy']);

// Integrations
$router->post('/integrations/trendyol/send-product/{id}', [IntegrationController::class, 'sendProduct']);
$router->post('/integrations/woo/send-product/{id}', [IntegrationController::class, 'sendWooProduct']);
// enqueue
$router->post('/integrations/trendyol/enqueue-products', [IntegrationController::class, 'enqueueTrendyol']);
$router->post('/integrations/woo/enqueue-products', [IntegrationController::class, 'enqueueWoo']);
// process
$router->post('/queue/process', [IntegrationController::class, 'processQueue']);

// CSV Import/Export
$router->get('/csv/category-mappings/export', [CsvController::class, 'exportCategoryMappings']);
$router->post('/csv/category-mappings/import', [CsvController::class, 'importCategoryMappings']);
$router->get('/csv/products/export', [CsvController::class, 'exportProducts']);
$router->post('/csv/products/import', [CsvController::class, 'importProducts']);

// Users
$router->get('/users', [UserController::class, 'index']);

// Authorization
$router->get('/roles', [AuthzController::class, 'roles']);
$router->get('/permissions', [AuthzController::class, 'permissions']);
$router->post('/user-roles/assign', [AuthzController::class, 'assignRole']);
$router->post('/user-roles/revoke', [AuthzController::class, 'revokeRole']);
$router->put('/user-roles/set', [AuthzController::class, 'setRoles']);
$router->get('/user-permissions', [AuthzController::class, 'userPermissions']);

// Logs
$router->get('/logs', [LogController::class, 'index']);

// Audit Logs
$router->get('/audit-logs', [AuditController::class, 'index']);

// User Invite
$router->post('/users/invite', [UserController::class, 'invite']);

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = $_SERVER['REQUEST_URI'] ?? '/';

// Debug: Path'i logla
error_log("DEBUG: Method=$method, Path=$path");
error_log("DEBUG: REQUEST_METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'NOT_SET'));
error_log("DEBUG: HTTP_METHOD=" . ($_SERVER['HTTP_METHOD'] ?? 'NOT_SET'));
error_log("DEBUG: HTTP_X_HTTP_METHOD=" . ($_SERVER['HTTP_X_HTTP_METHOD'] ?? 'NOT_SET'));

$response = $router->dispatch($method, $path);

// Her request'i audit log'a yaz
\App\Middleware\AuditMiddleware::log(
  $_SESSION['user_id'] ?? null,
  "call",
  $method,
  $path,
  $method === "GET" ? $_GET : ($_POST ?: file_get_contents("php://input"))
);
