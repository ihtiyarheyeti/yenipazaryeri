<?php
namespace App\Controllers;

use App\Database;
use Throwable;

final class HealthController {
    // Basit canlılık kontrolü
    public function liveness(): array {
        error_log("HealthController::liveness called");
        return ['ok' => true, 'time' => date('c')];
    }

    // DB bağlantısı kontrolü
    public function readiness(): array {
        try {
            $pdo = Database::pdo();
            $pdo->query("SELECT 1");
            return ['ok' => true, 'time' => date('c')];
        } catch (Throwable $e) {
            http_response_code(500);
            error_log("HealthController::readiness DB FAIL - " . $e->getMessage());
            return ['ok' => false, 'error' => 'db', 'detail' => $e->getMessage()];
        }
    }

    // Basit metric (sadece test için)
    public function metrics(): array {
        try {
            $pdo = Database::pdo();
            $cnt = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            return [
                'ok' => true,
                'metrics' => [
                    'products_total' => (int)$cnt,
                ],
            ];
        } catch (Throwable $e) {
            http_response_code(500);
            error_log("HealthController::metrics FAIL - " . $e->getMessage());
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }
}
