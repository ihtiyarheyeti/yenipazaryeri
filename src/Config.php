<?php
namespace App;

final class Config {
    // Database ayarları
    public const DB_HOST    = 'localhost';
    public const DB_PORT    = 3306;
    public const DB_NAME    = 'entegrasyon_paneli';
    public const DB_USER    = 'woontegra_api';   // ✅ az önce oluşturduğun user
    public const DB_PASS    = 'EGic28R5DE@?';    // ✅ senin gerçek şifren
    public const DB_CHARSET = 'utf8mb4';

    // JWT ayarları
    public const JWT_SECRET = 'your-secret-key-here-change-in-production';
    public const JWT_EXPIRY = 3600; // 1 saat

    // Queue ayarları
    public const QUEUE_MAX_ATTEMPTS = 5;
    public const QUEUE_BACKOFF_BASE = 15;
    public const QUEUE_BACKOFF_CAP  = 900;
    public const QUEUE_BATCH_LIMIT  = 20;

    public static function database(): array {
        return [
            'host'    => getenv('DB_HOST') ?: self::DB_HOST,
            'port'    => getenv('DB_PORT') ?: self::DB_PORT,
            'name'    => getenv('DB_NAME') ?: self::DB_NAME,
            'user'    => getenv('DB_USER') ?: self::DB_USER,
            'pass'    => getenv('DB_PASS') ?: self::DB_PASS,
            'charset' => getenv('DB_CHARSET') ?: self::DB_CHARSET,
        ];
    }

    public static function queue(): array {
        return [
            'max_attempts' => (int)(getenv('QUEUE_MAX_ATTEMPTS') ?: self::QUEUE_MAX_ATTEMPTS),
            'backoff_base' => (int)(getenv('QUEUE_BACKOFF_BASE') ?: self::QUEUE_BACKOFF_BASE),
            'backoff_cap'  => (int)(getenv('QUEUE_BACKOFF_CAP')  ?: self::QUEUE_BACKOFF_CAP),
            'batch_limit'  => (int)(getenv('QUEUE_BATCH_LIMIT')  ?: self::QUEUE_BATCH_LIMIT),
        ];
    }
}
