<?php $__env->startSection('title', 'Thêm cơ sở'); ?>
<?php $__env->startSection('page-title', 'Thêm cơ sở'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .card {background:#fff;border-radius:0.5rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.08);max-width:640px;}
    .form-group {margin-bottom:1rem;}
    .label {display:block;font-weight:600;margin-bottom:0.35rem;color:#374151;}
    .input {width:100%;padding:0.65rem;border:1px solid #d1d5db;border-radius:0.375rem;font-size:0.95rem;}
    .input:focus {outline:none;border-color:#4299e1;box-shadow:0 0 0 3px rgba(66,153,225,0.15);}
    .actions {display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.25rem;}
    .btn {display:inline-block;padding:0.5rem 1rem;background-color:#4299e1;color:white;border-radius:0.375rem;cursor:pointer;border:none;font-weight:500;text-decoration:none;transition:background-color 0.2s;font-size:0.875rem;}
    .btn:hover {background-color:#3182ce;}
    .btn-success {background-color:#10b981;}
    .btn-success:hover {background-color:#059669;}
    .btn-danger {background-color:#ef4444;}
    .btn-danger:hover {background-color:#dc2626;}
    .btn-secondary {background-color:#6b7280;}
    .btn-secondary:hover {background-color:#4b5563;}
    .btn-sm {padding:0.375rem 0.75rem;font-size:0.8125rem;}
    .error {color:#ef4444;font-size:0.85rem;margin-top:0.25rem;}
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="card">
    <form action="<?php echo e(route('facilities.store')); ?>" method="POST">
        <?php echo csrf_field(); ?>
        <div class="form-group">
            <label class="label">Tên cơ sở <span style="color:#ef4444;">*</span></label>
            <input type="text" name="name" class="input" value="<?php echo e(old('name')); ?>" required placeholder="Ví dụ: Cơ sở Hà Nội">
            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="error"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="label">Prefix</label>
            <input type="text" name="prefix" class="input" value="<?php echo e(old('prefix')); ?>" placeholder="Ví dụ: VPC, HN, ...">
            <?php $__errorArgs = ['prefix'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="error"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="label">Địa chỉ</label>
            <input type="text" name="address" class="input" value="<?php echo e(old('address')); ?>" placeholder="Ví dụ: 123 Đường A, Quận B">
            <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="error"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <div class="form-group">
            <label class="label">Data - Link</label>
            <input type="url" name="data_link" class="input" value="<?php echo e(old('data_link')); ?>" placeholder="https://...">
            <?php $__errorArgs = ['data_link'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                <p class="error"><?php echo e($message); ?></p>
            <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>

        <div class="actions">
            <a href="<?php echo e(route('facilities.index')); ?>" class="btn btn-secondary">Hủy</a>
            <button type="submit" class="btn">Lưu</button>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /var/www/html/resources/views/facilities/create.blade.php ENDPATH**/ ?>