@extends('layouts.admin')

@section('title', ($file->name ?? 'File lương') . ' — Chỉnh sửa')

@section('page-breadcrumb')
<nav aria-label="breadcrumb" style="display:flex;align-items:center;gap:0;line-height:1.2;margin-top:0.35rem;">
    <ol style="display:flex;align-items:center;gap:0;list-style:none;margin:0;padding:0;flex-wrap:wrap;">
        <li style="display:flex;align-items:center;">
            <a href="{{ route('facilities.index') }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                Cơ sở
            </a>
        </li>
        @if($file->facility)
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('facilities.salary-files', $file->facility->id) }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="{{ $file->facility->name }}">
                {{ $file->facility->name }}
            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('facilities.salary-files', $file->facility->id) }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'">
                File lương
            </a>
        </li>
        @endif
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                  title="{{ $file->name }}">{{ $file->name }}</span>
        </li>
    </ol>
</nav>
@endsection

@push('styles')
<style>
    .page-container {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        width: 100%;
        max-width: 100%;
        overflow-x: hidden;
    }
    .form-card {
        background: white;
        border-radius: 0.5rem;
        padding: 0.85rem 1rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        width: 100%;
        flex-shrink: 0;
    }
    .form-main-layout {
        display: flex;
        gap: 1.25rem;
        align-items: flex-start;
    }
    .form-main-left {
        flex: 1;
        min-width: 0;
    }
    .form-main-right {
        width: 220px;
        max-width: 260px;
        flex-shrink: 0;
    }
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 0.5rem 0.7rem;
        align-items: flex-start;
        width: 100%;
    }
    .form-group {
        margin: 0;
    }
    .form-group.form-group-full {
        grid-column: 1 / -1;
    }
    .form-label {
        display: block;
        margin-bottom: 0.25rem;
        font-weight: 500;
        color: #374151;
        font-size: 0.8125rem;
    }
    .form-label .required {
        color: #ef4444;
    }
    .form-input {
        width: 100%;
        padding: 0.42rem 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.8rem;
        transition: border-color 0.2s;
    }
    .form-input:focus {
        outline: none;
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1);
    }
    .form-textarea {
        min-height: 64px;
        resize: vertical;
    }
    .btn {
        display: inline-block;
        padding: 0.45rem 0.9rem;
        background-color: #4299e1;
        color: white;
        border-radius: 0.375rem;
        cursor: pointer;
        border: none;
        font-weight: 500;
        transition: background-color 0.2s;
        font-size: 0.8125rem;
    }
    .btn:hover {
        background-color: #3182ce;
    }
    .btn-secondary {
        background-color: #6b7280;
    }
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    .btn-success {
        background-color:#10b981;
    }
    .btn-success:hover {
        background-color:#059669;
    }
    #submitUpdateBtn:disabled {
        background-color: #9ca3af;
        cursor: not-allowed;
        opacity: 0.7;
    }
    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        align-items: stretch;
    }
    .form-actions a,
    .form-actions button {
        width: 100%;
        text-align: center;
        text-decoration: none;
    }
    /* Toast notification */
    #toast-container {
        position: fixed;
        top: 1.25rem;
        right: 1.25rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        pointer-events: none;
    }
    .toast {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.75rem 1.1rem;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        font-size: 0.875rem;
        font-weight: 500;
        min-width: 260px;
        max-width: 360px;
        pointer-events: auto;
        opacity: 0;
        transform: translateX(40px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }
    .toast.toast-show {
        opacity: 1;
        transform: translateX(0);
    }
    .toast.toast-success {
        background: #ecfdf5;
        border: 1px solid #6ee7b7;
        color: #065f46;
    }
    .toast.toast-error {
        background: #fef2f2;
        border: 1px solid #fca5a5;
        color: #991b1b;
    }
    .toast-icon { font-size: 1.1rem; flex-shrink: 0; }
    .toast-close {
        margin-left: auto;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1rem;
        line-height: 1;
        color: inherit;
        opacity: 0.6;
        padding: 0;
    }
    .toast-close:hover { opacity: 1; }
    .help-text {
        font-size: 0.72rem;
        color: #6b7280;
        margin-top: 0.15rem;
    }
    .file-info-box {
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        padding: 0.6rem 0.75rem;
        margin-bottom: 0.75rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: center;
    }
    .file-info-box p {
        margin: 0;
        color: #374151;
        font-size: 0.8rem;
    }
    .excel-section {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        flex: 1;
        min-width: 0;
        width: 100%;
        height: 200px!important;
        font-size: 0.1rem;
    }
    .excel-section * {
        font-size: inherit;
    }
    .excel-section h3 {
        margin: 0 0 1rem 0;
        font-size: 1.25rem;
        font-weight: 600;
        color: #111827;
    }
    .excel-section .sheet-info {
        margin: 0 0 1rem 0;
        color: #6b7280;
        font-size: 0.875rem;
    }
    /* Modal preview */
    .modal-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.35);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        padding: 1rem;
    }
    .modal-card {
        background: #fff;
        border-radius: 0.5rem;
        width: min(480px, 100%);
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        overflow: hidden;
    }
    .modal-header {
        padding: 0.9rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.5rem;
    }
    .modal-body {
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .modal-actions {
        padding: 0.9rem 1rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .modal-close {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #6b7280;
    }
    .modal-backdrop.hide {
        display: none;
    }
    /* Giữ form ở trên, bảng Excel ở dưới trên mọi kích thước */

    /* Banner cảnh báo thay đổi / file bị mất */
    #drive-change-banner {
        display: none;
        align-items: center;
        gap: 0.75rem;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        font-size: 0.85rem;
        margin-bottom: 0;
        /* mặc định: cảnh báo vàng */
        background: #fffbeb;
        border: 1px solid #f59e0b;
        color: #92400e;
    }
    #drive-change-banner.show { display: flex; }
    #drive-change-banner.banner-danger {
        background: #fef2f2;
        border-color: #f87171;
        color: #991b1b;
    }
    #drive-change-banner .banner-icon { font-size: 1.2rem; flex-shrink: 0; }
    #drive-change-banner .banner-text { flex: 1; font-weight: 500; }
    #drive-change-banner .banner-sync-btn {
        background: #f59e0b;
        color: #fff;
        border: none;
        border-radius: 0.375rem;
        padding: 0.35rem 0.8rem;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        flex-shrink: 0;
    }
    #drive-change-banner.banner-danger .banner-sync-btn { background: #ef4444; }
    #drive-change-banner .banner-sync-btn:hover { filter: brightness(0.9); }
    #drive-change-banner .banner-close {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.1rem;
        color: inherit;
        opacity: 0.6;
        padding: 0;
        flex-shrink: 0;
    }
    #drive-change-banner .banner-close:hover { opacity: 1; }
    /* Cảnh báo file mất không có URL */
    .file-missing-alert {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        background: #fef2f2;
        border: 1px solid #f87171;
        border-radius: 0.5rem;
        padding: 0.7rem 1rem;
        font-size: 0.85rem;
        color: #991b1b;
        font-weight: 500;
    }
</style>
@endpush

@section('content')
<div class="page-container">
    {{-- Banner động: thay đổi trên Drive hoặc file bị mất (chỉ hiện khi có google_sheet_url) --}}
    @if(!empty($file->google_sheet_url))
    <div id="drive-change-banner" role="alert">
        <span class="banner-icon" id="bannerIcon">⚠️</span>
        <span class="banner-text" id="bannerText">Có thay đổi trên Drive cần đồng bộ về</span>
        <button type="button" class="banner-sync-btn" id="bannerSyncBtn">Đồng bộ ngay</button>
        <button type="button" class="banner-close" id="bannerCloseBtn" aria-label="Đóng">×</button>
    </div>
    @endif

    {{-- Cảnh báo tĩnh (server-side): file bị mất, không có URL để tự phục hồi --}}
    @php
        $localPath = $file->salary_sheet_path ?? $file->file_path;
        $localMissing = $localPath && !\Illuminate\Support\Facades\Storage::disk(env('FILESYSTEM_DISK','local'))
            ->exists(ltrim(preg_replace('/^storage\/app\/(private\/)?/', '', $localPath), '/'));
    @endphp
    @if($localMissing && empty($file->google_sheet_url))
    <div class="file-missing-alert" role="alert">
        <span style="font-size:1.2rem;">🚫</span>
        <span>File lương bị mất khỏi storage và không có Google Sheet URL để phục hồi. Vui lòng upload lại file thủ công.</span>
    </div>
    @endif

    <div class="form-card">
        {{-- Form update (PUT) --}}
        <form id="salaryUpdateForm" action="{{ route('salary-files.update', $file->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-main-layout">
                <div class="form-main-left">
                    <div class="form-grid">
                        {{-- Thông tin cơ bản của file lương --}}
                        <div class="form-group">
                            <label class="form-label">
                                Tên file lương <span class="required">*</span>
                            </label>
                            <input type="text" name="name" class="form-input" value="{{ old('name', $file->name) }}" required placeholder="Ví dụ: Lương tháng 1/2026">
                            @error('name')
                                <p style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tháng</label>
                            <input type="hidden" name="month" id="month_value" value="{{ old('month', $file->month) }}">
                            <div style="display:flex; gap:0.5rem; align-items:center;">
                                <select id="month_mm" class="form-input" style="max-width: 110px;">
                                    @for($m = 1; $m <= 12; $m++)
                                        @php($mm = str_pad((string)$m, 2, '0', STR_PAD_LEFT))
                                        <option value="{{ $mm }}">{{ $mm }}</option>
                                    @endfor
                                </select>
                                <select id="month_yyyy" class="form-input" style="max-width: 140px;"></select>
                            </div>
                            @error('month')
                                <p style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Đồng bộ dữ liệu lương từ Google Sheet --}}
                        <div class="form-group">
                            <label class="form-label" style="font-size: 0.75rem;">
                                Đồng bộ từ Google Sheet
                            </label>

                            <div style="display:flex; align-items:center; gap:0.5rem;">
                                @if(!empty($facilitySheetOptions ?? []))
                                    <select
                                        id="salary_google_sheet_url"
                                        class="form-input"
                                        style="font-size: 0.75rem; flex: 0 0 60%; max-width: 60%;"
                                    >
                                        <option value="">-- Chọn file --</option>
                                        @foreach($facilitySheetOptions as $item)
                                            @php($optionUrl = $item['url'] ?? '')
                                            <option
                                                value="{{ $optionUrl }}"
                                                {{ (string)($file->google_sheet_url ?? '') === (string)$optionUrl ? 'selected' : '' }}
                                            >
                                                {{ $item['label'] ?? $optionUrl }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <p class="help-text" style="font-size: 0.6875rem; margin: 0;">
                                        Cơ sở chưa cấu hình `data_link` hoặc không có file Google Sheet nào.
                                    </p>
                                @endif

                                <button
                                    type="button"
                                    class="btn btn-success btn-sm"
                                    id="salarySyncFromSheetBtn"
                                    style="flex: 1; text-align: center;"
                                >
                                    Đồng bộ
                                </button>
                            </div>

                            <span id="salarySyncFromSheetMsg" class="help-text" style="display:block; font-size:0.6875rem; margin-top:0.25rem;"></span>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Template</label>
                            <select id="template_selection_id" name="template_selection_id" class="form-input">
                                <option value="">-- Không chọn --</option>
                                @foreach($templates ?? [] as $tpl)
                                    <option value="{{ $tpl->id }}" {{ (string)old('template_selection_id', $file->template_selection_id) === (string)$tpl->id ? 'selected' : '' }}>
                                        {{ $tpl->name ?? ('Template #' . $tpl->id) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template_selection_id')
                                <p style="color: #ef4444; font-size: 0.75rem; margin-top: 0.25rem;">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="form-main-right">
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" id="previewPdfBtn">
                            Kiểm tra PDF
                        </button>
                        <button type="button" class="btn btn-secondary" id="bulkPdfBtn" {{ isset($hasPendingBulk) && $hasPendingBulk ? 'disabled title="Đang có tiến trình tạo file đang chạy"' : '' }}>
                            Tạo nhiều PDF theo danh sách
                        </button>
                        <a href="{{ route('salary-files.bulk-history', $file->id) }}" class="btn btn-secondary">
                            Xem lịch sử file ZIP
                        </a>
                        <button type="submit" class="btn" id="submitUpdateBtn" disabled>Cập nhật</button>
                    </div>
                </div>
            </div>
        </form>

        {{-- Form preview PDF (POST) - tách riêng để không dính _method=PUT --}}
        <form id="previewPdfForm" action="{{ route('salary-files.preview-pdf', $file->id) }}" method="POST" target="_blank" style="display:none;">
            @csrf
            <input type="hidden" name="template_selection_id" id="preview_template_selection_id" value="">
            <input type="hidden" name="row_number" id="preview_row_number" value="">
        </form>

        {{-- Form bulk PDF (POST) - tạo zip nhiều PDF --}}
        <form id="bulkPdfForm" action="{{ route('salary-files.bulk-pdf', $file->id) }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="template_selection_id" id="bulk_template_selection_id" value="">
            <div id="bulk_row_numbers_container"></div>
        </form>

        {{-- Modal chọn nhân viên để xem PDF --}}
        <div id="previewModal" class="modal-backdrop hide" role="dialog" aria-modal="true" aria-labelledby="previewModalTitle">
            <div class="modal-card">
                <div class="modal-header">
                    <h4 id="previewModalTitle" style="margin:0; font-size:1rem; font-weight:600;">Chọn nhân viên để xem PDF</h4>
                    <button type="button" class="modal-close" id="closePreviewModal" aria-label="Đóng">×</button>
                </div>
                <div class="modal-body">
                    <p class="help-text" style="margin:0;">Chọn tên nhân viên tương ứng với dòng dữ liệu trong file lương.</p>
                    <div class="form-group" style="margin:0;">
                        <label class="form-label" style="margin-bottom:0.35rem;">Nhân viên</label>
                        <select id="employeeSelect" class="form-input" {{ empty($employeeOptions) ? 'disabled' : '' }}>
                            @forelse($employeeOptions as $emp)
                                <option value="{{ $emp['row'] }}">{{ $emp['stt'] }} - {{ $emp['name'] }}</option>
                            @empty
                                <option value="">Không tìm thấy tên trong file lương</option>
                            @endforelse
                        </select>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" id="cancelPreviewModal">Hủy</button>
                    <button type="button" class="btn" id="confirmPreviewModal" {{ empty($employeeOptions) ? 'disabled' : '' }}>Xem PDF</button>
                </div>
            </div>
        </div>
    </div>

    <div class="excel-section">
        @include('partials.excel-table', ['data' => $data ?? [], 'styles' => $styles ?? []])
    </div>
</div>

<div id="toast-container"></div>
@endsection

@push('scripts')
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
        const msg = sessionStorage.getItem('syncToast');
        if (msg) {
            sessionStorage.removeItem('syncToast');
            showToast(msg, 'success');
        }
    })();

    (function () {
        const syncBtn = document.getElementById('salarySyncFromSheetBtn');
        const syncMsg = document.getElementById('salarySyncFromSheetMsg');
        const sheetSelect = document.getElementById('salary_google_sheet_url');

        if (syncBtn && syncMsg) {
            syncBtn.addEventListener('click', () => {
                if (!sheetSelect || !sheetSelect.value.trim()) {
                    syncMsg.textContent = 'Vui lòng chọn file Google Sheet để đồng bộ.';
                    syncMsg.style.color = '#991b1b';
                    return;
                }

                const sheetUrl = sheetSelect.value.trim();

                syncMsg.textContent = 'Đang đồng bộ từ Google Sheet...';
                syncMsg.style.color = '#6b7280';
                syncBtn.disabled = true;

                const formData = new FormData();
                formData.append('_token', document.querySelector('input[name="_token"]').value);
                formData.append('sheet_url', sheetUrl);

                fetch('{{ route("salary-files.sync-from-sheet", $file->id) }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    syncBtn.disabled = false;
                    if (data.success) {
                        syncMsg.textContent = data.message || 'Đồng bộ thành công.';
                        syncMsg.style.color = '#059669';
                        sessionStorage.setItem('syncToast', data.message || 'Đồng bộ dữ liệu thành công!');
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    } else {
                        syncMsg.textContent = data.message || 'Đồng bộ thất bại.';
                        syncMsg.style.color = '#991b1b';
                    }
                })
                .catch(() => {
                    syncBtn.disabled = false;
                    syncMsg.textContent = 'Lỗi kết nối. Vui lòng thử lại.';
                    syncMsg.style.color = '#991b1b';
                });
            });
        }
    })();

    (function () {
        const btn = document.getElementById('previewPdfBtn');
        const previewForm = document.getElementById('previewPdfForm');
        const select = document.getElementById('template_selection_id');
        const hiddenTpl = document.getElementById('preview_template_selection_id');
        const hiddenRow = document.getElementById('preview_row_number');
        const modal = document.getElementById('previewModal');
        const employeeSelect = document.getElementById('employeeSelect');
        const closeModalBtn = document.getElementById('closePreviewModal');
        const cancelModalBtn = document.getElementById('cancelPreviewModal');
        const confirmModalBtn = document.getElementById('confirmPreviewModal');
        const employees = <?php echo json_encode($employeeOptions ?? []); ?>;

        if (!btn || !previewForm || !select || !hiddenTpl || !modal) return;

        const openModal = () => {
            if (!employees.length) {
                alert('Không tìm thấy danh sách nhân viên trong file lương.');
                return;
            }
            modal.classList.remove('hide');
        };

        const closeModal = () => modal.classList.add('hide');

        btn.addEventListener('click', function () {
            openModal();
        });

        [closeModalBtn, cancelModalBtn].forEach(el => {
            if (el) el.addEventListener('click', closeModal);
        });

        if (confirmModalBtn) {
            confirmModalBtn.addEventListener('click', function () {
                const selectedRow = employeeSelect ? employeeSelect.value : '';
                if (!selectedRow) {
                    alert('Vui lòng chọn nhân viên để xem PDF.');
                    return;
                }

                hiddenTpl.value = select.value || '';
                hiddenRow.value = selectedRow;
                previewForm.submit();
                closeModal();
            });
        }
    })();

    // Bulk PDF: tạo nhiều file theo danh sách (mặc định toàn bộ danh sách nhân viên detect được)
    (function () {
        const btn = document.getElementById('bulkPdfBtn');
        const bulkForm = document.getElementById('bulkPdfForm');
        const select = document.getElementById('template_selection_id');
        const hiddenTpl = document.getElementById('bulk_template_selection_id');
        const rowsContainer = document.getElementById('bulk_row_numbers_container');
        const employees = <?php echo json_encode($employeeOptions ?? []); ?>;

        if (!btn || !bulkForm || !select || !hiddenTpl || !rowsContainer) return;

        btn.addEventListener('click', function () {
            if (btn.hasAttribute('disabled')) return;
            if (!employees.length) {
                alert('Không tìm thấy danh sách nhân viên trong file lương.');
                return;
            }
            if (!select.value) {
                alert('Vui lòng chọn template trước khi tạo PDF hàng loạt.');
                return;
            }
            const ok = confirm(`Tạo ${employees.length} PDF theo danh sách và tải về file ZIP?`);
            if (!ok) return;

            hiddenTpl.value = select.value || '';
            rowsContainer.innerHTML = '';

            employees.forEach(emp => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'row_numbers[]';
                input.value = String(emp.row || '');
                rowsContainer.appendChild(input);
            });

            bulkForm.submit();
        });
    })();

    // Disable/enable nút Cập nhật theo sự thay đổi form
    // (Phải đặt TRƯỚC IIFE month-select vì syncHidden() gọi checkFormChanges())
    (function () {
        const submitBtn = document.getElementById('submitUpdateBtn');
        if (!submitBtn) return;

        const nameInput   = document.querySelector('[name="name"]');
        const monthHidden = document.getElementById('month_value');
        const tplSelect   = document.getElementById('template_selection_id');

        const initialName  = nameInput  ? nameInput.value  : '';
        const initialMonth = monthHidden ? monthHidden.value : '';
        const initialTpl   = tplSelect  ? tplSelect.value  : '';

        window.checkFormChanges = function () {
            const changed =
                (nameInput   && nameInput.value   !== initialName)  ||
                (monthHidden && monthHidden.value !== initialMonth)  ||
                (tplSelect   && tplSelect.value   !== initialTpl);

            submitBtn.disabled = !changed;
        };

        if (nameInput) nameInput.addEventListener('input',  checkFormChanges);
        if (tplSelect) tplSelect.addEventListener('change', checkFormChanges);

        submitBtn.style.cssText += '; transition: opacity 0.2s;';

        // Dùng form submit event (không dùng button click) để tránh browser
        // cancel submission do button bị disabled trước khi form kịp submit.
        const form = document.getElementById('salaryUpdateForm');
        if (form) {
            form.addEventListener('submit', () => {
                submitBtn.textContent = 'Đang xử lý...';
                submitBtn.disabled = true;
            });
        }
    })();

    // Kiểm tra thay đổi trên Drive khi page load
    @if(!empty($file->google_sheet_url))
    (function () {
        const banner       = document.getElementById('drive-change-banner');
        const bannerIcon   = document.getElementById('bannerIcon');
        const bannerText   = document.getElementById('bannerText');
        const closeBtn     = document.getElementById('bannerCloseBtn');
        const syncBtn      = document.getElementById('bannerSyncBtn');
        const sheetSelect  = document.getElementById('salary_google_sheet_url');
        const syncSheetBtn = document.getElementById('salarySyncFromSheetBtn');

        if (!banner) return;

        function showBanner(fileMissing) {
            if (fileMissing) {
                banner.classList.add('banner-danger');
                bannerIcon.textContent = '🚫';
                bannerText.textContent = 'File lương bị mất khỏi storage. Cần đồng bộ lại từ Drive để phục hồi.';
                // Không cho đóng khi file bị mất (quan trọng)
                if (closeBtn) closeBtn.style.display = 'none';
            } else {
                bannerIcon.textContent = '⚠️';
                bannerText.textContent = 'Có thay đổi trên Drive cần đồng bộ về';
            }
            banner.classList.add('show');
        }

        // Đóng banner (chỉ dùng cho trường hợp has_changes, không phải file_missing)
        if (closeBtn) {
            closeBtn.addEventListener('click', () => banner.classList.remove('show'));
        }

        // Nút "Đồng bộ ngay": chọn đúng sheet URL rồi trigger sync
        if (syncBtn) {
            syncBtn.addEventListener('click', () => {
                const currentUrl = @json($file->google_sheet_url);
                if (sheetSelect) sheetSelect.value = currentUrl;
                if (syncSheetBtn) syncSheetBtn.click();
                banner.classList.remove('show');
            });
        }

        let pollInterval = 5000;
        const MAX_INTERVAL = {{ env('SYNC_POLL_MAX_INTERVAL', 30000) }};
        let pollCount = 0;
        const MAX_POLLS = {{ env('SYNC_POLL_MAX_RETRIES', 10) }};

        function getNextInterval() {
            const current = pollInterval;
            if (pollInterval < MAX_INTERVAL) {
                pollInterval += 5000;
            }
            return current;
        }

        function scheduleNextPoll() {
            if (pollCount < MAX_POLLS) {
                setTimeout(checkDriveChanges, getNextInterval());
            }
        }

        // Gọi API sau khi page đã render (không block UI), lặp lại với thời gian giãn cách tăng dần, tối đa 10 lần
        function checkDriveChanges() {
            pollCount++;
            fetch('{{ route("salary-files.check-drive-changes", $file->id) }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (!data) {
                    scheduleNextPoll();
                    return;
                }
                if (data.file_missing) {
                    showBanner(true);
                } else if (data.has_changes) {
                    showBanner(false);
                } else {
                    scheduleNextPoll();
                }
            })
            .catch(() => {
                scheduleNextPoll();
            });
        }

        setTimeout(checkDriveChanges, pollInterval);
    })();
    @endif

    // Month select (mm + yyyy) -> sync với hidden input month (mm-yyyy)
    (function () {
        const hidden = document.getElementById('month_value');
        const mmSelect = document.getElementById('month_mm');
        const yyyySelect = document.getElementById('month_yyyy');
        if (!hidden || !mmSelect || !yyyySelect) return;

        const now = new Date();
        const currentYear = now.getFullYear();

        let initialMm = '';
        let initialYyyy = '';
        if (hidden.value) {
            const val = hidden.value.trim();
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

        if (initialMm) mmSelect.value = initialMm;
        if (initialYyyy) yyyySelect.value = initialYyyy;

        const syncHidden = () => {
            const mm = (mmSelect.value || '').trim();
            const yyyy = (yyyySelect.value || '').trim();
            if (!mm || !yyyy) { hidden.value = ''; return; }
            hidden.value = `${mm}-${yyyy}`;
            if (typeof window.checkFormChanges === 'function') window.checkFormChanges();
        };

        mmSelect.addEventListener('change', syncHidden);
        yyyySelect.addEventListener('change', syncHidden);
        syncHidden();
    })();
</script>
@endpush
