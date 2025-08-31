<?php namespace App\Utils;

use App\Database;

final class Policy {
    public static function get(string $key, int $tenantId) {
        $st = Database::pdo()->prepare("SELECT rules FROM policies WHERE tenant_id=? AND name=?");
        $st->execute([$tenantId, $key]);
        $v = $st->fetchColumn();
        return $v ? json_decode($v, true) : [];
    }
}
