<?php
// lib/CSRF.php

class CSRF {
    /**
     * Tạo hoặc lấy CSRF token hiện tại từ Session
     */
    public static function generateToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Xác thực CSRF token
     */
    public static function validateToken($token) {
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        // Sử dụng hash_equals để chống lại Timing attacks
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
