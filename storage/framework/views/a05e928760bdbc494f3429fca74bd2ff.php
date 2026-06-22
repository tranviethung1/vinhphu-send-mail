<?php $__env->startSection('title', 'Quản lý cơ sở'); ?>
<?php $__env->startSection('page-title', 'Quản lý cơ sở'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .page-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;gap:1rem;flex-wrap:wrap;}
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .badge {display:inline-block;padding:0.2rem 0.6rem;border-radius:999px;font-size:0.75rem;font-weight:600;background:#dbeafe;color:#1e40af;}
    .empty {padding:2.5rem;text-align:center;color:#6b7280;background:#fff;border-radius:0.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);}
    .grid {display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:1rem;}
    .card {background:#fff;border-radius:0.75rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);padding:1rem;display:flex;flex-direction:column;gap:0.75rem;min-width:0;}
    .card-top {display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;}
    .title {font-weight:800;color:#111827;font-size:1rem;line-height:1.25rem;word-break:break-word;}
    .sub {color:#6b7280;font-size:0.9rem;line-height:1.2rem;word-break:break-word;}
    .file-list {display:flex;flex-direction:column;gap:0.25rem;color:#374151;font-size:0.92rem;}
    .file-item {white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
    .actions {display:flex;gap:0.5rem;flex-wrap:wrap;justify-content:flex-end;border-top:1px solid #e5e7eb;padding-top:0.75rem;margin-top:0.25rem;}
    @media (max-width: 1100px){.grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
    @media (max-width: 700px){.grid{grid-template-columns:1fr;}}

    /* Tooltip indicator for mail sync */
    .sync-indicator {
        position: absolute;
        bottom: calc(100% + 12px);
        left: 50%;
        transform: translateX(-50%);
        background-color: #ef4444;
        color: white;
        padding: 5px 10px;
        border-radius: 6px;
        font-size: 0.72rem;
        font-weight: 600;
        white-space: nowrap;
        display: none;
        z-index: 50;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        pointer-events: none;
        line-height: 1.2;
    }
    .sync-indicator.active {
        display: block;
        animation: tooltipPop 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .sync-indicator::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border-width: 6px;
        border-style: solid;
        border-color: #ef4444 transparent transparent transparent;
    }
    @keyframes tooltipPop {
        from { opacity: 0; transform: translateX(-50%) translateY(8px) scale(0.9); }
        to { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div></div>
    <a href="<?php echo e(route('facilities.create')); ?>" class="btn">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m7-7H5"/></svg>
        Thêm cơ sở
    </a>
</div>

<?php if($facilities->isEmpty()): ?>
    <div class="empty">
        Chưa có cơ sở nào. Hãy thêm mới.
    </div>
<?php else: ?>
    <div class="grid">
        <?php $__currentLoopData = $facilities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $facility): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="card">
                <div class="card-top">
                    <div style="min-width:0;">
                        <div class="title"><?php echo e($facility->name); ?></div>
                        <div class="sub"><?php echo e($facility->address ?: '—'); ?></div>
                        <?php if($facility->defaultMailList): ?>
                            <div style="margin-top:0.3rem;">
                                <span class="badge">Mail mặc định: <?php echo e($facility->defaultMailList?->name ?? '—'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex-shrink:0;">
                        <span class="badge"><?php echo e($facility->salary_files_count); ?> file</span>
                    </div>
                </div>

                <div>
                    <div style="font-weight:700;color:#111827;margin-bottom:0.35rem;">File gần đây</div>
                    <div class="file-list">
                        <?php $__empty_1 = true; $__currentLoopData = $facility->salaryFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $file): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="file-item" title="<?php echo e($file->name); ?>">
                                - <?php echo e($file->name); ?> <?php if($file->month): ?><span style="color:#6b7280;">(<?php echo e($file->month); ?>)</span><?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div style="color:#6b7280;">Chưa có file</div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="actions">
                    <a href="<?php echo e(route('facilities.salary-files', $facility)); ?>" class="btn btn-sm">File</a>
                    <a href="<?php echo e(route('facilities.mail-lists', $facility)); ?>" class="btn btn-sm" style="position: relative;" id="mail-list-btn-<?php echo e($facility->id); ?>">
                        Danh sách email
                        <span class="sync-indicator">Có thay đổi trên Drive cần đồng bộ</span>
                    </a>
                    <a href="<?php echo e(route('facilities.edit', $facility)); ?>" class="btn btn-sm btn-secondary" title="Sửa" style="padding:0.375rem 0.5rem;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </a>
                    <form action="<?php echo e(route('facilities.destroy', $facility)); ?>" method="POST" onsubmit="return confirm('Xóa cơ sở này? Các file lương sẽ mất liên kết.');" style="display:inline;">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button type="submit" class="btn btn-sm btn-danger" title="Xóa" style="padding:0.375rem 0.5rem;">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    (function () {
        let pollInterval = 5000;
        const MAX_INTERVAL = <?php echo e(env('SYNC_POLL_MAX_INTERVAL', 30000)); ?>;
        let pollCount = 0;
        const MAX_POLLS = <?php echo e(env('SYNC_POLL_MAX_RETRIES', 10)); ?>;

        function getNextInterval() {
            const current = pollInterval;
            if (pollInterval < MAX_INTERVAL) {
                pollInterval += 5000;
            }
            return current;
        }

        function scheduleNextPoll() {
            if (pollCount < MAX_POLLS) {
                setTimeout(checkDefaultMailListsDriveChanges, getNextInterval());
            }
        }

        function checkDefaultMailListsDriveChanges() {
            pollCount++;
            fetch('<?php echo e(route("facilities.check-default-mail-lists-drive-changes")); ?>', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(res => res.ok ? res.json() : null)
            .then(data => {
                if (!data || !data.changed_facilities) {
                    scheduleNextPoll();
                    return;
                }
                
                // Ẩn tất cả indicator trước
                document.querySelectorAll('.sync-indicator').forEach(el => el.classList.remove('active'));

                let hasChanges = false;
                // Hiển thị indicator cho các cơ sở có thay đổi
                data.changed_facilities.forEach(facilityId => {
                    const btn = document.getElementById('mail-list-btn-' + facilityId);
                    if (btn) {
                        const indicator = btn.querySelector('.sync-indicator');
                        if (indicator) {
                            indicator.classList.add('active');
                            hasChanges = true;
                        }
                    }
                });

                // Lặp lại với timeout tăng dần
                scheduleNextPoll();
            })
            .catch(() => {
                scheduleNextPoll();
            });
        }

        // Bắt đầu gọi hàm kiểm tra
        setTimeout(checkDefaultMailListsDriveChanges, pollInterval);
    })();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/facilities/index.blade.php ENDPATH**/ ?>