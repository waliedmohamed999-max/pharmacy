import React from 'react';
import { cn } from '../../lib/utils';

const variants = {
    primary: 'bg-medical-600 text-white shadow-lg shadow-medical-600/20 hover:bg-medical-700',
    secondary: 'bg-white text-slate-900 border border-slate-200 hover:bg-slate-50 dark:bg-slate-900 dark:text-white dark:border-slate-800',
    ghost: 'bg-transparent text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800',
    dark: 'bg-slate-950 text-white hover:bg-slate-800',
};

export function Button({ className, variant = 'primary', children, ...props }) {
    return (
        <button
            className={cn(
                'inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-bold transition-all duration-200 active:scale-[0.98] disabled:pointer-events-none disabled:opacity-50',
                variants[variant],
                className
            )}
            {...props}
        >
            {children}
        </button>
    );
}
