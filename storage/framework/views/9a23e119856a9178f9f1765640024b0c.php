<?php $__env->startSection('title', 'Quản lý Mail'); ?>
<?php $__env->startSection('page-title', 'Quản lý Mail'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .mail-grid { display: grid; grid-template-columns: 1fr; gap: 1.25rem; }
    .mail-card { background: #ffffff; border-radius: 0.75rem; padding: 1.5rem; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.05); border: 1px solid #e5e7eb; }
    .card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; margin-bottom: 1rem; }
    .card-header h2 { font-size: 1.1rem; font-weight: 700; margin: 0; color: #111827; }
    .card-header p { margin: 0.25rem 0 0; color: #6b7280; font-size: 0.95rem; }
    .badge { display: inline-flex; align-items: center; gap: 0.35rem; background: #f3f4f6; color: #111827; border-radius: 9999px; padding: 0.35rem 0.75rem; font-size: 0.85rem; font-weight: 600; white-space: nowrap; }
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .table-wrapper { margin-top: 1rem; border: 1px solid #e5e7eb; border-radius: 0.75rem; overflow: hidden; }
    table.mail-table { width: 100%; border-collapse: collapse; }
    table.mail-table thead { background: #f9fafb; }
    table.mail-table th, table.mail-table td { padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; text-align: left; color: #111827; }
    table.mail-table tbody tr:nth-child(even) { background: #f9fafb; }
    table.mail-table tbody tr:hover { background: #f3f4f6; }
    .empty-state { margin-top: 0.5rem; color: #6b7280; }
    @media (max-width: 768px) { .card-header { flex-direction: column; } }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="mail-grid">
    <div class="mail-card">
        <div class="card-header">
            <div>
                <h2>Danh sách email</h2>
                <p>Tạo list mới từ Excel, đặt tên và lưu lại.</p>
            </div>
            <div style="display:flex; gap:0.5rem; flex-wrap:wrap; align-items:center;">
                <a href="<?php echo e(route('mails.create', ['facility_id' => $facilityId])); ?>" class="btn btn-success">Tạo mới</a>
            </div>
        </div>

        <?php if(empty($lists) || count($lists) === 0): ?>
            <div class="empty-state">Chưa có mail list nào. Bấm “Tạo mới” để bắt đầu.</div>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="mail-table">
                    <thead>
                        <tr>
                            <th style="width: 90px;">ID</th>
                            <th>Tên list</th>
                            <th>File</th>
                            <th style="width: 160px;">Cơ sở</th>
                            <th style="width: 120px;">Số người</th>
                            <th style="width: 180px;">Tạo lúc</th>
                            <th style="width: 240px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $lists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($list->id); ?></td>
                                <td><?php echo e($list->name); ?></td>
                                <td><?php echo e($list->original_filename ?? '-'); ?></td>
                                <td><?php echo e($list->facility?->name ?? '-'); ?></td>
                                <td><?php echo e(is_array($list->rows) ? count($list->rows) : 0); ?></td>
                                <td><?php echo e(optional($list->created_at)->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <a class="btn btn-sm btn-success" href="<?php echo e(route('mails.download', $list)); ?>" title="Download Excel" style="padding:0.375rem 0.5rem; background-color: #10b981;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                        </svg>
                                    </a>
                                    <a class="btn btn-sm btn-secondary" href="<?php echo e(route('mails.show', $list)); ?>">Xem</a>
                                    <a class="btn btn-sm" href="<?php echo e(route('mails.edit', $list)); ?>" title="Sửa" style="padding:0.375rem 0.5rem;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form action="<?php echo e(route('mails.destroy', $list)); ?>" method="POST" style="display:inline;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Xóa mail list này?');" title="Xóa" style="padding:0.375rem 0.5rem;">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/mails/index.blade.php ENDPATH**/ ?>