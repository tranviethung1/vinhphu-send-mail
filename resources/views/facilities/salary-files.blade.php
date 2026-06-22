@extends('layouts.admin')

@section('title', $facility->name . ' — File lương')

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
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('facilities.salary-files', $facility->id) }}"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="{{ $facility->name }}">
                {{ $facility->name }}
            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;">File lương</span>
        </li>
    </ol>
</nav>
@endsection

@push('styles')
<style>
    .header {display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;}
    .title {font-weight:800;color:#111827;font-size:1.05rem;}
    .sub {color:#6b7280;font-size:0.95rem;}
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-warning {background-color:#f59e0b;}
    .btn-warning:hover {background-color:#d97706;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .table-wrapper {background:#fff;border-radius:0.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;}
    table {width:100%;border-collapse:collapse;}
    th,td {padding:0.9rem 1rem;text-align:left;border-bottom:1px solid #e5e7eb;font-size:0.92rem;vertical-align:top;}
    thead {background:#f9fafb;}
    th {text-transform:uppercase;font-size:0.78rem;letter-spacing:0.05em;color:#374151;}
    .badge {display:inline-block;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.75rem;font-weight:700;background:#dbeafe;color:#1e40af;}
    .actions {display:flex;gap:0.5rem;flex-wrap:wrap;}
    .empty {padding:2.5rem;text-align:center;color:#6b7280;}

    /* Modal */
    .modal-backdrop {position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;z-index:1000;padding:1rem;}
    .modal-backdrop.hide {display:none;}
    .modal-card {background:#fff;border-radius:0.5rem;width:min(480px,100%);box-shadow:0 10px 30px rgba(0,0,0,0.2);overflow:hidden;}
    .modal-header {padding:1rem 1.25rem;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center;}
    .modal-header h4 {margin:0;font-size:1rem;font-weight:700;color:#111827;}
    .modal-close {background:none;border:none;font-size:1.4rem;cursor:pointer;color:#6b7280;line-height:1;}
    .modal-body {padding:1.25rem;display:flex;flex-direction:column;gap:1rem;}
    .modal-actions {padding:1rem 1.25rem;border-top:1px solid #e5e7eb;display:flex;justify-content:flex-end;gap:0.5rem;}
    .form-label {display:block;margin-bottom:0.3rem;font-weight:500;color:#374151;font-size:0.8125rem;}
    .form-label .req {color:#ef4444;}
    .form-input {width:100%;padding:0.45rem 0.6rem;border:1px solid #d1d5db;border-radius:0.375rem;font-size:0.875rem;box-sizing:border-box;}
    .form-input:focus {outline:none;border-color:#4299e1;box-shadow:0 0 0 3px rgba(66,153,225,0.12);}
    .sheet-preview {background:#f0fdf4;border:1px solid #86efac;border-radius:0.375rem;padding:0.6rem 0.9rem;font-size:0.8125rem;color:#065f46;}
    .sheet-preview strong {font-family:monospace;font-size:0.9rem;}
    .help-text {font-size:0.75rem;color:#6b7280;margin-top:0.2rem;}
</style>
@endpush

@section('content')
<div class="header">
    <div>
   
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="{{ route('salary-files.create', ['facility_id' => $facility->id]) }}" class="btn">Upload file</a>
        <a href="{{ route('facilities.index') }}" class="btn btn-secondary">Quay lại cơ sở</a>
    </div>
</div>

<div class="table-wrapper">
    @if($files->isEmpty())
        <div class="empty">Cơ sở này chưa có file.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:30%;">Tên</th>
                    <th style="width:10%;">Tháng</th>
                    <th style="width:15%;">Google Sheet</th>
                    <th style="width:15%;">Ngày upload</th>
                    <th style="width:25%;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach($files as $idx => $file)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $file->name }}</strong></td>
                        <td>
                            @if($file->month)
                                <span class="badge">{{ $file->month }}</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            @if($file->google_sheet_url)
                                <a href="{{ $file->google_sheet_url }}" target="_blank" rel="noopener noreferrer" style="color:#2563eb; text-decoration:none; display:inline-flex; align-items:center; gap:0.25rem; font-weight:500;">
                                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Mở link
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $file->created_at?->format('d/m/Y H:i') }}</td>
                        <td>
                            <div class="actions">
                                <a href="{{ route('salary-files.download', $file->id) }}" class="btn btn-sm btn-secondary" title="Tải xuống" style="padding:0.375rem 0.5rem;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                                <a href="{{ route('salary-files.edit', $file->id) }}" class="btn btn-sm" title="Sửa" style="padding:0.375rem 0.5rem;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                {{-- Copy button → mở modal --}}
                                <button
                                    type="button"
                                    class="btn btn-sm btn-warning copy-btn"
                                    title="Sao chép"
                                    style="padding:0.375rem 0.5rem;"
                                    data-file-id="{{ $file->id }}"
                                    data-file-name="{{ $file->name }}"
                                    data-file-month="{{ $file->month }}"
                                    data-copy-url="{{ route('salary-files.copy', $file->id) }}"
                                >
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                                <form action="{{ route('salary-files.destroy', $file->id) }}" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá file lương này?');" style="display:inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" title="Xóa" style="padding:0.375rem 0.5rem;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

{{-- Modal Sao chép file lương --}}
<div id="copyModal" class="modal-backdrop hide" role="dialog" aria-modal="true">
    <div class="modal-card">
        <div class="modal-header">
            <h4>Sao chép file lương</h4>
            <button type="button" class="modal-close" id="closeCopyModal">×</button>
        </div>
        <div class="modal-body">
            {{-- Tên file lương --}}
            <div>
                <label class="form-label" for="copy_name">Tên file lương <span class="req">*</span></label>
                <input type="text" id="copy_name" class="form-input" placeholder="VD: Lương BN tháng 03/2026" required>
            </div>

            {{-- Tháng --}}
            <div>
                <label class="form-label">Tháng <span class="req">*</span></label>
                <div style="display:flex;gap:0.5rem;">
                    <select id="copy_month_mm" class="form-input" style="max-width:110px;">
                        @for($m = 1; $m <= 12; $m++)
                            @php($mm = str_pad((string)$m, 2, '0', STR_PAD_LEFT))
                            <option value="{{ $mm }}">{{ $mm }}</option>
                        @endfor
                    </select>
                    <select id="copy_month_yyyy" class="form-input" style="max-width:140px;"></select>
                </div>
                <p class="help-text">Chọn tháng áp dụng của file lương.</p>
            </div>

            {{-- Preview tên Google Sheet --}}
            <div>
                <label class="form-label">Tên Google Sheet sẽ tạo</label>
                <div class="sheet-preview">
                    <strong id="copy_sheet_name_preview">—</strong>
                </div>
                <p class="help-text">
                    Định dạng: <code>{{ $facility->prefix ?: 'PREFIX' }}.MM.YYYY</code>
                    @if(!$facility->prefix)
                        &nbsp;— Cơ sở chưa có prefix, vui lòng thiết lập trong phần quản lý cơ sở.
                    @endif
                </p>
            </div>
        </div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" id="cancelCopyModal">Hủy</button>
            <button type="button" class="btn btn-warning" id="confirmCopyBtn">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display:inline;vertical-align:middle;margin-right:4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Sao chép
            </button>
        </div>
    </div>
</div>

{{-- Hidden form submit copy --}}
<form id="copyForm" method="POST" style="display:none;">
    @csrf
    <input type="hidden" name="name"  id="copy_form_name">
    <input type="hidden" name="month" id="copy_form_month">
    <input type="hidden" name="sheet_name" id="copy_form_sheet_name">
</form>
@endsection

@push('scripts')
<script>
(function () {
    const facilityPrefix = @json($facility->prefix ?? '');
    const modal          = document.getElementById('copyModal');
    const copyForm       = document.getElementById('copyForm');
    const nameInput      = document.getElementById('copy_name');
    const mmSelect       = document.getElementById('copy_month_mm');
    const yyyySelect     = document.getElementById('copy_month_yyyy');
    const sheetPreview   = document.getElementById('copy_sheet_name_preview');
    const confirmBtn     = document.getElementById('confirmCopyBtn');

    // Điền năm vào select
    const now         = new Date();
    const currentYear = now.getFullYear();
    for (let y = currentYear + 2; y >= currentYear - 5; y--) {
        const opt = document.createElement('option');
        opt.value = String(y);
        opt.textContent = String(y);
        yyyySelect.appendChild(opt);
    }
    yyyySelect.value = String(currentYear);

    // Cập nhật preview tên Google Sheet
    function updateSheetPreview() {
        const mm   = mmSelect.value.padStart(2, '0');
        const yyyy = yyyySelect.value;
        const prefix = facilityPrefix || 'PREFIX';
        sheetPreview.textContent = prefix + '.' + mm + '.' + yyyy;
    }

    mmSelect.addEventListener('change',   updateSheetPreview);
    yyyySelect.addEventListener('change', updateSheetPreview);
    updateSheetPreview();

    // Mở modal khi bấm nút Copy
    document.querySelectorAll('.copy-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const fileName  = this.dataset.fileName  || '';
            const fileMonth = this.dataset.fileMonth || '';
            const copyUrl   = this.dataset.copyUrl;

            // Điền tên mặc định
            nameInput.value = fileName + ' (Copy)';

            // Điền tháng mặc định từ file gốc
            if (fileMonth && /^(0[1-9]|1[0-2])-\d{4}$/.test(fileMonth)) {
                const parts = fileMonth.split('-');
                mmSelect.value   = parts[0];
                yyyySelect.value = parts[1];
            } else {
                mmSelect.value   = String(now.getMonth() + 1).padStart(2, '0');
                yyyySelect.value = String(currentYear);
            }

            updateSheetPreview();

            copyForm.action = copyUrl;
            modal.classList.remove('hide');
            nameInput.focus();
            nameInput.select();
        });
    });

    // Đóng modal
    function closeModal() {
        modal.classList.add('hide');
    }
    document.getElementById('closeCopyModal').addEventListener('click',  closeModal);
    document.getElementById('cancelCopyModal').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });

    // Submit
    confirmBtn.addEventListener('click', function () {
        const name = nameInput.value.trim();
        if (!name) {
            nameInput.focus();
            nameInput.style.borderColor = '#ef4444';
            return;
        }
        nameInput.style.borderColor = '';

        const mm         = mmSelect.value.padStart(2, '0');
        const yyyy       = yyyySelect.value;
        const month      = mm + '-' + yyyy;
        const sheetName  = sheetPreview.textContent;

        document.getElementById('copy_form_name').value       = name;
        document.getElementById('copy_form_month').value      = month;
        document.getElementById('copy_form_sheet_name').value = sheetName;

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Đang xử lý...';
        copyForm.submit();
    });
})();
</script>
@endpush
