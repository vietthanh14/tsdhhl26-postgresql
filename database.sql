-- ==============================================================================
-- DATABASE SCHEMA CHO HỆ THỐNG ĐĂNG KÝ TUYỂN SINH (SUPABASE POSTGRESQL)
-- Phiên bản: 18.3.2026v3
-- ==============================================================================

-- Xóa sạch các bảng dữ liệu cũ (theo thứ tự phụ thuộc)
DROP TABLE IF EXISTS public.admission_period_major_methods CASCADE;
DROP TABLE IF EXISTS public.admission_period_majors CASCADE;
DROP TABLE IF EXISTS public.applications CASCADE;
DROP TABLE IF EXISTS public.user_documents CASCADE;
DROP TABLE IF EXISTS public.user_profiles CASCADE;
DROP TABLE IF EXISTS public.admission_periods CASCADE;
DROP TABLE IF EXISTS public.majors CASCADE;
DROP TABLE IF EXISTS public.education_levels CASCADE;
DROP TABLE IF EXISTS public.document_types CASCADE;
DROP TABLE IF EXISTS public.admission_methods CASCADE;

-- ==============================================================================
-- TẠO BẢNG (theo đúng thứ tự phụ thuộc)
-- ==============================================================================

-- 1. Bảng Thông tin cá nhân (Thí sinh)
CREATE TABLE IF NOT EXISTS public.user_profiles (
    id UUID REFERENCES auth.users(id) ON DELETE CASCADE PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    identity_card VARCHAR(20),
    contact_email VARCHAR(255),
    date_of_birth DATE,
    phone_number VARCHAR(15),
    gender VARCHAR(10),
    ethnicity VARCHAR(50),
    province VARCHAR(100),
    ward VARCHAR(100),
    address_detail TEXT,
    school_name VARCHAR(255),
    school_province VARCHAR(100),
    school_ward VARCHAR(100),
    school_address_detail TEXT,
    priority_area VARCHAR(50),
    academic_performance VARCHAR(20),
    conduct VARCHAR(20),
    graduation_year INTEGER,
    priority_object VARCHAR(10),
    prev_degree_level VARCHAR(20),
    prev_major VARCHAR(255),
    prev_admission_date DATE,
    prev_graduation_date DATE,
    prev_graduation_rank VARCHAR(50),
    prev_diploma_school VARCHAR(255),
    prev_diploma_date DATE,
    current_position VARCHAR(255),
    current_workplace TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 2. Bảng Hệ Đào Tạo (phải tạo TRƯỚC admission_periods và majors)
CREATE TABLE IF NOT EXISTS public.education_levels (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 3. Bảng Ngành Học (phụ thuộc education_levels)
CREATE TABLE IF NOT EXISTS public.majors (
    id SERIAL PRIMARY KEY,
    major_name VARCHAR(255) NOT NULL,
    major_code VARCHAR(50),
    education_level_id INTEGER REFERENCES public.education_levels(id) ON DELETE CASCADE,
    application_fee NUMERIC(15, 2) DEFAULT 0.00,
    zalo_link TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    -- Cho phép cùng mã ngành ở các hệ khác nhau
    CONSTRAINT majors_code_level_unique UNIQUE (major_code, education_level_id)
);

-- 4. Bảng Đợt Tuyển Sinh (phụ thuộc education_levels)
CREATE TABLE IF NOT EXISTS public.admission_periods (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    education_level_id INTEGER REFERENCES public.education_levels(id) ON DELETE CASCADE,
    is_active BOOLEAN DEFAULT false,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 5. Bảng Phương Thức Xét Tuyển
CREATE TABLE IF NOT EXISTS public.admission_methods (
    id SERIAL PRIMARY KEY,
    method_name VARCHAR(255) NOT NULL,
    description TEXT,
    application_fee NUMERIC(15, 2) DEFAULT 0.00,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 6. Bảng Mapping Đợt - Ngành (phụ thuộc admission_periods + majors)
CREATE TABLE IF NOT EXISTS public.admission_period_majors (
    period_id INTEGER REFERENCES public.admission_periods(id) ON DELETE CASCADE,
    major_id INTEGER REFERENCES public.majors(id) ON DELETE CASCADE,
    PRIMARY KEY (period_id, major_id)
);

-- 7. Bảng Mapping Đợt - Ngành - Phương thức
CREATE TABLE IF NOT EXISTS public.admission_period_major_methods (
    id SERIAL PRIMARY KEY,
    period_id INTEGER REFERENCES public.admission_periods(id) ON DELETE CASCADE,
    major_id INTEGER REFERENCES public.majors(id) ON DELETE CASCADE,
    method_id INTEGER REFERENCES public.admission_methods(id) ON DELETE CASCADE,
    UNIQUE (period_id, major_id, method_id)
);

-- 8. Bảng Loại Tài Liệu
CREATE TABLE IF NOT EXISTS public.document_types (
    id SERIAL PRIMARY KEY,
    type_name VARCHAR(150) NOT NULL,
    is_required BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 9. Bảng Tài Liệu Đã Upload
CREATE TABLE IF NOT EXISTS public.user_documents (
    id UUID DEFAULT extensions.uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    document_type_id INTEGER REFERENCES public.document_types(id) ON DELETE CASCADE,
    drive_file_url TEXT NOT NULL,
    uploaded_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL
);

-- 10. Bảng Hồ Sơ Đăng Ký Xét Tuyển
CREATE TABLE IF NOT EXISTS public.applications (
    id UUID DEFAULT extensions.uuid_generate_v4() PRIMARY KEY,
    user_id UUID REFERENCES auth.users(id) ON DELETE CASCADE,
    admission_period_id INTEGER REFERENCES public.admission_periods(id) ON DELETE RESTRICT,
    major_id INTEGER REFERENCES public.majors(id) ON DELETE RESTRICT,
    admission_method_id INTEGER REFERENCES public.admission_methods(id) ON DELETE RESTRICT,
    priority INTEGER DEFAULT 1,
    fee_amount NUMERIC(15, 2) NOT NULL,
    status VARCHAR(50) DEFAULT 'PENDING',
    payment_status VARCHAR(50) DEFAULT 'UNPAID',
    receipt_url TEXT,
    submitted_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT timezone('utc'::text, now()) NOT NULL,
    UNIQUE(user_id, admission_period_id, major_id, admission_method_id)
);

-- ==============================================================================
-- INDEXES (Tối ưu hiệu suất truy vấn)
-- ==============================================================================
CREATE INDEX IF NOT EXISTS idx_applications_user_id ON public.applications(user_id);
CREATE INDEX IF NOT EXISTS idx_applications_period_id ON public.applications(admission_period_id);
CREATE INDEX IF NOT EXISTS idx_applications_status ON public.applications(status);
CREATE INDEX IF NOT EXISTS idx_user_documents_user_id ON public.user_documents(user_id);
CREATE INDEX IF NOT EXISTS idx_majors_level_id ON public.majors(education_level_id);
CREATE INDEX IF NOT EXISTS idx_periods_active_end ON public.admission_periods(is_active, end_date);
CREATE INDEX IF NOT EXISTS idx_user_profiles_username ON public.user_profiles(username);

-- ==============================================================================
-- ROW LEVEL SECURITY (RLS)
-- ==============================================================================

-- user_profiles: Thí sinh chỉ xem/sửa profile của mình
ALTER TABLE public.user_profiles ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Users can view own profile" ON public.user_profiles;
CREATE POLICY "Users can view own profile" ON public.user_profiles FOR SELECT USING (auth.uid() = id);
DROP POLICY IF EXISTS "Users can update own profile" ON public.user_profiles;
CREATE POLICY "Users can update own profile" ON public.user_profiles FOR UPDATE USING (auth.uid() = id);
DROP POLICY IF EXISTS "Users can insert own profile" ON public.user_profiles;
CREATE POLICY "Users can insert own profile" ON public.user_profiles FOR INSERT WITH CHECK (auth.uid() = id);

-- user_documents: Thí sinh chỉ xem/quản lý tài liệu của mình
ALTER TABLE public.user_documents ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Users can view own documents" ON public.user_documents;
CREATE POLICY "Users can view own documents" ON public.user_documents FOR SELECT USING (auth.uid() = user_id);
DROP POLICY IF EXISTS "Users can insert own documents" ON public.user_documents;
CREATE POLICY "Users can insert own documents" ON public.user_documents FOR INSERT WITH CHECK (auth.uid() = user_id);
DROP POLICY IF EXISTS "Users can update own documents" ON public.user_documents;
CREATE POLICY "Users can update own documents" ON public.user_documents FOR UPDATE USING (auth.uid() = user_id);

-- applications: Thí sinh chỉ xem/quản lý hồ sơ của mình
ALTER TABLE public.applications ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Users can view own applications" ON public.applications;
CREATE POLICY "Users can view own applications" ON public.applications FOR SELECT USING (auth.uid() = user_id);
DROP POLICY IF EXISTS "Users can insert own applications" ON public.applications;
CREATE POLICY "Users can insert own applications" ON public.applications FOR INSERT WITH CHECK (auth.uid() = user_id);

-- Bảng danh mục: Public đọc
ALTER TABLE public.education_levels ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view education levels" ON public.education_levels;
CREATE POLICY "Anyone can view education levels" ON public.education_levels FOR SELECT USING (true);

ALTER TABLE public.majors ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view majors" ON public.majors;
CREATE POLICY "Anyone can view majors" ON public.majors FOR SELECT USING (true);

ALTER TABLE public.admission_periods ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view admission periods" ON public.admission_periods;
CREATE POLICY "Anyone can view admission periods" ON public.admission_periods FOR SELECT USING (true);

ALTER TABLE public.admission_methods ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view admission methods" ON public.admission_methods;
CREATE POLICY "Anyone can view admission methods" ON public.admission_methods FOR SELECT USING (true);

ALTER TABLE public.document_types ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view document types" ON public.document_types;
CREATE POLICY "Anyone can view document types" ON public.document_types FOR SELECT USING (true);

ALTER TABLE public.admission_period_majors ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view period_majors" ON public.admission_period_majors;
CREATE POLICY "Anyone can view period_majors" ON public.admission_period_majors FOR SELECT USING (true);

ALTER TABLE public.admission_period_major_methods ENABLE ROW LEVEL SECURITY;
DROP POLICY IF EXISTS "Anyone can view period_major_methods" ON public.admission_period_major_methods;
CREATE POLICY "Anyone can view period_major_methods" ON public.admission_period_major_methods FOR SELECT USING (true);

-- ==============================================================================
-- DỮ LIỆU MẪU (SEED DATA)
-- ==============================================================================

-- Hệ đào tạo
INSERT INTO public.education_levels (name, description) VALUES
('Đại học Chính quy', 'Đào tạo tập trung 4 năm'),
('Liên thông Đại học', 'Dành cho người đã tốt nghiệp Cao đẳng'),
('Thạc sĩ', 'Chương trình cao học');

-- Phương thức xét tuyển
INSERT INTO public.admission_methods (method_name, description) VALUES
('Xét kết quả thi tốt nghiệp THPT', 'Dựa trên tổ hợp môn đăng ký xét tuyển'),
('Xét kết quả học tập cấp THPT (Học bạ)', 'Dựa trên điểm trung bình 3 năm hoặc học kỳ 1 lớp 12'),
('Xét tuyển thẳng', 'Dành cho thí sinh đạt giải quốc gia, quốc tế hoặc có chứng chỉ ngoại ngữ');

-- Loại tài liệu (chỉ 1 lần, không trùng)
INSERT INTO public.document_types (type_name, is_required) VALUES
('Ảnh chân dung (4x6)', true),
('Bản sao Thẻ căn cước công dân (CCCD)', true),
('Học bạ THPT (Bản sao công chứng)', true),
('Giấy chứng nhận tốt nghiệp THPT hoặc Bằng TN', true),
('Giấy chứng nhận kết quả thi tốt nghiệp THPT', false),
('Giấy tờ ưu tiên (Nếu có)', false);

-- Ngành học mẫu
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

-- Đợt tuyển sinh mẫu
INSERT INTO public.admission_periods (name, start_date, end_date, education_level_id, is_active) VALUES
('Đợt xét tuyển đợt 1 - 2026', '2026-03-01', '2026-08-30', 1, true);

-- ==============================================================================
-- MIGRATION HELPERS (chạy trên DB đã có data, không phá dữ liệu)
-- ==============================================================================

-- Thêm cột thiếu (an toàn nếu đã tồn tại)
ALTER TABLE public.majors ADD COLUMN IF NOT EXISTS zalo_link TEXT;
ALTER TABLE public.applications ADD COLUMN IF NOT EXISTS priority INTEGER DEFAULT 1;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS province VARCHAR(100);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS ward VARCHAR(100);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS address_detail TEXT;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS gender VARCHAR(10);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS ethnicity VARCHAR(50);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS school_name VARCHAR(255);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS school_province VARCHAR(100);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS school_ward VARCHAR(100);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS school_address_detail TEXT;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS priority_area VARCHAR(50);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS academic_performance VARCHAR(20);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS conduct VARCHAR(20);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS graduation_year INTEGER;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS priority_object VARCHAR(10);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_degree_level VARCHAR(20);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_major VARCHAR(255);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_admission_date DATE;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_graduation_date DATE;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_graduation_rank VARCHAR(50);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_diploma_school VARCHAR(255);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS prev_diploma_date DATE;
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS current_position VARCHAR(255);
ALTER TABLE public.user_profiles ADD COLUMN IF NOT EXISTS current_workplace TEXT;

-- Chuyển lệ phí từ ngành sang phương thức xét tuyển (v24.3.2026)
ALTER TABLE public.admission_methods ADD COLUMN IF NOT EXISTS application_fee NUMERIC(15,2) DEFAULT 0.00;

-- Reset sequences (fix lỗi trùng ID sau insert thủ công)
SELECT setval(pg_get_serial_sequence('education_levels', 'id'), (SELECT COALESCE(MAX(id), 0) FROM education_levels));
SELECT setval(pg_get_serial_sequence('majors', 'id'), (SELECT COALESCE(MAX(id), 0) FROM majors));
SELECT setval(pg_get_serial_sequence('admission_periods', 'id'), (SELECT COALESCE(MAX(id), 0) FROM admission_periods));
SELECT setval(pg_get_serial_sequence('admission_methods', 'id'), (SELECT COALESCE(MAX(id), 0) FROM admission_methods));
SELECT setval(pg_get_serial_sequence('document_types', 'id'), (SELECT COALESCE(MAX(id), 0) FROM document_types));
