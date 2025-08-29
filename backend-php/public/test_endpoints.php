<?php
header('Content-Type: application/json');

// Database bağlantısı
try {
    $pdo = new PDO('mysql:host=localhost;dbname=entegrasyon_paneli;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'database_connection_failed', 'message' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'marketplaces':
        try {
            $st = $pdo->query("SELECT id,name,base_url FROM marketplaces ORDER BY id ASC");
            $items = $st->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['ok' => true, 'items' => $items]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'connections':
        try {
            $tenant = (int)($_GET['tenant_id'] ?? 1);
            $page = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, (int)($_GET['pageSize'] ?? 10)));
            $offset = ($page - 1) * $pageSize;
            
            // Total count
            $totalSt = $pdo->prepare("SELECT COUNT(*) FROM marketplace_connections WHERE tenant_id = ?");
            $totalSt->execute([$tenant]);
            $total = (int)$totalSt->fetchColumn();
            
            // Connections
            $st = $pdo->prepare("SELECT c.*, m.name as marketplace_name 
                                 FROM marketplace_connections c 
                                 JOIN marketplaces m ON m.id = c.marketplace_id 
                                 WHERE c.tenant_id = ? 
                                 ORDER BY c.id DESC 
                                 LIMIT ?, ?");
            $st->execute([$tenant, $offset, $pageSize]);
            $items = $st->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'ok' => true, 
                'items' => $items, 
                'total' => $total, 
                'page' => $page, 
                'pageSize' => $pageSize
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'ping_trendyol':
        try {
            $connection_id = (int)($_GET['connection_id'] ?? 0);
            if (!$connection_id) {
                echo json_encode(['ok' => false, 'error' => 'connection_id_required']);
                break;
            }
            
            // Connection bilgilerini al
            $st = $pdo->prepare("SELECT c.*, m.base_url FROM marketplace_connections c 
                                 JOIN marketplaces m ON m.id = c.marketplace_id 
                                 WHERE c.id = ? AND c.marketplace_id = 1");
            $st->execute([$connection_id]);
            $conn = $st->fetch(PDO::FETCH_ASSOC);
            
            if (!$conn) {
                echo json_encode(['ok' => false, 'error' => 'connection_not_found']);
                break;
            }
            
            // Trendyol API test
            $url = $conn['base_url'] . '/suppliers/' . $conn['supplier_id'] . '/products';
            $headers = [
                'Authorization: Basic ' . base64_encode($conn['api_key'] . ':' . $conn['api_secret']),
                'Content-Type: application/json',
                'User-Agent: Yenipazaryeri/1.0'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo json_encode(['ok' => false, 'error' => 'curl_error', 'message' => $error]);
                break;
            }
            
            // Response header ve body'yi ayır
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);
            
            echo json_encode([
                'ok' => true,
                'http_code' => $httpCode,
                'url' => $url,
                'supplier_id' => $conn['supplier_id'],
                'api_key_length' => strlen($conn['api_key']),
                'api_secret_length' => strlen($conn['api_secret']),
                'response_headers' => $responseHeaders,
                'response_body' => $responseBody,
                'curl_error' => $error
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'ping_woo':
        try {
            $connection_id = (int)($_GET['connection_id'] ?? 0);
            if (!$connection_id) {
                echo json_encode(['ok' => false, 'error' => 'connection_id_required']);
                break;
            }
            
            // Connection bilgilerini al
            $st = $pdo->prepare("SELECT c.*, m.base_url FROM marketplace_connections c 
                                 JOIN marketplaces m ON m.id = c.marketplace_id 
                                 WHERE c.id = ? AND c.marketplace_id = 2");
            $st->execute([$connection_id]);
            $conn = $st->fetch(PDO::FETCH_ASSOC);
            
            if (!$conn) {
                echo json_encode(['ok' => false, 'error' => 'connection_not_found']);
                break;
            }
            
            // WooCommerce API test
            $url = $conn['base_url'] . '/products';
            $auth = base64_encode($conn['api_key'] . ':' . $conn['api_secret']);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/json',
                'User-Agent: Yenipazaryeri/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HEADER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($error) {
                echo json_encode(['ok' => false, 'error' => 'curl_error', 'message' => $error]);
                break;
            }
            
            // Response header ve body'yi ayır
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $responseHeaders = substr($response, 0, $headerSize);
            $responseBody = substr($response, $headerSize);
            
            echo json_encode([
                'ok' => true,
                'http_code' => $httpCode,
                'url' => $url,
                'base_url' => $conn['base_url'],
                'api_key_length' => strlen($conn['api_key']),
                'api_secret_length' => strlen($conn['api_secret']),
                'response_headers' => $responseHeaders,
                'response_body' => $responseBody,
                'curl_error' => $error
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'products':
        try {
            $tenant = (int)($_GET['tenant_id'] ?? 1);
            $page = max(1, (int)($_GET['page'] ?? 1));
            $pageSize = min(50, max(1, (int)($_GET['pageSize'] ?? 10)));
            $offset = ($page - 1) * $pageSize;
            
            // Total count
            $totalSt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE tenant_id = ?");
            $totalSt->execute([$tenant]);
            $total = (int)$totalSt->fetchColumn();
            
            // Products
            $st = $pdo->prepare("SELECT p.*, 
                (SELECT COUNT(*) FROM variants v WHERE v.product_id=p.id) AS variant_count
                FROM products p 
                WHERE p.tenant_id = ? 
                ORDER BY p.id DESC 
                LIMIT ?, ?");
            $st->execute([$tenant, $offset, $pageSize]);
            $items = $st->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'ok' => true, 
                'items' => $items, 
                'total' => $total, 
                'page' => $page, 
                'pageSize' => $pageSize
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    case 'test_db':
        try {
            // Test database connection
            $st = $pdo->query("SELECT 1 as test");
            $result = $st->fetch();
            
            // Check tables
            $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
            
            echo json_encode([
                'ok' => true, 
                'db_test' => $result['test'],
                'tables' => $tables
            ]);
        } catch (Exception $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        break;
        
    default:
        echo json_encode([
            'ok' => false, 
            'error' => 'unknown_action',
            'available_actions' => ['marketplaces', 'connections', 'ping_trendyol', 'ping_woo', 'products', 'test_db']
        ]);
}
