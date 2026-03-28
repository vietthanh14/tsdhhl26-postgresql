<?php
// lib/SupabaseClient.php

require_once __DIR__ . '/../config/supabase.php';

class SupabaseClient {
    private $url;
    private $key;

    public function __construct($role = 'anon') {
        $this->url = SUPABASE_URL;
        $this->key = ($role === 'service') ? SUPABASE_SERVICE_ROLE_KEY : SUPABASE_ANON_KEY;
        
        if (empty($this->url) || empty($this->key)) {
            throw new Exception("Supabase URL hoặc Key chưa được cấu hình. Vui lòng kiểm tra file .env");
        }
    }

    private function request($method, $endpoint, $data = null, $token = null, $extraHeaders = []) {
        $ch = curl_init($this->url . $endpoint);

        $headers = [
            'apikey: ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation',
        ];

        // Cho phép override/thêm header (dùng bởi upsert, v.v.)
        foreach ($extraHeaders as $h) {
            // Nếu header đã tồn tại (cùng tên), ghi đè; nếu chưa, thêm vào
            $hName = strtolower(explode(':', $h)[0]);
            $headers = array_filter($headers, fn($existing) => strtolower(explode(':', $existing)[0]) !== $hName);
            $headers[] = $h;
        }
        $headers = array_values($headers);

        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        } else {
            $headers[] = 'Authorization: Bearer ' . $this->key;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'PATCH' || $method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        // Tự động giải nén (Brotli/Gzip) từ Supabase để tiết kiệm Egress 
        curl_setopt($ch, CURLOPT_ENCODING, '');

        // Tắt xác minh SSL khi đang ở môi trường Localhost (XAMPP có thể bị lỗi cert)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => $error, 'code' => $httpCode];
        return ['data' => json_decode($response, true), 'code' => $httpCode];
    }

    // Auth functions
    public function signUp($email, $password) {
        $data = ['email' => $email, 'password' => $password];
        return $this->request('POST', '/auth/v1/signup', $data);
    }

    public function signIn($email, $password) {
        $data = ['email' => $email, 'password' => $password];
        return $this->request('POST', '/auth/v1/token?grant_type=password', $data);
    }

    public function updateAuthUser($userId, $data) {
        // Sử dụng Supabase Auth Admin API (Cần quyền service_role key)
        $endpoint = "/auth/v1/admin/users/" . $userId;
        return $this->request('PUT', $endpoint, $data);
    }

    // Database functions
    public function select($table, $query = '', $token = null) {
        $endpoint = "/rest/v1/" . $table . ($query ? '?' . $query : '?select=*');
        return $this->request('GET', $endpoint, null, $token);
    }

    public function insert($table, $data, $token = null) {
         return $this->request('POST', "/rest/v1/" . $table, $data, $token);
    }

    public function update($table, $matchField, $matchValue, $data, $token = null) {
        $endpoint = "/rest/v1/" . $table . "?$matchField=eq." . $matchValue;
        return $this->request('PATCH', $endpoint, $data, $token);
    }

    public function upsert($table, $data, $token = null) {
        return $this->request(
            'POST',
            "/rest/v1/{$table}",
            $data,
            $token,
            ['Prefer: return=representation,resolution=merge-duplicates']
        );
    }

    public function delete($table, $matchField, $matchValue, $token = null) {
        $endpoint = "/rest/v1/" . $table . "?$matchField=eq." . $matchValue;
        return $this->request('DELETE', $endpoint, null, $token);
    }

    /**
     * Cập nhật nhiều hàng cùng lúc bằng IN filter — 1 API call thay vì N calls.
     * $ids: mảng các giá trị cần match (thường là UUID hoặc integer).
     */
    public function updateBulk($table, $matchField, array $ids, $data, $token = null) {
        if (empty($ids)) return ['code' => 400, 'data' => ['error' => 'No IDs provided']];
        $inList = implode(',', array_map('strval', $ids));
        $endpoint = "/rest/v1/{$table}?{$matchField}=in.({$inList})";
        return $this->request('PATCH', $endpoint, $data, $token);
    }

    /**
     * Lấy đếm số lượng bản ghi (Count) bằng giao thức HEAD (Zero Egress Payload).
     * Rất tiết kiệm băng thông khi chỉ cần đếm tổng số mà không tải mảng Data về.
     */
    public function count($table) {
        $ch = curl_init($this->url . "/rest/v1/" . $table . "?select=id&limit=1");
        $headers = [
            'apikey: ' . $this->key,
            'Prefer: count=exact',
            'Authorization: Bearer ' . $this->key,
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true); // Chuyển thành HEAD request
        curl_setopt($ch, CURLOPT_HEADER, true); // Yêu cầu trả cả headers để bóc tách
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headerStr = substr($response, 0, $headerSize);
        curl_close($ch);
        
        // Cú pháp của PostgREST: Content-Range: 0-0/5123 (5123 là tổng số)
        if ($httpCode >= 200 && $httpCode < 300) {
            if (preg_match('/Content-Range:\s*\w*\s*\d+-\d+\/(\d+)/i', $headerStr, $matches)) {
                return (int)$matches[1];
            }
        }
        return 0; // Fallback
    }
}
