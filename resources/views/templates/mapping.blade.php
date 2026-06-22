@extends('layouts.admin')

@section('title', 'Quản lý Template')

@section('content')
@push('styles')
<style>
    .upload-card {
        background: white;
        border-radius: 0.375rem;
        padding: 1rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        margin-bottom: 1rem;
    }
    .upload-area {
        border: 2px dashed #cbd5e0;
        border-radius: 0.375rem;
        padding: 1.5rem;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }
    .upload-area:hover {
        border-color: #4299e1;
        background-color: #f7fafc;
    }
    .upload-area.dragover {
        border-color: #4299e1;
        background-color: #ebf8ff;
    }
    .btn {
        display: inline-block;
        padding: 0.5rem 1rem;
        background-color: #4299e1;
        color: white;
        border-radius: 0.25rem;
        cursor: pointer;
        border: none;
        font-weight: 500;
        text-decoration: none;
        transition: background-color 0.2s;
        font-size: 0.8125rem;
    }
    .btn:hover { background-color: #3182ce; }
    .btn-secondary { background-color: #6b7280; }
    .btn-secondary:hover { background-color: #4b5563; }
    .file-info {
        margin-top: 0.75rem;
        padding: 0.75rem;
        background-color: #f7fafc;
        border-radius: 0.25rem;
        font-size: 0.8125rem;
    }
    .sheet-block {
        background: white;
        border-radius: 0.375rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 0.75rem;
    }
    .sheet-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    .sheet-header h3 { margin: 0; font-size: 0.9375rem; font-weight: 600; }
    .excel-container { overflow: auto; max-height: 600px; }
    .excel-table { border-collapse: separate; border-spacing: 0; font-size: 10pt; }
    .excel-table th, .excel-table td {
        border: 1px solid #D0D7E5;
        padding: 4px 8px;
        white-space: nowrap;
    }
    /* Độ rộng cột sẽ được set inline từ Excel, bỏ min-width và max-width cố định */
    .excel-table th {
        background-color: #F2F2F2;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid #D0D7E5;
        text-align: center;
    }
    .row-header {
        background-color: #F2F2F2 !important;
        font-weight: 600;
        text-align: center;
        min-width: 40px;
        max-width: 40px;
        position: sticky;
        left: 0;
        z-index: 5;
        border-right: 2px solid #D0D7E5;
        font-size: 0.75rem;
    }
    .number-cell { text-align: right; font-variant-numeric: tabular-nums; }
    .empty-cell { background-color: #FFFFFF; }

    /* Template editor layout (after upload) */
    .template-editor {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 320px;
        gap: 0.75rem;
        align-items: start;
    }
    @media (max-width: 1024px) {
        .template-editor {
            grid-template-columns: 1fr;
        }
    }
    .side-panel {
        background: #ffffff;
        border-radius: 0.375rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e5e7eb;
        padding: 0.75rem;
        position: sticky;
        top: 1rem;
        font-size: 0.8125rem;
    }
    .side-panel h3 {
        margin: 0 0 0.75rem 0;
        font-size: 0.875rem;
    }
    .form-group { margin-bottom: 0.65rem; }
    .form-group label {
        display: block;
        font-size: 0.72rem;
        color: #374151;
        margin-bottom: 0.2rem;
        font-weight: 600;
    }
    .form-control {
        width: 100%;
        padding: 0.45rem 0.6rem;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
        font-size: 0.78rem;
        outline: none;
        background: #fff;
    }
    .form-control:focus {
        border-color: #4299e1;
        box-shadow: 0 0 0 3px rgba(66,153,225,0.15);
    }
    .help-text {
        font-size: 0.75rem;
        color: #6b7280;
        margin: 0.5rem 0 0 0;
    }
    .selected-cell {
        outline: 2px solid #2563eb !important;
        outline-offset: -2px;
        box-shadow: inset 0 0 0 1px rgba(37,99,235,0.35);
    }
    .selection-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        margin-bottom: 0.75rem;
    }
    .selection-chip {
        padding: 0.35rem 0.5rem;
        border: 1px solid #cbd5e0;
        border-radius: 0.375rem;
        background: #f8fafc;
        cursor: pointer;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.35rem;
        transition: all 0.2s;
    }
    .selection-chip.active {
        border-color: #2563eb;
        background: #e0ecff;
        color: #1d4ed8;
        font-weight: 600;
    }
    .color-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
        border: 1px solid rgba(0,0,0,0.1);
    }
    .chip-delete {
        background: transparent;
        border: none;
        color: #6b7280;
        cursor: pointer;
        font-size: 1.25rem;
        line-height: 1;
        padding: 0;
        width: 18px;
        height: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 0.125rem;
        transition: all 0.2s;
    }
    .chip-delete:hover {
        background: #ef4444;
        color: white;
    }
    .selection-chip.active .chip-delete {
        color: #1d4ed8;
    }
    .selection-chip.active .chip-delete:hover {
        background: #dc2626;
        color: white;
    }
    .type-toggle-help {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
    }
    .type-section { display: none; }
    .type-section.active { display: block; }
    .formula-source {
        margin-top: 0.35rem;
        gap: 0.25rem;
    }
    .formula-chip {
        padding: 0.3rem 0.45rem;
        border: 1px dashed #cbd5e0;
        border-radius: 0.25rem;
        background: #f9fafb;
        cursor: pointer;
        font-size: 0.75rem;
    }
    .formula-chip:hover {
        border-color: #2563eb;
        color: #1d4ed8;
    }
</style>
@endpush

<div class="page-header" style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
    <div class="page-title">
        <h1 style="font-size: 1.25rem; margin-bottom: 0.25rem;">
            {{ isset($editingTemplate) ? 'Chỉnh sửa mapping Template' : 'Tạo mapping Template từ Excel' }}
        </h1>
        <p style="font-size: 0.8125rem; margin: 0;">Upload file Excel và lấy nội dung từ Sheet 1 (cột A-D).</p>
    </div>
    <div>
        <a href="{{ route('templates.index') }}" class="btn btn-secondary">Quay lại danh sách</a>
    </div>
</div>

@if(!isset($fileName))
    <div class="upload-card">
        <form action="{{ route('templates.upload') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
            @csrf
            <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                <div>
                    <label style="font-size: 0.875rem; font-weight: 600; display:block; margin-bottom: 0.5rem;">
                        Chọn file Excel <span style="color: #ef4444;">*</span>
                    </label>

                    <input
                        type="file"
                        name="excel_file"
                        id="excel_file"
                        accept=".xlsx,.xls,.xlsm"
                        required
                        style="width: 100%; padding: 0.5rem; border: 1px solid #d1d5db; border-radius: 0.375rem; font-size: 0.875rem; background: white;"
                    >

                    <p style="color: #718096; margin-top: 0.5rem; font-size: 0.75rem;">
                        Chỉ chấp nhận file .xlsx, .xls hoặc .xlsm (tối đa 10MB)
                    </p>

                    <div id="excel_file_info" class="file-info" style="display:none; margin-top:0.75rem; padding: 0.75rem; background-color: #f7fafc; border-radius: 0.375rem; font-size: 0.875rem;">
                        <strong>File:</strong> <span id="excel_file_name"></span> -
                        <strong>Kích thước:</strong> <span id="excel_file_size"></span>
                    </div>
                </div>

                <div style="text-align: left;">
                    <button type="submit" class="btn" id="submitBtn">Upload và Xem nội dung</button>
                </div>
            </div>
        </form>
    </div>
@endif

@if(!empty($error ?? null))
    <div class="upload-card" style="border-left: 4px solid #ef4444;">
        <strong style="color:#b91c1c;">{{ $error }}</strong>
    </div>
@endif

@if(isset($fileName))
    <div class="upload-card" style="padding: 0.75rem;">
        <div style="display:flex; justify-content: space-between; gap: 0.75rem; align-items: center; flex-wrap: wrap;">
            <div style="font-size: 0.8125rem;">
                <div><strong>Tên file:</strong> {{ $fileName }}</div>
                @if(isset($sheetNames))
                    <div style="margin-top: 0.25rem;"><strong>Số sheet:</strong> {{ count($sheetNames) }}</div>
                @endif
            </div>
            @if(isset($editingTemplate))
                <div>
                    <form action="{{ route('templates.replaceFile', $editingTemplate->id) }}" method="POST" enctype="multipart/form-data" style="display:flex; align-items: center; gap: 0.5rem;">
                        @csrf
                        <label for="excel_file_replace" class="btn btn-secondary">Đổi file Excel</label>
                        <input type="file" name="excel_file" id="excel_file_replace" accept=".xlsx,.xls,.xlsm" required>
                        <button type="submit" class="btn">Upload</button>
                    </form>
                    <p style="color: #718096; margin-top: 0.25rem; font-size: 0.75rem;">
                        Upload file Excel mới để thay thế file hiện tại cho template này.
                    </p>
                </div>
            @endif
        </div>
    </div>
@endif

@if(!empty($sheets ?? []))
    @php
        if (!function_exists('getColumnLetter')) {
            function getColumnLetter($num) {
                $result = '';
                while ($num > 0) {
                    $num--;
                    $result = chr(65 + ($num % 26)) . $result;
                    $num = intval($num / 26);
                }
                return $result;
            }
        }
    @endphp

    <div class="template-editor" id="templateEditor">
        <div>
    @foreach($sheets as $sheetIndex => $sheet)
        @php
            $data = $sheet['data'] ?? [];
            $styles = $sheet['styles'] ?? [];
            $rowHeights = $sheet['rowHeights'] ?? [];
            $columnWidths = $sheet['columnWidths'] ?? [];
            $maxColumns = 4; // Chỉ hiển thị 4 cột: A, B, C, D
        @endphp
        <div class="sheet-block">
            <div class="sheet-header">
                <h3>Sheet: {{ $sheet['name'] ?? ('Sheet ' . ($sheetIndex + 1)) }}</h3>
                <div style="color:#6b7280; font-size: 0.75rem;">
                    {{ count($data) }} dòng · {{ $maxColumns }} cột
                </div>
            </div>
            <div class="excel-container">
                <table class="excel-table">
                    <thead>
                        <tr>
                            <th class="row-header" style="z-index: 20;">&nbsp;</th>
                            @for($i = 0; $i < $maxColumns; $i++)
                                @php
                                    $colIndex = $i + 1;
                                    $colWidth = $columnWidths[$colIndex] ?? null;
                                    $widthStyle = $colWidth ? "width: {$colWidth}px; min-width: {$colWidth}px; max-width: {$colWidth}px;" : '';
                                @endphp
                                <th style="{{ $widthStyle }}">{{ getColumnLetter($i + 1) }}</th>
                            @endfor
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $rowIndex => $row)
                            @php
                                $excelRow = $rowIndex + 1; // 1-based
                                $rowHeightPx = $rowHeights[$excelRow] ?? null;
                            @endphp
                            <tr @if($rowHeightPx) data-row-height="{{ $rowHeightPx }}" @endif>
                                <td class="row-header">{{ $rowIndex + 1 }}</td>
                                @for($i = 0; $i < $maxColumns; $i++)
                                    @php
                                        $cellStyle = $styles[$rowIndex][$i] ?? [];
                                        $styleString = '';
                                        if (!empty($cellStyle)) {
                                            $styleString = 'background-color: ' . ($cellStyle['backgroundColor'] ?? '#FFFFFF') . '; ';
                                            $styleString .= 'color: ' . ($cellStyle['fontColor'] ?? '#000000') . '; ';
                                            $styleString .= 'font-size: ' . ($cellStyle['fontSize'] ?? '11pt') . '; ';
                                            $styleString .= 'font-family: ' . ($cellStyle['fontFamily'] ?? 'Calibri, Arial, sans-serif') . '; ';
                                            $styleString .= 'text-align: ' . ($cellStyle['textAlign'] ?? 'left') . '; ';
                                            $styleString .= 'vertical-align: ' . ($cellStyle['verticalAlign'] ?? 'middle') . '; ';
                                            if (!empty($cellStyle['fontBold'])) $styleString .= 'font-weight: bold; ';
                                            if (!empty($cellStyle['fontItalic'])) $styleString .= 'font-style: italic; ';
                                            if (!empty($cellStyle['fontUnderline'])) $styleString .= 'text-decoration: underline; ';
                                            if (!empty($cellStyle['border']) && $cellStyle['border'] !== 'none') {
                                                $styleString .= 'border: 1px solid ' . ($cellStyle['borderColor'] ?? '#000000') . '; ';
                                            }
                                        }
                                        
                                        // Thêm độ rộng cột vào style
                                        $colIndex = $i + 1;
                                        $colWidth = $columnWidths[$colIndex] ?? null;
                                        if ($colWidth) {
                                            $styleString .= "width: {$colWidth}px; min-width: {$colWidth}px; max-width: {$colWidth}px; ";
                                        }

                                        $val = $row[$i] ?? '';
                                        $addr = getColumnLetter($i + 1) . ($rowIndex + 1);
                                    @endphp
                                    <td
                                        style="{{ $styleString }}"
                                        class="tpl-cell {{ is_numeric(str_replace([',', ' '], '', (string)$val)) ? 'number-cell' : '' }}"
                                        data-row="{{ $rowIndex + 1 }}"
                                        data-col="{{ $i + 1 }}"
                                        data-address="{{ $addr }}"
                                        data-value="{{ e((string)$val) }}"
                                        data-original="{{ e((string)$val) }}"
                                        data-original-style="{{ e($styleString) }}"
                                    >
                                        @if(trim((string)$val) !== '')
                                            {{ $val }}
                                        @else
                                            <span class="empty-cell">&nbsp;</span>
                                        @endif
                                    </td>
                                @endfor
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
        </div>

        <aside class="side-panel">
            <h3>Thông tin ô đã chọn</h3>
            <div style="display:flex; justify-content: space-between; gap: 0.5rem; align-items: center; margin-bottom: 0.5rem;">
                <div style="font-size: 0.8rem; color:#4b5563;">Danh sách ô</div>
                <button type="button" class="btn" id="addCellBtn" style="padding: 0.35rem 0.6rem; font-size: 0.75rem;">+ Thêm ô</button>
            </div>
            <div class="selection-list" id="selectionList"></div>

            <form id="cellForm">
                <div class="form-group">
                    <label>Tên template</label>
                    <input type="text" class="form-control" id="tplName" name="tpl_name" value="{{ $editingTemplate->name ?? '' }}" placeholder="VD: Template lương tháng 1">
                </div>
                <div class="form-group">
                    <label>Loại template</label>
                    <select class="form-control" id="tplType" name="tpl_type">
                        <option value="salary" {{ ($editingTemplate->type ?? '') == 'salary' ? 'selected' : '' }}>Phiếu lương</option>
                        <option value="bonus" {{ ($editingTemplate->type ?? '') == 'bonus' ? 'selected' : '' }}>Phiếu thưởng</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea class="form-control" id="tplDesc" name="tpl_desc" rows="2" placeholder="Ghi chú ngắn về template">{{ $editingTemplate->description ?? '' }}</textarea>
                </div>
                <div class="form-group">
                    <label>Địa chỉ ô</label>
                    <input type="text" class="form-control" id="cellAddress" name="cell_address" readonly>
                </div>
                <div class="form-group">
                    <label>Loại giá trị</label>
                    <select class="form-control" id="valueType" name="value_type">
                        <option value="cell" selected>Ô (chọn cột)</option>
                        <option value="value">Giá trị</option>
                        <option value="formula">Công thức</option>
                    </select>
                    <p class="type-toggle-help">
                        Giá trị: nhập số thủ công · Ô: chọn theo cột B-E · Công thức: thêm các ô từ danh sách để kết hợp.
                    </p>
                </div>
                <div class="form-group">
                    <label>Giá trị</label>
                    <input type="text" class="form-control type-section type-value" id="cellValue" name="cell_value">
                </div>
                <div class="form-group type-section type-cell active">
                    <label>Ô (nhập cột, lấy từ sheet khác)</label>
                    <input list="columnList" class="form-control" id="cellRef" name="cell_ref" placeholder="Ví dụ: A, B, AA, AC...">
                    <datalist id="columnList">
                        <option value="A"><option value="B"><option value="C"><option value="D"><option value="E">
                        <option value="F"><option value="G"><option value="H"><option value="I"><option value="J">
                        <option value="K"><option value="L"><option value="M"><option value="N"><option value="O">
                        <option value="P"><option value="Q"><option value="R"><option value="S"><option value="T">
                        <option value="U"><option value="V"><option value="W"><option value="X"><option value="Y">
                        <option value="Z">
                        <option value="AA"><option value="AB"><option value="AC"><option value="AD"><option value="AE">
                        <option value="AF"><option value="AG"><option value="AH"><option value="AI"><option value="AJ">
                        <option value="AK"><option value="AL"><option value="AM"><option value="AN"><option value="AO">
                        <option value="AP"><option value="AQ"><option value="AR"><option value="AS"><option value="AT">
                        <option value="AU"><option value="AV"><option value="AW"><option value="AX"><option value="AY">
                        <option value="AZ">
                    </datalist>
                </div>
                <div class="form-group type-section type-formula">
                    <label>Công thức</label>
                    <textarea class="form-control" id="formulaInput" name="formula_input" rows="2" placeholder="Ví dụ: A1+B2+C3"></textarea>
                    <div class="selection-list formula-source" id="formulaSources"></div>
                </div>
                <div class="form-group">
                    <label>Ghi chú / mapping</label>
                    <input type="text" class="form-control" id="cellNote" name="cell_note" placeholder="Ví dụ: salary.basic / allowance.attendance">
                </div>

                <p class="help-text">
                    Click vào 1 ô trong bảng để điền form. (Phần lưu xuống DB sẽ làm ở bước tiếp theo khi bạn chốt format.)
                </p>
                <div style="margin-top: 1rem; display:flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button type="button" class="btn" id="submitMappings">Lưu vào DB</button>
                    <span id="submitStatus" style="font-size: 0.8rem; color: #6b7280;"></span>
                </div>
            </form>
        </aside>
    </div>
@endif
@endsection

<div id="templateData"
     data-editing-id="{{ $editingTemplate->id ?? '' }}"
     data-editing-selections='@json($editingTemplate->selections ?? [])'>
</div>

@push('scripts')
<script>
    // Hiển thị tên file đã chọn cho input Excel với kích thước
    (function () {
        const excelInput = document.getElementById('excel_file');
        const infoBox = document.getElementById('excel_file_info');
        const nameSpan = document.getElementById('excel_file_name');
        const sizeSpan = document.getElementById('excel_file_size');
        const uploadForm = document.getElementById('uploadForm');
        const submitBtn = document.getElementById('submitBtn');

        function formatFileSize(bytes) {
            if (!bytes) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
        }

        if (excelInput && infoBox && nameSpan && sizeSpan) {
            excelInput.addEventListener('change', function () {
                if (!this.files || !this.files.length) {
                    infoBox.style.display = 'none';
                    nameSpan.textContent = '';
                    sizeSpan.textContent = '';
                    return;
                }
                const file = this.files[0];
                nameSpan.textContent = file.name;
                sizeSpan.textContent = formatFileSize(file.size);
                infoBox.style.display = 'block';
            });
        }

        if (uploadForm && submitBtn) {
            uploadForm.addEventListener('submit', () => {
                submitBtn.textContent = 'Đang xử lý...';
                submitBtn.disabled = true;
            });
        }
    })();

    // Select cell in template and show on right form
    const editor = document.getElementById('templateEditor');
    if (editor) {
        // Apply row heights (from Excel) to improve match with real file
        editor.querySelectorAll('tr[data-row-height]').forEach((tr) => {
            const h = parseInt(tr.dataset.rowHeight || '', 10);
            if (!Number.isNaN(h) && h > 0) {
                tr.style.height = `${h}px`;
            }
        });

        const addressEl = document.getElementById('cellAddress');
        const valueEl = document.getElementById('cellValue');
        const noteEl = document.getElementById('cellNote');
        const addBtn = document.getElementById('addCellBtn');
        const listEl = document.getElementById('selectionList');
        const valueTypeEl = document.getElementById('valueType');
        const cellRefEl = document.getElementById('cellRef');
        const formulaEl = document.getElementById('formulaInput');
        const formulaSourcesEl = document.getElementById('formulaSources');
        const submitBtn = document.getElementById('submitMappings');
        const submitStatus = document.getElementById('submitStatus');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const tplNameEl = document.getElementById('tplName');
        const tplTypeEl = document.getElementById('tplType');
        const tplDescEl = document.getElementById('tplDesc');

        let selected = null;
        let selections = []; // { cellEl, address, value, note, color, valueType, cellRef, formula }
        let addMode = false;
        const templateDataEl = document.getElementById('templateData');
        const editingTemplateId = templateDataEl?.dataset.editingId || null;
        let editingTemplateSelections = [];
        try {
            editingTemplateSelections = templateDataEl?.dataset.editingSelections
                ? JSON.parse(templateDataEl.dataset.editingSelections)
                : [];
        } catch (e) {
            editingTemplateSelections = [];
        }

        // Palette màu đẹp cho các ô được chọn
        const colorPalette = [
            '#2563eb', // Blue
            '#dc2626', // Red
            '#16a34a', // Green
            '#ca8a04', // Yellow/Amber
            '#9333ea', // Purple
            '#ea580c', // Orange
            '#0891b2', // Cyan
            '#be185d', // Pink
            '#059669', // Emerald
            '#7c3aed', // Violet
            '#c2410c', // Orange Red
            '#1e40af', // Dark Blue
        ];
        let colorIndex = 0;

        const getNextColor = () => {
            const color = colorPalette[colorIndex % colorPalette.length];
            colorIndex++;
            return color;
        };

        const applyCellColor = (sel) => {
            if (!sel.cellEl || !sel.color) return;
            sel.cellEl.style.border = `2px solid ${sel.color}`;
            sel.cellEl.style.backgroundColor = sel.color + '15'; // 15 = ~8% opacity
            sel.cellEl.style.boxShadow = `inset 0 0 0 1px ${sel.color}40`; // 40 = ~25% opacity
        };

        const removeCellColor = (sel) => {
            if (!sel.cellEl) return;
            sel.cellEl.style.border = '';
            sel.cellEl.style.backgroundColor = '';
            sel.cellEl.style.boxShadow = '';
        };

        const restoreOriginalCell = (cellEl) => {
            if (!cellEl) return;
            const original = cellEl.dataset.original ?? '';
            const originalStyle = cellEl.dataset.originalStyle ?? '';
            if (originalStyle) {
                cellEl.setAttribute('style', originalStyle);
            }
            if (String(original).trim() === '') {
                cellEl.innerHTML = '<span class="empty-cell">&nbsp;</span>';
            } else {
                cellEl.textContent = original;
            }
            cellEl.dataset.value = original;
        };

        const refreshCellDisplay = (sel) => {
            if (!sel || !sel.cellEl) return;
            const type = sel.valueType || 'value';
            let display = '';
            if (type === 'value') {
                display = sel.value ?? '';
            } else if (type === 'cell') {
                display = sel.cellRef ?? '';
            } else if (type === 'formula') {
                display = sel.formula ?? '';
            }

            sel.cellEl.dataset.value = display;
            if (String(display).trim() === '') {
                sel.cellEl.innerHTML = '<span class="empty-cell">&nbsp;</span>';
            } else {
                sel.cellEl.textContent = display;
            }
        };

        const updateTypeUI = (type) => {
            const valueSection = document.querySelector('.type-value');
            const cellSection = document.querySelector('.type-cell');
            const formulaSection = document.querySelector('.type-formula');
            if (valueSection) valueSection.classList.toggle('active', type === 'value');
            if (cellSection) valueSection && cellSection.classList.toggle('active', type === 'cell');
            if (formulaSection) formulaSection.classList.toggle('active', type === 'formula');
        };

        const renderList = () => {
            if (!listEl) return;
            listEl.innerHTML = '';
            selections.forEach((sel) => {
                const chip = document.createElement('div');
                chip.className = 'selection-chip' + (sel === selected ? ' active' : '');
                if (sel.color) {
                    chip.style.borderColor = sel.color;
                    if (sel === selected) {
                        chip.style.backgroundColor = sel.color + '20';
                    }
                }
                
                const colorIndicator = document.createElement('span');
                colorIndicator.className = 'color-indicator';
                if (sel.color) {
                    colorIndicator.style.backgroundColor = sel.color;
                }
                
                const textSpan = document.createElement('span');
                textSpan.textContent = sel.address || '(ô trống)';
                textSpan.style.flex = '1';
                textSpan.style.cursor = 'pointer';
                textSpan.addEventListener('click', (e) => {
                    e.stopPropagation();
                    focusSelection(sel);
                });
                
                const deleteBtn = document.createElement('button');
                deleteBtn.innerHTML = '×';
                deleteBtn.className = 'chip-delete';
                deleteBtn.type = 'button';
                deleteBtn.title = 'Xóa ô này';
                deleteBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    removeSelection(sel);
                });
                
                chip.appendChild(colorIndicator);
                chip.appendChild(textSpan);
                chip.appendChild(deleteBtn);
                listEl.appendChild(chip);
            });
            renderFormulaSources();
        };

        const removeSelection = (sel) => {
            // Remove color styling
            removeCellColor(sel);
            
            // Remove highlight
            if (sel.cellEl) {
                sel.cellEl.classList.remove('selected-cell');
                // Restore original cell value
                restoreOriginalCell(sel.cellEl);
            }
            
            // Remove from array
            const index = selections.indexOf(sel);
            if (index > -1) {
                selections.splice(index, 1);
            }
            
            // If deleted was selected, clear form and select another if available
            if (sel === selected) {
                selected = null;
                addressEl.value = '';
                valueEl.value = '';
                noteEl.value = '';
                
                // Select first remaining if any
                if (selections.length > 0) {
                    focusSelection(selections[0]);
                }
            }
            
            renderList();
        };

        const renderFormulaSources = () => {
            if (!formulaSourcesEl) return;
            formulaSourcesEl.innerHTML = '';
            selections.forEach((sel) => {
                const btn = document.createElement('div');
                btn.className = 'formula-chip';
                btn.textContent = sel.address || '';
                btn.addEventListener('click', () => {
                    if (!formulaEl) return;
                    const toAdd = sel.address || '';
                    if (!toAdd) return;
                    const current = formulaEl.value || '';
                    const separator = current && !current.trim().endsWith('+') ? '+' : '';
                    formulaEl.value = current ? (current + separator + toAdd) : toAdd;
                    if (selected) {
                        selected.formula = formulaEl.value;
                        selected.valueType = 'formula';
                        if (valueTypeEl) valueTypeEl.value = 'formula';
                        updateTypeUI('formula');
                        refreshCellDisplay(selected);
                        renderList();
                    }
                });
                formulaSourcesEl.appendChild(btn);
            });
        };

        const focusSelection = (sel) => {
            if (!sel) return;
            if (selected) {
                selected.cellEl.classList.remove('selected-cell');
            }
            selected = sel;
            selected.cellEl.classList.add('selected-cell');
            addressEl.value = sel.address || '';
            valueEl.value = sel.value || '';
            noteEl.value = sel.note || '';
            if (valueTypeEl) valueTypeEl.value = sel.valueType || 'cell';
            if (cellRefEl) cellRefEl.value = sel.cellRef || '';
            if (formulaEl) formulaEl.value = sel.formula || '';
            updateTypeUI(sel.valueType || 'cell');
            renderList();
        };

        // Restore selections từ editingTemplate khi load trang
        const restoreSelections = () => {
            if (!editingTemplateId || !editingTemplateSelections || editingTemplateSelections.length === 0) return;
            
            editingTemplateSelections.forEach((savedSel) => {
                const address = savedSel.address || '';
                if (!address) return;
                
                // Tìm cell element theo address (ví dụ "B13" -> tìm cell có data-address="B13")
                const cellEl = editor.querySelector(`.tpl-cell[data-address="${address}"]`);
                if (!cellEl) return;
                
                const color = savedSel.color || getNextColor();
                const sel = {
                    cellEl: cellEl,
                    address: address,
                    value: savedSel.value || '',
                    note: savedSel.note || '',
                    color: color,
                    valueType: savedSel.valueType || 'cell',
                    cellRef: savedSel.cellRef || '',
                    formula: savedSel.formula || '',
                };
                
                // Update cell display value based on valueType
                if (sel.valueType === 'cell' && sel.cellRef) {
                    sel.value = sel.cellRef;
                } else if (sel.valueType === 'formula' && sel.formula) {
                    sel.value = sel.formula;
                }
                
                selections.push(sel);
                applyCellColor(sel);
                
                // Update cell display
                if (String(sel.value).trim() === '') {
                    cellEl.innerHTML = '<span class="empty-cell">&nbsp;</span>';
                } else {
                    cellEl.textContent = sel.value;
                }
                cellEl.dataset.value = sel.value;
            });
            
            // Select first selection if any
            if (selections.length > 0) {
                focusSelection(selections[0]);
            }
            renderList();
        };

        editor.addEventListener('click', (e) => {
            const cell = e.target.closest('.tpl-cell');
            if (!cell) return;

            let existing = selections.find(s => s.cellEl === cell);
            if (!existing) {
                const color = getNextColor();
                existing = {
                    cellEl: cell,
                    address: cell.dataset.address || '',
                    value: cell.dataset.value || '',
                    note: '',
                    color: color,
                    valueType: 'cell',
                    cellRef: '',
                    formula: '',
                };
                selections.push(existing);
                applyCellColor(existing);
                refreshCellDisplay(existing);
            }
            focusSelection(existing);
            addMode = false; // reset add mode after selecting
        });

        // Restore selections sau khi DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', restoreSelections);
        } else {
            setTimeout(restoreSelections, 100);
        }

        // Sync: edit value in form -> update selected cell content on the left
        valueEl.addEventListener('input', () => {
            if (!selected || (selected.valueType || 'value') !== 'value') return;

            const v = valueEl.value ?? '';
            selected.value = v;
            selected.cellEl.dataset.value = v;

            // Preserve "empty" UI when value is blank
            if (String(v).trim() === '') {
                selected.cellEl.innerHTML = '<span class="empty-cell">&nbsp;</span>';
            } else {
                selected.cellEl.textContent = v;
            }
            renderList();
        });

        // Sync: cell ref
        cellRefEl?.addEventListener('change', () => {
            if (!selected || (selected.valueType || 'value') !== 'cell') return;
            selected.cellRef = cellRefEl.value || '';
            refreshCellDisplay(selected);
            renderList();
        });

        // Sync note
        noteEl.addEventListener('input', () => {
            if (!selected) return;
            selected.note = noteEl.value ?? '';
            renderList();
        });

        // Add new cell mode: show hint and wait for click (already handled by click)
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                addMode = true;
                addBtn.textContent = 'Chọn ô trên bảng...';
                setTimeout(() => addBtn.textContent = '+ Thêm ô', 2000);
            });
        }

        // Value type toggle
        valueTypeEl?.addEventListener('change', () => {
            if (!selected) return;
            const type = valueTypeEl.value || 'value';
            selected.valueType = type;
            updateTypeUI(type);
            refreshCellDisplay(selected);
            renderList();
        });

        // Formula input sync
        formulaEl?.addEventListener('input', () => {
            if (!selected || (selected.valueType || 'value') !== 'formula') return;
            selected.formula = formulaEl.value || '';
            refreshCellDisplay(selected);
            renderList();
        });

        // Initial render of formula sources (empty)
        renderFormulaSources();

        // Submit mappings
        submitBtn?.addEventListener('click', async () => {
            if (!selections.length) {
                alert('Chưa có ô nào được chọn.');
                return;
            }
            if (!csrfToken) {
                alert('Thiếu CSRF token.');
                return;
            }
            submitBtn.disabled = true;
            submitStatus.textContent = 'Đang lưu...';

                const payload = {
                    file_name: "{{ $fileName ?? '' }}",
                    name: tplNameEl?.value || '',
                    type: tplTypeEl?.value || 'salary',
                    description: tplDescEl?.value || '',
                selections: selections.map(sel => ({
                    address: sel.address || '',
                    valueType: sel.valueType || 'value',
                    value: sel.value || '',
                    cellRef: sel.cellRef || '',
                    formula: sel.formula || '',
                    note: sel.note || '',
                    color: sel.color || '',
                })),
            };

            try {
                const url = editingTemplateId 
                    ? "{{ route('templates.update', ':id') }}".replace(':id', editingTemplateId)
                    : "{{ route('templates.save') }}";
                const method = editingTemplateId ? 'PUT' : 'POST';
                
                const res = await fetch(url, {
                    method: method,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload),
                });

                if (!res.ok) {
                    const err = await res.json().catch(() => ({}));
                    throw new Error(err.message || 'Lưu thất bại');
                }

                const data = await res.json();
                submitStatus.textContent = 'Lưu thành công (ID: ' + data.id + ')';
            } catch (e) {
                submitStatus.textContent = '';
                alert(e.message || 'Lưu thất bại');
            } finally {
                submitBtn.disabled = false;
            }
        });
    }
</script>
@endpush

