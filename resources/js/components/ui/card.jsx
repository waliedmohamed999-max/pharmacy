import React from 'react';
import { cn } from '../../lib/utils';

export function Card({ className, children, ...props }) {
    return (
        <div
            className={cn(
                'rounded-[1.35rem] border border-slate-200/80 bg-white shadow-sm transition dark:border-slate-800 dark:bg-slate-950',
                className
            )}
            {...props}
        >
            {children}
        </div>
    );
}

export function Badge({ className, children }) {
    return (
        <span className={cn('inline-flex items-center rounded-full px-2.5 py-1 text-xs font-black', className)}>
            {children}
        </span>
    );
}
