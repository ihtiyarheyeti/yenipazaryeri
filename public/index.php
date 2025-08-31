<?php
declare(strict_types=1);

// Output buffering başlat
ob_start();

// Debug kapalı (React JSON parse hatalarını önlemek için)
error_reporting(0);
ini_set('display_errors', '0');

// WooCommerce import için timeout artır
set_time_limit(300);

// ✅ CORS ayarları - en üstte (her durumda gönderilecek)
header("Access-Control-Allow-Origin: https://panel.woontegra.com");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Eğer istek OPTIONS ise 204 döndür
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    ob_end_flush();
    exit;
}

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

// 🔹 Local config override
if (file_exists(__DIR__ . '/../config.local.php')) {
    require_once __DIR__ . '/../config.local.php';
}

// Tenant context
\App\Context::$tenantId = \App\Multitenancy::currentTenantId();

use App\Router;
use App\Controllers\HealthController;
use App\Controllers\DashboardController;
use App\Controllers\TenantController;
use App\Controllers\UploadTenantLogoController;
use App\Controllers\ImageSyncController;
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
use App\Controllers\ProductCsvController;
use App\Controllers\AuthzController;
use App\Controllers\UserController;
use App\Controllers\AuditController;
use App\Controllers\TwoFAController;
use App\Controllers\MailController;
use App\Controllers\QueueController;
use App\Controllers\UploadController;
use App\Controllers\UsersController;
use App\Controllers\RolesController;
use App\Controllers\InvitesController;
use App\Controllers\PasswordResetController;
use App\Controllers\PermissionsController;
use App\Controllers\AuditLogsController;
use App\Middleware\AuditMiddleware;
use App\Controllers\BatchController;
use App\Controllers\NotificationController;
use App\Controllers\CatalogController;
use App\Controllers\WooWebhookController;
use App\Controllers\OrdersController;
use App\Controllers\ReturnsController;
use App\Controllers\CancellationsController;
use App\Controllers\ShipmentController;
use App\Controllers\InvoiceController;
use App\Controllers\ReconcileController;
use App\Controllers\PolicyController;
use App\Controllers\ConnectionsController;
use App\Controllers\DevController;
use App\Controllers\ProductSyncController;
use App\Controllers\ProductImportController;
use App\Controllers\StdController;
use App\Controllers\MediaController;
use App\Controllers\WebhookController;
use App\Controllers\OrderImportController;
use App\Controllers\OrderSyncController;
use App\Controllers\MappingController;
use App\Controllers\WooCommerceController;   // ✅ Woo controller eklendi

$router = new Router();

// ---- ROUTES ----
// Dummy routes
$router->get('/notifications', fn() => ['ok'=>true,'notifications'=>[]]);
$router->get('/auth/permissions', fn() => ['ok'=>true,'permissions'=>[]]);

// Health
$router->get('/health', [HealthController::class, 'liveness']);
$router->get('/ready', [HealthController::class, 'readiness']);
$router->get('/metrics', [HealthController::class, 'metrics']);

// Dashboard
$router->get('/dashboard/metrics', [DashboardController::class, 'metrics']);
$router->get('/dashboard/alerts', [DashboardController::class, 'alerts']);

// Tenant
$router->get('/tenant/branding', [TenantController::class, 'brandingGet']);
$router->post('/tenant/branding', [TenantController::class, 'brandingSet']);
$router->post('/upload/tenant-logo', [UploadTenantLogoController::class, 'upload']);

// Image sync
$router->post('/integrations/woo/images/(\\d+)', [ImageSyncController::class, 'syncWoo']);
$router->post('/integrations/trendyol/images/(\\d+)', [ImageSyncController::class, 'syncTrendyol']);

// Products
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{id}', [ProductController::class, 'show']);
$router->post('/products', [ProductController::class, 'store']);
$router->put('/products/{id}', [ProductController::class, 'update']);
$router->delete('/products/{id}', [ProductController::class, 'destroy']);

// Auth
$router->post('/auth/login', [AuthController::class, 'login']);
$router->get('/auth/me', [AuthController::class, 'me']);

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
$router->get('/connections', [ConnectionsController::class, 'index']);
$router->post('/connections', [ConnectionsController::class, 'store']);
$router->get('/connections/{id}/test', [ConnectionsController::class, 'test']);

// ✅ WooCommerce route’ları
$router->get('/api/woo/products', [WooCommerceController::class, 'listProducts']);
$router->get('/import/woocommerce/pull', [WooCommerceController::class, 'listProducts']);
$router->post('/import/woocommerce/pull', [WooCommerceController::class, 'listProducts']); // frontend beklediği route

// ---- DISPATCH ----
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path   = $_SERVER['REQUEST_URI'] ?? '/';

try {
    $response = $router->dispatch($method, $path);
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    // CORS header'larını catch bloklarında da ekle (her durumda)
    header("Access-Control-Allow-Origin: https://panel.woontegra.com");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    
    // HTTP 200 döndür, JSON hata mesajı gönder
    http_response_code(200);
    echo json_encode(['ok' => false, 'error' => 'Backend hata mesajı: ' . $e->getMessage()]);
}

// Output buffering sonlandır
ob_end_flush();
