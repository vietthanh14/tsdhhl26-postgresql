        </div><!-- /.main-content -->
    </div><!-- /.row -->
</div><!-- /.container-fluid -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Confirm Delete (dùng chung) -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-octagon me-2"></i> Xác nhận Xóa</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 text-center">
                <i class="bi bi-trash text-danger mb-3 d-block" style="font-size: 3rem;"></i>
                <p class="mb-0 fs-5" id="confirmDeleteMsg">Bạn có chắc chắn muốn xóa mục này?</p>
                <p class="text-muted small mt-2">Hành động này không thể hoàn tác.</p>
            </div>
            <div class="modal-footer border-0 justify-content-center pb-4">
                <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Hủy bỏ</button>
                <button type="button" class="btn btn-danger px-4" id="btnConfirmDelete">Xóa</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999;"></div>

<script>
// --- Shared: Confirm Delete ---
let deleteFormToSubmit = null;
let confirmDeleteModalInstance = null;
document.addEventListener("DOMContentLoaded", function() {
    confirmDeleteModalInstance = new bootstrap.Modal(document.getElementById('confirmDeleteModal'));
    document.getElementById('btnConfirmDelete').addEventListener('click', function() {
        if (deleteFormToSubmit) {
            deleteFormToSubmit.submit();
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Đang xóa...';
            this.classList.add('disabled');
        }
    });
});
function confirmDelete(formElement, message) {
    deleteFormToSubmit = formElement;
    document.getElementById('confirmDeleteMsg').innerText = message;
    confirmDeleteModalInstance.show();
}

// --- Shared: Filter Table ---
function filterTable(query, tbodyId) {
    const tbody = document.getElementById(tbodyId);
    if (!tbody) return;
    const rows = tbody.querySelectorAll('tr');
    const q = query.toLowerCase().trim();
    rows.forEach(row => {
        row.style.display = q === '' || row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}

// --- Shared: Toast ---
function showToast(msg, type) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    const id = 'toast_' + Date.now();
    const bgClass = type === 'warning' ? 'bg-warning text-dark' : 'bg-danger text-white';
    const icon = type === 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill';
    container.innerHTML = `<div id="${id}" class="toast align-items-center border-0 ${bgClass}" role="alert">
        <div class="d-flex"><div class="toast-body fw-semibold"><i class="bi ${icon} me-2"></i>${msg}</div>
        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast"></button></div></div>`;
    new bootstrap.Toast(document.getElementById(id), {delay: 3000}).show();
}
</script>
<?php include __DIR__ . '/../../includes/footer.php'; ?>
</body>
</html>
