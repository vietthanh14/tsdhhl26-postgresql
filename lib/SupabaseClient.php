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

    private function request($method, $endpoint, $data = null, $token = null) {
        $ch = curl_init($this->url . $endpoint);

        $headers = [
            'apikey: ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];

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
        $endpoint = "/rest/v1/" . $table . ($query ? '?' . $query : '&select=*');
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
        $endpoint = "/rest/v1/" . $table;
        
        $ch = curl_init($this->url . $endpoint);
        $headers = [
            'apikey: ' . $this->key,
            'Content-Type: application/json',
            'Prefer: return=representation,resolution=merge-duplicates'
        ];
        if ($token) {
            $headers[] = 'Authorization: Bearer ' . $token;
        } else {
            $headers[] = 'Authorization: Bearer ' . $this->key;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) return ['error' => $error, 'code' => $httpCode];
        return ['data' => json_decode($response, true), 'code' => $httpCode];
    }

    public function delete($table, $matchField, $matchValue, $token = null) {
        $endpoint = "/rest/v1/" . $table . "?$matchField=eq." . $matchValue;
        return $this->request('DELETE', $endpoint, null, $token);
    }
}
