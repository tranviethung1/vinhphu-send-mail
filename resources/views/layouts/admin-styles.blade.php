<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
        background-color: #f5f7fa;
        color: #1b1b18;
        overflow-x: hidden;
    }

    .admin-wrapper {
        display: flex;
        min-height: 100vh;
    }

    /* Sidebar Styles */
    .sidebar {
        width: 260px;
        background: #1f2937;
        color: #fff;
        position: fixed;
        left: 0;
        top: 0;
        height: 100vh;
        overflow-y: auto;
        transition: transform 0.3s ease;
        z-index: 1000;
    }

    .sidebar-header {
        padding: 1.5rem;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .sidebar-logo {
        margin: auto;
        display: inline-flex;
        align-items: center;
    }

    .sidebar-logo-image {
        height: 112px;
        width: auto;
        display: block;
    }

    .sidebar-toggle {
        display: none;
        background: none;
        border: none;
        color: #fff;
        cursor: pointer;
        padding: 0.5rem;
    }

    .sidebar-nav {
        padding: 1rem 0;
    }

    .nav-menu {
        list-style: none;
    }

    .nav-item {
        margin: 0.25rem 0;
    }

    .nav-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        color: #d1d5db;
        text-decoration: none;
        transition: all 0.2s;
        border-left: 3px solid transparent;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    .nav-link.active {
        background-color: rgba(255, 255, 255, 0.1);
        color: #fff;
        border-left-color: #4299e1;
    }

    .nav-link svg {
        flex-shrink: 0;
    }

    /* Main Content */
    .main-content {
        flex: 1;
        margin-left: 260px;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    /* Header Styles */
    .header {
        background: #fff;
        padding: 1rem 2rem;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .header-left {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .mobile-menu-toggle {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.5rem;
        color: #1f2937;
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-menu {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        cursor: pointer;
    }

    .user-name {
        color: #1f2937;
        font-weight: 500;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
    }

    /* Content Wrapper */
    .content-wrapper {
        flex: 1;
        padding: 2rem;
        width: 86vw;
    }

    /* Alert Styles */
    .alert {
        padding: 1rem 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .alert-success {
        background-color: #d1fae5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.open {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: block;
        }

        .main-content {
            margin-left: 0;
        }

        .mobile-menu-toggle {
            display: block;
        }

        .header {
            padding: 1rem;
        }

        .content-wrapper {
            padding: 1rem;
        }

        .page-title {
            font-size: 1.25rem;
        }
    }

    /* Scrollbar Styles */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
</style>
