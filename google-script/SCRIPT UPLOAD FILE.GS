/**
 * GOOGLE APPS SCRIPT: FILE UPLOAD HANDLER
 * 
 * Hướng dẫn triển khai:
 * 1. Truy cập: script.google.com
 * 2. Tạo Project mới, dán mã này vào.
 * 3. Thay folderId bên dưới bằng ID thư mục Google Drive của bạn.
 * 4. Nhấn 'Deploy' -> 'New Deployment'.
 * 5. Chọn 'Web App'.
 * 6. Set 'Execute as': Me.
 * 7. Set 'Who has access': Anyone.
 * 8. Copy URL Web App dán vào file .env (GAS_WEBAPP_URL).
 */

var folderId = "1eoGhazohGEs6UWZAVm5QLjXbuc4DUgzA";

function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var base64String = data.base64;
    var fileName = data.fileName;
    var mimeType = data.mimeType;
    
    // Giải mã Base64
    var bytes = Utilities.base64Decode(base64String);
    var blob = Utilities.newBlob(bytes, mimeType, fileName);
    
    // Lưu vào Drive
    var folder = DriveApp.getFolderById(folderId);
    var file = folder.createFile(blob);
    
    // Cho phép bất kỳ ai có link đều xem được (Public Read)
    try {
      file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
    } catch (sharingError) {
      // Bỏ qua lỗi nếu Google Workspace của tổ chức chặn Share Public. 
      // Lời khuyên: Anh/chị nên Share Public cái Folder ngay từ trên Google Drive luôn để file tự kế thừa.
    }
    
    return ContentService.createTextOutput(JSON.stringify({
      "status": "success",
      "downloadUrl": file.getDownloadUrl(),
      "webViewLink": file.getUrl(),
      "fileId": file.getId()
    })).setMimeType(ContentService.MimeType.JSON);
    
  } catch (f) {
    return ContentService.createTextOutput(JSON.stringify({
      "status": "error",
      "message": f.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  return ContentService.createTextOutput("GAS Web App is running. Use POST to upload files.");
}