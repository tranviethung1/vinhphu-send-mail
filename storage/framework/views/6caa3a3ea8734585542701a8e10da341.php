<header class="header">
    <div class="header-left">
        <button class="mobile-menu-toggle" id="mobileMenuToggle">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <?php if (! empty(trim($__env->yieldContent('page-breadcrumb')))): ?>
            <?php echo $__env->yieldContent('page-breadcrumb'); ?>
        <?php else: ?>
            <h1 class="page-title"><?php echo $__env->yieldContent('page-title', 'Dashboard'); ?></h1>
        <?php endif; ?>
    </div>
    
    <div class="header-right">
        <?php echo $__env->yieldPushContent('header-info'); ?>
        <div class="user-menu">
            <span class="user-name">Admin</span>
            <div class="user-avatar">
                <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
        </div>
    </div>
</header>
<?php /**PATH /var/www/html/resources/views/layouts/partials/header.blade.php ENDPATH**/ ?>