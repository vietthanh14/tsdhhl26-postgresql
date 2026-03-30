/**
 * CẤU HÌNH HỆ THỐNG
 * Thay đổi các ID bên dưới tương ứng với file của bạn.
 */
const CONFIG = {
    TEMPLATE_ID: '1saBi-GbNpu98LV0IsSx0wvVAXE2S4Epplklgcq_hq8U', // ID file Google Doc mẫu
    FOLDER_ID: '1SGo2LnmBsEiZDkEAGmmQE-B4zjjoJOeU',           // ID thư mục lưu file PDF
    SHEET_NAME: 'DSTT',                                       // Tên Sheet chứa dữ liệu
    LINK_COL_INDEX: 20,                                       // Cột T là cột thứ 20
    SET_PUBLIC_PERMISSION: false                              // Đặt true để set quyền public cho từng file (chậm hơn).
    // Đặt false nếu Folder đã được share public (nhanh hơn).
};

/**
 * Tạo menu "Tuyển sinh" trên thanh công cụ khi mở file Sheet.
 */
function onOpen() {
    const ui = SpreadsheetApp.getUi();
    ui.createMenu('Tuyển sinh')
        .addItem('Tạo Giấy Báo Nhập Học (PDF) - Tối Ưu', 'createBulkAdmissionLetters')
        .addToUi();
}

/**
 * Hàm chính để tạo giấy báo hàng loạt (Phiên bản Tối ưu Tốc độ).
 */
function createBulkAdmissionLetters() {
    const ui = SpreadsheetApp.getUi();
    const sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(CONFIG.SHEET_NAME);

    if (!sheet) {
        ui.alert('Lỗi', `Không tìm thấy sheet tên "${CONFIG.SHEET_NAME}"`, ui.ButtonSet.OK);
        return;
    }

    const lastRow = sheet.getLastRow();
    if (lastRow < 2) {
        ui.alert('Thông báo', 'Sheet hiện chưa có dữ liệu nào.', ui.ButtonSet.OK);
        return;
    }

    // 1. Hỏi người dùng nhập dòng bắt đầu và kết thúc / hoặc các dòng chỉ định
    const rangePrompt = ui.prompt(
        'Tạo Giấy Báo Nhập Học (Tối Ưu)',
        `Cú pháp chọn dòng:\n- Khoảng liền nhau: Nhập 2-10\n- Dòng rời rạc: Nhập 3, 6, 10\nTổng số dòng hiện có: ${lastRow}.`,
        ui.ButtonSet.OK_CANCEL
    );

    if (rangePrompt.getSelectedButton() !== ui.Button.OK) return;

    const input = rangePrompt.getResponseText().trim();
    const targetRows = [];

    // Phân tích input của người dùng (dấu phẩy hoặc dấu gạch ngang)
    if (input.includes(',')) {
        // Nhập danh sách: 3, 6, 10
        const parts = input.split(',');
        for (let p of parts) {
            const r = parseInt(p.trim());
            if (!isNaN(r) && r > 1 && r <= lastRow && !targetRows.includes(r)) {
                targetRows.push(r);
            }
        }
        targetRows.sort((a, b) => a - b); // Sắp xếp tăng dần
    } else if (input.includes('-')) {
        // Nhập khoảng: 2-10
        const parts = input.split('-');
        let startRow = parseInt(parts[0]);
        let endRow = parts.length > 1 ? parseInt(parts[1]) : startRow;
        
        if (isNaN(startRow) || startRow < 1) startRow = 2;
        if (isNaN(endRow) || endRow > lastRow) endRow = lastRow;
        if (startRow > endRow) {
            ui.alert('Lỗi', 'Dòng bắt đầu không thể lớn hơn dòng kết thúc.', ui.ButtonSet.OK);
            return;
        }
        
        for (let r = startRow; r <= endRow; r++) {
            targetRows.push(r);
        }
    } else {
        // Chỉ nhập 1 số
        const r = parseInt(input);
        if (!isNaN(r) && r > 1 && r <= lastRow) {
            targetRows.push(r);
        }
    }

    if (targetRows.length === 0) {
        ui.alert('Lỗi', 'Không có dòng hợp lệ nào được chọn để xử lý.', ui.ButtonSet.OK);
        return;
    }

    // 2. Chuẩn bị tài nguyên
    const templateFile = DriveApp.getFileById(CONFIG.TEMPLATE_ID);
    const destinationFolder = DriveApp.getFolderById(CONFIG.FOLDER_ID);

    SpreadsheetApp.getActiveSpreadsheet().toast(`Đang xử lý ${targetRows.length} hồ sơ...`, 'Hệ thống', -1);

    // Lấy toàn bộ dữ liệu Sheet 1 lần để quét cho tối ưu
    const allValues = sheet.getRange(1, 1, lastRow, 20).getDisplayValues();

    // 3. Duyệt qua từng dòng mục tiêu và xử lý
    const startTime = Date.now();
    let processedCount = 0;

    for (let i = 0; i < targetRows.length; i++) {
        const currentRowIndex = targetRows[i];
        const rowData = allValues[currentRowIndex - 1]; // array index bắt đầu từ 0

        // --- CƠ CHẾ AN TOÀN CHỐNG CRASH TIMEOUT ---
        if (Date.now() - startTime >= 288000) {
            ui.alert(
                'Tạm ngưng an toàn (Giới hạn Google)', 
                `Google chỉ cho phép chạy tối đa 6 phút mỗi lần.\nMới tạo PDF xong đến dòng ${currentRowIndex - 1}.\n\n✅ Dữ liệu đã tạo được ghi an toàn.\n⚠️ Lần chạy tiếp theo, anh/chị chú ý nhập các dòng còn lại!`, 
                ui.ButtonSet.OK
            );
            break;
        }

        try {
            const hoTen = rowData[2];   // Cột C (index = 2)
            const cccd  = rowData[5];   // Cột F (index = 5)
            const maNganh = rowData[14] || ''; // Cột O (index = 14)

            if (!hoTen || !cccd) {
                console.log(`Dòng ${currentRowIndex}: Bỏ qua do thiếu Họ tên hoặc CCCD.`);
                continue;
            }

            // Gắn thêm Mã ngành vào tên file PDF để phân biệt dứt điểm nếu 1 người đậu nhiều ngành
            const pdfName = `GiayBao_${cccd}_${maNganh}_${convertTiengViet(hoTen)}`.replace(/_+/g, '_');

            // a. Tạo bản sao tạm của Google Doc template
            const tempFile = templateFile.makeCopy(`TEMP_${pdfName}`, destinationFolder);
            const tempDoc  = DocumentApp.openById(tempFile.getId());

            // b. Thay thế placeholders
            replacePlaceholders(tempDoc.getBody(), rowData);
            tempDoc.saveAndClose();

            // c. Convert sang PDF
            const pdfBlob = tempFile.getAs(MimeType.PDF);
            pdfBlob.setName(pdfName + '.pdf');
            const pdfFile = destinationFolder.createFile(pdfBlob);

            if (CONFIG.SET_PUBLIC_PERMISSION) {
                pdfFile.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
            }

            // Ghi lại URL vào Sheet trực tiếp cho dòng đó
            sheet.getRange(currentRowIndex, CONFIG.LINK_COL_INDEX).setValue(pdfFile.getUrl());
            tempFile.setTrashed(true); // Xóa file Doc tạm

            if (i % 5 === 0) {
                SpreadsheetApp.getActiveSpreadsheet().toast(`Đang xử lý dòng ${currentRowIndex}...`, 'Tiến độ');
            }

        } catch (e) {
            console.error(`Lỗi tại dòng ${currentRowIndex}: ${e.toString()}`);
            sheet.getRange(currentRowIndex, CONFIG.LINK_COL_INDEX).setValue(`Lỗi: ${e.toString()}`);
        }
        
        processedCount++;
    }

    // Xóa Flush (đẩy lệnh setValues tồn đọng lên Google Sheet)
    SpreadsheetApp.flush();

    if (processedCount === targetRows.length) {
        ui.alert('Hoàn tất trọn vẹn', `Đã tự động tạo và lưu xong ${targetRows.length} hồ sơ.`, ui.ButtonSet.OK);
    }
}

/**
 * Thay thế các placeholder trong Doc bằng dữ liệu từ Sheet.
 * Mapping theo thứ tự cột: A=0, B=1, ..., T=19
 */
function replacePlaceholders(body, rowData) {
    const mapping = {
        '{{STT}}':           0,
        '{{TrangThai}}':     1,
        '{{HoTen}}':         2,
        '{{NgaySinh}}':      3,
        '{{GioiTinh}}':      4,
        '{{CCCD}}':          5,
        '{{KhuVuc}}':        6,
        '{{DoiTuong}}':      7,
        '{{Tinh}}':          8,
        '{{MaPTXT}}':        9,
        '{{MaTHM}}':        10,
        '{{DiemUuTien}}':   11,
        '{{TongDiemSo}}':   12,
        '{{DiemXetTuyen}}': 13,
        '{{MaNganh}}':      14,
        '{{MaHoSo}}':       15,
        '{{TenNganh}}':     16,
        '{{HeDaoTao}}':     17,
        '{{ThoiGian}}':     18,
        '{{DiaDiem}}':      19
    };

    for (const [placeholder, index] of Object.entries(mapping)) {
        let value = rowData[index];
        // Dự phòng nếu vô tình bị parse thành Date, ép chuẩn múi giờ Việt Nam
        if (value instanceof Date) {
            value = Utilities.formatDate(value, "Asia/Ho_Chi_Minh", "dd/MM/yyyy");
        }
        body.replaceText(placeholder, value != null ? value.toString() : '');
    }
}

/**
 * Chuyển tên tiếng Việt có dấu sang không dấu để đặt tên file.
 */
function convertTiengViet(str) {
    str = str.toLowerCase();
    str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g, 'a');
    str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g, 'e');
    str = str.replace(/ì|í|ị|ỉ|ĩ/g, 'i');
    str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g, 'o');
    str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g, 'u');
    str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g, 'y');
    str = str.replace(/đ/g, 'd');
    str = str.replace(/ /g, '_');
    return str;
}
