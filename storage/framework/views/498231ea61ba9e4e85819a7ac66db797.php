<?php $__env->startSection('title', 'Upload mail list'); ?>

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
        <?php if(!empty($selectedFacility)): ?>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.mail-lists', $selectedFacility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="<?php echo e($selectedFacility->name); ?>">
                <?php echo e($selectedFacility->name); ?>

            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24" style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.mail-lists', $selectedFacility->id)); ?>"
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
            <span style="font-size:1.15rem;font-weight:700;color:#111827;">Upload mail list</span>
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
    <?php $__errorArgs = ['google_sheet_url'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="alert-local"><?php echo e($message); ?></div>
    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

    <form id="mailUploadForm" action="<?php echo e(route('mails.store')); ?>" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label" for="name">
                Tên danh sách <span class="required">*</span>
            </label>
            <input class="form-input" type="text" id="name" name="name" value="<?php echo e(old('name')); ?>" placeholder="Ví dụ: Danh sách mail tháng 1" required>
            <p class="help-text">Tên để quản lý danh sách này trong hệ thống</p>
        </div>

        <div class="form-group">
            <label class="form-label" for="facility_id">
                Cơ sở <span class="required">*</span>
            </label>
            <?php if(!empty($selectedFacilityId)): ?>
                <input type="hidden" name="facility_id" value="<?php echo e($selectedFacilityId); ?>">
                <select class="form-input" disabled>
                    <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($facility->id == $selectedFacilityId): ?>
                            <option selected><?php echo e($facility->name); ?> <?php echo e($facility->address ? ' - ' . $facility->address : ''); ?></option>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            <?php else: ?>
                <select id="facility_id" name="facility_id" class="form-input" required>
                    <option value="">-- Chọn cơ sở --</option>
                    <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($facility->id); ?>" <?php echo e(old('facility_id') == $facility->id ? 'selected' : ''); ?>>
                            <?php echo e($facility->name); ?> <?php echo e($facility->address ? ' - ' . $facility->address : ''); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            <?php endif; ?>
            <p class="help-text">Chọn cơ sở sở hữu danh sách email này</p>
            <?php $__errorArgs = ['facility_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p style="color: #ef4444; font-size: 0.8125rem; margin-top: 0.25rem;"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="form-group">
            <label class="form-label" for="data_source_type">
                Kiểu nhập dữ liệu
            </label>
            <select class="form-input" id="data_source_type" name="data_source_type" style="max-width: 280px;">
                <option value="excel" <?php echo e(old('data_source_type', 'excel') === 'excel' ? 'selected' : ''); ?>>Từ file Excel</option>
                <option value="sheet" <?php echo e(old('data_source_type') === 'sheet' ? 'selected' : ''); ?>>Từ Google Sheet</option>
            </select>
        </div>

        <div id="dataSourceSheet" class="form-group" style="<?php echo e(old('data_source_type') === 'sheet' ? '' : 'display:none;'); ?>">
            <label class="form-label" for="google_sheet_url">
                Link Google Sheets <span class="required">*</span>
            </label>
            <input class="form-input" type="url" id="google_sheet_url" name="google_sheet_url" value="<?php echo e(old('google_sheet_url')); ?>" placeholder="https://docs.google.com/spreadsheets/d/.../edit?usp=sharing">
            <p class="help-text">Dán link Google Sheets để lấy dữ liệu. Sheet cần được chia sẻ "Bất kỳ ai có link đều xem được".</p>
            <div class="form-actions" style="margin-top: 0.75rem; justify-content: flex-start;">
                <button type="button" id="previewFromSheetBtn" class="btn btn-success">Đồng bộ từ Sheet</button>
                <span id="previewFromSheetMsg" class="help-text" style="margin-left: 0.5rem; align-self: center;"></span>
            </div>
        </div>

        <div id="dataSourceExcel" class="form-group" style="<?php echo e(old('data_source_type', 'excel') === 'excel' ? '' : 'display:none;'); ?>">
            <label class="form-label" for="excel_file">
                File Excel <span class="required">*</span>
            </label>
            <input type="file" id="excel_file" name="excel_file" class="form-input" accept=".xlsx,.xls,.xlsm" required style="padding: 0.5rem;">
            <p class="help-text">Chỉ chấp nhận file .xlsx, .xls hoặc .xlsm (tối đa 10MB)</p>
            <div id="fileInfo" class="file-info">
                <strong>File:</strong> <span id="fileName"></span> - 
                <strong>Kích thước:</strong> <span id="fileSize"></span>
            </div>
        </div>

        <div class="form-actions">
            <a href="<?php echo e(route('mails.index')); ?>" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn" id="submitBtn">Lưu danh sách</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const fileInput = document.getElementById('excel_file');
    const fileInfo = document.getElementById('fileInfo');
    const fileNameEl = document.getElementById('fileName');
    const fileSizeEl = document.getElementById('fileSize');
    const submitBtn = document.getElementById('submitBtn');
    const dataSourceType = document.getElementById('data_source_type');
    const dataSourceSheet = document.getElementById('dataSourceSheet');
    const dataSourceExcel = document.getElementById('dataSourceExcel');
    const googleSheetUrlInput = document.getElementById('google_sheet_url');
    const previewFromSheetBtn = document.getElementById('previewFromSheetBtn');
    const previewFromSheetMsg = document.getElementById('previewFromSheetMsg');

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
        }
    });

    function toggleDataSource() {
        const v = dataSourceType.value;
        const isSheet = v === 'sheet';
        dataSourceSheet.style.display = isSheet ? '' : 'none';
        dataSourceExcel.style.display = !isSheet ? '' : 'none';

        // required theo nguồn dữ liệu
        fileInput.required = !isSheet;
        if (googleSheetUrlInput) googleSheetUrlInput.required = isSheet;

        if (isSheet) {
            fileInput.value = '';
            fileInfo.style.display = 'none';
        }
    }
    dataSourceType.addEventListener('change', toggleDataSource);
    toggleDataSource();

    // Đồng bộ/đọc thử từ Google Sheet (màn create)
    previewFromSheetBtn.addEventListener('click', () => {
        const url = (googleSheetUrlInput.value || '').trim();
        if (!url) {
            previewFromSheetMsg.textContent = 'Vui lòng nhập link Google Sheets.';
            previewFromSheetMsg.style.color = '#991b1b';
            return;
        }
        if (!url.includes('docs.google.com/spreadsheets/')) {
            previewFromSheetMsg.textContent = 'Link không đúng định dạng Google Sheets.';
            previewFromSheetMsg.style.color = '#991b1b';
            return;
        }

        previewFromSheetMsg.textContent = 'Đang đồng bộ...';
        previewFromSheetMsg.style.color = '#6b7280';
        previewFromSheetBtn.disabled = true;

        const formData = new FormData();
        formData.append('_token', document.querySelector('input[name="_token"]').value);
        formData.append('sheet_url', url);

        fetch('<?php echo e(route("mails.preview-sheet")); ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            previewFromSheetBtn.disabled = false;
            if (data.success) {
                previewFromSheetMsg.textContent = data.message || 'Đọc sheet thành công.';
                previewFromSheetMsg.style.color = '#059669';
            } else {
                previewFromSheetMsg.textContent = data.message || 'Đọc sheet thất bại.';
                previewFromSheetMsg.style.color = '#991b1b';
            }
        })
        .catch(() => {
            previewFromSheetBtn.disabled = false;
            previewFromSheetMsg.textContent = 'Lỗi kết nối. Vui lòng thử lại.';
            previewFromSheetMsg.style.color = '#991b1b';
        });
    });

    document.getElementById('mailUploadForm').addEventListener('submit', () => {
        submitBtn.textContent = 'Đang xử lý...';
        submitBtn.disabled = true;
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/mails/create.blade.php ENDPATH**/ ?>