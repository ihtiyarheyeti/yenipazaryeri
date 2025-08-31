<?php
declare(strict_types=1);

namespace App\Adapters;

use PDO;

class WooAdapter
{
    private string $baseUrl;
    private string $consumerKey;
    private string $consumerSecret;
    private PDO $db;

    public function __construct(string $baseUrl, string $consumerKey, string $consumerSecret, PDO $db)
    {
        $this->baseUrl        = rtrim($baseUrl, '/');
        $this->consumerKey    = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->db             = $db;
    }

    /**
     * WooCommerce ürünlerini çeker
     */
    public function pullProducts(int $perPage = 10, int $page = 1): array
    {
        $url = $this->baseUrl . "/wp-json/wc/v3/products?per_page={$perPage}&page={$page}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $this->consumerKey . ':' . $this->consumerSecret,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $error    = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Log kaydı
        $this->log('woocommerce_pull', [
            'url'       => $url,
            'http_code' => $httpCode,
            'errno'     => $errno,
            'error'     => $error,
            'response'  => $response,
        ]);

        if ($errno !== 0) {
            return ['success' => false, 'error' => $error];
        }

        if ($httpCode !== 200) {
            return ['success' => false, 'error' => "HTTP {$httpCode}", 'response' => $response];
        }

        $data = json_decode($response, true);

        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Invalid JSON', 'raw' => $response];
        }

        return [
            'success' => true,
            'count'   => count($data),
            'data'    => $data,
        ];
    }

    /**
     * Log kaydı DB'ye yazar
     */
    private function log(string $type, array $payload): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO logs (type, message, created_at)
                VALUES (:type, :message, NOW())
            ");
            $stmt->execute([
                ':type'    => $type,
                ':message' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            ]);
        } catch (\Throwable $e) {
            // Sessiz hata, DB loglaması çalışmazsa PHP error log'a düşsün
            error_log("Log insert failed: " . $e->getMessage());
        }
    }
}
