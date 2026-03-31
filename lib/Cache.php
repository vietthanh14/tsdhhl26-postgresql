<?php
/**
 * lib/Cache.php — File-based cache for Supabase static data
 * 
 * Usage:
 *   $levels = Cache::remember('education_levels', 3600, function() use ($supabase) {
 *       $res = $supabase->select('education_levels', 'order=id.asc');
 *       return ($res['code'] == 200) ? $res['data'] : [];
 *   });
 * 
 *   Cache::forget('education_levels');   // Xóa 1 key
 *   Cache::flush();                      // Xóa toàn bộ cache
 */

class Cache {
    private static string $cacheDir = __DIR__ . '/../storage/cache';

    private static function ensureDir(): void {
        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    private static function path(string $key): string {
        return self::$cacheDir . '/' . md5($key) . '.cache';
    }

    /**
     * Lấy data từ cache, nếu hết hạn thì gọi $callback để fetch mới
     */
    public static function remember(string $key, int $ttlSeconds, callable $callback): mixed {
        self::ensureDir();
        $file = self::path($key);

        if (file_exists($file)) {
            $cached = @unserialize(file_get_contents($file));
            if ($cached && $cached['expires_at'] > time()) {
                return $cached['data'];
            }
        }

        $data = $callback();
        
        // Cải tiến: Nếu callback trả về false (ví dụ API lỗi), ta không cache nó 
        // để tránh việc hệ thống bị khoá ở trạng thái lỗi trong suốt TTL.
        if ($data !== false) {
            $payload = serialize([
                'data' => $data,
                'expires_at' => time() + $ttlSeconds,
                'key' => $key,
            ]);
            file_put_contents($file, $payload, LOCK_EX);
        }
        return $data;
    }

    /**
     * Xóa 1 cache key
     */
    public static function forget(string $key): void {
        $file = self::path($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Xóa nhiều cache keys theo prefix
     */
    public static function forgetByPrefix(string $prefix): void {
        self::ensureDir();
        $files = glob(self::$cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                $cached = @unserialize(file_get_contents($file));
                if ($cached && isset($cached['key']) && str_starts_with($cached['key'], $prefix)) {
                    @unlink($file);
                }
            }
        }
    }

    /**
     * Xóa toàn bộ cache
     */
    public static function flush(): void {
        self::ensureDir();
        $files = glob(self::$cacheDir . '/*.cache');
        if (is_array($files)) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }

    /**
     * Trả về danh sách cache hiện tại (cho admin debug)
     */
    public static function stats(): array {
        self::ensureDir();
        $files = glob(self::$cacheDir . '/*.cache');
        $stats = [];
        if (is_array($files)) {
            foreach ($files as $file) {
                $cached = @unserialize(file_get_contents($file));
                if ($cached && isset($cached['key'])) {
                    $stats[] = [
                        'key' => $cached['key'],
                        'expires_at' => date('H:i:s d/m/Y', $cached['expires_at']),
                        'expired' => $cached['expires_at'] < time(),
                        'size' => filesize($file),
                    ];
                }
            }
        }
        return $stats;
    }
}
