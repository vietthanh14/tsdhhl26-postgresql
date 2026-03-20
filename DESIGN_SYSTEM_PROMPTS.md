# DESIGN SYSTEM PROMPTS & UI GUIDELINES

Tài liệu này cung cấp các prompt mẫu (Prompts) và hệ thống quy tắc thiêt kế (Design System Rules) dành riêng cho AI Agents, AI Assistants (như Claude, GPT, Gemini) và các Developers khi xây dựng, chỉnh sửa UI trong dự án **TSDHHL26**.

Mục tiêu cốt lõi: **ĐỒNG NHẤT TOÀN BỘ GIAO DIỆN**.

---

## 1. MÀU SẮC CHỦ ĐẠO (Brand Colors)
Hệ thống sử dụng các biến CSS chuẩn trong `public.css` thay vì mã màu Hex cứng.

- 🔵 **Primary / Brand (`var(--brand)`)**: `#1A3A6E` (Xanh dương đậm)
  👉 *Class CSS*: `.bg-brand`, `.text-brand`, `.btn-brand`, `.border-brand`
- ⚪ **Background (`var(--bg)`)**: `#f7f9fc` (Xám nhạt / Trắng sữa)
  👉 *Dùng cho*: Màu nền của trang web (Body, Main Content).
- 🟡 **Accent / Warning (`#e6b800`)**: Vàng nghệ.
  👉 *Dùng cho*: Border-bottom Header/Footer, Icon nổi bật, các nút cập nhật nhanh (`btn-warning`).

> ⛔ **TUYỆT ĐỐI TRÁNH**: 
> - Màu `btn-primary` mặc định của Bootstrap (vì màu xanh đó không khớp với `--brand` của trường).
> - Sử dụng mã HEX cứng (`color: #...`) trong code HTML inline.

---

## 2. TYPOGRAPHY & ICONOGRAPHY (Kiểu chữ & Biểu tượng)
- **Font-family**: `Inter`, sans-serif.
- **Biểu tượng (Icons)**: Bắt buộc dùng **Bootstrap Icons 1.11+** (`<i class="bi bi-tên-icon"></i>`).
- **Khoảng cách Icon**: Luôn dùng class `me-1` hoặc `me-2` sau icon để cách chữ.
  *Ví dụ:* `<i class="bi bi-trash me-2"></i> Xóa`
- **Trọng lượng chữ (Font-weight)**:
  - Text thường: `fw-normal` (400)
  - Text nút bấm / Label: `fw-medium` (500) hoặc `fw-semibold` (600)
  - Tiêu đề (Headings): `fw-bold` (700)

---

## 3. CƠ CẤU LAYOUT CHUẨN (Spacing & Layout)
Tất cả layout dựa trên Grid 12 cột và Utilities của **Bootstrap 5.3**.

- **Thẻ chứa nội dung (Card/Container)**: 
  Sử dụng combo chuẩn để tạo khối trắng nội dung:
  ```html
  <div class="bg-white p-4 rounded-3 shadow-sm border-0 mb-4">...</div>
  ```
- **Flexbox (Căn chỉnh nhóm phần tử)**:
  Luôn ưu tiên Flexbox với Gap, hạn chế dùng Margin/Padding thủ công.
  ```html
  <!-- Khối dạt 2 bên (Space-between), căn giữa dọc (Align-center) -->
  <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-3">...</div>
  ```

---

## 4. UI COMPONENTS CHUẨN

### A. Nút bấm (Buttons)
- Nút chính (Lưu, Thêm mới, Xác nhận): `btn btn-brand`
- Nút phụ (Hủy, Đóng): `btn btn-light` hoặc `btn btn-outline-secondary`
- Nút sửa (Edit): `btn btn-sm btn-outline-warning`
- Nút xóa (Delete): `btn btn-sm btn-outline-danger`

### B. Input / Form (Biểu mẫu)
- Các trường Input bắt buộc có: `class="form-control"` hoặc `form-select`.
- Nhãn (Label): `<label class="form-label">Tên trường</label>`.
- Khối nhập liệu: Đặt trong `div.mb-3`.

### C. Tables (Bảng dữ liệu)
Cấu trúc chuẩn, có hover, và tiêu đề có màu nhạt:
```html
<div class="table-responsive bg-white p-4 rounded-3 shadow-sm border-0">
    <table class="table table-hover align-middle mb-0">
        <thead class="table-light"><tr>...</tr></thead>
        <tbody><tr>...</tr></tbody>
    </table>
</div>
```

---

## 5. UI/UX CHO MOBILE (Responsive)
- **Mobile First**: Cỡ chữ, khoảng cách luôn ưu tiên sự gọn nhẹ trên màn hình nhỏ.
- **Kích thước chạm (Touch target)**: Nút bấm trên Mobile phải đạt min-height `44px` (ngoại trừ các nút `btn-sm` trong table action).
- **Sidebar Admin**: Trên mobile, Admin Sidebar bị ẩn (`d-none d-md-block`) và chuyển sang dùng cấu trúc **Offcanvas** mở ra từ nút Menu góc trái.

---

## 6. 🔥 COPY & PASTE PROMPTS (Dành cho AI)
Khi yêu cầu AI viết code trang mới, hãy **dán nguyên khối Prompt dưới đây** vào đầu câu hỏi:

```markdown
Vui lòng thiết kế màn hình/component này tuân thủ các quy tắc Design System của dự án TSDHHL26 (Bootstrap 5.3):
1. Dùng biến CSS hệ thống: Tránh viết CSS inline (`style="..."`). KHÔNG dùng `btn-primary`, hãy dùng `btn-brand` / `text-brand` / `bg-brand` cho màu chính.
2. Thẻ chứa (Container): Áp dụng chuẩn `<div class="bg-white p-4 rounded-3 shadow-sm border-0 mb-4">` để làm nền cho các form/table.
3. Bảng (Tables): Phải có class `table-hover align-middle mb-0` và `thead` dùng class `table-light`.
4. Buttons & Icons: Phím chức năng chính dùng `btn-brand`. Icon dùng `Bootstrap Icons` (kèm `me-1` hoặc `me-2`). Phím Sửa dùng `btn-outline-warning`, Xóa dùng `btn-outline-danger`.
5. Bố cục Flexbox: Sử dụng `d-flex gap-X align-items-center` để canh lề, không dùng margin tĩnh lộn xộn.
6. Responsive mượt mà trên Mobile (ưu tiên Table-responsive và cột xếp chồng).
Cảm ơn!
```

---

## 7. QUY TẮC ANTI-PATTERNS (QUY TẮC CẤM)
1. **CẤM** sử dụng CSS inline cứng (`style="margin-top: 50px; color: blue;"`). Thay vào đó dùng Bootstrap Utility classes (`mt-4`, `text-primary`).
2. **CẤM** tạo gap / khoảng trắng dư thừa do sử dụng sai hệ thống position (Ví dụ: Header và Sidebar phải kết dính lý tưởng `top: 61px`).
3. **CẤM** việc lơ là cấu trúc Header - Sidebar - Footer. Khi viết file, tuyệt đối đóng đủ tag `</div>` (`main-content`, `row`, `container-fluid`).
4. **CẤM** dùng màu mặc định sặc sỡ, lòe loẹt làm vỡ tinh thần giao diện học thuật/trường học (Thương hiệu: Hạ Long University).
