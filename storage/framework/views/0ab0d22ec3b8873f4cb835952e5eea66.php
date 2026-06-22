<?php $__env->startSection('title', 'Upload File Lương'); ?>

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
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.salary-files', $selectedFacility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="<?php echo e($selectedFacility->name); ?>">
                <?php echo e($selectedFacility->name); ?>

            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.salary-files', $selectedFacility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                File lương
            </a>
        </li>
        <?php endif; ?>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;">Upload File Lương</span>
        </li>
    </ol>
</nav>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .wizard-container {
        background: white;
        border-radius: 0.5rem;
        padding: 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        max-width: 800px;
        margin: 0 auto;
    }
    
    /* Step Indicator */
    .step-indicator {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2rem;
        position: relative;
    }
    .step-indicator::before {
        content: '';
        position: absolute;
        top: 20px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e5e7eb;
        z-index: 0;
    }
    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
        flex: 1;
    }
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e5e7eb;
        color: #6b7280;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-bottom: 0.5rem;
        transition: all 0.3s;
    }
    .step.active .step-number {
        background: #4299e1;
        color: white;
    }
    .step.completed .step-number {
        background: #10b981;
        color: white;
    }
    .step-label {
        font-size: 0.875rem;
        color: #6b7280;
        text-align: center;
    }
    .step.active .step-label {
        color: #1f2937;
        font-weight: 500;
    }
    
    /* Step Content */
    .step-content {
        display: none;
    }
    .step-content.active {
        display: block;
    }
    
    /* Form Styles */
    .form-group {
        margin-bottom: 1.5rem;
    }
    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
    }
    .form-label .required {
        color: #ef4444;
    }
    .form-input {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: border-color 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }
    .help-text {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    .file-info {
        margin-top: 0.75rem;
        padding: 0.75rem;
        background-color: #f7fafc;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        display: none;
    }
    
    /* Sheet Selection */
    .sheet-list {
        display: grid;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .sheet-item {
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 0.375rem;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .sheet-item:hover {
        border-color: #4299e1;
        background-color: #f0f9ff;
    }
    .sheet-item.selected {
        border-color: #4299e1;
        background-color: #eff6ff;
    }
    .sheet-radio {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }
    .sheet-name {
        font-weight: 500;
        color: #1f2937;
    }
    
    /* Buttons */
    .btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        background-color: #4299e1;
        color: white;
        border-radius: 0.375rem;
        cursor: pointer;
        border: none;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.2s;
        font-size: 0.875rem;
    }
    .btn:hover {
        background-color: #3182ce;
    }
    .btn:disabled {
        background-color: #9ca3af;
        cursor: not-allowed;
    }
    .btn-secondary {
        background-color: #6b7280;
    }
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: space-between;
        margin-top: 2rem;
    }
    
    .loading {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-radius: 50%;
        border-top-color: transparent;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="wizard-container">
    <!-- Step Indicator -->
    <div class="step-indicator">
        <div class="step active" data-step="1">
            <div class="step-number">1</div>
            <div class="step-label">Chọn File</div>
        </div>
        <div class="step" data-step="2">
            <div class="step-number">2</div>
            <div class="step-label">Thông Tin</div>
        </div>
    </div>

    <form action="<?php echo e(route('salary-files.store')); ?>" method="POST" enctype="multipart/form-data" id="wizardForm">
        <?php echo csrf_field(); ?>
        
        <!-- Step 1: Chọn file Google Sheets từ Data - Link -->
        <div class="step-content active" data-step="1">
            <div class="form-group">
                <input type="hidden" name="data_source_type" value="sheet">
                <?php
                $currentFacility = null;
                    if (!empty($selectedFacilityId ?? null) && !empty($facilities ?? null)) {
                        $currentFacility = $facilities->firstWhere('id', (int) $selectedFacilityId);
                    }
                ?>
                <?php if($currentFacility): ?>
                    <div class="form-group">
                        <label class="form-label">
                            Link google sheet
                        </label>
                        <a href="<?php echo e(trim((string) $currentFacility->data_link)); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo e(trim((string) $currentFacility->data_link)); ?>

                        </a>
                    </div>
                <?php endif; ?>
                <label class="form-label" for="google_sheet_url">
                    Chọn file Google Sheets <span class="required">*</span>
                </label>

                <?php if(!empty($facilitySheetOptions ?? [])): ?>
                    <select name="google_sheet_url" id="google_sheet_url" class="form-input">
                        <option value="">-- Chọn file --</option>
                        <?php $__currentLoopData = $facilitySheetOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($item['url']); ?>" <?php echo e(old('google_sheet_url') === ($item['url'] ?? '') ? 'selected' : ''); ?>>
                                <?php echo e($item['label'] ?? $item['url']); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                <?php endif; ?>

                <?php $__errorArgs = ['google_sheet_url'];
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

     
            
            <div class="form-actions">
                <a href="<?php echo e(route('salary-files.index')); ?>" class="btn btn-secondary">Hủy</a>
                <button type="button" class="btn" id="nextToStep2">
                    Tiếp theo
                </button>
            </div>
        </div>

        <!-- Step 3: Form Information -->
        <div class="step-content" data-step="2">
            <div class="form-group">
                <label class="form-label">
                    Tên file <span class="required">*</span>
                </label>
                <input type="text" name="name" class="form-input" value="<?php echo e(old('name')); ?>" required placeholder="Ví dụ: Lương tháng 1/2026">
                <p class="help-text">Tên để quản lý file này trong hệ thống</p>
                <?php $__errorArgs = ['name'];
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
                <label class="form-label">Template</label>
                <select name="template_selection_id" class="form-input">
                    <option value="">-- Không chọn --</option>
                    <?php $__currentLoopData = $templates ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tpl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($tpl->id); ?>" <?php echo e((string)old('template_selection_id') === (string)$tpl->id ? 'selected' : ''); ?>>
                            <?php echo e($tpl->name ?? ('Template #' . $tpl->id)); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <p class="help-text">Chọn template đã lưu để áp dụng (tùy chọn)</p>
                <?php $__errorArgs = ['template_selection_id'];
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

            <?php
                $selectedFacilityId = $selectedFacilityId ?? null;
                $selectedFacility = $selectedFacilityId ? $facilities->firstWhere('id', $selectedFacilityId) : null;
            ?>

            <?php if($selectedFacilityId): ?>
                <input type="hidden" name="facility_id" value="<?php echo e($selectedFacilityId); ?>">
                <div class="form-group">
                    <label class="form-label">Cơ sở</label>
                    <select class="form-input" disabled>
                        <?php if($selectedFacility): ?>
                            <option selected><?php echo e($selectedFacility->name); ?> <?php echo e($selectedFacility->address ? ' - ' . $selectedFacility->address : ''); ?></option>
                        <?php else: ?>
                            <option selected>Đã chọn cơ sở ID <?php echo e($selectedFacilityId); ?></option>
                        <?php endif; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Cơ sở</label>
                    <select name="facility_id" class="form-input">
                        <option value="">-- Chưa chọn --</option>
                        <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($facility->id); ?>" <?php echo e(old('facility_id') == $facility->id ? 'selected' : ''); ?>>
                                <?php echo e($facility->name); ?> <?php echo e($facility->address ? ' - ' . $facility->address : ''); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <p class="help-text">Chọn cơ sở sở hữu file lương này (tùy chọn)</p>
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
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Tháng</label>
                <input type="hidden" name="month" id="month_value_create" value="<?php echo e(old('month')); ?>">
                <div style="display:flex; gap:0.5rem; align-items:center;">
                    <select id="month_mm_create" class="form-input" style="max-width: 110px;">
                        <?php for($m = 1; $m <= 12; $m++): ?>
                            <?php ($mm = str_pad((string)$m, 2, '0', STR_PAD_LEFT)); ?>
                            <option value="<?php echo e($mm); ?>"><?php echo e($mm); ?></option>
                        <?php endfor; ?>
                    </select>
                    <select id="month_yyyy_create" class="form-input" style="max-width: 140px;"></select>
                </div>
                <p class="help-text">Chọn tháng bằng số (mm-yyyy). Có thể chọn các tháng trước.</p>
                <?php $__errorArgs = ['month'];
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

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" id="backToStep2">Quay lại</button>
                <button type="submit" class="btn" id="submitBtn">
                    <span id="submitText">Upload File</span>
                    <span id="submitLoading" style="display: none;"><span class="loading"></span> Đang xử lý...</span>
                </button>
            </div>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    let currentStep = 1;
    const nextToStep2Btn = document.getElementById('nextToStep2');
    const backToStep2Btn = document.getElementById('backToStep2');
    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitText');
    const submitLoading = document.getElementById('submitLoading');
    const wizardForm = document.getElementById('wizardForm');
    const googleSheetUrlInput = document.getElementById('google_sheet_url');

    function updateStepIndicator(step) {
        document.querySelectorAll('.step').forEach(el => {
            const stepNum = parseInt(el.dataset.step);
            el.classList.remove('active', 'completed');
            if (stepNum === step) {
                el.classList.add('active');
            } else if (stepNum < step) {
                el.classList.add('completed');
            }
        });
    }

    function showStep(step) {
        document.querySelectorAll('.step-content').forEach(el => {
            el.classList.remove('active');
        });
        document.querySelector(`.step-content[data-step="${step}"]`).classList.add('active');
        updateStepIndicator(step);
        currentStep = step;
    }

    nextToStep2Btn.addEventListener('click', () => {
        const url = (googleSheetUrlInput && googleSheetUrlInput.value) ? googleSheetUrlInput.value.trim() : '';
        if (!url) {
            alert('Vui lòng chọn hoặc nhập link Google Sheets.');
            return;
        }
        showStep(2);
    });

    backToStep2Btn.addEventListener('click', () => {
        showStep(1);
    });

    wizardForm.addEventListener('submit', (e) => {
        submitBtn.disabled = true;
        submitText.style.display = 'none';
        submitLoading.style.display = 'inline';
    });

    // Month select (mm + yyyy) (create) -> sync với hidden input month (mm-yyyy)
    (function () {
        const hidden = document.getElementById('month_value_create');
        const mmSelect = document.getElementById('month_mm_create');
        const yyyySelect = document.getElementById('month_yyyy_create');
        if (!hidden || !mmSelect || !yyyySelect) return;

        const now = new Date();
        const currentYear = now.getFullYear();

        let initialMm = '';
        let initialYyyy = '';
        if (hidden.value) {
            const val = hidden.value.trim(); // mm-yyyy
            const parts = val.split('-');
            if (parts.length === 2) {
                const mm = parts[0];
                const yyyy = parts[1];
                if (/^(0[1-9]|1[0-2])$/.test(mm) && /^[0-9]{4}$/.test(yyyy)) {
                    initialMm = mm;
                    initialYyyy = yyyy;
                }
            }
        }

        const minYear = Math.min(currentYear - 10, initialYyyy ? parseInt(initialYyyy, 10) - 2 : currentYear - 10);
        const maxYear = Math.max(currentYear + 2, initialYyyy ? parseInt(initialYyyy, 10) + 2 : currentYear + 2);

        yyyySelect.innerHTML = '';
        for (let y = maxYear; y >= minYear; y--) {
            const opt = document.createElement('option');
            opt.value = String(y);
            opt.textContent = String(y);
            yyyySelect.appendChild(opt);
        }

        const currentMm = String(now.getMonth() + 1).padStart(2, '0');
        mmSelect.value   = initialMm   || currentMm;
        yyyySelect.value = initialYyyy || String(currentYear);

        const syncHidden = () => {
            const mm = (mmSelect.value || '').trim();
            const yyyy = (yyyySelect.value || '').trim();
            if (!mm || !yyyy) {
                hidden.value = '';
                return;
            }
            hidden.value = `${mm}-${yyyy}`;
        };

        mmSelect.addEventListener('change', syncHidden);
        yyyySelect.addEventListener('change', syncHidden);
        syncHidden();
    })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/salary-files/create.blade.php ENDPATH**/ ?>