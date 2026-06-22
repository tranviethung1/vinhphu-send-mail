<?php $__env->startSection('title', $facility->name . ' — Danh sách email'); ?>

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
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?php echo e(route('facilities.mail-lists', $facility->id)); ?>"
               style="font-size:1rem;font-weight:500;color:#6b7280;text-decoration:none;transition:color .15s;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
               onmouseover="this.style.color='#111827'" onmouseout="this.style.color='#6b7280'"
               title="<?php echo e($facility->name); ?>">
                <?php echo e($facility->name); ?>

            </a>
        </li>
        <li style="display:flex;align-items:center;">
            <svg width="16" height="16" fill="none" stroke="#9ca3af" viewBox="0 0 24 24"
                 style="margin:0 0.35rem;flex-shrink:0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span style="font-size:1.15rem;font-weight:700;color:#111827;">Danh sách email</span>
        </li>
    </ol>
</nav>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .header {display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;}
    .title {font-weight:800;color:#111827;font-size:1.05rem;}
    .sub {color:#6b7280;font-size:0.95rem;}
    .badge {display:inline-block;padding:0.25rem 0.75rem;border-radius:999px;background:#dbeafe;color:#1e40af;font-weight:700;font-size:0.82rem;}
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .table-wrapper {background:#fff;border-radius:0.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);overflow:hidden;}
    table {width:100%;border-collapse:collapse;}
    th,td {padding:0.9rem 1rem;text-align:left;border-bottom:1px solid #e5e7eb;font-size:0.92rem;vertical-align:top;}
    thead {background:#f9fafb;}
    th {text-transform:uppercase;font-size:0.78rem;letter-spacing:0.05em;color:#374151;}
    .empty {padding:2.5rem;text-align:center;color:#6b7280;}
    .actions {display:flex;gap:0.5rem;flex-wrap:wrap;}
    .toggle {
        width: 52px;
        height: 28px;
        border-radius: 999px;
        border: 1px solid #d1d5db;
        background: #e5e7eb;
        position: relative;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        padding: 2px;
    }
    .toggle::after {
        content: '';
        width: 22px;
        height: 22px;
        background: #fff;
        border-radius: 999px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
        transition: all 0.2s;
        display: block;
    }
    .toggle.active {
        background: #2563eb;
        border-color: #1d4ed8;
    }
    .toggle.active::after {
        transform: translateX(24px);
        background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" stroke="%232563eb" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>') center center no-repeat;
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="header">
    <div>
    </div>
    <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
        <a href="<?php echo e(route('mails.create', ['facility_id' => $facility->id])); ?>" class="btn">Upload mail list</a>
        <a href="<?php echo e(route('facilities.index')); ?>" class="btn btn-secondary">Quay lại cơ sở</a>
    </div>
</div>

<div class="table-wrapper">
    <?php if($mailLists->isEmpty()): ?>
        <div class="empty">Cơ sở này chưa có mail list.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:5%;">#</th>
                    <th style="width:23%;">Tên list</th>
                    <th style="width:20%;">File gốc</th>
                    <th style="width:9%;">Số người</th>
                    <th style="width:13%;">Tạo lúc</th>
                    <th style="width:10%;">Mặc định</th>
                    <th style="width:20%;">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $mailLists; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $list): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($idx + 1); ?></td>
                        <td><?php echo e($list->name); ?></td>
                        <td><?php echo e($list->original_filename ?? '-'); ?></td>
                        <td><?php echo e(is_array($list->rows) ? count($list->rows) : 0); ?></td>
                        <td><?php echo e(optional($list->created_at)->format('d/m/Y H:i')); ?></td>
                        <td>
                            <form action="<?php echo e(route('facilities.mail-lists.default', $facility)); ?>" method="POST">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="mail_list_id" value="<?php echo e($list->id); ?>">
                                <button type="submit" class="toggle <?php echo e($list->is_default ? 'active' : ''); ?>" aria-label="Chọn mail mặc định"></button>
                            </form>
                        </td>
                        <td>
                            <div class="actions">
                                <?php if($list->google_sheet_url): ?>
                                    <button type="button" class="btn btn-sm btn-success" onclick="syncMailList(this, <?php echo e($list->id); ?>, '<?php echo e(addslashes($list->google_sheet_url)); ?>')" title="Đồng bộ nhanh từ Google Sheet" style="padding:0.375rem 0.5rem;" aria-label="Đồng bộ nhanh">
                                        <svg width="16" height="16" fill="none" class="sync-icon" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                    </button>
                                <?php endif; ?>
                                <a class="btn btn-sm btn-secondary" href="<?php echo e(route('mails.show', $list)); ?>">Xem</a>
                                <a class="btn btn-sm btn-secondary" href="<?php echo e(route('mails.edit', $list)); ?>" title="Sửa" style="padding:0.375rem 0.5rem;">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </a>
                                <form action="<?php echo e(route('mails.destroy', $list)); ?>" method="POST" onsubmit="return confirm('Xóa mail list này?');" style="display:inline;">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button class="btn btn-sm btn-danger" type="submit" title="Xóa" style="padding:0.375rem 0.5rem;">
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
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function syncMailList(btn, id, sheetUrl) {
        if (!confirm('Đồng bộ danh sách này từ Google Sheet?')) return;

        btn.disabled = true;
        const icon = btn.querySelector('.sync-icon');
        if (icon) {
            icon.style.animation = 'spin 1s linear infinite';
        }

        const formData = new FormData();
        formData.append('_token', '<?php echo e(csrf_token()); ?>');
        formData.append('sheet_url', sheetUrl);

        fetch(`/mails/${id}/sync-from-sheet`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert(data.message || 'Đồng bộ thành công.');
                window.location.reload();
            } else {
                alert(data.message || 'Đồng bộ thất bại.');
                resetBtn(btn, icon);
            }
        })
        .catch(() => {
            alert('Lỗi kết nối. Vui lòng thử lại.');
            resetBtn(btn, icon);
        });
    }

    function resetBtn(btn, icon) {
        btn.disabled = false;
        if (icon) {
            icon.style.animation = 'none';
        }
    }
</script>
<style>
    @keyframes spin { 100% { transform: rotate(360deg); } }
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/facilities/mail-lists.blade.php ENDPATH**/ ?>