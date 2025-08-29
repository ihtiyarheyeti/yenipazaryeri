<?php declare(strict_types=1);

// Debug: Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', '1');

// CORS - Preflight OPTIONS request'i handle et
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Max-Age: 86400"); // 24 saat cache
    http_response_code(204);
    exit;
}

// Normal CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

header("Content-Type: application/json; charset=utf-8");

// Debug: Request bilgilerini logla
error_log("DEBUG: Request started - Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT_SET') . ", URI: " . ($_SERVER['REQUEST_URI'] ?? 'NOT_SET'));

spl_autoload_register(function($class){
    $prefix = 'App\\';
    $base = __DIR__ . '/../src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = substr($class, strlen($prefix));
    $file = $base . str_replace('\\','/',$rel) . '.php';
    if (file_exists($file)) require $file;
});

// Tenant context'i set et
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

$router = new Router();

// Health
$router->get('/health', [HealthController::class, 'liveness']);
$router->get('/ready', [HealthController::class, 'readiness']);
$router->get('/metrics', [HealthController::class, 'metrics']);

// Dashboard
$router->get('/dashboard/metrics', [DashboardController::class, 'metrics']);
$router->get('/dashboard/alerts', [DashboardController::class, 'alerts']);

// Tenant Branding
$router->get('/tenant/branding', [TenantController::class, 'brandingGet']);
$router->post('/tenant/branding', [TenantController::class, 'brandingSet']);
$router->post('/upload/tenant-logo', [UploadTenantLogoController::class, 'upload']);

// Image Sync
$router->post('/integrations/woo/images/(\\d+)', [ImageSyncController::class, 'syncWoo']);
$router->post('/integrations/trendyol/images/(\\d+)', [ImageSyncController::class, 'syncTrendyol']);

// RBAC & User Management
$router->get('/users', [UsersController::class, 'index']);
$router->post('/users/(\\d+)/roles', [UsersController::class, 'setRoles']);

$router->get('/roles', [RolesController::class, 'index']);
$router->post('/roles', [RolesController::class, 'create']);
$router->delete('/roles/(\\d+)', [RolesController::class, 'delete']);
$router->get('/roles/(\\d+)/permissions', [RolesController::class, 'getPermissions']);
$router->post('/roles/(\\d+)/permissions', [RolesController::class, 'setPermissions']);

$router->post('/invites', [InvitesController::class, 'create']);
$router->post('/invites/accept', [InvitesController::class, 'accept']);

$router->post('/password/forgot', [PasswordResetController::class, 'request']);
$router->post('/password/reset', [PasswordResetController::class, 'reset']);

$router->get('/permissions', [PermissionsController::class, 'index']);
$router->get('/audit-logs', [AuditLogsController::class, 'index']);

// Auth
$router->post('/auth/login', [AuthController::class, 'login']);
$router->post('/auth/refresh', [AuthController::class, 'refresh']);
$router->post('/auth/logout-all', [AuthController::class, 'logoutAll']);
$router->post('/auth/forgot', [AuthController::class, 'forgot']);
$router->post('/auth/reset', [AuthController::class, 'reset']);
$router->get('/auth/me', [AuthController::class, 'me']);
$router->get('/auth/permissions', [AuthController::class, 'permissions']);
$router->post('/auth/2fa/setup', [TwoFAController::class, 'setup']);
$router->post('/auth/2fa/enable', [TwoFAController::class, 'enable']);
$router->post('/auth/2fa/disable', [TwoFAController::class, 'disable']);
$router->get('/auth/2fa/status', [TwoFAController::class, 'status']);

// Mail Test
$router->get('/mail/test', [MailController::class, 'test']);

// Marketplaces
$router->get('/marketplaces', [MarketplaceController::class, 'index']);

// Connections
$router->get('/connections', [ConnectionController::class, 'index']);
$router->post('/connections', [ConnectionController::class, 'create']);
$router->get('/connections/ping/(\\d+)', [ConnectionController::class, 'ping']);
$router->delete('/connections/(\\d+)', [ConnectionController::class, 'delete']);

// Category Mappings
$router->get('/category-mappings', [CategoryMappingController::class, 'index']);
$router->post('/category-mappings', [CategoryMappingController::class, 'create']);
$router->put('/category-mappings/(\\d+)', [CategoryMappingController::class, 'update']);
$router->delete('/category-mappings/(\\d+)', [CategoryMappingController::class, 'delete']);
$router->get('/csv/category-mappings/export', [CategoryMappingController::class, 'exportCsv']);
$router->post('/csv/category-mappings/import', [CategoryMappingController::class, 'importCsv']);

// Products
$router->get('/products', [ProductController::class, 'index']);
$router->get('/products/{id}', [ProductController::class, 'show']);
$router->post('/products', [ProductController::class, 'store']);
$router->put('/products/{id}', [ProductController::class, 'update']);
$router->delete('/products/{id}', [ProductController::class, 'destroy']);

// Product Lifecycle
$router->post('/products/(\\d+)/publish', [ProductController::class, 'publish']);
$router->post('/products/(\\d+)/archive', [ProductController::class, 'archive']);
$router->post('/products/(\\d+)/restore', [ProductController::class, 'restore']);
$router->post('/products/bulk-status', [ProductController::class, 'bulkStatus']);
$router->post('/products/(\\d+)/review', [ProductController::class, 'review']);

// Upload
$router->post('/upload/product-image', [UploadController::class, 'productImage']);
$router->get('/product-images', [UploadController::class, 'list']);
$router->delete('/product-images/(\\d+)', [UploadController::class, 'delete']);

// Variants
$router->get('/variants', [VariantController::class, 'index']);
$router->post('/variants', [VariantController::class, 'store']);
$router->get('/variants/{id}', [VariantController::class, 'show']);
$router->put('/variants/{id}', [VariantController::class, 'update']);
$router->delete('/variants/{id}', [VariantController::class, 'destroy']);

// Marketplaces
$router->get('/marketplaces', [MarketplaceController::class, 'index']);
$router->post('/marketplaces', [MarketplaceController::class, 'store']);

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

// Integrations
$router->post('/integrations/trendyol/send-product/(\\d+)', [IntegrationController::class, 'sendTrendyol']);
$router->post('/integrations/woo/send-product/(\\d+)', [IntegrationController::class, 'sendWoo']);
$router->post('/integrations/trendyol/sync/(\\d+)', [IntegrationController::class, 'syncTrendyol']);
$router->post('/integrations/woo/sync/(\\d+)', [IntegrationController::class, 'syncWoo']);
$router->post('/integrations/bulk-send', [IntegrationController::class, 'bulkSend']);
$router->get('/products/(\\d+)/logs', [IntegrationController::class, 'productLogs']);

// Queue
$router->post('/queue/process', [QueueController::class, 'process']);
$router->get('/queue/metrics', [QueueController::class, 'metrics']);
$router->post('/queue/requeue/(\\d+)', [QueueController::class, 'requeue']);
$router->post('/queue/cancel/(\\d+)', [QueueController::class, 'cancel']);

// Batch Jobs
$router->get('/batches', [BatchController::class, 'list']);
$router->get('/batches/(.+)', [BatchController::class, 'detail']);

// Notifications
$router->get('/notifications', [NotificationController::class, 'list']);

// Catalog & Mapping
$router->post('/catalog/pull', [CatalogController::class, 'pullCategories']);
$router->get('/catalog/category-map', [CatalogController::class, 'listCategoryMap']);
$router->post('/catalog/category-map', [CatalogController::class, 'upsertCategoryMap']);
$router->get('/catalog/attr-map', [CatalogController::class, 'listAttrMap']);
$router->post('/catalog/attr-map', [CatalogController::class, 'upsertAttrMap']);

// Webhooks
$router->post('/webhooks/woo/product-updated', [WooWebhookController::class, 'productUpdated']);

// Orders
$router->get('/orders', [OrdersController::class, 'list']);
$router->post('/orders/pull/trendyol', [OrdersController::class, 'pullTrendyol']);
$router->post('/orders/push/woo/(\\d+)', [OrdersController::class, 'pushToWoo']);

// Returns & Cancellations
$router->post('/returns/pull', [ReturnsController::class, 'pull']);
$router->post('/returns/(.+)/act', [ReturnsController::class, 'act']);
$router->post('/returns/(.+)/push-woo', [ReturnsController::class, 'pushToWoo']);
$router->post('/cancellations/pull', [CancellationsController::class, 'pull']);
$router->post('/cancellations/(.+)/approve', [CancellationsController::class, 'approve']);
$router->post('/cancellations/(.+)/push-woo', [CancellationsController::class, 'pushToWoo']);

// Shipments & Invoices
$router->post('/shipments', [ShipmentController::class, 'create']);
$router->post('/shipments/(\\d+)/tracking', [ShipmentController::class, 'updateTracking']);
$router->post('/invoices', [InvoiceController::class, 'create']);
$router->post('/invoices/(\\d+)/attach', [InvoiceController::class, 'attachPdf']);

// Reconcile
$router->post('/reconcile/suggest', [ReconcileController::class, 'suggest']);
$router->post('/reconcile/suggestions/(\\d+)/resolve', [ReconcileController::class, 'resolve']);

// Policies
$router->get('/policies', [PolicyController::class, 'list']);
$router->post('/policies', [PolicyController::class, 'upsert']);

// Connection Testing
$router->post('/connections/(\\d+)/test', [ConnectionsController::class, 'test']);

// Dev Testing Endpoints
$router->get('/dev/woo/cats', [DevController::class, 'wooCats']);
$router->get('/dev/ty/tax', [DevController::class, 'tyTax']);

// CSV Import/Export
$router->get('/csv/category-mappings/export', [CsvController::class, 'exportCategoryMappings']);
$router->post('/csv/category-mappings/import', [CsvController::class, 'importCategoryMappings']);
$router->get('/csv/products/export', [ProductCsvController::class, 'export']);
$router->post('/csv/products/import', [ProductCsvController::class, 'import']);
$router->post('/csv/products/validate', [ProductCsvController::class, 'validate']);
$router->post('/csv/products/import-sync', [ProductCsvController::class, 'importAndSync']);

// Product Sync
$router->post('/products/(\\d+)/push/trendyol', [ProductSyncController::class, 'pushTrendyol']);
$router->post('/products/(\\d+)/push/woo', [ProductSyncController::class, 'pushWoo']);
$router->post('/products/(\\d+)/create-woo-variations', [ProductSyncController::class, 'createWooVariationsJob']);

// Product Import
$router->post('/import/trendyol/pull', [ProductImportController::class, 'pullTrendyol']);
$router->post('/import/woocommerce/pull', [ProductImportController::class, 'pullWooCommerce']);
$router->post('/import/csv', [ProductImportController::class, 'importFromCsv']);

// Standardizer
$router->post('/standardize', [StdController::class, 'standardize']);

// Media
$router->post('/products/(\d+)/media/fetch-woo', [MediaController::class, 'fetchFromWoo']);
$router->post('/products/(\d+)/media/push-ty', [MediaController::class, 'pushToTrendyol']);
$router->get('/products/(\d+)/images', function($p){ $st=\App\Database::pdo()->prepare("SELECT id,url,position,status FROM images WHERE product_id=? ORDER BY position,id"); $st->execute([(int)$p[0]]); return ['ok'=>true,'items'=>$st->fetchAll()]; });

// Orders
$router->get('/orders', function($p,$b,$q){
  $src=$q['source']??null; $w="1=1"; $a=[];
  if($src){ $w.=" AND origin_mp=?"; $a[]=$src; }
  $st=\App\Database::pdo()->prepare("SELECT id,origin_mp,origin_external_id,customer_name,total_amount,status FROM orders WHERE $w ORDER BY id DESC LIMIT 1000");
  $st->execute($a); return ['ok'=>true,'items'=>$st->fetchAll()];
});
$router->get('/orders/(\d+)', function($p){
  $oid=(int)$p[0];
  $db=\App\Database::pdo();
  $o=$db->prepare("SELECT * FROM orders WHERE id=?"); $o->execute([$oid]); $order=$o->fetch();
  $i=$db->prepare("SELECT * FROM order_items WHERE order_id=?"); $i->execute([$oid]); $items=$i->fetchAll();
  return ['ok'=>true,'order'=>$order,'items'=>$items];
});
$router->post('/orders/import/woo', [OrderImportController::class, 'pullWoo']);
$router->post('/orders/import/trendyol', [OrderImportController::class, 'pullTrendyol']);
$router->post('/orders/(\d+)/push/woo', [OrderSyncController::class, 'pushToWoo']);
$router->post('/orders/(\d+)/push/trendyol', [OrderSyncController::class, 'pushToTrendyol']);
$router->post('/orders/(\d+)/status/woo', [OrderSyncController::class, 'updateWooStatus']);

// Webhooks
$router->post('/webhooks/woo', [WebhookController::class, 'woo']);
$router->post('/webhooks/trendyol', [WebhookController::class, 'trendyol']);

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
$router->get('/logs/export', [LogController::class, 'export']);

// Audit Logs
$router->get('/audit-logs', [AuditController::class, 'index']);

// User Invite
$router->post('/users/invite', [UserController::class, 'invite']);

// Mapping
$router->post('/mapping/woo/resolve', [MappingController::class, 'resolveWoo']);

// Autocomplete
$router->get('/autocomplete/categories', function($p,$b,$q){
  $tenant=\App\Context::$tenantId;
  $target = ($q['target']??'trendyol')==='woo' ? 2 : 1;
  $term = '%'.($q['q']??'').'%';
  $st=\App\Database::pdo()->prepare("SELECT external_id, local_path FROM category_mapping WHERE tenant_id=? AND marketplace_id=? AND (external_id LIKE ? OR local_path LIKE ?) LIMIT 20");
  $st->execute([$tenant,$target,$term,$term]);
  return ['ok'=>true,'items'=>$st->fetchAll()];
});

$router->get('/autocomplete/attributes', function($p,$b,$q){
  $tenant=\App\Context::$tenantId;
  $target = ($q['target']??'trendyol')==='woo' ? 2 : 1;
  $term = '%'.($q['q']??'').'%';
  $st=\App\Database::pdo()->prepare("SELECT external_key, external_value, local_key, local_value
    FROM attribute_mapping
    WHERE tenant_id=? AND marketplace_id=? AND (external_key LIKE ? OR external_value LIKE ? OR local_key LIKE ? OR local_value LIKE ?)
    LIMIT 20");
  $st->execute([$tenant,$target,$term,$term,$term,$term]);
  return ['ok'=>true,'items'=>$st->fetchAll()];
});

// Dispatch
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = $_SERVER['REQUEST_URI'] ?? '/';

// Debug: Path'i logla
error_log("DEBUG: Method=$method, Path=$path");
error_log("DEBUG: REQUEST_METHOD=" . ($_SERVER['REQUEST_METHOD'] ?? 'NOT_SET'));
error_log("DEBUG: HTTP_METHOD=" . ($_SERVER['HTTP_METHOD'] ?? 'NOT_SET'));
error_log("DEBUG: HTTP_X_HTTP_METHOD=" . ($_SERVER['HTTP_X_HTTP_METHOD'] ?? 'NOT_SET'));

// Debug: Router'da tanımlı route'ları logla
error_log("DEBUG: All registered routes: " . json_encode($router->getRoutes($method)));

try {
    $response = $router->dispatch($method, $path);
    error_log("DEBUG: Router response: " . json_encode($response));
    
    if (is_array($response)) {
        echo json_encode($response);
    } else {
        echo $response;
    }
} catch (Exception $e) {
    error_log("DEBUG: Exception caught: " . $e->getMessage());
    error_log("DEBUG: Exception trace: " . $e->getTraceAsString());
    echo json_encode(['ok' => false, 'error' => 'Internal server error: ' . $e->getMessage()]);
}

// Her request'i audit log'a yaz - Geçici olarak devre dışı
/*
\App\Middleware\AuditMiddleware::log(
  $_SESSION['user_id'] ?? null,
  "call",
  $method,
  $path,
  $method === "GET" ? $_GET : ($_POST ?: file_get_contents("php://input"))
);
*/
