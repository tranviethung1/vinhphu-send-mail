
<?php if(!empty($items)): ?>
<nav class="breadcrumb-nav" aria-label="breadcrumb">
    <ol class="breadcrumb-list">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $isLast = $i === array_key_last($items); ?>
            <li class="breadcrumb-item <?php echo e($isLast ? 'breadcrumb-active' : ''); ?>">
                <?php if(!$isLast && !empty($item['url'])): ?>
                    <a href="<?php echo e($item['url']); ?>" class="breadcrumb-link"><?php echo e($item['label']); ?></a>
                    <span class="breadcrumb-sep" aria-hidden="true">
                        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </span>
                <?php else: ?>
                    <span><?php echo e($item['label']); ?></span>
                <?php endif; ?>
            </li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ol>
</nav>
<?php endif; ?>
<?php /**PATH /var/www/html/resources/views/layouts/partials/breadcrumb.blade.php ENDPATH**/ ?>