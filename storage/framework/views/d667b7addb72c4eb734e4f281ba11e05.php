<?php $__env->startSection('title', 'Danh sách các mẫu'); ?>

<?php $__env->startSection('content'); ?>
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
    .btn-danger {
        background-color: #ef4444;
    }
    .btn-danger:hover {
        background-color: #dc2626;
    }
</style>
<?php $__env->stopPush(); ?>

<div class="page-header">
    <div>
        <h1 style="font-size: 1.25rem; margin-bottom: 0.25rem;">Danh sách template</h1>
        <p style="font-size: 0.8125rem; margin: 0;">Các template được tạo từ file Excel (cột A-D).</p>
    </div>
    <div style="display:flex; gap: 0.5rem; flex-wrap: wrap;">
        <a href="<?php echo e(route('templates.mapping.create')); ?>" class="btn">
            + Tạo template
        </a>
    </div>
</div>

<?php if(!empty($savedSelections ?? []) && count($savedSelections) > 0): ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th style="min-width: 160px;">Tên template</th>
                    <th style="min-width: 160px;">Tên file</th>
                    <th style="width: 100px;">Số ô</th>
                    <th style="min-width: 160px;">Tạo lúc</th>
                    <th style="width: 160px;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $savedSelections; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td>#<?php echo e($item->id); ?></td>
                        <td>
                            <a href="<?php echo e(route('templates.show', $item->id)); ?>" style="color: #2563eb; text-decoration: underline;">
                                <?php echo e($item->name ?? '(chưa đặt tên)'); ?>

                            </a>
                        </td>
                        <td>
                            <span><?php echo e($item->file_name ?? 'N/A'); ?></span>
                        </td>
                        <td>
                            <span class="badge"><?php echo e(is_array($item->selections) ? count($item->selections) : 0); ?></span>
                        </td>
                        <td><?php echo e($item->created_at?->format('Y-m-d H:i')); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="<?php echo e(route('templates.show', $item->id)); ?>" class="btn" title="Sửa" style="padding:0.375rem 0.5rem;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="<?php echo e(route('templates.destroy', $item->id)); ?>" method="POST" onsubmit="return confirm('Bạn có chắc chắn muốn xoá template mapping này?');" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-danger" title="Xóa" style="padding:0.375rem 0.5rem;">
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
<?php else: ?>
    <div class="table-wrapper">
        <div class="empty-state">
            <p style="font-size: 1.1rem; margin-bottom: 0.5rem;">Chưa có template mapping nào</p>
            <p style="color: #9ca3af; margin-bottom: 1rem;">Hãy tạo mapping mới từ file Excel.</p>
            <a href="<?php echo e(route('templates.mapping.create')); ?>" class="btn">
                + Tạo mapping từ Excel
            </a>
        </div>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/templates/index.blade.php ENDPATH**/ ?>