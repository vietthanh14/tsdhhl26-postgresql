-- ==============================================================================
-- DATABASE SCHEMA CHO HỆ THỐNG ĐĂNG KÝ TUYỂN SINH (SUPABASE POSTGRESQL)
-- KỊCH BẢN NÀY LÀ CÀI MỚI HOÀN TOÀN: SẼ XÓA TOÀN BỘ DỮ LIỆU CŨ VÀ TẠO CHUẨN LẠI!
-- ==============================================================================

-- Xóa sạch các bảng dữ liệu cũ (Xóa triệt để, cẩn thận nếu đã có data thật)
DROP TABLE IF EXISTS public.applications CASCADE;
DROP TABLE IF EXISTS public.user_documents CASCADE;
DROP TABLE IF EXISTS public.user_profiles CASCADE;
DROP TABLE IF EXISTS public.majors CASCADE;
DROP TABLE IF EXISTS public.education_levels CASCADE;
DROP TABLE IF EXISTS public.admission_periods CASCADE;
DROP TABLE IF EXISTS public.document_types CASCADE;
DROP TABLE IF EXISTS public.admission_methods CASCADE;

-- 1. Tham chiếu đến bảng auth.users của Supabase (Không cần tạo lại, chỉ note lại để join)
-- auth.users (id, email, encrypted_password, created_at, ...)

-- 2. Bảng Thông tin cá nhân của người dùng (Thí sinh)
CREATE TABLE IF NOT EXISTS public.user_profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL, -- Tên đăng nhập
    full_name VARCHAR(100) NOT NULL,
    identity_card VARCHAR(20), -- Số CMND/CCCD
    contact_email VARCHAR(255), -- Email thật để liên lạc (có thể thay đổi)
    date_of_birth DATE,
    phone_number VARCHAR(15),
    province VARCHAR(100), -- Tỉnh / Thành phố
    ward VARCHAR(100), -- Phường / Xã
    address_detail TEXT, -- Địa chỉ chi tiết (số nhà, thôn, đường...)
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 3. Bảng Quản lý các đợt/chu kỳ tuyển sinh (Admin quản lý)
CREATE TABLE IF NOT EXISTS public.admission_periods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL, -- Vd: Kì thi tuyển sinh mùa Xuân 2026
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    education_level_id INTEGER REFERENCES public.education_levels(id) ON DELETE CASCADE,
    is_active BOOLEAN DEFAULT false, -- Trạng thái Mở/Đóng
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 3.5. Bảng Mapping Đợt Xét Tuyển - Ngành Học
CREATE TABLE IF NOT EXISTS public.admission_period_majors (
    period_id INTEGER REFERENCES public.admission_periods(id) ON DELETE CASCADE,
    major_id INTEGER REFERENCES public.majors(id) ON DELETE CASCADE,
    PRIMARY KEY (period_id, major_id)
);
ALTER TABLE public.admission_period_majors ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view period_majors" ON public.admission_period_majors;
CREATE POLICY "Anyone can view period_majors" ON public.admission_period_majors FOR SELECT USING (true);

-- 4. Bảng Danh mục Cấp độ / Hệ đào tạo (Admin quản lý)
CREATE TABLE IF NOT EXISTS public.education_levels (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL, -- Vd: Đại học, Thạc sĩ, Cao đẳng
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 5. Bảng Danh mục Ngành học kèm giá (Admin quản lý)
CREATE TABLE IF NOT EXISTS public.majors (
    id SERIAL PRIMARY KEY,
    major_name VARCHAR(255) NOT NULL,
    major_code VARCHAR(50) UNIQUE, -- Mã ngành
    education_level_id INTEGER REFERENCES public.education_levels(id) ON DELETE CASCADE,
    application_fee NUMERIC(15, 2) DEFAULT 0.00, -- Lệ phí hồ sơ riêng cho ngành này
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 6. Bảng Danh mục các loại tài liệu yêu cầu (Admin quản lý)
CREATE TABLE IF NOT EXISTS public.document_types (
    id SERIAL PRIMARY KEY,
    type_name VARCHAR(150) NOT NULL, -- Vd: CCCD (Mặt trước), Học bạ THPT...
    is_required BOOLEAN DEFAULT true, -- Có bắt buộc nộp không
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 7. Bảng Quản lý File tài liệu đã tải lên của User (Liên kết vòng ngoài)
CREATE TABLE IF NOT EXISTS public.user_documents (
    id UUID DEFAULT extensions.uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    document_type_id INTEGER REFERENCES public.document_types(id) ON DELETE CASCADE,
    drive_file_url TEXT NOT NULL, -- Link lưu trữ từ Google Drive Web App
    uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

ALTER TABLE public.user_documents ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Users can view own documents" ON public.user_documents;
CREATE POLICY "Users can view own documents" ON public.user_documents FOR SELECT USING (auth.uid() = user_id);

-- 8. Bảng Quản lý Hồ sơ Đăng ký xét tuyển
CREATE TABLE IF NOT EXISTS public.applications (
    id UUID DEFAULT extensions.uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    admission_period_id INTEGER REFERENCES public.admission_periods(id) ON DELETE RESTRICT,
    major_id INTEGER REFERENCES public.majors(id) ON DELETE RESTRICT,
    admission_method_id INTEGER, 

    
    -- Lưu cứng lệ phí tại thời điểm nộp (bảo toàn dữ liệu hóa đơn)
    fee_amount NUMERIC(15, 2) NOT NULL, 
    
    -- Các trạng thái của đơn
    status VARCHAR(50) DEFAULT 'PENDING', -- PENDING, APPROVED, REJECTED
    payment_status VARCHAR(50) DEFAULT 'UNPAID', -- UNPAID, PAID, REFUNDED
    receipt_url TEXT, -- Link ảnh chụp biên lai thanh toán (qua Google Drive)
    
    submitted_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    
    -- Một user trong một đợt có thể đăng ký cùng ngành nhưng phải khác phương thức tuyển sinh
    UNIQUE(user_id, admission_period_id, major_id, admission_method_id) 
);

-- ==============================================================================
-- ROW LEVEL SECURITY (RLS) - Cấu hình bảo mật nâng cao trên Supabase
-- ==============================================================================
-- Kích hoạt RLS cho tất cả các bảng
ALTER TABLE public.user_profiles ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.user_documents ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.applications ENABLE ROW LEVEL SECURITY;

-- Chính sách: Thí sinh chỉ được xem & sửa Profile của chính mình
DROP POLICY IF EXISTS "Users can view own profile" ON public.user_profiles;
CREATE POLICY "Users can view own profile" 
ON public.user_profiles FOR SELECT USING (auth.uid() = id);

DROP POLICY IF EXISTS "Users can update own profile" ON public.user_profiles;
CREATE POLICY "Users can update own profile" 
ON public.user_profiles FOR UPDATE USING (auth.uid() = id);

DROP POLICY IF EXISTS "Users can insert own profile" ON public.user_profiles;
CREATE POLICY "Users can insert own profile" 
ON public.user_profiles FOR INSERT WITH CHECK (auth.uid() = id);

-- Chính sách: Thí sinh chỉ xem & quản lý Document của chính mình
DROP POLICY IF EXISTS "Users can view own documents" ON public.user_documents;
CREATE POLICY "Users can view own documents" 
ON public.user_documents FOR SELECT USING (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can insert own documents" ON public.user_documents;
CREATE POLICY "Users can insert own documents" 
ON public.user_documents FOR INSERT WITH CHECK (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can update own documents" ON public.user_documents;
CREATE POLICY "Users can update own documents" 
ON public.user_documents FOR UPDATE USING (auth.uid() = user_id);

-- Chính sách: Thí sinh chỉ xem & quản lý Application của chính mình
DROP POLICY IF EXISTS "Users can view own applications" ON public.applications;
CREATE POLICY "Users can view own applications" 
ON public.applications FOR SELECT USING (auth.uid() = user_id);

DROP POLICY IF EXISTS "Users can insert own applications" ON public.applications;
CREATE POLICY "Users can insert own applications" 
ON public.applications FOR INSERT WITH CHECK (auth.uid() = user_id);

-- *Lưu ý: Các bảng danh mục (majors, education_levels, etc...) cấu hình RLS cho phép Public đọc (SELECT).
ALTER TABLE public.admission_periods ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view admission periods" ON public.admission_periods;
CREATE POLICY "Anyone can view admission periods" ON public.admission_periods FOR SELECT USING (true);

ALTER TABLE public.education_levels ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view education levels" ON public.education_levels;
CREATE POLICY "Anyone can view education levels" ON public.education_levels FOR SELECT USING (true);

ALTER TABLE public.majors ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view majors" ON public.majors;
CREATE POLICY "Anyone can view majors" ON public.majors FOR SELECT USING (true);

ALTER TABLE public.document_types ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view document types" ON public.document_types;
CREATE POLICY "Anyone can view document types" ON public.document_types FOR SELECT USING (true);

ALTER TABLE public.admission_methods ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view admission methods" ON public.admission_methods;
CREATE POLICY "Anyone can view admission methods" ON public.admission_methods FOR SELECT USING (true);
-- 9. Bảng Danh mục Phương thức xét tuyển
CREATE TABLE IF NOT EXISTS public.admission_methods (
    id SERIAL PRIMARY KEY,
    method_name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- Thêm cột admission_method_id vào bảng applications nếu chưa có (Tránh lỗi khi DB đã tồn tại)
ALTER TABLE public.applications ADD COLUMN IF NOT EXISTS admission_method_id INTEGER;

-- Bổ sung khóa ngoại cho bảng applications
ALTER TABLE public.applications DROP CONSTRAINT IF EXISTS fk_admission_method;
ALTER TABLE public.applications 
ADD CONSTRAINT fk_admission_method 
FOREIGN KEY (admission_method_id) REFERENCES public.admission_methods(id) ON DELETE RESTRICT;

ALTER TABLE public.admission_methods ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view admission methods" ON public.admission_methods;
CREATE POLICY "Anyone can view admission methods" ON public.admission_methods FOR SELECT USING (true);

ALTER TABLE public.document_types ENABLE ROW LEVEL SECURITY;

-- ==============================================================================
-- DỮ LIỆU MẪU (SEED DATA)
-- ==============================================================================

-- Thêm các loại tài liệu mẫu
INSERT INTO public.document_types (type_name, is_required) VALUES 
('Ảnh chân dung (4x6)', true),
('Mặt trước CMND/CCCD', true),
('Mặt sau CMND/CCCD', true),
('Học bạ THPT (Trang 1)', true),
('Bằng tốt nghiệp (hoặc Giấy chứng nhận tạm thời)', true),
('Giấy chứng nhận ưu tiên (nếu có)', false);

-- Thêm hệ đào tạo mẫu
INSERT INTO public.education_levels (name, description) VALUES 
('Đại học Chính quy', 'Đào tạo tập trung 4 năm'),
('Liên thông Đại học', 'Dành cho người đã tốt nghiệp Cao đẳng'),
('Thạc sĩ', 'Chương trình cao học');

-- Thêm một số đợt tuyển sinh
INSERT INTO public.admission_periods (name, start_date, end_date, is_active) VALUES 
('Đợt xét tuyển đợt 1 - 2026', '2026-03-01', '2026-08-30', true);
-- Thêm danh sách ngành học
INSERT INTO public.majors (major_code, major_name, education_level_id, application_fee) VALUES 
('51140201', 'Giáo dục Mầm non', 1, 300000.00),
('6210225', 'Thanh nhạc', 1, 300000.00),
('7810101', 'Du lịch (Du lịch và dịch vụ hàng không)', 1, 300000.00),
('7810103', 'Quản trị dịch vụ du lịch và lữ hành', 1, 300000.00),
('7810201', 'Quản trị khách sạn', 1, 300000.00),
('7810202', 'Quản trị nhà hàng và dịch vụ ăn uống', 1, 300000.00),
('7340101', 'Quản trị kinh doanh', 1, 300000.00),
('7340301', 'Kế toán', 1, 300000.00),
('7220201', 'Ngôn ngữ Anh', 1, 300000.00),
('7220204', 'Ngôn ngữ Trung Quốc', 1, 300000.00),
('7220209', 'Ngôn ngữ Nhật', 1, 300000.00),
('7220210', 'Ngôn ngữ Hàn Quốc', 1, 300000.00),
('7229042', 'Quản lý văn hóa', 1, 300000.00),
('7480201', 'Công nghệ thông tin', 1, 300000.00),
('7480101', 'Khoa học máy tính', 1, 300000.00),
('7210403', 'Thiết kế đồ họa', 1, 300000.00),
('7140201', 'Giáo dục Mầm non', 1, 300000.00),
('7140202', 'Giáo dục Tiểu học', 1, 300000.00),
('7140217', 'Sư phạm Ngữ văn', 1, 300000.00),
('7140247', 'Sư phạm Khoa học tự nhiên', 1, 300000.00),
('7140231', 'Sư phạm Tiếng Anh', 1, 300000.00),
('7140210', 'Sư phạm Tin học', 1, 300000.00),
('7140209', 'Sư phạm Toán học', 1, 300000.00),
('7229030', 'Văn học (Văn báo chí truyền thông)', 1, 300000.00),
('7850101', 'Quản lý tài nguyên và môi trường', 1, 300000.00),
('7620301', 'Nuôi trồng thủy sản', 1, 300000.00),
('7140221', 'Sư phạm Âm nhạc', 1, 300000.00),
('8220201', 'Ngôn ngữ Anh', 3, 500000.00);

-- Thêm các phương thức xét tuyển mẫu
INSERT INTO public.admission_methods (method_name, description) VALUES 
('Xét kết quả thi tốt nghiệp THPT', 'Dựa trên tổ hợp môn đăng ký xét tuyển'),
('Xét kết quả học tập cấp THPT (Học bạ)', 'Dựa trên điểm trung bình 3 năm hoặc học kỳ 1 lớp 12'),
('Xét tuyển thẳng', 'Dành cho thí sinh đạt giải quốc gia, quốc tế hoặc có chứng chỉ ngoại ngữ');

-- Thêm các loại tài liệu minh chứng cơ bản
INSERT INTO public.document_types (type_name, is_required) VALUES 
('Bản sao Thẻ căn cước công dân (CCCD)', true),
('Học bạ THPT (Bản sao công chứng)', true),
('Giấy chứng nhận tốt nghiệp THPT tạm thời hoặc Bằng TN', true),
('Giấy chứng nhận kết quả thi tốt nghiệp THPT', false),
('Giấy tờ ưu tiên (Nếu có)', false);

-- ==============================================================================
-- MIGRATION: Thêm cột địa danh vào user_profiles (chạy nếu bảng đã tồn tại)
-- ==============================================================================
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS province VARCHAR(100);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS ward VARCHAR(100);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS address_detail TEXT;
ALTER TABLE public.user_profiles DROP COLUMN IF EXISTS address;
