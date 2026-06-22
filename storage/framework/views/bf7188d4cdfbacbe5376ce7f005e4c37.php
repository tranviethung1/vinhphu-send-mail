<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="/" class="sidebar-logo">
            <img src="<?php echo e(asset('logo_vinh_phu.png')); ?>" alt="Cty Vinh Phu" class="sidebar-logo-image">
        </a>
        <button class="sidebar-toggle" id="sidebarToggle">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="<?php echo e(route('dashboard')); ?>" class="nav-link <?php echo e(request()->routeIs('dashboard') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(route('facilities.index')); ?>" class="nav-link <?php echo e(request()->routeIs('facilities.*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10l9-7 9 7v8a2 2 0 01-2 2h-4a2 2 0 01-2-2V12H5v6a2 2 0 01-2 2h0a2 2 0 01-2-2v-8z"/>
                    </svg>
                    <span>Quản lý cơ sở</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="<?php echo e(route('salary-files.index')); ?>" class="nav-link <?php echo e(request()->routeIs('salary-files.*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span>Quản lý lương/ thưởng</span>
                </a>
            </li> -->
            <li class="nav-item">
                <a href="<?php echo e(route('templates.index')); ?>" class="nav-link <?php echo e(request()->routeIs('templates.*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"></path>
                    </svg>
                    <span>Quản lý template</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="<?php echo e(route('ai-chat.index')); ?>" class="nav-link <?php echo e(request()->routeIs('ai-chat.*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0112 15a9.065 9.065 0 00-6.23-.693L5 14.5m14.8.8l1.402 1.402c1 1 .3 2.7-1.1 2.7H3.9c-1.4 0-2.1-1.7-1.1-2.7L4.2 15.3"/>
                    </svg>
                    <span>Trợ lý AI</span>
                </a>
            </li> -->
            <li class="nav-item">
                <a href="<?php echo e(route('qr-codes.index')); ?>" class="nav-link <?php echo e(request()->routeIs('qr-codes.*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <span>Tạo QR Code</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo e(url('/n8n')); ?>" class="nav-link <?php echo e(request()->is('n8n*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16" />
                    </svg>
                    <span>n8n</span>
                </a>
            </li>
            <!-- <li class="nav-item">
                <a href="<?php echo e(route('mails.index')); ?>" class="nav-link <?php echo e(request()->routeIs('mails.*') ? 'active' : ''); ?>">
                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    <span>Quản lý email</span>
                </a>
            </li> -->
        </ul>
    </nav>
</aside>
<?php /**PATH /var/www/html/resources/views/layouts/partials/sidebar.blade.php ENDPATH**/ ?>