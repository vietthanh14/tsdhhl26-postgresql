<?php
require_once __DIR__ . '/config/database.php';

try {
    // Kết nối CSDL với tư cách chủ sở hữu (quyền cao nhất) từ biến môi trường
    $host = defined('DB_HOST') ? DB_HOST : getenv('DB_HOST');
    $port = defined('DB_PORT') ? ltrim(getenv('DB_PORT'), '"') : '5432';
    $dbname = defined('DB_NAME') ? DB_NAME : getenv('DB_NAME');
    $user = defined('DB_USER') ? DB_USER : getenv('DB_USER');  // asapvnco_admints
    $pass = defined('DB_PASS') ? DB_PASS : getenv('DB_PASS');
    $port = trim($port, "'\"");

    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // 1. Cấp quyền trên tất cả các Table
    // 2. Cấp quyền trên tất cả các Sequence (cho khoá chính SERIAL tự tăng rỗng)
    $sql = "
        GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO asapvnco;
        GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO asapvnco;
    ";
    
    $pdo->exec($sql);

    echo "<div style='font-family: sans-serif; text-align: center; margin-top: 50px;'>";
    echo "<h1 style='color: green;'>✅ CẤP QUYỀN THÀNH CÔNG!</h1>";
    echo "<p>Đã cấp toàn bộ quyền thao tác trên các bảng cho tài khoản gốc <b>asapvnco</b>.</p>";
    echo "<p>Bây giờ bạn truy cập lại <b>phpPgAdmin</b> trên cPanel, tải lại trang và thử ấn Browse dữ liệu bình thường thay vì bị lỗi báo Permission Denied.</p>";
    echo "<p style='color: red; font-weight: bold;'>⚠️ WARNING: Hãy xóa ngay file <b>fix_grant.php</b> này khỏi source code sau khi bạn F5 phpPgAdmin thành công.</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<h1 style='color: red;'>❌ LỖI:</h1><p>" . $e->getMessage() . "</p>";
}
