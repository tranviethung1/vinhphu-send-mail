<?php $__env->startPush('styles'); ?>
<style>
    .excel-wrapper {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        width: 100%;
    }
    .excel-toolbar {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid #e5e7eb;
        background: #f9fafb;
        border-radius: 0.5rem 0.5rem 0 0;
    }
    .freeze-label {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #374151;
        white-space: nowrap;
    }
    .freeze-select {
        font-size: 0.75rem;
        padding: 0.2rem 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        background: white;
        color: #374151;
        cursor: pointer;
        outline: none;
    }
    .freeze-select:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99,102,241,0.15);
    }
    .col-frozen {
        position: sticky !important;
        z-index: 4 !important;
        background-color: #f0f4ff !important;
    }
    .col-frozen-header {
        position: sticky !important;
        z-index: 16 !important;
        background-color: #e8edff !important;
    }
    .col-frozen-border {
        border-right: 2px solid #6366f1 !important;
    }
    .excel-container {
        overflow: auto;
        max-height: calc(100vh - 200px);
        min-height: 400px;
        position: relative;
        width: 100%;
        cursor: grab;
    }
    .excel-container.dragging {
        cursor: grabbing;
    }
    .excel-table {
        border-collapse: separate;
        border-spacing: 0;
        font-size: 8pt;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    .excel-table th,
    .excel-table td[style] {
        font-size: 8pt !important;
    }
    .excel-table th,
    .excel-table td {
        border: 1px solid #D0D7E5;
        padding: 4px 8px;
        min-width: 80px;
        max-width: 200px;
        white-space: nowrap;
    }
    .excel-table td {
        background-color: #FFFFFF;
        color: #000;
        text-align: left;
        vertical-align: middle;
    }
    .excel-table th {
        background-color: #F2F2F2;
        font-weight: 600;
        position: sticky;
        top: 0;
        z-index: 10;
        border-bottom: 2px solid #D0D7E5;
    }
    .row-header {
        background-color: #F2F2F2 !important;
        font-weight: 600;
        text-align: center;
        min-width: 50px;
        max-width: 50px;
        position: sticky;
        left: 0;
        z-index: 5;
        border-right: 2px solid #D0D7E5;
    }
    .col-header {
        background-color: #F2F2F2 !important;
        font-weight: 600;
        text-align: center;
        position: sticky;
        top: 0;
        z-index: 15;
    }
    .excel-table tbody tr:hover td {
        background-color: #dbeafe !important;
    }
    .excel-table tbody tr:hover .col-frozen {
        background-color: #bfdbfe !important;
    }
    .excel-table tbody tr.row-selected td {
        background-color: #dbeafe !important;
    }
    .excel-table tbody tr.row-selected .col-frozen {
        background-color: #bfdbfe !important;
    }
    .excel-table tbody tr.row-selected {
        outline: 2px solid #3b82f6;
        outline-offset: -1px;
    }
    .excel-table tbody tr {
        cursor: pointer;
    }
    .empty-cell {
        background-color: #FFFFFF;
    }
    .number-cell {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .excel-table tbody tr:nth-child(even) td {
        background-color: #FAFAFA;
    }
    .excel-table tbody tr:nth-child(even) .row-header {
        background-color: #F2F2F2 !important;
    }
    @media (max-width: 768px) {
        .excel-table {
            font-size: 9pt;
        }
        .excel-table th,
        .excel-table td {
            padding: 2px 4px;
            min-width: 60px;
        }
    }
</style>
<?php $__env->stopPush(); ?>

<?php if(empty($data)): ?>
    <div style="background: white; padding: 1.5rem; text-align: center; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);">
        <p style="color: #6b7280; font-size: 1rem;">Không có dữ liệu trong file Excel.</p>
    </div>
<?php else: ?>
    <?php
        $maxColumns = !empty($data) ? max(array_map('count', $data)) : 0;
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
    ?>
    <div class="excel-wrapper">
        <div class="excel-toolbar">
            <label class="freeze-label">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 3v18M3 9h6"/></svg>
                Cố định cột:
            </label>
            <select id="freezeColSelect" class="freeze-select">
                <option value="0">Không cố định</option>
                <?php for($c = 1; $c <= min(10, $maxColumns); $c++): ?>
                    <option value="<?php echo e($c); ?>"><?php echo e($c); ?> cột đầu (<?php echo e(implode(', ', array_map(fn($x) => getColumnLetter($x), range(1, $c)))); ?>)</option>
                <?php endfor; ?>
            </select>
        </div>
        <div class="excel-container">
            <table class="excel-table" id="excelTable">
                <thead>
                    <tr>
                        <?php if($maxColumns > 0): ?>
                            <?php for($i = 0; $i < $maxColumns; $i++): ?>
                                <th class="col-header"><?php echo e(getColumnLetter($i + 1)); ?></th>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rowIndex => $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <?php for($i = 0; $i < $maxColumns; $i++): ?>
                                <?php
                                    $cellStyle = isset($styles[$rowIndex][$i]) ? $styles[$rowIndex][$i] : [];
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
                                ?>
                                <td style="<?php echo e($styleString); ?>" class="<?php echo e(isset($row[$i]) && is_numeric(str_replace([',', ' '], '', $row[$i])) ? 'number-cell' : ''); ?>">
                                    <?php if(isset($row[$i]) && $row[$i] !== null && trim((string)$row[$i]) !== ''): ?>
                                        <?php echo e($row[$i]); ?>

                                    <?php else: ?>
                                        <span class="empty-cell">&nbsp;</span>
                                    <?php endif; ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php $__env->startPush('scripts'); ?>
<script>
    (function () {
        const STORAGE_KEY = 'excelFreezeCol_<?php echo e($file->id ?? "default"); ?>';
        const table = document.getElementById('excelTable');
        const freezeSelect = document.getElementById('freezeColSelect');

        function applyFreezeColumns(count) {
            if (!table) return;

            // Reset tất cả cột
            table.querySelectorAll('th, td').forEach(cell => {
                cell.classList.remove('col-frozen', 'col-frozen-header', 'col-frozen-border');
                cell.style.left = '';
            });

            if (count <= 0) return;

            // Tính left offset dựa trên width thực của từng cột header
            const headerCells = table.querySelectorAll('thead tr th');
            const offsets = [];
            let accumulated = 0;
            for (let i = 0; i < count && i < headerCells.length; i++) {
                offsets.push(accumulated);
                accumulated += headerCells[i].getBoundingClientRect().width;
            }

            // Áp dụng sticky cho header
            headerCells.forEach((th, i) => {
                if (i < count) {
                    th.classList.add('col-frozen-header');
                    th.style.left = offsets[i] + 'px';
                    if (i === count - 1) th.classList.add('col-frozen-border');
                }
            });

            // Áp dụng sticky cho mỗi row trong tbody
            table.querySelectorAll('tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                cells.forEach((td, i) => {
                    if (i < count) {
                        td.classList.add('col-frozen');
                        td.style.left = offsets[i] + 'px';
                        if (i === count - 1) td.classList.add('col-frozen-border');
                    }
                });
            });
        }

        // Khôi phục từ localStorage
        const saved = parseInt(localStorage.getItem(STORAGE_KEY) || '2', 10);
        if (freezeSelect && saved >= 0) {
            freezeSelect.value = saved;
        }

        // Áp dụng sau khi layout xong
        requestAnimationFrame(() => applyFreezeColumns(saved));

        if (freezeSelect) {
            freezeSelect.addEventListener('change', function () {
                const val = parseInt(this.value, 10);
                localStorage.setItem(STORAGE_KEY, val);
                applyFreezeColumns(val);
            });
        }
    })();

    (function () {
        const container = document.querySelector('.excel-container');
        if (!container) return;

        let isDown = false;
        let startX, startY, scrollLeft, scrollTop;

        container.addEventListener('mousedown', (e) => {
            isDown = true;
            container.classList.add('dragging');
            startX = e.pageX - container.offsetLeft;
            startY = e.pageY - container.offsetTop;
            scrollLeft = container.scrollLeft;
            scrollTop = container.scrollTop;
        });

        container.addEventListener('mouseleave', () => {
            isDown = false;
            container.classList.remove('dragging');
        });

        container.addEventListener('mouseup', () => {
            isDown = false;
            container.classList.remove('dragging');
        });

        container.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const y = e.pageY - container.offsetTop;
            const walkX = (x - startX);
            const walkY = (y - startY);
            container.scrollLeft = scrollLeft - walkX;
            container.scrollTop = scrollTop - walkY;
        });
    })();

    // Click để đánh dấu/bỏ đánh dấu dòng (multi-select)
    (function () {
        const table = document.getElementById('excelTable');
        if (!table) return;

        let dragMoved = false;

        table.addEventListener('mousedown', () => { dragMoved = false; });
        table.addEventListener('mousemove', () => { dragMoved = true; });

        table.querySelector('tbody').addEventListener('click', (e) => {
            if (dragMoved) return;
            const row = e.target.closest('tr');
            if (!row) return;
            row.classList.toggle('row-selected');
        });
    })();
</script>
<?php $__env->stopPush(); ?>

<?php /**PATH /var/www/html/resources/views/partials/excel-table.blade.php ENDPATH**/ ?>