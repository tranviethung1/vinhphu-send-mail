<?php $__env->startSection('title', ($mailList->name ?? 'Mail List') . ' — Sửa'); ?>

<?php $__env->startSection('page-breadcrumb'); ?>
<nav aria-label="breadcrumb" style="display:flex;align-items:center;gap:0;line-height:1.2;margin-top:0.35rem;">
    <ol style="display:flex;align-items:center;gap:0;list-style:none;margin:0;padding:0;flex-wrap:wrap;">
        <li style="display:flex;align-items:center;">
            <a href="<?php echo e(route('facilities.index')); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                Cơ sở
            </a>
        </li>
        <?php if($mailList->facility): ?>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.mail-lists', $mailList->facility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="<?php echo e($mailList->facility->name); ?>">
                <?php echo e($mailList->facility->name); ?>

            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.mail-lists', $mailList->facility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                Danh sách email
            </a>
        </li>
        <?php endif; ?>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                  title="<?php echo e($mailList->name); ?>"><?php echo e($mailList->name); ?></span>
        </li>
    </ol>
</nav>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .mail-card { background: #ffffff; border-radius: 0.5rem; padding: 2rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1); max-width: 800px; }
    .form-group { margin-bottom: 1.5rem; }
    .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; }
    .form-label .required { color: #ef4444; }
    .form-input { width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; transition: border-color 0.2s; }
    .form-input:focus { outline: none; border-color: #4299e1; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1); }
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .form-actions { display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; }
    .help-text { font-size: 0.8125rem; color: #6b7280; margin-top: 0.25rem; }
    .alert-local { padding: 0.85rem 1rem; border-radius: 0.375rem; background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; margin-bottom: 0.75rem; }
    .file-info { margin-top: 0.5rem; padding: 0.75rem; background: #f7fafc; border-radius: 0.375rem; display: none; font-size: 0.875rem; }
    .current-file { padding: 0.75rem; background: #f3f4f6; border-radius: 0.375rem; margin-top: 0.5rem; font-size: 0.875rem; color: #374151; }
    #submitBtn:disabled { background-color: #9ca3af; cursor: not-allowed; opacity: 0.7; }
    /* Toast notification */
    #toast-container { position: fixed; top: 1.25rem; right: 1.25rem; z-index: 9999; display: flex; flex-direction: column; gap: 0.5rem; pointer-events: none; }
    .toast { display: flex; align-items: center; gap: 0.6rem; padding: 0.75rem 1.1rem; border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.12); pointer-events: all; min-width: 260px; max-width: 400px; opacity: 0; transform: translateX(2rem); transition: opacity 0.3s ease, transform 0.3s ease; }
    .toast.toast-show { opacity: 1; transform: translateX(0); }
    .toast.toast-success { background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; }
    .toast.toast-error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
    .toast-icon { font-size: 1.1rem; flex-shrink: 0; }
    .toast-close { margin-left: auto; background: none; border: none; cursor: pointer; font-size: 1rem; opacity: 0.6; padding: 0; color: inherit; }
    .toast-close:hover { opacity: 1; }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mail-card">
    <?php if(session('uploadError')): ?>
        <div class="alert-local"><?php echo e(session('uploadError')); ?></div>
    <?php endif; ?>
    <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="alert-local"><?php echo e($message); ?></div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    <?php $__errorArgs = ['excel_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="alert-local"><?php echo e($message); ?></div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

    <form id="mailUpdateForm" action="<?php echo e(route('mails.update', $mailList)); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PUT'); ?>

        <div class="form-group">
            <label class="form-label" for="name">
                Tên danh sách <span class="required">*</span>
            </label>
            <input class="form-input" type="text" id="name" name="name" value="<?php echo e(old('name', $mailList->name)); ?>" placeholder="Ví dụ: Danh sách mail tháng 1" required>
            <p class="help-text">Tên để quản lý danh sách này trong hệ thống</p>
        </div>

        <div class="form-group">
            <label class="form-label" for="data_source_type">
                Kiểu nhập dữ liệu
            </label>
            <select class="form-input" id="data_source_type" style="max-width: 280px;">
                <option value="excel" <?php echo e(!$mailList->google_sheet_url ? 'selected' : ''); ?>>Từ file Excel</option>
                <option value="sheet" <?php echo e($mailList->google_sheet_url ? 'selected' : ''); ?>>Từ Google Sheet</option>
            </select>
        </div>

        <div id="dataSourceSheet" class="form-group" style="<?php echo e($mailList->google_sheet_url ? '' : 'display:none;'); ?>">
            <label class="form-label" for="google_sheet_url">
                Link Google Sheets (tùy chọn)
            </label>
            <input class="form-input" type="url" id="google_sheet_url" name="google_sheet_url" value="<?php echo e(old('google_sheet_url', $mailList->google_sheet_url)); ?>" placeholder="https://docs.google.com/spreadsheets/d/.../edit?usp=sharing">
            <p class="help-text">Dán link Google Sheets để đồng bộ dữ liệu. Sheet cần được chia sẻ "Bất kỳ ai có link đều xem được".</p>
            <div class="form-actions" style="margin-top: 0.75rem; justify-content: flex-start;">
                <button type="button" id="syncFromSheetBtn" class="btn btn-success">Đồng bộ từ Sheet</button>
                <span id="syncFromSheetMsg" class="help-text" style="margin-left: 0.5rem; align-self: center;"></span>
            </div>
        </div>

        <div id="dataSourceExcel" class="form-group" style="<?php echo e($mailList->google_sheet_url ? 'display:none;' : ''); ?>">
            <label class="form-label" for="excel_file">
                File Excel mới (tùy chọn)
            </label>
            <div class="current-file">
                <strong>File hiện tại:</strong> <?php echo e($mailList->original_filename ?? '-'); ?>

                <br>
                <strong>Số dòng:</strong> <?php echo e(is_array($mailList->rows) ? count($mailList->rows) : 0); ?>

            </div>
            <input type="file" id="excel_file" name="excel_file" class="form-input" accept=".xlsx,.xls,.xlsm" style="padding: 0.5rem; margin-top: 0.75rem;">
            <p class="help-text">Chỉ upload nếu muốn thay đổi dữ liệu. File mới sẽ thay thế toàn bộ dữ liệu hiện tại.</p>
            <div id="fileInfo" class="file-info">
                <strong>File mới:</strong> <span id="fileName"></span> -
                <strong>Kích thước:</strong> <span id="fileSize"></span>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?php echo e(route('mails.show', $mailList)); ?>" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn" id="submitBtn" disabled>Cập nhật</button>
        </div>
    </form>
</div>

<div id="toast-container"></div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.innerHTML = `
            <span class="toast-icon">${type === 'success' ? '✓' : '✕'}</span>
            <span>${message}</span>
            <button class="toast-close" onclick="this.closest('.toast').remove()">×</button>
        `;
        container.appendChild(toast);
        requestAnimationFrame(() => {
            requestAnimationFrame(() => toast.classList.add('toast-show'));
        });
        setTimeout(() => {
            toast.classList.remove('toast-show');
            setTimeout(() => toast.remove(), 350);
        }, 4000);
    }

    // Hiện toast sau khi reload
    (function () {
        const msg = sessionStorage.getItem('syncSheetToast');
        const type = sessionStorage.getItem('syncSheetToastType') || 'success';
        if (msg) {
            sessionStorage.removeItem('syncSheetToast');
            sessionStorage.removeItem('syncSheetToastType');
            showToast(msg, type);
        }
    })();

    const fileInput = document.getElementById('excel_file');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameEl = document.getElementById('fileName');
    const fileSizeEl = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');

    // Lưu giá trị ban đầu để so sánh
    const initialValues = {
        name: document.getElementById('name').value,
        dataSourceType: document.getElementById('data_source_type').value,
        googleSheetUrl: document.getElementById('google_sheet_url').value,
    };

    function checkChanges() {
        const nameChanged = document.getElementById('name').value !== initialValues.name;
        const typeChanged = document.getElementById('data_source_type').value !== initialValues.dataSourceType;
        const sheetUrlChanged = document.getElementById('google_sheet_url').value !== initialValues.googleSheetUrl;
        const fileSelected = fileInput.files.length > 0;

        submitBtn.disabled = !(nameChanged || typeChanged || sheetUrlChanged || fileSelected);
    }

    document.getElementById('name').addEventListener('input', checkChanges);
    document.getElementById('data_source_type').addEventListener('change', checkChanges);
    document.getElementById('google_sheet_url').addEventListener('input', checkChanges);

    function formatFileSize(bytes) {
        if (!bytes) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
    }

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            fileInfo.style.display = 'block';
            fileNameEl.textContent = file.name;
            fileSizeEl.textContent = formatFileSize(file.size);
        } else {
            fileInfo.style.display = 'none';
        }
        checkChanges();
    });

    document.getElementById('mailUpdateForm').addEventListener('submit', () => {
        submitBtn.textContent = 'Đang xử lý...';
        submitBtn.disabled = true;
    });

    // Chọn kiểu nhập: Excel hoặc Google Sheet
    const dataSourceType = document.getElementById('data_source_type');
    const dataSourceSheet = document.getElementById('dataSourceSheet');
    const dataSourceExcel = document.getElementById('dataSourceExcel');

    function toggleDataSource() {
        const v = dataSourceType.value;
        dataSourceSheet.style.display = v === 'sheet' ? '' : 'none';
        dataSourceExcel.style.display = v === 'excel' ? '' : 'none';
        if (v === 'sheet') {
            fileInput.value = '';
            fileInfo.style.display = 'none';
        }
    }
    dataSourceType.addEventListener('change', toggleDataSource);

    // Đồng bộ từ Google Sheet
    const syncFromSheetBtn = document.getElementById('syncFromSheetBtn');
    const googleSheetUrlInput = document.getElementById('google_sheet_url');
    const syncFromSheetMsg = document.getElementById('syncFromSheetMsg');

    syncFromSheetBtn.addEventListener('click', () => {
        const url = (googleSheetUrlInput.value || '').trim();
        if (!url) {
            syncFromSheetMsg.textContent = 'Vui lòng nhập link Google Sheets.';
            syncFromSheetMsg.style.color = '#991b1b';
            return;
        }
        if (!url.includes('docs.google.com/spreadsheets/')) {
            syncFromSheetMsg.textContent = 'Link không đúng định dạng Google Sheets.';
            syncFromSheetMsg.style.color = '#991b1b';
            return;
        }
        syncFromSheetMsg.textContent = 'Đang đồng bộ...';
        syncFromSheetMsg.style.color = '#6b7280';
        syncFromSheetBtn.disabled = true;

        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('sheet_url', url);

        fetch('<?php echo e(route("mails.sync-from-sheet", $mailList)); ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            syncFromSheetBtn.disabled = false;
            syncFromSheetMsg.textContent = '';
            if (data.success) {
                const msg = data.message || 'Đồng bộ thành công.';
                if (data.redirect) {
                    sessionStorage.setItem('syncSheetToast', msg);
                    sessionStorage.setItem('syncSheetToastType', 'success');
                    window.location.href = data.redirect;
                } else {
                    showToast(msg, 'success');
                }
            } else {
                showToast(data.message || 'Đồng bộ thất bại.', 'error');
            }
        })
        .catch(() => {
            syncFromSheetBtn.disabled = false;
            syncFromSheetMsg.textContent = '';
            showToast('Lỗi kết nối. Vui lòng thử lại.', 'error');
        });
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/mails/edit.blade.php ENDPATH**/ ?>