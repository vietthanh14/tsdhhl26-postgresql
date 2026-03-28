-- WARNING: This schema is for context only and is not meant to be run.
-- Table order and constraints may not be valid for execution.

CREATE TABLE public.admission_methods (
  id integer NOT NULL DEFAULT nextval('admission_methods_id_seq'::regclass),
  method_name character varying NOT NULL,
  description text,
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  application_fee numeric DEFAULT 0.00,
  CONSTRAINT admission_methods_pkey PRIMARY KEY (id)
);
CREATE TABLE public.admission_period_major_methods (
  period_id integer NOT NULL,
  major_id integer NOT NULL,
  method_id integer NOT NULL,
  CONSTRAINT admission_period_major_methods_pkey PRIMARY KEY (period_id, major_id, method_id),
  CONSTRAINT admission_period_major_methods_period_id_fkey FOREIGN KEY (period_id) REFERENCES public.admission_periods(id),
  CONSTRAINT admission_period_major_methods_major_id_fkey FOREIGN KEY (major_id) REFERENCES public.majors(id),
  CONSTRAINT admission_period_major_methods_method_id_fkey FOREIGN KEY (method_id) REFERENCES public.admission_methods(id),
  CONSTRAINT admission_period_major_methods_period_id_major_id_fkey FOREIGN KEY (period_id) REFERENCES public.admission_period_majors(period_id),
  CONSTRAINT admission_period_major_methods_period_id_major_id_fkey FOREIGN KEY (major_id) REFERENCES public.admission_period_majors(period_id),
  CONSTRAINT admission_period_major_methods_period_id_major_id_fkey FOREIGN KEY (period_id) REFERENCES public.admission_period_majors(major_id),
  CONSTRAINT admission_period_major_methods_period_id_major_id_fkey FOREIGN KEY (major_id) REFERENCES public.admission_period_majors(major_id)
);
CREATE TABLE public.admission_period_majors (
  period_id integer NOT NULL,
  major_id integer NOT NULL,
  CONSTRAINT admission_period_majors_pkey PRIMARY KEY (period_id, major_id),
  CONSTRAINT admission_period_majors_period_id_fkey FOREIGN KEY (period_id) REFERENCES public.admission_periods(id),
  CONSTRAINT admission_period_majors_major_id_fkey FOREIGN KEY (major_id) REFERENCES public.majors(id)
);
CREATE TABLE public.admission_periods (
  id integer NOT NULL DEFAULT nextval('admission_periods_id_seq'::regclass),
  name character varying NOT NULL,
  start_date date NOT NULL,
  end_date date NOT NULL,
  is_active boolean DEFAULT false,
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  education_level_id integer,
  CONSTRAINT admission_periods_pkey PRIMARY KEY (id),
  CONSTRAINT admission_periods_education_level_id_fkey FOREIGN KEY (education_level_id) REFERENCES public.education_levels(id)
);
CREATE TABLE public.applications (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  user_id uuid,
  admission_period_id integer,
  major_id integer,
  admission_method_id integer,
  fee_amount numeric NOT NULL,
  status character varying DEFAULT 'PENDING'::character varying,
  payment_status character varying DEFAULT 'UNPAID'::character varying,
  submitted_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  updated_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  receipt_url text,
  priority integer DEFAULT 1,
  admin_notes text DEFAULT ''::text,
  CONSTRAINT applications_pkey PRIMARY KEY (id),
  CONSTRAINT applications_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id),
  CONSTRAINT applications_admission_period_id_fkey FOREIGN KEY (admission_period_id) REFERENCES public.admission_periods(id),
  CONSTRAINT applications_major_id_fkey FOREIGN KEY (major_id) REFERENCES public.majors(id),
  CONSTRAINT fk_admission_method FOREIGN KEY (admission_method_id) REFERENCES public.admission_methods(id)
);
CREATE TABLE public.document_types (
  id integer NOT NULL DEFAULT nextval('document_types_id_seq'::regclass),
  type_name character varying NOT NULL,
  is_required boolean DEFAULT true,
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  CONSTRAINT document_types_pkey PRIMARY KEY (id)
);
CREATE TABLE public.education_levels (
  id integer NOT NULL DEFAULT nextval('education_levels_id_seq'::regclass),
  name character varying NOT NULL UNIQUE,
  description text,
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  CONSTRAINT education_levels_pkey PRIMARY KEY (id)
);
CREATE TABLE public.majors (
  id integer NOT NULL DEFAULT nextval('majors_id_seq'::regclass),
  major_name character varying NOT NULL,
  major_code character varying,
  education_level_id integer,
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  zalo_link text,
  CONSTRAINT majors_pkey PRIMARY KEY (id),
  CONSTRAINT majors_education_level_id_fkey FOREIGN KEY (education_level_id) REFERENCES public.education_levels(id)
);
CREATE TABLE public.user_documents (
  id uuid NOT NULL DEFAULT uuid_generate_v4(),
  user_id uuid,
  document_type_id integer,
  drive_file_url text NOT NULL,
  uploaded_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  CONSTRAINT user_documents_pkey PRIMARY KEY (id),
  CONSTRAINT user_documents_user_id_fkey FOREIGN KEY (user_id) REFERENCES auth.users(id),
  CONSTRAINT user_documents_document_type_id_fkey FOREIGN KEY (document_type_id) REFERENCES public.document_types(id)
);
CREATE TABLE public.user_profiles (
  id uuid NOT NULL,
  username character varying NOT NULL UNIQUE,
  full_name character varying NOT NULL,
  identity_card character varying,
  contact_email character varying,
  date_of_birth date,
  phone_number character varying,
  created_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  updated_at timestamp with time zone NOT NULL DEFAULT timezone('utc'::text, now()),
  province character varying,
  ward character varying,
  address_detail text,
  gender character varying,
  ethnicity character varying,
  school_name character varying,
  school_province character varying,
  school_ward character varying,
  priority_area character varying,
  academic_performance character varying,
  conduct character varying,
  graduation_year integer,
  priority_object character varying,
  prev_degree_level character varying,
  prev_major character varying,
  prev_admission_date date,
  prev_graduation_date date,
  prev_graduation_rank character varying,
  prev_diploma_school character varying,
  prev_diploma_date date,
  current_position character varying,
  current_workplace text,
  CONSTRAINT user_profiles_pkey PRIMARY KEY (id),
  CONSTRAINT user_profiles_id_fkey FOREIGN KEY (id) REFERENCES auth.users(id)
);