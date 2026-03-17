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

    // 1. Hỏi người dùng nhập dòng bắt đầu và kết thúc
    const lastRow = sheet.getLastRow();
    const rangePrompt = ui.prompt(
        'Tạo Giấy Báo Nhập Học (Tối Ưu)',
        `Nhập dòng bắt đầu và kết thúc (ví dụ: 2-10).\nTổng số dòng hiện có: ${lastRow}.`,
        ui.ButtonSet.OK_CANCEL
    );

    if (rangePrompt.getSelectedButton() !== ui.Button.OK) return;

    const input = rangePrompt.getResponseText().trim();
    const parts = input.split('-');

    let startRow = parseInt(parts[0]);
    let endRow = parts.length > 1 ? parseInt(parts[1]) : startRow;

    if (isNaN(startRow) || startRow < 1) startRow = 2;
    if (isNaN(endRow) || endRow > lastRow) endRow = lastRow;
    if (startRow > endRow) {
        ui.alert('Lỗi', 'Dòng bắt đầu không thể lớn hơn dòng kết thúc.', ui.ButtonSet.OK);
        return;
    }

    // 2. Chuẩn bị tài nguyên
    const templateFile = DriveApp.getFileById(CONFIG.TEMPLATE_ID);
    const destinationFolder = DriveApp.getFolderById(CONFIG.FOLDER_ID);
    const outputLinks = [];

    SpreadsheetApp.getActiveSpreadsheet().toast(`Đang xử lý ${endRow - startRow + 1} hồ sơ...`, 'Hệ thống', -1);

    // Lấy toàn bộ dữ liệu cần xử lý 1 lần (Tối ưu: tránh gọi API nhiều lần)
    const numRows = endRow - startRow + 1;
    const dataValues = sheet.getRange(startRow, 1, numRows, 20).getValues();

    // 3. Duyệt qua từng dòng và xử lý
    for (let i = 0; i < numRows; i++) {
        const rowData = dataValues[i];
        const currentRowIndex = startRow + i;

        try {
            const hoTen = rowData[2]; // Cột C
            const cccd  = rowData[5]; // Cột F

            if (!hoTen || !cccd) {
                console.log(`Dòng ${currentRowIndex}: Bỏ qua do thiếu Họ tên hoặc CCCD.`);
                outputLinks.push(['']);
                continue;
            }

            const pdfName = `GiayBao_${cccd}_${convertTiengViet(hoTen)}`;

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

            outputLinks.push([pdfFile.getUrl()]);
            tempFile.setTrashed(true); // Xóa file Doc tạm

            if (i % 5 === 0) {
                SpreadsheetApp.getActiveSpreadsheet().toast(`Đang xử lý dòng ${currentRowIndex}...`, 'Tiến độ');
            }

        } catch (e) {
            console.error(`Lỗi tại dòng ${currentRowIndex}: ${e.toString()}`);
            outputLinks.push([`Lỗi: ${e.toString()}`]);
        }
    }

    // 4. Ghi toàn bộ link vào Sheet 1 lần (Batch Write - Nhanh nhất)
    if (outputLinks.length > 0) {
        sheet.getRange(startRow, CONFIG.LINK_COL_INDEX, outputLinks.length, 1).setValues(outputLinks);
        SpreadsheetApp.flush();
    }

    ui.alert('Hoàn tất', `Đã xử lý xong ${numRows} hồ sơ.`, ui.ButtonSet.OK);
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
        if (value instanceof Date) {
            value = Utilities.formatDate(value, Session.getScriptTimeZone(), 'dd/MM/yyyy');
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
