# 📋 TEST CASES — Hệ thống Tuyển sinh ĐH Hạ Long

> **URL gốc:** `http://localhost/tsdhhl26`
> **Cách đánh dấu:** `[x]` = PASS, `[!]` = FAIL (ghi chú lỗi bên cạnh)
> **Lưu ý:** Nhấn `Ctrl+Shift+R` trước khi test để xóa cache CSS/JS.

---

## A. XÁC THỰC (Auth)

### A1. Đăng ký tài khoản mới
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/auth/register.php` | Form hiển thị đầy đủ: Họ tên, Ngày sinh, SĐT, Email, CCCD, Username, Password | [ ] |
| 2 | Điền đầy đủ → Đăng ký | Thành công, chuyển về trang đăng nhập | [ ] |
| 3 | Đăng ký trùng username | Hiện thông báo lỗi "đã tồn tại" | [ ] |
| 4 | Bỏ trống trường bắt buộc → Đăng ký | Form báo lỗi validation | [ ] |

### A2. Đăng nhập Thí sinh
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/auth/login.php` | Form đăng nhập hiển thị | [ ] |
| 2 | Nhập đúng username/password → Đăng nhập | Chuyển về `/candidate/index.php` | [ ] |
| 3 | Nhập sai password → Đăng nhập | Hiện thông báo lỗi | [ ] |

### A3. Đăng nhập Admin
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/login.php` | Form đăng nhập hiển thị | [ ] |
| 2 | Nhập đúng admin/password → Đăng nhập | Chuyển về `/admin/index.php` | [ ] |

### A4. Auth Guard (Bảo vệ trang)
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Chưa login → vào `/candidate/index.php` | Redirect về `/auth/login.php` | [ ] |
| 2 | Chưa login → vào `/admin/index.php` | Redirect về `/admin/login.php` | [ ] |

### A5. Đăng xuất
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Đang login thí sinh → Click Đăng xuất | Về trang chủ, session bị xóa | [ ] |
| 2 | Đang login admin → Click Đăng xuất | Về trang chủ | [ ] |

### A6. Quên mật khẩu
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/auth/forgot_password.php` | Form khôi phục hiển thị | [ ] |

---

## B. CỔNG THÍ SINH (Candidate Portal)

### B1. Dashboard
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/candidate/index.php` | Hiện bảng điều khiển + sidebar + danh sách hồ sơ | [ ] |
| 2 | Kiểm tra sidebar | Menu "Bảng điều khiển" đang active (highlight) | [ ] |
| 3 | Kiểm tra sidebar | Có menu "Thông tin cá nhân" | [ ] |
| 4 | Kiểm tra sidebar | "Đăng Ký Xét Tuyển" mở ra danh sách 5 hệ | [ ] |

### B2. Thông tin cá nhân
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Click "Thông tin cá nhân" trong sidebar | Chuyển đến `/candidate/profile.php` | [ ] |
| 2 | Kiểm tra form | Hiện: Họ tên, CCCD, Email, SĐT, Ngày sinh, Giới tính, Dân tộc, Tỉnh, Phường, Địa chỉ | [ ] |
| 3 | Chọn Tỉnh | Dropdown Phường/Xã load dữ liệu tương ứng | [ ] |
| 4 | Điền đủ → Lưu thay đổi | Thông báo "Lưu thành công" | [ ] |
| 5 | Reload trang | Dữ liệu vừa lưu được khôi phục đúng | [ ] |

### B3. Đăng ký — Hệ Đại học Chính quy
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Click "Hệ Đại học Chính quy" trong sidebar | URL = `/candidate/apply_university.php` | [ ] |
| 2 | Kiểm tra bên phải | **Panel "Thông tin hệ đào tạo"** hiển thị mô tả Đại học | [ ] |
| 3 | Bước 1: Chọn Đợt Tuyển Sinh | Dropdown ngành load qua AJAX | [ ] |
| 4 | Chọn ngành → Tiếp tục | Chuyển sang Bước 2 | [ ] |
| 5 | Bước 2: Kiểm tra thông tin cá nhân | Hiện đúng dữ liệu đã lưu ở Profile | [ ] |
| 6 | Bước 3: Chọn ngành + phương thức | Hiện lệ phí xét tuyển | [ ] |
| 7 | Bước 4: QR Code | VietQR hiển thị đúng số tài khoản + số tiền | [ ] |

### B4. Đăng ký — Hệ Cao đẳng
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Click "Hệ Cao đẳng Chính quy" | URL = `/candidate/apply_college.php` | [ ] |
| 2 | Panel phải hiện thông tin Cao đẳng | ✅ | [ ] |

### B5. Đăng ký — Hệ Thạc sĩ
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Click "Hệ Thạc sĩ" | URL = `/candidate/apply_master.php` | [ ] |
| 2 | Panel phải hiện thông tin Thạc sĩ | ✅ | [ ] |

### B6. Đăng ký — Hệ Trung cấp
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Click "Hệ Trung Cấp" | URL = `/candidate/apply_vocational.php` | [ ] |
| 2 | Panel phải hiện thông tin Trung cấp | ✅ | [ ] |

### B7. Đăng ký — Hệ Văn bằng 2 ⚠️
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Click "Hệ Văn bằng 2, VLVH" trong sidebar | URL = `/candidate/apply_degree2.php` (⚠️ **KHÔNG PHẢI** `apply.php?level_id=5`) | [ ] |
| 2 | **Panel phải hiện thông tin Văn bằng 2** | Card "Thông tin hệ đào tạo" với mô tả VB2 | [ ] |

### B8. Kiểm tra trùng hồ sơ
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Nộp hồ sơ trùng ngành + phương thức + đợt | Cảnh báo trùng, không cho nộp | [ ] |

### B9. Quản lý nguyện vọng
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Dashboard → Danh sách hồ sơ | Hiện đúng: ngành, hệ, trạng thái, lệ phí | [ ] |
| 2 | Kéo thả thay đổi thứ tự nguyện vọng | Thứ tự cập nhật thành công | [ ] |
| 3 | Badge trạng thái | Đúng màu: Vàng=Chờ duyệt, Xanh=Đã duyệt, Đỏ=Từ chối | [ ] |

---

## C. CỔNG QUẢN TRỊ (Admin Portal)

### C1. Dashboard
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/index.php` | Hiện tổng hồ sơ + tài khoản thí sinh | [ ] |
| 2 | Sidebar đầy đủ menu | Bảng điều khiển, Cấu hình (Đợt/Hệ/Ngành/PT/TL), Hồ sơ, Tài liệu, Thí sinh | [ ] |

### C2. Quản lý Hệ Đào Tạo
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/manage_levels.php` | Bảng hệ đào tạo hiển thị | [ ] |
| 2 | Thêm hệ mới | Thêm thành công, hiện trong bảng | [ ] |
| 3 | Sửa tên hệ | Cập nhật thành công | [ ] |
| 4 | Xóa hệ (không có ngành) | Xóa thành công | [ ] |

### C3. Quản lý Ngành Học
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/manage_majors.php` | Bảng ngành: Mã, Tên, Hệ, Lệ phí, Zalo | [ ] |
| 2 | Thêm ngành mới | Thêm thành công | [ ] |
| 3 | Sửa ngành | Cập nhật thành công | [ ] |
| 4 | Tìm kiếm ngành | Lọc đúng kết quả | [ ] |
| 5 | Cập nhật lệ phí hàng loạt | Tất cả lệ phí được cập nhật | [ ] |

### C4. Quản lý Đợt Tuyển Sinh
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/manage_periods.php` | Bảng đợt tuyển sinh hiển thị | [ ] |
| 2 | Thêm đợt mới + gán ngành | Thêm thành công | [ ] |
| 3 | Sửa / Đóng / Sao chép / Xóa đợt | Thao tác thành công | [ ] |

### C5. Quản lý Phương thức XT
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/manage_methods.php` | Bảng phương thức hiển thị | [ ] |
| 2 | Thêm / Sửa / Xóa phương thức | CRUD hoạt động | [ ] |

### C6. Quản lý Hồ sơ
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/applications.php` | Bảng hồ sơ thí sinh hiển thị | [ ] |
| 2 | Duyệt hồ sơ | Trạng thái → "Đã duyệt" (xanh) | [ ] |
| 3 | Từ chối hồ sơ | Trạng thái → "Từ chối" (đỏ) | [ ] |
| 4 | Xuất CSV | File tải về đúng dữ liệu | [ ] |
| 5 | Xuất Google Sheets | Dữ liệu đẩy lên Sheets | [ ] |

### C7. Quản lý Thí sinh & Tài liệu
| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/admin/users.php` | Danh sách thí sinh hiển thị | [ ] |
| 2 | Vào `/admin/documents.php` | Danh sách tài liệu tải lên | [ ] |

---

## D. TRANG CÔNG KHAI (Public)

| # | Bước thực hiện | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Vào `/index.php` (trang chủ) | Header + Nội dung + Footer đầy đủ | [ ] |
| 2 | Vào `/search.php` (tra cứu) | Form tra cứu hiển thị | [ ] |
| 3 | Nhập thông tin tra cứu | Kết quả hiển thị đúng | [ ] |

---

## E. GIAO DIỆN (UI / Layout)

### E1. Sidebar — Desktop
| # | Kiểm tra | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Admin sidebar | Không có gap/khoảng trắng giữa header và sidebar | [ ] |
| 2 | Admin sidebar | Menu Cấu hình collapse/expand đúng | [ ] |
| 3 | Admin sidebar | Active state highlight đúng trang đang xem | [ ] |
| 4 | Candidate sidebar | Cấu trúc giống admin | [ ] |
| 5 | Candidate sidebar | Không có viền xanh bên trái | [ ] |
| 6 | Candidate sidebar | Menu "Thông tin cá nhân" hiển thị | [ ] |

### E2. Sidebar — Mobile ⚠️
| # | Kiểm tra | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Admin: Thu nhỏ trình duyệt < 768px | Desktop sidebar ẩn, nút ☰ xuất hiện ở header | [ ] |
| 2 | Admin: Click nút ☰ | **Offcanvas sidebar trượt ra từ bên trái, nền xanh đậm** | [ ] |
| 3 | Admin: Click nút X | Offcanvas đóng lại | [ ] |
| 4 | Candidate: Thu nhỏ trình duyệt < 768px | Tương tự admin — nút ☰ hiện, click mở offcanvas | [ ] |
| 5 | Candidate: Offcanvas menu | Đầy đủ link, nền xanh đậm, các hệ đào tạo hiện đúng | [ ] |

### E3. Header & Footer
| # | Kiểm tra | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Header | Logo + Tên trường hiển thị trên mọi trang | [ ] |
| 2 | Header (đã login) | Dropdown: Tên user + Đăng xuất | [ ] |
| 3 | Header (chưa login) | Nút Đăng nhập + Đăng ký | [ ] |
| 4 | Footer | Hiện ở tất cả trang | [ ] |

### E4. Responsive chung
| # | Kiểm tra | Kết quả mong đợi | ✅ |
|---|---|---|---|
| 1 | Bảng dữ liệu admin trên mobile | Không bị tràn ngang, có scroll | [ ] |
| 2 | Form đăng ký trên mobile | Layout 1 cột, dễ điền | [ ] |
| 3 | Dashboard thí sinh trên mobile | Card hồ sơ hiển thị gọn gàng | [ ] |

---

## Tổng hợp kết quả

| Module | Tổng test | Pass | Fail |
|--------|-----------|------|------|
| A. Xác thực | 12 | | |
| B. Cổng Thí sinh | 24 | | |
| C. Cổng Quản trị | 17 | | |
| D. Trang công khai | 3 | | |
| E. Giao diện | 16 | | |
| **TỔNG** | **72** | | |

> **Ngày test:** ___/___/2026
> **Người test:** ________________
> **Ghi chú thêm:**
