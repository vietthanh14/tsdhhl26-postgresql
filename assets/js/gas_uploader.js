/**
 * GAS Uploader Module — assets/js/gas_uploader.js
 * Tải file lên Google Drive qua Google Apps Script
 * Dùng chung cho: profile.php (tài liệu), apply.php (biên lai)
 *
 * Cách dùng:
 *   GasUploader.upload({
 *     gasUrl: '...',
 *     fileInput: document.getElementById('fileInput'),
 *     filePrefix: 'DOC_D3',
 *     identitySuffix: '789012',
 *     statusEl: document.getElementById('uploadStatus'),
 *     progressEl: document.getElementById('uploadProgress'),
 *     triggerBtn: document.getElementById('uploadBtn'),
 *     acceptTypes: ['image/png', 'image/jpeg', 'application/pdf'],
 *     onSuccess: function(webViewLink) { ... },
 *     onError: function(errMsg) { ... }
 *   });
 */
window.GasUploader = (function () {
    'use strict';

    function getExtension(mimeType) {
        if (mimeType === 'application/pdf') return 'pdf';
        if (mimeType === 'image/png') return 'png';
        return 'jpg';
    }

    /**
     * Upload a single file to GAS.
     * @param {object} opts — see JSDoc above
     */
    function upload(opts) {
        const { gasUrl, fileInput, filePrefix, identitySuffix, statusEl, progressEl, triggerBtn, onSuccess, onError } = opts;

        if (!fileInput || fileInput.files.length === 0) {
            if (onError) onError('Vui lòng chọn file!');
            return;
        }
        if (!gasUrl) {
            if (onError) onError('Chưa cấu hình Google Apps Script URL!');
            return;
        }

        const file = fileInput.files[0];
        
        // Kiểm tra dung lượng file (giới hạn 30MB)
        const maxSize = 30 * 1024 * 1024; // 30MB
        if (file.size > maxSize) {
            alert('Dung lượng file vượt quá giới hạn 30MB. Vui lòng chọn file nhỏ hơn hoặc nén lại trước khi tải lên.');
            if (onError) onError('File quá lớn (>30MB).');
            return;
        }

        const reader = new FileReader();

        if (statusEl) {
            statusEl.className = 'small mt-2 text-center text-brand';
            statusEl.innerText = 'Đang tải lên...';
        }
        if (progressEl) progressEl.classList.remove('d-none');
        if (triggerBtn) triggerBtn.disabled = true;

        reader.onload = async function () {
            const base64 = reader.result.split(',')[1];

            const ext = getExtension(file.type);
            const cccd6 = (identitySuffix || '000000').replace(/\D/g, '').slice(-6);
            const safeFileName = `${filePrefix}_${cccd6}_${Date.now()}.${ext}`;

            try {
                const response = await fetch(gasUrl, {
                    method: 'POST',
                    body: JSON.stringify({ base64, fileName: safeFileName, mimeType: file.type })
                });
                const gasData = await response.json();

                if (gasData.status === 'success') {
                    if (statusEl) {
                        statusEl.className = 'small mt-2 text-center text-success fw-bold';
                        statusEl.innerText = 'Đã tải xong';
                    }
                    if (onSuccess) onSuccess(gasData.webViewLink);
                } else {
                    throw new Error(gasData.message || 'Lỗi Google Drive');
                }
            } catch (err) {
                if (statusEl) {
                    statusEl.className = 'small mt-2 text-center text-danger';
                    statusEl.innerText = 'Lỗi: ' + err.message;
                }
                if (progressEl) progressEl.classList.add('d-none');
                if (triggerBtn) triggerBtn.disabled = false;
                if (onError) onError(err.message);
            }
        };
        reader.readAsDataURL(file);
    }

    return { upload };
})();
