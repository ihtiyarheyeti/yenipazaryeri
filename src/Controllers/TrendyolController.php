<?php
namespace App\Controllers;

use App\Integrations\TrendyolAdapter;

final class TrendyolController
{
    private TrendyolAdapter $adapter;

    public function __construct()
    {
        // Normalde connection bilgilerini DB veya config’ten alacağız.
        // Şimdilik test amaçlı ENV veya sabit değerlerden çalışabilir.
        $conn = [
            'api_key'     => getenv('TRENDYOL_API_KEY') ?: 'API_KEYINIZ',
            'api_secret'  => getenv('TRENDYOL_API_SECRET') ?: 'API_SECRETINIZ',
            'supplier_id' => getenv('TRENDYOL_SUPPLIER_ID') ?: 'SUPPLIER_IDNIZ',
        ];

        $this->adapter = new TrendyolAdapter($conn);
    }

    /**
     * Trendyol ürünlerini listele
     * GET /api/trendyol/products?page=0&size=20
     */
    public function listProducts(array $req): array
    {
        $page = isset($req['query']['page']) ? (int)$req['query']['page'] : 0;
        $size = isset($req['query']['size']) ? (int)$req['query']['size'] : 20;

        return $this->adapter->listPriceInventory(null, $page, $size);
    }

    /**
     * Trendyol siparişlerini listele (son 7 gün)
     * GET /api/trendyol/orders?page=0&size=20
     */
    public function listOrders(array $req): array
    {
        $start = date('c', strtotime('-7 days'));
        $end   = date('c');
        $page  = isset($req['query']['page']) ? (int)$req['query']['page'] : 0;
        $size  = isset($req['query']['size']) ? (int)$req['query']['size'] : 20;

        return $this->adapter->listOrders($start, $end, $page, $size);
    }

    /**
     * Trendyol’a ürün gönder (create/update)
     * POST /api/trendyol/products
     * Body: { "items": [ {...}, {...} ] }
     */
    public function createOrUpdateProducts(array $req): array
    {
        if (!isset($req['body']['items']) || !is_array($req['body']['items'])) {
            return ['ok' => false, 'error' => 'Missing items payload'];
        }

        return $this->adapter->createOrUpdateProducts($req['body']['items']);
    }
}
