<?php $__env->startSection('title', ($file->name ?? 'File lương') . ' — Lịch sử xuất PDF'); ?>

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
        <?php if($file->facility): ?>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.salary-files', $file->facility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="<?php echo e($file->facility->name); ?>">
                <?php echo e($file->facility->name); ?>

            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.salary-files', $file->facility->id)); ?>"
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
            <a href="<?php echo e(route('salary-files.edit', $file->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="<?php echo e($file->name); ?>">
                <?php echo e($file->name); ?>

            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;">Lịch sử xuất PDF</span>
        </li>
    </ol>
</nav>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }
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
    .btn-secondary {
        background-color: #6b7280;
    }
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    .btn-danger {
        background-color: #ef4444;
    }
    .btn-danger:hover {
        background-color: #dc2626;
    }
    .table-wrapper {
        background: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        overflow: hidden;
    }
    .table {
        width: 100%;
        border-collapse: collapse;
    }
    .table thead {
        background-color: #f9fafb;
    }
    .table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: #1f2937;
        font-size: 0.875rem;
    }
    .table tbody tr:hover {
        background-color: #f9fafb;
    }
    .table tbody tr:last-child td {
        border-bottom: none;
    }
    .empty-state {
        padding: 3rem;
        text-align: center;
        color: #6b7280;
    }
    .badge {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 500;
        background-color: #dbeafe;
        color: #1e40af;
    }
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .file-path {
        display: inline-block;
        max-width: 100%;
        font-family: monospace;
        font-size: 0.75rem;
        color: #6b7280;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
    }
    .modal.show {
        display: block;
    }
    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        border-radius: 0.5rem;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }
    .modal-header {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .modal-header h2 {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 600;
        color: #111827;
        line-height: 1.4;
    }
    .modal-body {
        padding: 0;
        max-height: 60vh;
        overflow-y: auto;
    }
    .modal-body .email-table {
        margin: 0;
    }
    .modal-body .email-count {
        padding: 0.75rem 1.5rem;
        background-color: #f9fafb;
        border-top: 1px solid #e5e7eb;
        margin: 0;
    }
    .modal-footer {
        padding: 1rem 1.5rem;
        border-top: 1px solid #e5e7eb;
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
    .close {
        color: #9ca3af;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
        border: none;
        background: none;
        padding: 0;
        line-height: 1;
    }
    .close:hover,
    .close:focus {
        color: #111827;
    }
    .email-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    .email-table thead {
        background-color: #f9fafb;
    }
    .email-table th {
        padding: 0.75rem 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        font-size: 0.8125rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .email-table th.checkbox-cell {
        padding: 0.75rem;
    }
    .email-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: #1f2937;
        font-size: 0.875rem;
    }
    .email-table td.checkbox-cell {
        padding: 0.75rem;
    }
    .email-table tbody tr:hover {
        background-color: #f9fafb;
    }
    .email-table tbody tr:last-child td {
        border-bottom: none;
    }
    .checkbox-cell {
        width: 40px;
        text-align: center;
    }
    .email-checkbox {
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    .select-all-row {
        background-color: #f9fafb;
    }
    .select-all-label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        cursor: pointer;
    }
    .empty-email-state {
        text-align: center;
        padding: 3rem 1.5rem;
        color: #6b7280;
    }
    .email-count {
        font-size: 0.8125rem;
        color: #6b7280;
        margin-top: 0.5rem;
    }
    .custom-context-menu {
        display: none;
        position: fixed;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        z-index: 9999;
        min-width: 180px;
        padding: 0.5rem 0;
    }
    .custom-context-menu-item {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        color: #374151;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .custom-context-menu-item:hover {
        background-color: #f3f4f6;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>

<div class="page-header">
    <div>
        <p style="font-size: 0.8125rem; margin: 0; color: #6b7280;">
            File lương: <strong><?php echo e($file->file_name); ?></strong> 
            <?php if($file->month): ?>
                | Tháng: <strong><?php echo e($file->month); ?></strong>
            <?php endif; ?>
        </p>
    </div>
    <div style="display:flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="<?php echo e(route('salary-files.edit', $file->id)); ?>" class="btn btn-secondary">
            ← Quay lại chỉnh sửa
        </a>
    </div>
</div>

<?php if(session('error')): ?>
    <div style="margin-bottom: 1rem; padding: 0.75rem 1rem; border-radius: 0.375rem; background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca;">
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>

<?php if($exports->isEmpty()): ?>
    <div class="table-wrapper">
        <div class="empty-state">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Chưa có lần xuất PDF hàng loạt nào</p>
            <p style="color: #9ca3af; margin-bottom: 1rem;">File lương này chưa được xuất PDF hàng loạt lần nào.</p>
            <a href="<?php echo e(route('salary-files.edit', $file->id)); ?>" class="btn">
                Quay lại chỉnh sửa
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 170px;">Thời gian</th>
                    <th style="min-width: 180px;">Template</th>
                    <th style="width: 110px;">Số nhân viên</th>
                    <th>Đường dẫn lưu</th>
                    <th style="width: 100px;">Trạng thái</th>
                    <th style="width: 200px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $exports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $export): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td style="white-space:nowrap;">
                            <?php echo e($export->created_at?->format('d/m/Y H:i:s') ?? '-'); ?>

                        </td>
                        <td>
                            <?php echo e($export->templateSelection->name ?? ('Template #' . ($export->template_selection_id ?? '-'))); ?>

                        </td>
                        <td>
                            <span class="badge"><?php echo e($export->rows_count); ?></span>
                        </td>
                        <td>
                            <span class="file-path">
                                <?php echo e($export->file_path ?: '-'); ?>

                            </span>
                        </td>
                        <td>
                            <?php if($export->status === 'pending'): ?>
                                <span class="badge" style="background-color:#fef3c7; color:#d97706;">Pending</span>
                            <?php elseif($export->status === 'fail'): ?>
                                <span class="badge" style="background-color:#fee2e2; color:#ef4444;">Fail</span>
                            <?php else: ?>
                                <span class="badge" style="background-color:#dcfce7; color:#16a34a;">Success</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if($export->status === 'success'): ?>
                                    <button onclick="openEmailModal(<?php echo e($export->id); ?>)" class="btn" title="Gửi Email" style="padding:0.375rem 0.5rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </button>
                                    <button onclick="openLogModal(<?php echo e($export->id); ?>)" class="btn btn-secondary" title="Xem Log" style="padding:0.375rem 0.5rem; background-color: #8b5cf6;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                    </button>
                                    <a href="<?php echo e(route('salary-bulk-exports.download', $export->id)); ?>"
                                       class="btn btn-secondary" title="Tải ZIP" style="padding:0.375rem 0.5rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                <form action="<?php echo e(route('salary-bulk-exports.destroy', $export->id)); ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá lần xuất này và file ZIP tương ứng không?');" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger" title="Xóa" style="padding:0.375rem 0.5rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Log Modal -->
<div id="logModal" class="modal">
    <div class="modal-content" style="width: 80%; max-width: 900px;">
        <div class="modal-header">
            <h2>Lịch sử gửi Email</h2>
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <button onclick="refreshLogs()" id="btnRefreshLogs" title="Làm mới" style="background: none; border: 1px solid #d1d5db; border-radius: 0.375rem; padding: 0.25rem 0.5rem; cursor: pointer; color: #6b7280; display: flex; align-items: center; gap: 0.25rem; font-size: 0.8125rem;">
                    <svg id="refreshIcon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Làm mới
                </button>
                <button class="close" onclick="closeLogModal()">&times;</button>
            </div>
        </div>
        <div class="modal-body" style="background: #f3f4f6; padding: 1rem;">
            <div id="logContainer"></div>
        </div>
        <div class="modal-footer" style="justify-content: space-between;">
            <button onclick="deleteLogs()" class="btn btn-danger" id="btnDeleteLogs" style="display: none;">Xóa lịch sử</button>
            <button onclick="closeLogModal()" class="btn btn-secondary">Đóng</button>
        </div>
    </div>
</div>

<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
.log-list { display: flex; flex-direction: column; gap: 0.5rem; }
.log-item { background: white; padding: 0.75rem; border-radius: 0.375rem; border-left: 4px solid #ccc; font-size: 0.9rem; }
.log-info { border-left-color: #3b82f6; }
.log-success { border-left-color: #10b981; }
.log-warning { border-left-color: #f59e0b; }
.log-error { border-left-color: #ef4444; }
.log-header { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem; }
.log-time { color: #6b7280; font-size: 0.8rem; min-width: 60px; }
.log-message { font-weight: 500; color: #1f2937; }
.log-user { margin-left: 75px; color: #4b5563; font-size: 0.85rem; }
.log-details pre { margin: 0.5rem 0 0 75px; background: #f9fafb; padding: 0.5rem; border-radius: 0.25rem; font-size: 0.75rem; overflow-x: auto; color: #6b7280; }
</style>

<!-- Email List Modal -->
<div id="emailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <div>
                <h2 style="display: flex; align-items: center; gap: 0.5rem;">
                    Gửi Email
                    <span style="font-size: 0.75rem; font-weight: 500; color: #3b82f6; background: #eff6ff; padding: 0.125rem 0.5rem; border-radius: 999px; border: 1px solid #bfdbfe;">
                        💡 Chuột phải vào bảng để chọn nhiều nhân viên
                    </span>
                </h2>
                <p style="font-size: 0.8125rem; color: #6b7280; margin: 0.25rem 0 0 0;" id="modalExportInfo"></p>
            </div>
            <button class="close" onclick="closeEmailModal()">&times;</button>
        </div>
        <div class="modal-body">
            <?php if($defaultMailList && !empty($defaultMailList->rows)): ?>
                <?php
                    $rows = is_array($defaultMailList->rows) ? $defaultMailList->rows : json_decode($defaultMailList->rows, true);
                    $headers = is_array($defaultMailList->headers) ? $defaultMailList->headers : json_decode($defaultMailList->headers, true);
                    
                    // Tìm index của cột email và name
                    $emailIndex = null;
                    $nameIndex = null;
                    
                    foreach($headers as $idx => $header) {
                        $headerLower = strtolower(trim($header));
                        
                        // Tìm cột Email (chính xác)
                        if ($headerLower === 'email' || $headerLower === 'mail') {
                            $emailIndex = $idx;
                        }
                        
                        // Tìm cột Name (ưu tiên exact match)
                        if ($headerLower === 'name' || $headerLower === 'tên' || $headerLower === 'họ tên') {
                            $nameIndex = $idx;
                        }
                    }
                    
                    // Nếu chưa tìm thấy, dùng fallback với contains
                    if ($emailIndex === null) {
                        foreach($headers as $idx => $header) {
                            $headerLower = strtolower(trim($header));
                            if (str_contains($headerLower, 'email') || str_contains($headerLower, 'mail')) {
                                $emailIndex = $idx;
                                break;
                            }
                        }
                    }
                    
                    if ($nameIndex === null) {
                        foreach($headers as $idx => $header) {
                            $headerLower = strtolower(trim($header));
                            // Ưu tiên "Name" thuần túy, không phải "Attachment name"
                            if ($headerLower === 'name' || 
                                (str_contains($headerLower, 'tên') && !str_contains($headerLower, 'attachment'))) {
                                $nameIndex = $idx;
                                break;
                            }
                        }
                    }
                    
                    // Default fallback
                    if ($emailIndex === null) $emailIndex = 3; // Cột cuối cùng thường là email
                    if ($nameIndex === null) $nameIndex = 1; // Cột thứ 2 thường là name
                    
                    $validEmails = [];
                    foreach($rows as $idx => $row) {
                        $email = isset($row[$emailIndex]) ? trim($row[$emailIndex]) : '';
                        if (!empty($email)) {
                            $name = $nameIndex !== null && isset($row[$nameIndex]) ? trim($row[$nameIndex]) : '';
                            $validEmails[] = ['email' => $email, 'name' => $name, 'index' => $idx];
                        }
                    }
                ?>
                
                <table class="email-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell">
                                <input type="checkbox" id="selectAllEmails" class="email-checkbox" onchange="toggleAllEmails(this)">
                            </th>
                            <th style="width: 60px; text-align: center;">STT</th>
                            <th>Tên</th>
                            <th>Email</th>
                            <th style="width: 100px;">Trạng thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $validEmails; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="checkbox-cell">
                                    <input type="checkbox" class="email-checkbox email-item-checkbox" 
                                           value="<?php echo e($item['email']); ?>" 
                                           data-name="<?php echo e($item['name']); ?>"
                                           data-index="<?php echo e($item['index'] + 1); ?>"
                                           onchange="updateEmailCount()">
                                </td>
                                <td style="text-align: center; color: #6b7280;"><?php echo e($item['index'] + 1); ?></td>
                                <td><?php echo e($item['name'] ?: '-'); ?></td>
                                <td><?php echo e($item['email']); ?></td>
                                <td class="email-status-cell" data-email="<?php echo e($item['email']); ?>" data-name="<?php echo e($item['name']); ?>">-</td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <div class="email-count">
                    <span id="selectedEmailCount">0</span> / <span id="totalEmailCount"><?php echo e(count($validEmails)); ?></span> email được chọn
                </div>
            <?php else: ?>
                <div class="empty-email-state">
                    <p style="font-size: 1rem; margin-bottom: 0.5rem; font-weight: 500;">Chưa có danh sách email mặc định</p>
                    <p style="color: #9ca3af; margin: 0;">
                        <?php if(!$file->facility_id): ?>
                            File lương này chưa được gán cơ sở.
                        <?php else: ?>
                            Cơ sở <strong><?php echo e($file->facility->name ?? 'N/A'); ?></strong> chưa có danh sách email mặc định.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        <div class="modal-footer" style="justify-content: space-between;">
            <div>
                <button onclick="clearEmailHistory()" class="btn btn-danger" id="btnClearEmailHistory" style="display: none;">Xóa lịch sử</button>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button onclick="closeEmailModal()" class="btn btn-secondary">
                    Đóng
                </button>
                <button onclick="sendEmails()" class="btn" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    Gửi Email
                </button>
            </div>
        </div>
</div>

<!-- Context Menu -->
<div id="emailContextMenu" class="custom-context-menu">
    <div class="custom-context-menu-item" onclick="selectNextGroup(5)">Chọn 5 người tiếp theo</div>
    <div class="custom-context-menu-item" onclick="selectNextGroup(10)">Chọn 10 người tiếp theo</div>
    <div class="custom-context-menu-item" onclick="selectNextGroup(20)">Chọn 20 người tiếp theo</div>
    <div class="custom-context-menu-item" onclick="selectNextGroup(30)">Chọn 30 người tiếp theo</div>
    <div class="custom-context-menu-item" onclick="selectNextGroup(40)">Chọn 40 người tiếp theo</div>
    <div class="custom-context-menu-item" onclick="selectNextGroup(50)">Chọn 50 người tiếp theo</div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
// Store export information for modal
const exportsData = <?php echo json_encode($exports->map(function($export) {
    return [
        'id' => $export->id,
        'created_at' => optional($export->created_at)->format('d/m/Y H:i:s') ?? '-',
        'template' => optional($export->templateSelection)->name ?? ('Template #' . ($export->template_selection_id ?? '-')),
        'rows_count' => $export->rows_count,
    ];
})->values()); ?>;

let currentExportId = null;
let statusPollingInterval = null;
let contextMenuTargetIndex = -1;

document.addEventListener('contextmenu', function(e) {
    const tr = e.target.closest('#emailModal .email-table tbody tr');
    const menu = document.getElementById('emailContextMenu');
    if (!menu) return;
    
    if (tr) {
        e.preventDefault();
        menu.style.display = 'block';
        
        let x = e.clientX;
        let y = e.clientY;
        if (x + menu.offsetWidth > window.innerWidth) x = window.innerWidth - menu.offsetWidth;
        if (y + menu.offsetHeight > window.innerHeight) y = window.innerHeight - menu.offsetHeight;
        
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
        
        const allTrs = document.querySelectorAll('#emailModal .email-table tbody tr');
        contextMenuTargetIndex = Array.from(allTrs).indexOf(tr);
    } else {
        menu.style.display = 'none';
    }
});

document.addEventListener('click', function(e) {
    const menu = document.getElementById('emailContextMenu');
    if (menu) menu.style.display = 'none';
});

function selectNextGroup(count) {
    if (contextMenuTargetIndex === -1) return;
    const allCheckboxes = document.querySelectorAll('.email-item-checkbox');
    
    // Bỏ check tất cả trước
    allCheckboxes.forEach(cb => cb.checked = false);

    // Tính số lượng check từ vị trí click
    let checkedCount = 0;
    for(let i = contextMenuTargetIndex; i < allCheckboxes.length && checkedCount < count; i++) {
        allCheckboxes[i].checked = true;
        checkedCount++;
    }
    
    const menu = document.getElementById('emailContextMenu');
    if (menu) menu.style.display = 'none';
    updateEmailCount();
}

function fetchEmailStatus(exportId, showLoading = true) {
    const btnClear = document.getElementById('btnClearEmailHistory');
    if (showLoading && btnClear) {
        btnClear.style.display = 'none';
        document.querySelectorAll('.email-status-cell').forEach(cell => {
            if (cell.innerHTML === '-') {
                cell.innerHTML = '<span style="color:#9ca3af; font-size:0.8rem;">Đang tải...</span>';
            }
            const tr = cell.closest('tr');
            if (tr) tr.style.backgroundColor = '';
        });
    }

    fetch(`/salary-bulk-exports/${exportId}/logs`)
    .then(res => res.json())
    .then(logs => {
        if (logs.length > 0 && btnClear && showLoading) {
            btnClear.style.display = 'inline-block';
        }

        const logMap = {};
        logs.forEach(log => {
            const logEmail = (log.email || '').trim();
            const logName = (log.name || '').trim();
            if (logEmail || logName) {
                logMap[logEmail + '|' + logName] = log.status; 
            }
        });

        document.querySelectorAll('.email-status-cell').forEach(cell => {
            const email = (cell.getAttribute('data-email') || '').trim();
            const name = (cell.getAttribute('data-name') || '').trim();
            const logKey = email + '|' + name;
            const tr = cell.closest('tr');
            
            if (tr) tr.style.backgroundColor = ''; // Reset màu cũ

            if (logMap[logKey]) {
                const st = logMap[logKey].toLowerCase();
                let badge = '';
                if (st === 'pending') {
                    badge = '<span class="badge" style="background-color:#fef3c7; color:#d97706;">Pending</span>';
                } else if (st === 'open') {
                    badge = '<span class="badge" style="background-color:#dbeafe; color:#1e40af;">Open</span>';
                } else if (st === 'success') {
                    badge = '<span class="badge" style="background-color:#dcfce7; color:#16a34a;">Success</span>';
                    if (tr) tr.style.backgroundColor = '#f0fdf4'; // Light green (màu xanh lá nhạt)
                } else if (st === 'fail' || st === 'error') {
                    badge = '<span class="badge" style="background-color:#fee2e2; color:#ef4444;">Fail</span>';
                    if (tr) tr.style.backgroundColor = '#fef2f2'; // Light red cho dể chú ý lỗi
                } else {
                    badge = `<span class="badge" style="background-color:#e5e7eb; color:#4b5563;">${logMap[logKey]}</span>`;
                }
                cell.innerHTML = badge;
            } else if (showLoading) {
                cell.innerHTML = '-'; // Chỉ reset dash ở lần đầu tiên load
            }
        });

        // Kiểm tra xem có ai đang pending không
        const isPending = Object.values(logMap).some(st => st.toLowerCase() === 'pending');
        const sendBtn = document.querySelector('#emailModal .modal-footer .btn:last-child');
        if (sendBtn) {
            sendBtn.disabled = isPending;
            if (isPending) {
                sendBtn.style.opacity = '0.5';
                sendBtn.style.cursor = 'not-allowed';
            } else {
                sendBtn.style.opacity = '1';
                sendBtn.style.cursor = 'pointer';
            }
        }
    })
    .catch(err => {
        if (showLoading) {
            document.querySelectorAll('.email-status-cell').forEach(cell => {
                cell.innerHTML = '<span style="color:#ef4444; font-size:0.8rem;">Lỗi tải</span>';
            });
        }
    });
}

function openEmailModal(exportId) {
    currentExportId = exportId;
    const exportData = exportsData.find(e => e.id === exportId);
    
    if (exportData) {
        const infoText = `${exportData.template} - ${exportData.created_at} (${exportData.rows_count} nhân viên)`;
        document.getElementById('modalExportInfo').textContent = infoText;
    }
    
    // Mở popup
    document.getElementById('emailModal').classList.add('show');
    
    // Reset checkboxes
    const selectAllCheckbox = document.getElementById('selectAllEmails');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
    document.querySelectorAll('.email-item-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateEmailCount();
    
    // Fetch dữ liệu lần đầu
    fetchEmailStatus(exportId, true);

    // Cài đặt polling tự làm mới status mỗi 10 giây (10000ms)
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval);
    }
    statusPollingInterval = setInterval(() => {
        if (currentExportId === exportId) {
            fetchEmailStatus(exportId, false);
        }
    }, 10000);
}

function clearEmailHistory() {
    if (!currentExportId) return;
    
    if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử trạng thái của lần xuất này không? Hành động này không thể hoàn tác.')) {
        return;
    }
    
    const btnDelete = document.getElementById('btnClearEmailHistory');
    const originalText = btnDelete.innerText;
    btnDelete.disabled = true;
    btnDelete.innerText = 'Đang xóa...';
    
    fetch(`/salary-bulk-exports/${currentExportId}/logs`, {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // refresh
            openEmailModal(currentExportId);
        } else {
            alert('Lỗi: ' + data.message);
            btnDelete.disabled = false;
            btnDelete.innerText = originalText;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi xóa lịch sử.');
        btnDelete.disabled = false;
        btnDelete.innerText = originalText;
    });
}

function closeEmailModal() {
    document.getElementById('emailModal').classList.remove('show');
    currentExportId = null;
    
    // Clear polling khi đóng modal
    if (statusPollingInterval) {
        clearInterval(statusPollingInterval);
        statusPollingInterval = null;
    }
    
    // Reset checkboxes
    const selectAllCheckbox = document.getElementById('selectAllEmails');
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = false;
    }
    document.querySelectorAll('.email-item-checkbox').forEach(cb => {
        cb.checked = false;
    });
    updateEmailCount();
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('emailModal');
    if (event.target === modal) {
        closeEmailModal();
    }
}

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeEmailModal();
    }
});

function toggleAllEmails(checkbox) {
    const allCheckboxes = document.querySelectorAll('.email-item-checkbox');
    allCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateEmailCount();
}

function updateEmailCount() {
    const selectedCheckboxes = document.querySelectorAll('.email-item-checkbox:checked');
    const countElement = document.getElementById('selectedEmailCount');
    if (countElement) {
        countElement.textContent = selectedCheckboxes.length;
    }
    
    // Update "select all" checkbox state
    const selectAllCheckbox = document.getElementById('selectAllEmails');
    const allCheckboxes = document.querySelectorAll('.email-item-checkbox');
    if (selectAllCheckbox && allCheckboxes.length > 0) {
        selectAllCheckbox.checked = selectedCheckboxes.length === allCheckboxes.length;
    }
}

function getSelectedEmails() {
    const selectedCheckboxes = document.querySelectorAll('.email-item-checkbox:checked');
    const emails = [];
    selectedCheckboxes.forEach(cb => {
        emails.push({
            email: cb.value,
            name: cb.getAttribute('data-name'),
            index: cb.getAttribute('data-index')
        });
    });
    return emails;
}

function sendEmails() {
    if (!currentExportId) {
        alert('Không tìm thấy thông tin export.');
        return;
    }
    
    const selectedEmails = getSelectedEmails();
    if (selectedEmails.length === 0) {
        alert('Vui lòng chọn ít nhất 1 email để gửi.');
        return;
    }
    
    // Confirm trước khi gửi
    if (!confirm(`Bạn có chắc muốn gửi email đến ${selectedEmails.length} người nhận?`)) {
        return;
    }
    
    // Disable button để tránh click nhiều lần
    const sendButton = event.currentTarget || document.querySelector('#emailModal .modal-footer .btn:last-child');
    let originalText = '';
    if (sendButton) {
        originalText = sendButton.innerHTML;
        sendButton.disabled = true;
        sendButton.innerHTML = '<span>Đang gửi...</span>';
    }
    
    // Gửi AJAX request
    fetch(`/salary-bulk-exports/${currentExportId}/send-mails`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            recipients: selectedEmails
        })
    })
    .then(response => response.json())
    .then(data => {
        if (sendButton) {
            sendButton.disabled = false;
            sendButton.innerHTML = originalText;
        }

        if (data.success) {
            // Cập nhật giao diện lập tức để hiện Pending
            selectedEmails.forEach(recipient => {
                document.querySelectorAll('.email-status-cell').forEach(cell => {
                    const e = (cell.getAttribute('data-email') || '').trim();
                    const n = (cell.getAttribute('data-name') || '').trim();
                    if (e === recipient.email && n === recipient.name) {
                        cell.innerHTML = '<span class="badge" style="background-color:#fef3c7; color:#d97706;">Pending</span>';
                    }
                });
            });
            alert('Đã bắt đầu gửi email! Hệ thống đang xử lý, trạng thái sẽ được cập nhật sớm.');
            // Reset các checkbox đã chọn
            const selectAllCheckbox = document.getElementById('selectAllEmails');
            if (selectAllCheckbox) selectAllCheckbox.checked = false;
            document.querySelectorAll('.email-item-checkbox').forEach(cb => cb.checked = false);
            updateEmailCount();
            // Gọi cập nhật trạng thái ngay sau 1s để thấy ngay quá trình pending chạy
            setTimeout(() => {
                if (currentExportId) fetchEmailStatus(currentExportId, false);
            }, 1000);
        } else {
            alert('Lỗi: ' + (data.message || 'Không thể gửi email'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra khi gửi email. Vui lòng thử lại.');
        if (sendButton) {
            sendButton.disabled = false;
            sendButton.innerHTML = originalText;
        }
    });
}
    // ... existing JS ...
    
    // --- Log Handling ---
    let currentLogExportId = null;

    function openLogModal(exportId) {
        currentLogExportId = exportId;
        // Reset delete button state
        const btnDeleteLogs = document.getElementById('btnDeleteLogs');
        if (btnDeleteLogs) btnDeleteLogs.style.display = 'none';
        // Clear previous logs
        const logContainer = document.getElementById('logContainer');
        logContainer.innerHTML = '<div style="text-align:center; padding:2rem; color:#6b7280;">Đang tải log...</div>';
        
        document.getElementById('logModal').style.display = 'block';
        
        fetch(`/salary-bulk-exports/${exportId}/logs`)
            .then(response => response.json())
            .then(logs => {
                if (logs.length === 0) {
                    logContainer.innerHTML = '<div style="text-align:center; padding:2rem; color:#6b7280;">Chưa có log nào được ghi nhận.</div>';
                    return;
                }
                
                // Show delete button if there are logs
                if (btnDeleteLogs) btnDeleteLogs.style.display = 'block';
                
                let html = '<div class="log-list">';
                const displayLogs = [...logs].reverse();
                displayLogs.forEach(log => {
                    const date = new Date(log.created_at);
                    const timeStr = date.toLocaleTimeString('vi-VN');
                    
                    let statusClass = 'log-info';
                    let icon = 'ℹ️';
                    if (log.status === 'success') { statusClass = 'log-success'; icon = '✅'; }
                    else if (log.status === 'warning') { statusClass = 'log-warning'; icon = '⚠️'; }
                    else if (log.status === 'error' || log.status === 'fail') { statusClass = 'log-error'; icon = '❌'; }
                    
                    html += `
                        <div class="log-item ${statusClass}">
                            <div class="log-header">
                                <span class="log-time">${timeStr}</span>
                                <span class="log-icon">${icon}</span>
                                <span class="log-message">${log.message}</span>
                            </div>
                            ${log.email ? `<div class="log-user">To: <strong>${log.name || 'N/A'}</strong> (${log.email})</div>` : ''}
                            ${log.details ? `<div class="log-details"><pre>${JSON.stringify(log.details, null, 2)}</pre></div>` : ''}
                        </div>
                    `;
                });
                html += '</div>';
                logContainer.innerHTML = html;
            })
            .catch(err => {
                console.error(err);
                logContainer.innerHTML = '<div style="text-align:center; padding:2rem; color:red;">Lỗi khi tải log.</div>';
            });
    }

    function closeLogModal() {
        document.getElementById('logModal').style.display = 'none';
        currentLogExportId = null;
    }

    function refreshLogs() {
        if (!currentLogExportId) return;
        const btn = document.getElementById('btnRefreshLogs');
        const icon = document.getElementById('refreshIcon');
        if (btn) btn.disabled = true;
        if (icon) icon.style.animation = 'spin 0.8s linear infinite';
        openLogModal(currentLogExportId);
        setTimeout(() => {
            if (btn) btn.disabled = false;
            if (icon) icon.style.animation = '';
        }, 800);
    }

    function deleteLogs() {
        if (!currentLogExportId) return;
        
        if (!confirm('Bạn có chắc chắn muốn xóa toàn bộ lịch sử gửi email của lần xuất này không? Hành động này không thể hoàn tác.')) {
            return;
        }
        
        const btnDelete = document.getElementById('btnDeleteLogs');
        const originalText = btnDelete.innerText;
        btnDelete.disabled = true;
        btnDelete.innerText = 'Đang xóa...';
        
        fetch(`/salary-bulk-exports/${currentLogExportId}/logs`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                // Refresh logs (will show empty state)
                openLogModal(currentLogExportId);
            } else {
                alert('Lỗi: ' + data.message);
                btnDelete.disabled = false;
                btnDelete.innerText = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi xóa lịch sử.');
            btnDelete.disabled = false;
            btnDelete.innerText = originalText;
        });
    }
</script>
<?php if($exports->contains('status', 'pending')): ?>
<script>
    setTimeout(() => {
        window.location.reload();
    }, 10000);
</script>
<?php endif; ?>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>



<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/salary-files/bulk-history.blade.php ENDPATH**/ ?>