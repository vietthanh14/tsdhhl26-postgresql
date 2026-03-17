<?php
// search.php - Trang tra cứu kết quả tuyển sinh từ Google Sheets
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra cứu kết quả tuyển sinh - ĐH Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/tsdhhl26/assets/css/public.css">
    <style>
        .search-container {
            max-width: 800px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }
        .result-card {
            display: none;
            margin-top: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            overflow: hidden;
        }
        .result-header {
            background-color: var(--brand-color, #0f4c81);
            color: #fff;
            padding: 15px 20px;
            font-weight: 600;
        }
        .result-body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px dashed #ddd;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            width: 40%;
            font-weight: 500;
            color: #555;
        }
        .info-value {
            width: 60%;
            font-weight: 600;
            color: #222;
        }
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .status-success { background: #d1e7dd; color: #0f5132; }
        .status-warning { background: #fff3cd; color: #664d03; }
        .status-danger { background: #f8d7da; color: #842029; }
    </style>
</head>
<body class="bg-light">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container pb-5">
    <div class="search-container">
        <h2 class="text-center fw-bold mb-4" style="color: var(--brand-color, #0f4c81);">
            <i class="bi bi-search me-2"></i>TRA CỨU KẾT QUẢ XÉT TUYỂN
        </h2>
        <p class="text-center text-muted mb-4">Nhập số Căn cước công dân (CMND/CCCD) của thí sinh để tra cứu kết quả xét tuyển ĐH Hạ Long năm 2026.</p>

        <form id="searchForm" onsubmit="handleSearch(event)">
            <div class="input-group input-group-lg mb-3 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person-vcard"></i></span>
                <input type="text" class="form-control border-start-0 ps-0" id="cccdInput" placeholder="Nhập số CCCD/CMND..." required pattern="[0-9]{9,12}" title="Vui lòng nhập từ 9 đến 12 chữ số">
                <button class="btn btn-primary px-4 fw-bold" type="submit" id="btnSearch" style="background-color: var(--brand-color, #0f4c81); border-color: var(--brand-color, #0f4c81);">
                    <i class="bi bi-search"></i> Tra cứu
                </button>
            </div>
            <div id="loadingIndicator" class="text-center mt-3 d-none">
                <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;">
                    <span class="visually-hidden">Đang tải...</span>
                </div>
                <span class="ms-2 text-muted">Đang kết nối hệ thống dữ liệu tra cứu...</span>
            </div>
        </form>

        <div id="alertError" class="alert alert-danger mt-4 d-none" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><span id="errorMsg">Không tìm thấy dữ liệu.</span>
        </div>

        <!-- Vùng hiển thị kết quả -->
        <div id="resultsContainer"></div>

    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function handleSearch(e) {
    e.preventDefault();
    const cccd = document.getElementById('cccdInput').value.trim();
    if(!cccd) return;

    // Reset UI
    document.getElementById('resultsContainer').innerHTML = '';
    document.getElementById('alertError').classList.add('d-none');
    document.getElementById('loadingIndicator').classList.remove('d-none');
    document.getElementById('btnSearch').disabled = true;

    fetch(`api_search_sheets.php?cccd=${encodeURIComponent(cccd)}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('loadingIndicator').classList.add('d-none');
            document.getElementById('btnSearch').disabled = false;

            if (data.status === 'success' && Array.isArray(data.data) && data.data.length > 0) {
                showResults(data.data);
            } else {
                document.getElementById('errorMsg').innerText = data.message || "Không tìm thấy dữ liệu tra cứu cho CCCD này.";
                document.getElementById('alertError').classList.remove('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            document.getElementById('loadingIndicator').classList.add('d-none');
            document.getElementById('btnSearch').disabled = false;
            document.getElementById('errorMsg').innerText = "Lỗi kết nối tới máy chủ tra cứu.";
            document.getElementById('alertError').classList.remove('d-none');
        });
}

function showResults(items) {
    const container = document.getElementById('resultsContainer');
    container.innerHTML = '';
    
    items.forEach((item, index) => {
        // Status Badge
        const statusText = item['Trạng thái hồ sơ trên cổng bộ GD&ĐT'] || 'Đã có kết quả';
        let badgeClass = 'bg-warning text-dark';
        if(statusText.toLowerCase().includes('trúng') || statusText.toLowerCase().includes('hợp lệ')) {
            badgeClass = 'bg-success';
        } else if(statusText.toLowerCase().includes('trượt') || statusText.toLowerCase().includes('từ chối')) {
            badgeClass = 'bg-danger';
        }

        // Link Giấy báo
        const link = item['Link Giấy Báo Nhập Học'];
        const linkHtml = (link && link.trim() !== '') 
            ? `<div class="mt-3 text-center">
                   <a href="${link}" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-4">
                       <i class="bi bi-cloud-download me-1"></i> Tải Giấy Báo Nhập Học
                   </a>
               </div>` 
            : '';

        const cardHtml = `
        <div class="result-card shadow-sm" style="display: block; margin-top: ${index === 0 ? '30px' : '40px'}; border-top-width: ${index === 0 ? '1px' : '4px'}; border-top-color: ${index === 0 ? '#e0e0e0' : 'var(--brand-color, #0f4c81)'};">
            <div class="result-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-file-earmark-person me-2"></i> KẾT QUẢ NGUYỆN VỌNG ${index + 1}</span>
                <span class="badge ${badgeClass}">${statusText}</span>
            </div>
            <div class="result-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Họ và tên:</div>
                            <div class="info-value text-uppercase">${item['Họ và tên'] || ''}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Số CMND/CCCD:</div>
                            <div class="info-value">${item['CMND'] || ''}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Ngày sinh:</div>
                            <div class="info-value">${item['Ngày sinh'] || ''}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Giới tính:</div>
                            <div class="info-value">${item['Giới tính'] || ''}</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="info-row">
                            <div class="info-label">Tên ngành:</div>
                            <div class="info-value text-primary fw-bold">${item['Tên ngành'] || ''}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Mã ngành:</div>
                            <div class="info-value">${item['Mã ngành'] || ''}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Điểm xét tuyển:</div>
                            <div class="info-value text-danger fw-bold fs-5">${item['Điểm xét tuyển'] || ''}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Trình độ:</div>
                            <div class="info-value">${item['Trình độ'] || ''}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 p-3 bg-white border rounded">
                    <h6 class="fw-bold text-success mb-3"><i class="bi bi-info-circle-fill me-2"></i>Thông tin bổ sung</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="small mb-2"><span class="text-muted">Phương thức xét tuyển:</span> <strong>${item['Mã PTXT'] || ''}</strong></div>
                            <div class="small mb-2"><span class="text-muted">Khu vực/Đối tượng ƯT:</span> <strong>${(item['KV ƯT'] || '') + ' - ' + (item['ĐT ƯT'] || '')}</strong></div>
                        </div>
                        <div class="col-md-6">
                            <div class="small mb-2"><span class="text-muted">Tổng điểm (Chưa ƯT):</span> <strong>${item['Tổng điểm chưa có ƯT (Thang 30)'] || ''}</strong></div>
                            <div class="small mb-2"><span class="text-muted">Thời gian nhập học:</span> <strong>${item['Thời gian nhập học'] || ''}</strong></div>
                        </div>
                    </div>
                    ${linkHtml}
                </div>
            </div>
        </div>
        `;
        container.insertAdjacentHTML('beforeend', cardHtml);
    });
}
</script>
</body>
</html>
