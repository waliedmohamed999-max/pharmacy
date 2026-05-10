import { create } from 'zustand';

const initialDark = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    const saved = window.localStorage.getItem('storefront-theme');
    if (saved) {
        return saved === 'dark';
    }

    return window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
};

export const useStorefront = create((set) => ({
    cartCount: 0,
    dark: initialDark(),
    previewProduct: null,
    setCartCount: (cartCount) => set({ cartCount }),
    toggleDark: () => set((state) => {
        const dark = !state.dark;
        window.localStorage.setItem('storefront-theme', dark ? 'dark' : 'light');
        return { dark };
    }),
    openPreview: (previewProduct) => set({ previewProduct }),
    closePreview: () => set({ previewProduct: null }),
}));
