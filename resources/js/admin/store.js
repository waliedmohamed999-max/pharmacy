import { create } from 'zustand';

const initialDark = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    const saved = window.localStorage.getItem('admin-theme');
    if (saved) {
        return saved === 'dark';
    }

    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
};

export const useAdminStore = create((set) => ({
    collapsed: false,
    dark: initialDark(),
    commandOpen: false,
    notificationsOpen: false,
    toggleCollapsed: () => set((state) => ({ collapsed: !state.collapsed })),
    toggleDark: () => set((state) => {
        const dark = !state.dark;
        window.localStorage.setItem('admin-theme', dark ? 'dark' : 'light');
        return { dark };
    }),
    setCommandOpen: (commandOpen) => set({ commandOpen }),
    setNotificationsOpen: (notificationsOpen) => set({ notificationsOpen }),
}));
