<?php $__env->startSection('title', 'Quản lý lương/ thưởng'); ?>

<?php $__env->startSection('page-title', 'Quản lý lương/ thưởng'); ?>

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
    .btn-success {
        background-color: #10b981;
    }
    .btn-success:hover {
        background-color: #059669;
    }
    .btn-danger {
        background-color: #ef4444;
    }
    .btn-danger:hover {
        background-color: #dc2626;
    }
    .btn-secondary {
        background-color: #6b7280;
    }
    .btn-secondary:hover {
        background-color: #4b5563;
    }
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.8125rem;
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
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #374151;
        border-bottom: 2px solid #e5e7eb;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    .table td {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
        color: #1f2937;
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
    .action-buttons {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .description-cell {
        max-width: 300px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
    }
    .badge-info {
        background-color: #dbeafe;
        color: #1e40af;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div></div>
    <div>
        <a href="<?php echo e(route('salary-files.create')); ?>" class="btn btn-success">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Upload File Mới
        </a>
    </div>
</div>

<?php if($files->isEmpty()): ?>
    <div class="table-wrapper">
        <div class="empty-state">
            <svg width="64" height="64" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin: 0 auto 1rem; color: #9ca3af;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p style="font-size: 1.125rem; margin-bottom: 0.5rem;">Chưa có file lương nào</p>
            <p style="color: #9ca3af;">Hãy upload file lương đầu tiên của bạn</p>
        </div>
    </div>
<?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 18%;">Tên</th>
                    <th style="width: 26%;">Cơ sở</th>
                    <th style="width: 12%;">Tháng</th>
                    <th style="width: 10%;">Kích thước</th>
                    <th style="width: 15%;">Ngày upload</th>
                    <th style="width: 11%;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $files; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($index + 1); ?></td>
                        <td><strong><?php echo e($file->name); ?></strong></td>
                        <td>
                            <?php echo e($file->facility?->name ?: '-'); ?>

                        </td>
                        <td>
                            <?php if($file->month): ?>
                                <span class="badge badge-info"><?php echo e($file->month); ?></span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo e($file->file_size); ?></td>
                        <td><?php echo e($file->created_at->format('d/m/Y H:i')); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo e(route('salary-files.download', $file->id)); ?>" class="btn btn-secondary btn-sm" title="Tải xuống" style="padding:0.375rem 0.5rem;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                    </svg>
                                </a>
                                <a href="<?php echo e(route('salary-files.edit', $file->id)); ?>" class="btn btn-sm" title="Chỉnh sửa" style="padding:0.375rem 0.5rem;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="<?php echo e(route('salary-files.destroy', $file->id)); ?>" method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc chắn muốn xóa file này?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger btn-sm" title="Xóa" style="padding:0.375rem 0.5rem;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/salary-files/index.blade.php ENDPATH**/ ?>