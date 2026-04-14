<?php
require_once __DIR__ . '/config/database.php';

// search.php - Trang tra cứu kết quả tuyển sinh từ Google Sheets
session_start();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>/assets/logo.png">
    <title>Tra cứu kết quả tuyển sinh - ĐH Hạ Long</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/public.css">
    <style>
        .search-hero {
            background: #f7f9fc;
            padding: 48px 0 32px;
        }
        .search-box {
            max-width: 560px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        .search-box .form-control {
            height: 52px;
            border: none;
            border-radius: 12px 0 0 12px;
            font-size: 1rem;
            padding-left: 44px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .search-box .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 5;
            font-size: 1.1rem;
        }
        .search-box .btn-search {
            height: 52px;
            border-radius: 0 12px 12px 0;
            padding: 0 24px;
            font-weight: 600;
            background: #f59e0b;
            border: none;
            color: #fff;
            white-space: nowrap;
            transition: background .2s;
        }
        .search-box .btn-search:hover { background: #d97706; }

        /* Result cards */
        .result-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .result-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .result-card .card-top {
            background: #1A3A6E;
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .result-card .card-top .nv-label { color: #fff; font-weight: 700; font-size: .9rem; }
        .result-card .card-top .badge-status {
            font-size: .75rem;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
        }
        .info-cell {
            padding: 10px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        .info-cell:nth-child(odd) { border-right: 1px solid #f1f5f9; }
        .info-cell .label { font-size: .75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: .3px; margin-bottom: 2px; }
        .info-cell .value { font-size: .9rem; font-weight: 600; color: #1e293b; }
        .info-cell .value.highlight { color: #dc2626; font-size: 1.05rem; }
        .info-cell .value.major { color: #1A3A6E; }
        .download-bar {
            padding: 14px 20px;
            background: linear-gradient(135deg, #ecfdf5, #d1fae5);
            border-top: 2px solid #10b981;
            text-align: center;
        }
        .download-bar a {
            display: inline-block;
            padding: 8px 24px;
            font-size: .88rem;
            font-weight: 700;
            text-decoration: none;
            color: #fff;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 8px;
            transition: transform .2s, box-shadow .2s;
        }
        .download-bar a:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,0.4); color: #fff; }

        @media (max-width: 575px) {
            .info-grid { grid-template-columns: 1fr; }
            .info-cell:nth-child(odd) { border-right: none; }
            .search-hero { padding: 32px 0 44px; }
        }
    </style>
</head>
<body style="background: #f7f9fc;">

<?php include __DIR__ . '/includes/header.php'; ?>

<!-- Hero Search -->
<div class="search-hero">
    <div class="container text-center">
        <h1 class="fw-bold mb-2" style="font-size: 1.6rem; color: #1A3A6E;">
            <i class="bi bi-search me-2"></i>Tra cứu kết quả xét tuyển
        </h1>
        <p class="text-muted mb-4" style="font-size: .9rem;">Nhập số CCCD/CMND để tra cứu kết quả tuyển sinh ĐH Hạ Long 2026</p>
        <form id="searchForm" onsubmit="handleSearch(event)">
            <div class="search-box d-flex">
                <div class="position-relative flex-grow-1">
                    <i class="bi bi-person-vcard input-icon"></i>
                    <input type="text" class="form-control" id="cccdInput" placeholder="Nhập số CCCD/CMND…" required pattern="[0-9]{9,12}" title="Nhập từ 9-12 chữ số" autocomplete="off" inputmode="numeric">
                </div>
                <button class="btn btn-search" type="submit" id="btnSearch">
                    <i class="bi bi-search me-1"></i> Tra cứu
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Results Area -->
<div class="container-xl">
    <div id="loadingIndicator" class="text-center py-4 d-none" aria-live="polite">
        <div class="spinner-border text-primary" role="status" style="width: 1.5rem; height: 1.5rem;"></div>
        <span class="ms-2 text-muted small">Đang tra cứu dữ liệu…</span>
    </div>
    <div id="alertError" class="alert alert-danger border-0 shadow-sm d-none" role="alert" aria-live="polite">
        <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i><span id="errorMsg"></span>
    </div>
    <div id="resultsContainer" aria-live="polite"></div>
</div>

<div class="pb-5"></div>

<?php include __DIR__ . '/includes/footer.php'; ?>

<script>
function handleSearch(e) {
    e.preventDefault();
    const cccd = document.getElementById('cccdInput').value.trim();
    if (!cccd) return;

    document.getElementById('resultsContainer').innerHTML = '';
    document.getElementById('alertError').classList.add('d-none');
    document.getElementById('loadingIndicator').classList.remove('d-none');
    document.getElementById('btnSearch').disabled = true;

    fetch(`api_search_sheets.php?cccd=${encodeURIComponent(cccd)}`)
        .then(r => r.json())
        .then(data => {
            document.getElementById('loadingIndicator').classList.add('d-none');
            document.getElementById('btnSearch').disabled = false;
            if (data.status === 'success' && Array.isArray(data.data) && data.data.length > 0) {
                showResults(data.data);
            } else {
                showError(data.message || 'Không tìm thấy kết quả cho số CCCD này.');
            }
        })
        .catch(() => {
            document.getElementById('loadingIndicator').classList.add('d-none');
            document.getElementById('btnSearch').disabled = false;
            showError('Lỗi kết nối máy chủ. Vui lòng thử lại.');
        });
}

function showError(msg) {
    document.getElementById('errorMsg').innerText = msg;
    document.getElementById('alertError').classList.remove('d-none');
}

function showResults(items) {
    const c = document.getElementById('resultsContainer');
    let html = `<p class="text-muted small mb-3 text-center"><i class="bi bi-check-circle text-success me-1"></i>Tìm thấy <strong>${items.length}</strong> nguyện vọng</p>`;

    // === Desktop: Table (inside card like dashboard) ===
    html += `<div class="d-none d-md-block mb-3">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center py-3">
            <h6 class="mb-0 fw-bold">Kết quả tra cứu</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">#</th>
                        <th>Họ và tên</th>
                        <th>Ngày sinh</th>
                        <th>Ngành xét tuyển</th>
                        <th>Mã ngành</th>
                        <th>Trình độ</th>
                        <th>PT XT</th>
                        <th>Điểm XT</th>
                        <th>Trạng thái</th>
                        <th>Nhập học</th>
                        <th>Giấy báo</th>
                    </tr>
                </thead><tbody>`;

    items.forEach((item, i) => {
        const st = item['Trạng thái hồ sơ trên cổng bộ GD&ĐT'] || '';
        let bc = 'bg-secondary text-white';
        if (/trúng|hợp lệ/i.test(st)) bc = 'bg-success text-white';
        else if (/trượt|từ chối/i.test(st)) bc = 'bg-danger text-white';
        else if (st) bc = 'bg-warning text-dark';
        const badgeHtml = st ? `<span class="badge ${bc}">${st}</span>` : '<span class="text-muted">—</span>';

        const link = item['Link Giấy Báo Nhập Học'];
        const dlHtml = (link && link.trim())
            ? `<a href="${link}" target="_blank" class="btn btn-sm btn-success"><i class="bi bi-download"></i></a>`
            : '<span class="text-muted">—</span>';

        html += `<tr>
            <td class="ps-3 text-center fw-bold">${i + 1}</td>
            <td class="fw-semibold">${item['Họ và tên'] || ''}</td>
            <td>${item['Ngày sinh'] || ''}</td>
            <td class="fw-semibold text-brand">${item['Tên ngành'] || ''}</td>
            <td>${item['Mã ngành'] || ''}</td>
            <td>${item['Trình độ'] || ''}</td>
            <td>${item['Mã PTXT'] || ''}</td>
            <td class="text-center fw-bold text-danger">${item['Điểm xét tuyển'] || ''}</td>
            <td>${badgeHtml}</td>
            <td class="small">${item['Thời gian nhập học'] || '—'}</td>
            <td class="text-center">${dlHtml}</td>
        </tr>`;
    });
    html += `</tbody></table></div></div></div></div>`;

    // === Mobile: Cards ===
    html += `<div class="d-md-none">`;
    items.forEach((item, i) => {
        const st = item['Trạng thái hồ sơ trên cổng bộ GD&ĐT'] || '';
        let badgeHtml = '';
        if (st) {
            let bc = 'bg-warning text-dark';
            if (/trúng|hợp lệ/i.test(st)) bc = 'bg-success text-white';
            else if (/trượt|từ chối/i.test(st)) bc = 'bg-danger text-white';
            badgeHtml = `<span class="badge-status ${bc}">${st}</span>`;
        }

        const majorName = item['Tên ngành'] || `Nguyện vọng ${i + 1}`;
        const levelName = item['Trình độ'] || '';
        const titleText = levelName ? `${majorName} — ${levelName}` : majorName;
        const link = item['Link Giấy Báo Nhập Học'];
        const dlBar = (link && link.trim())
            ? `<div class="download-bar"><a href="${link}" target="_blank"><i class="bi bi-file-earmark-pdf me-1"></i>Tải Giấy Báo Nhập Học</a></div>`
            : '';

        html += `
        <div class="result-card mb-3">
            <div class="card-top">
                <span class="nv-label"><i class="bi bi-mortarboard me-1"></i>${titleText}</span>
                ${badgeHtml}
            </div>
            <div class="card-body-mobile p-3">
                <div class="row g-2 small">
                    <div class="col-5 text-muted fw-medium">Họ và tên</div>
                    <div class="col-7 fw-semibold">${item['Họ và tên'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">Ngày sinh</div>
                    <div class="col-7">${item['Ngày sinh'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">Giới tính</div>
                    <div class="col-7">${item['Giới tính'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">Mã ngành</div>
                    <div class="col-7">${item['Mã ngành'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">Điểm XT</div>
                    <div class="col-7 fw-bold text-danger">${item['Điểm xét tuyển'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">PT xét tuyển</div>
                    <div class="col-7">${item['Mã PTXT'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">KV/ĐT ưu tiên</div>
                    <div class="col-7">${(item['KV ƯT'] || '') + (item['ĐT ƯT'] ? ' - ' + item['ĐT ƯT'] : '')}</div>
                    <div class="col-5 text-muted fw-medium">Tổng điểm</div>
                    <div class="col-7">${item['Tổng điểm chưa có ƯT (Thang 30)'] || ''}</div>
                    <div class="col-5 text-muted fw-medium">Nhập học</div>
                    <div class="col-7">${item['Thời gian nhập học'] || '—'}</div>
                </div>
            </div>
            ${dlBar}
        </div>`;
    });
    html += `</div>`;

    c.innerHTML = html;
}
</script>
</body>
</html>
