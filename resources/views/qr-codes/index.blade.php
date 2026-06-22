@extends('layouts.admin')

@section('title', 'Tạo QR Code')
@section('page-title', 'Tạo QR Code')

@push('styles')
<style>
    .qr-page {
        display: grid;
        grid-template-columns: 420px 1fr;
        gap: 1.5rem;
        align-items: start;
    }
    @media (max-width: 900px) {
        .qr-page { grid-template-columns: 1fr; }
    }

    .qr-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 0.875rem;
        padding: 1.5rem;
        box-shadow: 0 1px 6px rgba(0,0,0,.06);
    }
    .qr-card h2 {
        font-size: 1rem;
        font-weight: 600;
        color: #111827;
        margin: 0 0 1.25rem;
        display: flex;
        align-items: center;
        gap: .5rem;
    }

    .form-group { margin-bottom: 1rem; }
    .form-group label {
        display: block;
        font-size: .8125rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: .375rem;
    }
    .form-group input[type="text"],
    .form-group input[type="url"] {
        width: 100%;
        padding: .5rem .75rem;
        border: 1px solid #d1d5db;
        border-radius: .5rem;
        font-size: .9rem;
        color: #111827;
        background: #f9fafb;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
    }
    .form-group input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        background: #fff;
    }

    .qr-preview-wrap {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .75rem;
        padding: 1.25rem 0 .5rem;
    }
    #qr-img {
        padding-top: 5px;
        width: 200px;
        height: auto;
        max-height: 300px;
        border-radius: .5rem;
        box-shadow: 0 2px 12px rgba(0,0,0,.1);
        image-rendering: pixelated;
        display: none;
    }
    #qr-placeholder {
        width: 200px;
        height: 200px;
        border: 2px dashed #d1d5db;
        border-radius: .5rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        gap: .5rem;
        font-size: .8125rem;
    }
    #qr-url-display {
        font-size: .75rem;
        color: #6b7280;
        word-break: break-all;
        text-align: center;
        max-width: 220px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: .375rem;
        padding: .5rem 1rem;
        border-radius: .5rem;
        font-size: .875rem;
        font-weight: 500;
        cursor: pointer;
        border: none;
        transition: background .2s, transform .1s;
        text-decoration: none;
    }
    .btn:active { transform: scale(.97); }
    .btn-primary { background: #6366f1; color: #fff; }
    .btn-primary:hover { background: #4f46e5; }
    .btn-success { background: #10b981; color: #fff; }
    .btn-success:hover { background: #059669; }
    .btn-danger  { background: #ef4444; color: #fff; }
    .btn-danger:hover  { background: #dc2626; }
    .btn-outline {
        background: transparent;
        border: 1px solid #d1d5db;
        color: #374151;
    }
    .btn-outline:hover { background: #f3f4f6; }
    .btn-sm { padding: .325rem .65rem; font-size: .8rem; }
    .btn:disabled { opacity: .45; cursor: not-allowed; }
    .btn-group { display: flex; gap: .5rem; flex-wrap: wrap; margin-top: 1rem; }

    .history-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }
    .history-header h2 { margin: 0; font-size: 1rem; font-weight: 600; color: #111827; display: flex; align-items: center; gap: .5rem; }
    .badge {
        display: inline-flex; align-items: center; justify-content: center;
        background: #e0e7ff; color: #4f46e5;
        border-radius: 9999px; font-size: .75rem; font-weight: 600;
        padding: .1rem .55rem; min-width: 1.4rem;
    }

    .table-wrap { overflow-x: auto; }
    table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    thead th {
        padding: .625rem .875rem; text-align: left; font-weight: 600;
        font-size: .75rem; color: #6b7280; text-transform: uppercase;
        letter-spacing: .04em; border-bottom: 1px solid #e5e7eb; background: #f9fafb;
    }
    tbody tr { border-bottom: 1px solid #f3f4f6; transition: background .15s; }
    tbody tr:hover { background: #f9fafb; }
    tbody td { padding: .875rem .875rem; vertical-align: middle; }

    .qr-thumb-wrap { cursor: pointer; display: flex; align-items: center; justify-content: center; }
    .qr-thumb { width: 52px; height: 52px; image-rendering: pixelated; border-radius: .25rem; }

    .label-text { font-weight: 500; color: #111827; }
    .url-text { font-size: .8125rem; color: #6366f1; word-break: break-all; max-width: 320px; display: block; }
    .date-text { color: #6b7280; white-space: nowrap; }

    .empty-state { padding: 3rem 1rem; text-align: center; color: #9ca3af; }
    .empty-state svg { margin: 0 auto 1rem; display: block; opacity: .4; }
    .empty-state p { font-size: .9rem; }

    .modal-overlay {
        display: none; position: fixed; inset: 0;
        background: rgba(0,0,0,.55); z-index: 1000;
        align-items: center; justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal-box {
        background: #fff; border-radius: 1rem; padding: 1.5rem;
        display: flex; flex-direction: column; align-items: center;
        gap: 1rem; box-shadow: 0 8px 40px rgba(0,0,0,.18);
        max-width: 320px; width: 90%;
    }
    #modal-img { width: 260px; height: 260px; image-rendering: pixelated; border-radius: .5rem; }
    .modal-label { font-weight: 600; font-size: 1rem; color: #111827; text-align: center; }
    .modal-url { font-size: .8rem; color: #6b7280; word-break: break-all; text-align: center; }

    .alert { padding: .75rem 1rem; border-radius: .5rem; margin-bottom: 1rem; font-size: .9rem; display: flex; align-items: center; gap: .5rem; }
    .alert-success { background: #d1fae5; color: #065f46; }
    .alert-error   { background: #fee2e2; color: #991b1b; }

    /* Toggle switch */
    .toggle-wrap {
        display: flex;
        align-items: center;
        gap: .6rem;
        padding: .6rem .75rem;
        background: #f5f3ff;
        border: 1px solid #e0e7ff;
        border-radius: .5rem;
        cursor: pointer;
        user-select: none;
        margin-bottom: 1rem;
        font-size: .875rem;
        font-weight: 500;
        color: #4338ca;
    }
    .toggle-wrap input[type="checkbox"] { display: none; }
    .toggle-track {
        position: relative;
        width: 36px;
        height: 20px;
        background: #d1d5db;
        border-radius: 9999px;
        transition: background .2s;
        flex-shrink: 0;
    }
    .toggle-thumb {
        position: absolute;
        top: 2px; left: 2px;
        width: 16px; height: 16px;
        background: #fff;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,.25);
        transition: left .2s;
    }
    .toggle-wrap input:checked ~ .toggle-track { background: #6366f1; }
    .toggle-wrap input:checked ~ .toggle-track .toggle-thumb { left: 18px; }
    .toggle-logo-img {
        width: 20px; height: 20px;
        object-fit: contain;
        border-radius: 2px;
    }

    /* Frame template picker */
    .frame-section { margin-bottom: 1rem; }
    .frame-section > .frame-section-label {
        display: block;
        font-size: .8125rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: .5rem;
    }
    .frame-templates {
        display: flex;
        gap: .5rem;
    }
    .frame-tpl {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .3rem;
        padding: .5rem .25rem .4rem;
        border: 2px solid #e5e7eb;
        border-radius: .625rem;
        cursor: pointer;
        background: #f9fafb;
        transition: border-color .18s, background .18s;
        user-select: none;
        font-size: .72rem;
        font-weight: 500;
        color: #6b7280;
    }
    .frame-tpl:hover { border-color: #a5b4fc; background: #f5f3ff; color: #4338ca; }
    .frame-tpl.active { border-color: #6366f1; background: #eef2ff; color: #4338ca; }
    .frame-tpl svg, .frame-tpl .tpl-icon { display: block; }

    /* Bubble text input */
    #bubble-text-wrap {
        margin-top: .625rem;
        display: none;
    }
    #bubble-text-wrap label {
        display: block;
        font-size: .8125rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: .375rem;
    }
    #bubble-text {
        width: 100%;
        padding: .5rem .75rem;
        border: 1px solid #d1d5db;
        border-radius: .5rem;
        font-size: .9rem;
        color: #111827;
        background: #f9fafb;
        transition: border-color .2s, box-shadow .2s;
        box-sizing: border-box;
    }
    #bubble-text:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99,102,241,.15);
        background: #fff;
    }
</style>
@endpush

@section('content')


<div class="qr-page">

    {{-- ===== TRÁI: Form tạo QR ===== --}}
    <div class="qr-card">
        <h2>
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            Tạo QR Code mới
        </h2>

        <div class="form-group">
            <label for="live-url">Đường dẫn (URL) <span style="color:#ef4444">*</span></label>
            <input id="live-url" type="url" placeholder="https://example.com/..." autocomplete="off">
        </div>

        <div class="form-group">
            <label for="live-label">Tiêu đề / Ghi chú</label>
            <input id="live-label" type="text" placeholder="Ví dụ: Link bảng lương tháng 1">
        </div>

        {{-- Toggle logo --}}
        <label class="toggle-wrap" for="toggle-logo">
            <input type="checkbox" id="toggle-logo" checked>
            <span class="toggle-track"><span class="toggle-thumb"></span></span>
            <img class="toggle-logo-img" src="{{ asset('logo_vinh_phu.png') }}?v={{ filemtime(public_path('logo_vinh_phu.png')) }}" alt="Logo">
            Thêm logo Vinh Phú vào giữa QR
        </label>

        {{-- Frame template picker --}}
        <div class="frame-section">
            <span class="frame-section-label">Mẫu viền trang trí</span>
            <div class="frame-templates">

                {{-- None --}}
                <div class="frame-tpl active" data-frame="none" onclick="selectFrame(this)">
                    <svg width="38" height="38" viewBox="0 0 38 38" fill="none">
                        <rect x="5" y="5" width="28" height="28" rx="3" fill="#f3f4f6" stroke="#d1d5db" stroke-width="1.5"/>
                        <rect x="9" y="9" width="8" height="8" rx="1" fill="#9ca3af"/>
                        <rect x="21" y="9" width="8" height="8" rx="1" fill="#9ca3af"/>
                        <rect x="9" y="21" width="8" height="8" rx="1" fill="#9ca3af"/>
                        <rect x="19" y="19" width="10" height="10" rx="1" fill="#d1d5db"/>
                    </svg>
                    Không viền
                </div>

                {{-- Golden border --}}
                <div class="frame-tpl" data-frame="gold" onclick="selectFrame(this)">
                    <svg width="38" height="38" viewBox="0 0 38 38" fill="none">
                        <rect x="2" y="2" width="34" height="34" rx="5" fill="#fffbeb" stroke="#f59e0b" stroke-width="3"/>
                        <rect x="7" y="7" width="24" height="24" rx="2" fill="none" stroke="#dc2626" stroke-width="1.2" stroke-dasharray="2 1"/>
                        <circle cx="2"  cy="2"  r="2.5" fill="#fbbf24"/>
                        <circle cx="36" cy="2"  r="2.5" fill="#fbbf24"/>
                        <circle cx="2"  cy="36" r="2.5" fill="#fbbf24"/>
                        <circle cx="36" cy="36" r="2.5" fill="#fbbf24"/>
                        <rect x="10" y="10" width="7" height="7" rx="1" fill="#fde68a"/>
                        <rect x="21" y="10" width="7" height="7" rx="1" fill="#fde68a"/>
                        <rect x="10" y="21" width="7" height="7" rx="1" fill="#fde68a"/>
                    </svg>
                    Viền vàng
                </div>

                {{-- Speech bubble --}}
                <div class="frame-tpl" data-frame="bubble" onclick="selectFrame(this)">
                    <svg width="38" height="46" viewBox="0 0 38 46" fill="none">
                        <rect x="1" y="1" width="36" height="14" rx="4" fill="#111827"/>
                        <polygon points="12,15 26,15 19,22" fill="#111827"/>
                        <rect x="3" y="24" width="32" height="20" rx="3" fill="#f3f4f6" stroke="#d1d5db" stroke-width="1.2"/>
                        <rect x="6" y="27" width="7" height="7" rx="1" fill="#9ca3af"/>
                        <rect x="16" y="27" width="7" height="3" rx="1" fill="#9ca3af"/>
                        <rect x="16" y="32" width="5" height="2" rx="1" fill="#d1d5db"/>
                        <rect x="6" y="36" width="7" height="5" rx="1" fill="#9ca3af"/>
                    </svg>
                    Bong bóng
                </div>

            </div>

            {{-- Text input for bubble template --}}
            <div id="bubble-text-wrap">
                <label for="bubble-text">Nội dung bong bóng</label>
                <input type="text" id="bubble-text" placeholder="Ví dụ: Quét mã để xem lương" maxlength="60">
            </div>
        </div>

        {{-- Preview QR --}}
        <div class="qr-preview-wrap">
            <div id="qr-placeholder">
                <svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Nhập URL để xem QR</span>
            </div>
            <img id="qr-img" alt="QR Code preview">
            <span id="qr-url-display"></span>
        </div>

        <div class="btn-group">
            <button id="btn-download" class="btn btn-success" disabled>
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Tải xuống
            </button>
            <button id="btn-save" class="btn btn-primary" disabled>
                <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                </svg>
                Lưu vào lịch sử
            </button>
        </div>

        <form id="save-form" action="{{ route('qr-codes.store') }}" method="POST" style="display:none;">
            @csrf
            <input type="hidden" name="url"         id="form-url">
            <input type="hidden" name="label"       id="form-label">
            <input type="hidden" name="frame_type"  id="form-frame-type">
            <input type="hidden" name="bubble_text" id="form-bubble-text">
        </form>
    </div>

    {{-- ===== PHẢI: Lịch sử ===== --}}
    <div class="qr-card">
        <div class="history-header">
            <h2>
                <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Lịch sử tạo QR
                <span class="badge">{{ $qrCodes->count() }}</span>
            </h2>
        </div>

        @if($qrCodes->isEmpty())
        <div class="empty-state">
            <svg width="56" height="56" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <p>Chưa có QR code nào được lưu.<br>Hãy tạo QR code đầu tiên!</p>
        </div>
        @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th style="width:70px;">QR</th>
                        <th>Tiêu đề / URL</th>
                        <th>Ngày tạo</th>
                        <th style="width:130px;text-align:right;">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($qrCodes as $qr)
                    <tr>
                        <td>
                            <div class="qr-thumb-wrap"
                                onclick="openModal('{{ addslashes($qr->url) }}', '{{ addslashes($qr->label ?? $qr->url) }}', '{{ $qr->frame_type ?? 'none' }}', '{{ addslashes($qr->bubble_text ?? '') }}')">
                                <img class="qr-thumb history-qr"
                                    data-url="{{ $qr->url }}"
                                    data-frame="{{ $qr->frame_type ?? 'none' }}"
                                    data-bubble="{{ $qr->bubble_text ?? '' }}"
                                    src=""
                                    alt="QR"
                                    title="Click để phóng to">
                            </div>
                        </td>
                        <td>
                            <span class="label-text">{{ $qr->label ?: '—' }}</span>
                            <a class="url-text" href="{{ $qr->url }}" target="_blank" rel="noopener">
                                {{ Str::limit($qr->url, 60) }}
                            </a>
                        </td>
                        <td>
                            <span class="date-text">{{ $qr->created_at->format('d/m/Y H:i') }}</span>
                        </td>
                        <td style="text-align:right;">
                            <div style="display:flex;gap:.375rem;justify-content:flex-end;flex-wrap:wrap;">
                                <button class="btn btn-outline btn-sm"
                                    onclick="downloadHistoryQr('{{ addslashes($qr->url) }}', '{{ addslashes($qr->label ?: 'qrcode') }}', '{{ $qr->frame_type ?? 'none' }}', '{{ addslashes($qr->bubble_text ?? '') }}')">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                    Tải
                                </button>
                                <form action="{{ route('qr-codes.destroy', $qr->id) }}" method="POST"
                                    onsubmit="return confirm('Xóa QR code này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Xóa
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
</div>

{{-- Modal phóng to --}}
<div class="modal-overlay" id="qr-modal" onclick="closeModal(event)">
    <div class="modal-box" onclick="event.stopPropagation()">
        <p class="modal-label" id="modal-label"></p>
        <img id="modal-img" src="" alt="QR Code">
        <p class="modal-url" id="modal-url"></p>
        <div style="display:flex;gap:.5rem;">
            <button id="modal-download" class="btn btn-success btn-sm">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Tải xuống
            </button>
            <button onclick="closeModalDirect()" class="btn btn-outline btn-sm">Đóng</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
{{-- Thư viện QR Code Generator (local, không cần internet) --}}
<script src="{{ asset('js/qrcode-generator.js') }}"></script>
<script>
var LOGO_SRC = '{{ asset('logo_vinh_phu.png') }}?v={{ filemtime(public_path('logo_vinh_phu.png')) }}';

// ===== Tạo QR data URL thuần (không logo) =====
// ecLevel: 'L','M','Q','H' — dùng 'H' khi có logo để đảm bảo scan được
function makeQrDataUrl(text, cellSize, ecLevel) {
    ecLevel = ecLevel || 'M';
    try {
        var qr = qrcode(0, ecLevel);
        qr.addData(text);
        qr.make();
        return qr.createDataURL(cellSize || 5, 0);
    } catch (e) {
        try {
            var qr2 = qrcode(10, 'L');
            qr2.addData(text);
            qr2.make();
            return qr2.createDataURL(cellSize || 5, 0);
        } catch (e2) { return ''; }
    }
}

// ===== Vẽ logo vào giữa QR bằng Canvas =====
// callback(dataUrl) — bất đồng bộ vì cần load ảnh
function makeQrWithLogo(text, cellSize, callback) {
    // Dùng error correction 'H' (30%) để QR vẫn scan được khi che logo
    var qrDataUrl = makeQrDataUrl(text, cellSize, 'H');
    if (!qrDataUrl) { callback(''); return; }

    var canvas  = document.createElement('canvas');
    var qrImage = new Image();
    qrImage.onload = function () {
        canvas.width  = qrImage.width;
        canvas.height = qrImage.height;
        var ctx = canvas.getContext('2d');

        // Vẽ QR code
        ctx.drawImage(qrImage, 0, 0);

        // Kích thước logo: ~22% chiều rộng QR
        var logoSize = Math.round(canvas.width * 0.22);
        var lx = Math.round((canvas.width  - logoSize) / 2);
        var ly = Math.round((canvas.height - logoSize) / 2);
        var pad = Math.round(logoSize * 0.12);

        // Nền trắng bo góc cho logo
        ctx.fillStyle = '#ffffff';
        ctx.beginPath();
        var rx = lx - pad, ry = ly - pad;
        var rw = logoSize + pad * 2, rh = logoSize + pad * 2, r = 8;
        ctx.moveTo(rx + r, ry);
        ctx.lineTo(rx + rw - r, ry); ctx.quadraticCurveTo(rx + rw, ry, rx + rw, ry + r);
        ctx.lineTo(rx + rw, ry + rh - r); ctx.quadraticCurveTo(rx + rw, ry + rh, rx + rw - r, ry + rh);
        ctx.lineTo(rx + r, ry + rh); ctx.quadraticCurveTo(rx, ry + rh, rx, ry + rh - r);
        ctx.lineTo(rx, ry + r); ctx.quadraticCurveTo(rx, ry, rx + r, ry);
        ctx.closePath();
        ctx.fill();

        // Vẽ logo
        var logo = new Image();
        logo.onload = function () {
            ctx.drawImage(logo, lx, ly, logoSize, logoSize);
            callback(canvas.toDataURL('image/png'));
        };
        logo.onerror = function () {
            // Logo không load được — trả về QR không có logo
            callback(canvas.toDataURL('image/png'));
        };
        logo.src = LOGO_SRC;
    };
    qrImage.onerror = function () { callback(''); };
    qrImage.src = qrDataUrl;
}

// ===== Vẽ viền trang trí xung quanh QR =====
function _drawRoundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.lineTo(x + w - r, y); ctx.quadraticCurveTo(x + w, y, x + w, y + r);
    ctx.lineTo(x + w, y + h - r); ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    ctx.lineTo(x + r, y + h); ctx.quadraticCurveTo(x, y + h, x, y + h - r);
    ctx.lineTo(x, y + r); ctx.quadraticCurveTo(x, y, x + r, y);
    ctx.closePath();
}

function wrapWithFrame(dataUrl, callback) {
    if (!dataUrl) { callback(dataUrl); return; }
    var img = new Image();
    img.onload = function () {
        var pad      = Math.round(img.width * 0.16);
        var bWidth   = Math.max(6, Math.round(img.width * 0.028));
        var total    = img.width + pad * 2;
        var canvas   = document.createElement('canvas');
        canvas.width = canvas.height = total;
        var ctx = canvas.getContext('2d');

        // Nền trắng
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, total, total);

        // Bóng mờ nhẹ bên trong viền
        ctx.save();
        _drawRoundRect(ctx, bWidth, bWidth, total - bWidth * 2, total - bWidth * 2, 12);
        ctx.shadowColor = 'rgba(245,158,11,0.18)';
        ctx.shadowBlur  = 12;
        ctx.fillStyle   = '#ffffff';
        ctx.fill();
        ctx.restore();

        // Viền ngoài vàng/cam đậm
        ctx.strokeStyle = '#f59e0b';
        ctx.lineWidth   = bWidth;
        _drawRoundRect(ctx, bWidth / 2, bWidth / 2, total - bWidth, total - bWidth, 14);
        ctx.stroke();

        // Đường viền trong đỏ mỏng
        var innerOff = pad - Math.round(pad * 0.28);
        ctx.strokeStyle = '#dc2626';
        ctx.lineWidth   = 1.8;
        _drawRoundRect(ctx, innerOff, innerOff, total - innerOff * 2, total - innerOff * 2, 6);
        ctx.stroke();

        // QR ở giữa
        ctx.drawImage(img, pad, pad);

        // Điểm trang trí ở 4 góc viền ngoài
        var dotR = Math.round(bWidth * 1.6);
        var inset = bWidth / 2;
        [[inset, inset], [total - inset, inset], [inset, total - inset], [total - inset, total - inset]].forEach(function (p) {
            ctx.beginPath();
            ctx.arc(p[0], p[1], dotR, 0, Math.PI * 2);
            ctx.fillStyle = '#fbbf24';
            ctx.fill();
            ctx.strokeStyle = '#ffffff';
            ctx.lineWidth = 1.5;
            ctx.stroke();

            // Vòng nhỏ bên trong điểm góc
            ctx.beginPath();
            ctx.arc(p[0], p[1], dotR * 0.45, 0, Math.PI * 2);
            ctx.fillStyle = '#f59e0b';
            ctx.fill();
        });

        // Điểm nhỏ giữa mỗi cạnh viền
        var midDotR = Math.round(bWidth * 0.9);
        [[total / 2, inset], [total / 2, total - inset], [inset, total / 2], [total - inset, total / 2]].forEach(function (p) {
            ctx.beginPath();
            ctx.arc(p[0], p[1], midDotR, 0, Math.PI * 2);
            ctx.fillStyle = '#fde68a';
            ctx.fill();
            ctx.strokeStyle = '#f59e0b';
            ctx.lineWidth = 1.2;
            ctx.stroke();
        });

        callback(canvas.toDataURL('image/png'));
    };
    img.onerror = function () { callback(dataUrl); };
    img.src = dataUrl;
}

// ===== Vẽ mẫu bong bóng chat phía trên QR =====
function wrapWithSpeechBubble(dataUrl, bubbleText, callback) {
    if (!dataUrl) { callback(dataUrl); return; }
    var img = new Image();
    img.onload = function () {
        var qrW     = img.width;
        var qrH     = img.height;

        // Kích thước viền QR
        var bPad    = Math.round(qrW * 0.025);
        var borderW = Math.max(3, Math.round(qrW * 0.018));
        var borderR = Math.round(qrW * 0.04);
        // Phần viền tổng (path + nửa nét vẽ) tính từ mép ảnh QR ra ngoài
        var outer   = bPad + Math.ceil(borderW / 2);

        // Margin đều 4 phía của canvas
        var margin  = Math.round(qrW * 0.05);

        // Kích thước bong bóng = đúng bằng viền ngoài cùng của QR border
        var bubbleH  = Math.round(qrW * 0.22);
        var arrowH   = Math.round(qrW * 0.09);
        var gapH     = Math.round(qrW * 0.04);

        // Vị trí
        var bubbleX  = margin;
        var bubbleW  = qrW + outer * 2;
        var bubbleCX = bubbleX + bubbleW / 2;
        var qrX      = margin + outer;
        var qrY      = margin + bubbleH + arrowH + gapH;

        var totalW   = bubbleW + margin * 2;
        var totalH   = margin + bubbleH + arrowH + gapH + qrH + outer * 2 + margin;

        var canvas = document.createElement('canvas');
        canvas.width  = totalW;
        canvas.height = totalH;
        var ctx = canvas.getContext('2d');

        // Nền trắng
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, totalW, totalH);

        // Hộp bong bóng — rộng đúng bằng outer edge của viền QR
        var br = Math.round(bubbleH * 0.22);
        ctx.fillStyle = '#111827';
        _drawRoundRect(ctx, bubbleX, margin, bubbleW, bubbleH, br);
        ctx.fill();

        // Tam giác mũi tên chỉ xuống
        var arrowHalfW = Math.round(arrowH * 0.75);
        ctx.beginPath();
        ctx.moveTo(bubbleCX - arrowHalfW, margin + bubbleH);
        ctx.lineTo(bubbleCX + arrowHalfW, margin + bubbleH);
        ctx.lineTo(bubbleCX, margin + bubbleH + arrowH);
        ctx.closePath();
        ctx.fillStyle = '#111827';
        ctx.fill();

        // Chữ bên trong bong bóng
        var displayText = (bubbleText || '').trim();
        if (displayText) {
            var fontSize = Math.round(bubbleH * 0.34);
            ctx.font      = 'bold ' + fontSize + 'px Arial, sans-serif';
            ctx.fillStyle = '#ffffff';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            var maxW = bubbleW - Math.round(bubbleW * 0.1);
            ctx.fillText(displayText, bubbleCX, margin + bubbleH / 2, maxW);
        }

        // Viền đen bo góc quanh QR
        ctx.strokeStyle = '#111827';
        ctx.lineWidth   = borderW;
        _drawRoundRect(ctx, qrX - bPad, qrY - bPad, qrW + bPad * 2, qrH + bPad * 2, borderR);
        ctx.stroke();

        // QR code
        ctx.drawImage(img, qrX, qrY, qrW, qrH);

        callback(canvas.toDataURL('image/png'));
    };
    img.onerror = function () { callback(dataUrl); };
    img.src = dataUrl;
}

// ===== Thêm padding trắng xung quanh ảnh =====
function addPadding(dataUrl, top, right, bottom, left, callback) {
    if (!dataUrl) { callback(dataUrl); return; }
    var img = new Image();
    img.onload = function () {
        var canvas = document.createElement('canvas');
        canvas.width  = img.width  + left + right;
        canvas.height = img.height + top  + bottom;
        var ctx = canvas.getContext('2d');
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(img, left, top);
        callback(canvas.toDataURL('image/png'));
    };
    img.onerror = function () { callback(dataUrl); };
    img.src = dataUrl;
}

// ===== Sinh QR có hoặc không logo / viền tùy lựa chọn =====
// frameType: 'none' | 'gold' | 'bubble'
function makeQr(text, cellSize, withLogo, frameType, bubbleText, callback) {
    if (typeof frameType === 'function') { callback = frameType; frameType = 'none'; bubbleText = ''; }
    if (typeof bubbleText === 'function') { callback = bubbleText; bubbleText = ''; }
    frameType = frameType || 'none';

    var finish = function (dataUrl) {
        if (!dataUrl) { callback(dataUrl); return; }
        if (frameType === 'gold') {
            wrapWithFrame(dataUrl, callback);
        } else if (frameType === 'bubble') {
            wrapWithSpeechBubble(dataUrl, bubbleText, callback);
        } else {
            addPadding(dataUrl, 5, 0, 0, 0, callback);
        }
    };
    if (withLogo) {
        makeQrWithLogo(text, cellSize, finish);
    } else {
        finish(makeQrDataUrl(text, cellSize));
    }
}

// ===== Helper: download =====
function downloadDataUrl(dataUrl, filename) {
    var a = document.createElement('a');
    a.href = dataUrl; a.download = filename;
    document.body.appendChild(a); a.click(); document.body.removeChild(a);
}
function sanitizeFilename(str) {
    return (str || 'qrcode').replace(/[^\w\u00C0-\u024F\u1E00-\u1EFF\s\-]/g, '')
        .trim().replace(/\s+/g, '_').substring(0, 80) || 'qrcode';
}

// ===== Lấy frameType & bubbleText từ UI =====
function getFrameType() {
    var active = document.querySelector('.frame-tpl.active');
    return active ? active.getAttribute('data-frame') : 'none';
}
function getBubbleText() {
    var el = document.getElementById('bubble-text');
    return el ? el.value.trim() : '';
}

// ===== Chọn mẫu viền =====
function selectFrame(el) {
    document.querySelectorAll('.frame-tpl').forEach(function (t) { t.classList.remove('active'); });
    el.classList.add('active');
    var isBubble = el.getAttribute('data-frame') === 'bubble';
    document.getElementById('bubble-text-wrap').style.display = isBubble ? 'block' : 'none';
    // Re-render live preview
    var url = document.getElementById('live-url') && document.getElementById('live-url').value.trim();
    if (url) generateLiveQR(url);
}

var generateLiveQR; // hoisted so selectFrame can call it

// ===== LIVE PREVIEW =====
document.addEventListener('DOMContentLoaded', function () {
    var liveUrl     = document.getElementById('live-url');
    var liveLabel   = document.getElementById('live-label');
    var toggleLogo  = document.getElementById('toggle-logo');
    var bubbleTextEl= document.getElementById('bubble-text');
    var qrImg       = document.getElementById('qr-img');
    var placeholder = document.getElementById('qr-placeholder');
    var urlDisplay  = document.getElementById('qr-url-display');
    var btnDownload = document.getElementById('btn-download');
    var btnSave     = document.getElementById('btn-save');
    var currentDataUrl = '';
    var debounceTimer;

    generateLiveQR = function (url) {
        url = (url || '').trim();
        if (!url) {
            qrImg.style.display = 'none';
            placeholder.style.display = 'flex';
            urlDisplay.textContent = '';
            btnDownload.disabled = true;
            btnSave.disabled = true;
            currentDataUrl = '';
            return;
        }
        var withLogo  = toggleLogo.checked;
        var frameType = getFrameType();
        var bText     = getBubbleText();
        makeQr(url, 5, withLogo, frameType, bText, function (dataUrl) {
            if (!dataUrl) return;
            currentDataUrl = dataUrl;
            qrImg.src = dataUrl;
            qrImg.style.display = 'block';
            placeholder.style.display = 'none';
            urlDisplay.textContent = url.length > 45 ? url.substring(0, 45) + '…' : url;
            btnDownload.disabled = false;
            btnSave.disabled = false;
        });
    };

    liveUrl.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () { generateLiveQR(liveUrl.value); }, 250);
    });

    toggleLogo.addEventListener('change', function () {
        if (liveUrl.value.trim()) generateLiveQR(liveUrl.value);
    });

    // Re-render khi đổi nội dung bong bóng
    bubbleTextEl.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            if (liveUrl.value.trim()) generateLiveQR(liveUrl.value);
        }, 300);
    });

    btnDownload.addEventListener('click', function () {
        var url   = liveUrl.value.trim();
        var label = liveLabel.value.trim() || 'qrcode';
        if (!url) return;
        makeQr(url, 8, toggleLogo.checked, getFrameType(), getBubbleText(), function (dataUrl) {
            if (dataUrl) downloadDataUrl(dataUrl, sanitizeFilename(label) + '.png');
        });
    });

    btnSave.addEventListener('click', function () {
        var url = liveUrl.value.trim();
        if (!url) return;
        document.getElementById('form-url').value         = url;
        document.getElementById('form-label').value       = liveLabel.value.trim();
        document.getElementById('form-frame-type').value  = getFrameType();
        document.getElementById('form-bubble-text').value = getBubbleText();
        document.getElementById('save-form').submit();
    });

    // ===== THUMBNAIL LỊCH SỬ (có logo + frame đã lưu) =====
    document.querySelectorAll('.history-qr').forEach(function (el) {
        var url       = el.getAttribute('data-url');
        var frameType = el.getAttribute('data-frame') || 'none';
        var bubbleTxt = el.getAttribute('data-bubble') || '';
        if (!url) return;
        makeQr(url, 2, true, frameType, bubbleTxt, function (dataUrl) {
            if (dataUrl) el.src = dataUrl;
        });
    });
});

// ===== DOWNLOAD TỪ LỊCH SỬ (dùng frame đã lưu) =====
function downloadHistoryQr(url, label, frameType, bubbleText) {
    frameType  = frameType  || 'none';
    bubbleText = bubbleText || '';
    makeQr(url, 8, true, frameType, bubbleText, function (dataUrl) {
        if (!dataUrl) dataUrl = makeQrDataUrl(url, 8);
        if (dataUrl) downloadDataUrl(dataUrl, sanitizeFilename(label) + '.png');
    });
}

// ===== MODAL (dùng frame đã lưu) =====
function openModal(url, label, frameType, bubbleText) {
    frameType  = frameType  || 'none';
    bubbleText = bubbleText || '';
    document.getElementById('modal-label').textContent = label;
    document.getElementById('modal-url').textContent   = url;
    document.getElementById('modal-img').src = '';
    makeQr(url, 6, true, frameType, bubbleText, function (dataUrl) {
        document.getElementById('modal-img').src = dataUrl || makeQrDataUrl(url, 6);
    });
    document.getElementById('modal-download').onclick = function () {
        downloadHistoryQr(url, label, frameType, bubbleText);
    };
    document.getElementById('qr-modal').classList.add('open');
}
function closeModal(e) {
    if (e.target === document.getElementById('qr-modal')) {
        document.getElementById('qr-modal').classList.remove('open');
    }
}
function closeModalDirect() {
    document.getElementById('qr-modal').classList.remove('open');
}
</script>
@endpush
