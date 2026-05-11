import './bootstrap';

import Alpine from 'alpinejs';
import { mountStorefrontHome } from './storefront/HomePage.jsx';
import { mountAdminDashboard } from './admin/AdminDashboard.jsx';

window.Alpine = Alpine;
Alpine.start();

const onReady = (fn) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn);
    } else {
        fn();
    }
};

onReady(() => {
    const storefrontHome = document.getElementById('storefront-home-root');
    if (storefrontHome) {
        const payload = JSON.parse(storefrontHome.dataset.payload || '{}');
        mountStorefrontHome(storefrontHome, payload);
    }

    const adminDashboard = document.getElementById('admin-dashboard-root');
    if (adminDashboard) {
        const payload = JSON.parse(adminDashboard.dataset.payload || '{}');
        mountAdminDashboard(adminDashboard, payload);
    }

    const sidebar = document.getElementById('adminSidebar');
    const openSidebar = document.getElementById('openSidebar');
    const closeSidebar = document.getElementById('closeSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    const showSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.remove('translate-x-full');
        backdrop?.classList.remove('hidden');
    };

    const hideSidebar = () => {
        if (!sidebar) return;
        sidebar.classList.add('translate-x-full');
        backdrop?.classList.add('hidden');
    };

    openSidebar?.addEventListener('click', showSidebar);
    closeSidebar?.addEventListener('click', hideSidebar);
    backdrop?.addEventListener('click', hideSidebar);

    document.querySelectorAll('[data-toast]').forEach((toast) => {
        const closeBtn = toast.querySelector('[data-toast-close]');
        const remove = () => toast.remove();
        closeBtn?.addEventListener('click', remove);
        setTimeout(remove, 4200);
    });

    const modal = document.getElementById('confirmModal');
    const confirmOk = document.getElementById('confirmModalOk');
    let pendingForm = null;

    const closeModal = () => {
        modal?.classList.add('hidden');
        pendingForm = null;
    };

    document.querySelectorAll('form[data-confirm-delete]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            pendingForm = form;
            modal?.classList.remove('hidden');
        });
    });

    confirmOk?.addEventListener('click', () => {
        if (pendingForm) {
            pendingForm.submit();
        }
    });

    modal?.querySelectorAll('[data-confirm-cancel]').forEach((el) => {
        el.addEventListener('click', closeModal);
    });

    const html = document.documentElement;
    const savedTheme = window.localStorage.getItem('admin-theme');
    if (savedTheme === 'dark') {
        html.classList.add('dark');
    }

    const themeToggle = document.getElementById('adminThemeToggle');
    themeToggle?.addEventListener('click', () => {
        const isDark = html.classList.toggle('dark');
        window.localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
    });

    const commandOverlay = document.getElementById('adminCommandOverlay');
    const commandTrigger = document.getElementById('adminCommandTrigger');
    const commandClose = document.getElementById('adminCommandClose');
    const commandInput = document.getElementById('adminCommandInput');

    const openCommand = () => {
        commandOverlay?.classList.add('is-open');
        window.setTimeout(() => commandInput?.focus(), 30);
    };

    const closeCommand = () => {
        commandOverlay?.classList.remove('is-open');
        commandInput && (commandInput.value = '');
    };

    commandTrigger?.addEventListener('click', openCommand);
    commandClose?.addEventListener('click', closeCommand);
    commandOverlay?.addEventListener('click', (event) => {
        if (event.target === commandOverlay) closeCommand();
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            openCommand();
        }
        if (event.key === 'Escape') {
            closeCommand();
            notificationsPanel?.classList.remove('is-open');
        }
    });

    commandInput?.addEventListener('input', () => {
        const term = commandInput.value.trim().toLowerCase();
        commandOverlay?.querySelectorAll('.admin-command-item').forEach((item) => {
            item.classList.toggle('hidden', term.length > 0 && !item.textContent.toLowerCase().includes(term));
        });
    });

    const notificationsTrigger = document.getElementById('adminNotifyTrigger');
    const notificationsPanel = document.getElementById('adminNotificationsPanel');
    notificationsTrigger?.addEventListener('click', (event) => {
        event.stopPropagation();
        notificationsPanel?.classList.toggle('is-open');
    });

    document.addEventListener('click', (event) => {
        if (!notificationsPanel?.contains(event.target) && !notificationsTrigger?.contains(event.target)) {
            notificationsPanel?.classList.remove('is-open');
        }
    });
});
