<?php
/**
 * Component tái sử dụng cho Input Address (Tỉnh/Thành phố & Phường/Xã)
 * 
 * Các biến yêu cầu trước khi include:
 * @var string $ac_id_prefix       Prefix dùng cho ID HTML (ex: '', 'school')
 * @var string $ac_name_prefix     Prefix dùng cho Name attribute (ex: '', 'school_')
 * @var string $ac_label_province  Tiêu đề Label cho Tỉnh (ex: 'Tỉnh / Thành phố')
 * @var string $ac_label_ward      Tiêu đề Label cho Phường (ex: 'Phường / Xã')
 */

$id_prov = $ac_id_prefix ? $ac_id_prefix . 'Province' : 'province';
$id_ward = $ac_id_prefix ? $ac_id_prefix . 'Ward' : 'ward';
$name_prov = $ac_name_prefix . 'province';
$name_ward = $ac_name_prefix . 'ward';
?>
<div class="col-md-6">
    <label class="form-label text-muted fw-bold"><?php echo htmlspecialchars($ac_label_province); ?></label>
    <div class="combo-wrapper" id="<?php echo htmlspecialchars($id_prov); ?>Wrapper">
        <span class="combo-clear" id="<?php echo htmlspecialchars($id_prov); ?>Clear" title="Xóa">&times;</span>
        <input type="text" class="combo-input" id="<?php echo htmlspecialchars($id_prov); ?>Input"
            placeholder="-- Chọn Tỉnh/Thành phố --" readonly>
        <div class="combo-dropdown" id="<?php echo htmlspecialchars($id_prov); ?>Dropdown">
            <div class="combo-search">
                <input type="text" id="<?php echo htmlspecialchars($id_prov); ?>Search"
                    placeholder="🔍 Tìm tỉnh/thành..." autocomplete="off">
            </div>
            <div id="<?php echo htmlspecialchars($id_prov); ?>List"></div>
        </div>
    </div>
    <input type="hidden" name="<?php echo htmlspecialchars($name_prov); ?>_name" id="<?php echo htmlspecialchars($id_prov); ?>Name">
    <input type="hidden" name="<?php echo htmlspecialchars($name_prov); ?>_code" id="<?php echo htmlspecialchars($id_prov); ?>Code">
</div>
<div class="col-md-6 mt-3 mt-md-0">
    <label class="form-label text-muted fw-bold"><?php echo htmlspecialchars($ac_label_ward); ?></label>
    <div class="combo-wrapper" id="<?php echo htmlspecialchars($id_ward); ?>Wrapper">
        <span class="combo-clear" id="<?php echo htmlspecialchars($id_ward); ?>Clear" title="Xóa">&times;</span>
        <input type="text" class="combo-input" id="<?php echo htmlspecialchars($id_ward); ?>Input"
            placeholder="-- Chọn Phường/Xã --" readonly disabled>
        <div class="combo-dropdown" id="<?php echo htmlspecialchars($id_ward); ?>Dropdown">
            <div class="combo-search">
                <input type="text" id="<?php echo htmlspecialchars($id_ward); ?>Search"
                    placeholder="🔍 Tìm phường/xã..." autocomplete="off">
            </div>
            <div id="<?php echo htmlspecialchars($id_ward); ?>List"></div>
        </div>
    </div>
    <input type="hidden" name="<?php echo htmlspecialchars($name_ward); ?>_name" id="<?php echo htmlspecialchars($id_ward); ?>Name">
    <input type="hidden" name="<?php echo htmlspecialchars($name_ward); ?>_code" id="<?php echo htmlspecialchars($id_ward); ?>Code">
</div>
