<!-- Modal: View User Detail -->
<div class="modal fade" id="viewUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-brand text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-lines-fill me-2"></i>Chi tiết Thí sinh</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="viewUserBody">
                <!-- JS will populate -->
            </div>
            <div class="modal-footer border-0 p-3">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Đóng</button>
                <button type="button" class="btn btn-brand px-4" id="btnViewToEdit"><i class="bi bi-pencil-square me-1"></i>Chỉnh sửa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-brand text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Chỉnh sửa Thông tin Thí sinh</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" name="user_id" id="edit_user_id">

                <!-- Section 1: Thông tin định danh -->
                <div class="section-title"><i class="bi bi-person-vcard me-2"></i>Thông tin định danh</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Họ và Tên <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">CMND/CCCD</label>
                        <input type="text" name="identity_card" id="edit_identity_card" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngày sinh</label>
                        <input type="date" name="date_of_birth" id="edit_date_of_birth" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Giới tính</label>
                        <select name="gender" id="edit_gender" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Nam">Nam</option>
                            <option value="Nữ">Nữ</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Dân tộc</label>
                        <input type="text" name="ethnicity" id="edit_ethnicity" class="form-control">
                    </div>
                </div>

                <!-- Section 2: Liên hệ -->
                <div class="section-title"><i class="bi bi-telephone me-2"></i>Thông tin liên hệ</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Email liên hệ</label>
                        <input type="email" name="contact_email" id="edit_contact_email" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Số điện thoại</label>
                        <input type="text" name="phone_number" id="edit_phone_number" class="form-control">
                    </div>
                </div>

                <!-- Section 3: Địa chỉ thường trú -->
                <div class="section-title"><i class="bi bi-house-door me-2"></i>Địa chỉ thường trú</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tỉnh / Thành phố</label>
                        <div class="combo-wrapper" id="editProvinceWrapper">
                            <span class="combo-clear" id="editProvinceClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editProvinceInput" placeholder="-- Chọn Tỉnh/TP --" readonly>
                            <div class="combo-dropdown" id="editProvinceDropdown">
                                <div class="combo-search"><input type="text" id="editProvinceSearch" placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off"></div>
                                <div id="editProvinceList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="province" id="edit_province">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Phường / Xã</label>
                        <div class="combo-wrapper" id="editWardWrapper">
                            <span class="combo-clear" id="editWardClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editWardInput" placeholder="-- Chọn Phường/Xã --" readonly disabled>
                            <div class="combo-dropdown" id="editWardDropdown">
                                <div class="combo-search"><input type="text" id="editWardSearch" placeholder="🔍 Tìm phường/xã..." autocomplete="off"></div>
                                <div id="editWardList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="ward" id="edit_ward">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Địa chỉ chi tiết</label>
                        <input type="text" name="address_detail" id="edit_address_detail" class="form-control">
                    </div>
                </div>

                <!-- Section 4: Trường THPT -->
                <div class="section-title"><i class="bi bi-mortarboard me-2"></i>Trường THPT</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tên trường THPT</label>
                        <input type="text" name="school_name" id="edit_school_name" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Tỉnh (Trường)</label>
                        <div class="combo-wrapper" id="editSchoolProvinceWrapper">
                            <span class="combo-clear" id="editSchoolProvinceClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editSchoolProvinceInput" placeholder="-- Chọn Tỉnh/TP --" readonly>
                            <div class="combo-dropdown" id="editSchoolProvinceDropdown">
                                <div class="combo-search"><input type="text" id="editSchoolProvinceSearch" placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off"></div>
                                <div id="editSchoolProvinceList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="school_province" id="edit_school_province">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Phường/Xã (Trường)</label>
                        <div class="combo-wrapper" id="editSchoolWardWrapper">
                            <span class="combo-clear" id="editSchoolWardClear" title="Xóa">&times;</span>
                            <input type="text" class="combo-input" id="editSchoolWardInput" placeholder="-- Chọn Phường/Xã --" readonly disabled>
                            <div class="combo-dropdown" id="editSchoolWardDropdown">
                                <div class="combo-search"><input type="text" id="editSchoolWardSearch" placeholder="🔍 Tìm phường/xã..." autocomplete="off"></div>
                                <div id="editSchoolWardList"></div>
                            </div>
                        </div>
                        <input type="hidden" name="school_ward" id="edit_school_ward">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Địa chỉ chi tiết (Trường)</label>
                        <input type="text" name="school_address_detail" id="edit_school_address_detail" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Năm tốt nghiệp</label>
                        <input type="number" name="graduation_year" id="edit_graduation_year" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Học lực lớp 12</label>
                        <select name="academic_performance" id="edit_academic_performance" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Xuất sắc">Xuất sắc(Tốt)</option>
                            <option value="Giỏi">Giỏi(Tốt)</option>
                            <option value="Khá">Khá(Khá)</option>
                            <option value="Trung bình">Trung bình(Đạt)</option>
                            <option value="Yếu">Yếu(Chưa đạt)</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Hạnh kiểm lớp 12</label>
                        <select name="conduct" id="edit_conduct" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Tốt">Tốt(Tốt)</option>
                            <option value="Khá">Khá(Khá)</option>
                            <option value="Trung bình">Trung bình(Đạt)</option>
                            <option value="Yếu">Yếu(Chưa đạt)</option>
                        </select>
                    </div>
                </div>

                <!-- Section 5: Ưu tiên -->
                <div class="section-title"><i class="bi bi-star me-2"></i>Ưu tiên</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Khu vực ưu tiên</label>
                        <select name="priority_area" id="edit_priority_area" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="KV1">KV1</option>
                            <option value="KV2">KV2</option>
                            <option value="KV2-NT">KV2-NT</option>
                            <option value="KV3">KV3</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Đối tượng ưu tiên</label>
                        <select name="priority_object" id="edit_priority_object" class="form-select">
                            <option value="">-- Không có --</option>
                            <option value="01">01 - Dân tộc thiểu số (KV1)</option>
                            <option value="02">02 - CN sản xuất ưu tú</option>
                            <option value="03">03 - Thương binh, Quân/CA</option>
                            <option value="04">04 - Con liệt sĩ, Con TB/BB (≥81%)</option>
                            <option value="05">05 - TNXP, Quân/CA xuất ngũ</option>
                            <option value="06">06 - DTTS ngoài KV1</option>
                            <option value="07">07 - Người KT nặng, LĐ/Nhà giáo/YT XS</option>
                        </select>
                    </div>
                </div>

                <!-- Section 6: Đã tốt nghiệp -->
                <div class="section-title"><i class="bi bi-award me-2"></i>Đã tốt nghiệp (nếu có)</div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Trình độ đã TN</label>
                        <select name="prev_degree_level" id="edit_prev_degree_level" class="form-select">
                            <option value="">-- Chưa có --</option>
                            <option value="Trung cấp">Trung cấp</option>
                            <option value="Cao đẳng">Cao đẳng</option>
                            <option value="Đại học">Đại học</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngành đã TN</label>
                        <input type="text" name="prev_major" id="edit_prev_major" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Xếp loại TN</label>
                        <select name="prev_graduation_rank" id="edit_prev_graduation_rank" class="form-select">
                            <option value="">-- Chọn --</option>
                            <option value="Xuất sắc">Xuất sắc</option>
                            <option value="Giỏi">Giỏi</option>
                            <option value="Khá">Khá</option>
                            <option value="Trung bình khá">Trung bình khá</option>
                            <option value="Trung bình">Trung bình</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngày trúng tuyển</label>
                        <input type="date" name="prev_admission_date" id="edit_prev_admission_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Ngày tốt nghiệp</label>
                        <input type="date" name="prev_graduation_date" id="edit_prev_graduation_date" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Bằng TN do trường cấp</label>
                        <input type="text" name="prev_diploma_school" id="edit_prev_diploma_school" class="form-control">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold small text-muted">Cấp ngày</label>
                        <input type="date" name="prev_diploma_date" id="edit_prev_diploma_date" class="form-control">
                    </div>
                </div>

                <!-- Section 7: Công tác hiện tại -->
                <div class="section-title"><i class="bi bi-briefcase me-2"></i>Công tác hiện tại</div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Chức vụ</label>
                        <input type="text" name="current_position" id="edit_current_position" class="form-control">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold small text-muted">Cơ quan công tác</label>
                        <input type="text" name="current_workplace" id="edit_current_workplace" class="form-control">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-brand px-4"><i class="bi bi-check-lg me-1"></i>Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Reset Password -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <div class="modal-header bg-warning border-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key me-2"></i>Cấp lại Mật khẩu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">

                <div class="alert alert-info border-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Đặt mật khẩu mới cho thí sinh: <strong id="reset_user_name"></strong>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted">Mật khẩu mới <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6" placeholder="Tối thiểu 6 ký tự">
                        <button class="btn btn-outline-secondary password-toggle" type="button" onclick="togglePassword()">
                            <i class="bi bi-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generatePassword()">
                        <i class="bi bi-shuffle me-1"></i>Tạo mật khẩu ngẫu nhiên
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-warning px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>Xác nhận đổi mật khẩu</button>
            </div>
        </form>
    </div>
</div>
