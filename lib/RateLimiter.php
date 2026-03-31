<?php
/**
 * lib/RateLimiter.php — Tiện ích giới hạn lượt truy cập (Rate Limiting)
 */

class RateLimiter {
    /**
     * Kiểm tra giới hạn số lần thao tác của 1 hành động theo Session
     * 
     * @param string $actionKey  Tên hành động (vd: 'export_csv')
     * @param int $maxAttempts   Số lần cho phép tối đa
     * @param int $timeWindow    Khung thời gian (tính bằng giây)
     * @return bool              True nếu HỢP LỆ (chưa vượt quá), False nếu VƯỢT QUÁ giới hạn
     */
    public static function checkSessionLimit(string $actionKey, int $maxAttempts, int $timeWindow): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $now = time();
        $sessionKey = 'rate_limit_' . $actionKey;

        // Khởi tạo mảng lưu timestamp nếu chưa có
        if (!isset($_SESSION[$sessionKey]) || !is_array($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }

        // Lọc bỏ những lượt truy cập đã cũ hơn $timeWindow (Hết hạn)
        $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function($timestamp) use ($now, $timeWindow) {
            return ($now - $timestamp) < $timeWindow;
        });

        // Kiểm tra xem số lượng lượt yêu cầu còn lại có vượt quá giới hạn không
        if (count($_SESSION[$sessionKey]) >= $maxAttempts) {
            return false; // Vượt giới hạn
        }

        // Nếu hợp lệ, ghi nhận thêm 1 lượt truy cập mới vào mảng
        $_SESSION[$sessionKey][] = $now;
        
        return true; // Cho phép đi tiếp
    }
}
