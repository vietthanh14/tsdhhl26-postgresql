<?php
// admin/api/export_docs_csv.php — Xuất CSV danh sách tài liệu thí sinh
require_once __DIR__ . '/_guard.php';

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
    $userProfilesMap = $supabaseAdmin->fetchUserProfilesMap(
        array_column($documents, 'user_id'),
        'id,full_name,identity_card,phone_number,contact_email'
    );
    
    $filename = 'Danh_sach_TaiLieu_' . date('Y_m_d_His') . '.csv';
    $exportDir = __DIR__ . '/../../uploads/exports';
    if (!is_dir($exportDir)) {
        mkdir($exportDir, 0777, true);
    }
    
    // Xoá file csv cũ hơn 1 ngày
    foreach (glob($exportDir . '/*.csv') as $file) {
        if (is_file($file) && time() - filemtime($file) >= 86400) {
            unlink($file);
        }
    }

    $filepath = $exportDir . '/' . $filename;
    $fileUrl = '../../uploads/exports/' . $filename; 

    $output = fopen($filepath, 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM

    fputcsv($output, [
        'STT', 'Họ và tên thí sinh', 'CMND/CCCD', 'Số điện thoại',
        'Email', 'Loại tài liệu', 'Ngày tải lên', 'Link tải tài liệu'
    ], ';');

    $stt = 1;
    foreach ($documents as $doc) {
        $user = $userProfilesMap[$doc['user_id']] ?? [];
        $cmnd = '="' . ($user['identity_card'] ?? '') . '"';
        $dateUploaded = !empty($doc['uploaded_at']) ? date('d/m/Y H:i', strtotime($doc['uploaded_at'])) : '';

        fputcsv($output, [
            $stt++,
            $user['full_name'] ?? 'Không xác định',
            $cmnd,
            $user['phone_number'] ?? '',
            $user['contact_email'] ?? '',
            $doc['document_types']['type_name'] ?? 'Khác',
            $dateUploaded,
            $doc['drive_file_url'] ?? ''
        ], ';');
    }

    fclose($output);

    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'success', 'filename' => $filename, 'file_url' => $fileUrl]);
    exit;

} catch (Exception $e) {
    if (ob_get_level()) ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
