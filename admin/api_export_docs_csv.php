<?php
// admin/api_export_docs_csv.php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo "Unauthorized";
    exit;
}

require_once __DIR__ . '/../lib/SupabaseClient.php';

try {
    $supabaseAdmin = new SupabaseClient('service');
    
    // 1. Lấy dữ liệu tài liệu từ Supabase 
    $query = 'select=*,document_types(type_name)&order=uploaded_at.desc';
    $docsRes = $supabaseAdmin->select('user_documents', $query);
    if ($docsRes['code'] != 200) {
        throw new Exception("Lỗi khi lấy dữ liệu tài liệu từ Supabase: " . json_encode($docsRes['data'] ?? []));
    }
    $documents = $docsRes['data'];

    // 2. Lấy Profile để map thông tin user
    $usersRes = $supabaseAdmin->select('user_profiles', 'select=id,full_name,identity_card,phone_number,contact_email');
    $userProfilesMap = [];
    if ($usersRes['code'] == 200 && is_array($usersRes['data'])) {
        foreach ($usersRes['data'] as $u) {
            $userProfilesMap[$u['id']] = $u;
        }
    }
    
    // Tạo cấu trúc thư mục và dọn dẹp
    $filename = 'Danh_sach_TaiLieu_' . date('Y_m_d_His') . '.csv';
    $exportDir = __DIR__ . '/../uploads/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }
    
    $files = glob($exportDir . '/*.csv');
    $now   = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= 24 * 60 * 60) {
                unlink($file);
            }
        }
    }

    $filepath = $exportDir . '/' . $filename;
    $fileUrl = '../uploads/exports/' . $filename; 

    // Mở file vật lý để ghi
    $output = fopen($filepath, 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // Ghi BOM

    // Tiêu đề cột
    fputcsv($output, [
        'STT', 
        'Họ và tên thí sinh', 
        'CMND/CCCD', 
        'Số điện thoại',
        'Email',
        'Loại tài liệu',
        'Ngày tải lên',
        'Link tải tài liệu'
    ], ';');

    // Streaming dữ liệu
    $stt = 1;
    foreach ($documents as $doc) {
        $user = $userProfilesMap[$doc['user_id']] ?? [];
        $docTypeName = $doc['document_types']['type_name'] ?? 'Khác';
        
        // Thay vì nháy đơn, dùng dạng ="chuỗi" để Excel hiểu là Text mà không bị hiện nháy đơn và không mất số 0
        $cmnd = '="' . ($user['identity_card'] ?? '') . '"';
        $dateUploaded = !empty($doc['uploaded_at']) ? date('d/m/Y H:i', strtotime($doc['uploaded_at'])) : '';
        $docUrl = $doc['drive_file_url'] ?? '';

        fputcsv($output, [
            $stt++,
            $user['full_name'] ?? 'Không xác định',
            $cmnd,
            $user['phone_number'] ?? '',
            $user['contact_email'] ?? '',
            $docTypeName,
            $dateUploaded,
            $docUrl
        ], ';');
    }

    fclose($output);

    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    
    echo json_encode([
        'status' => 'success',
        'filename' => $filename,
        'file_url' => $fileUrl
    ]);
    exit;

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
