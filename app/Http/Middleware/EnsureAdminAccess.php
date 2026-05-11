<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->role === 'admin') {
            return $next($request);
        }

        $required = $this->permissionFor($request);
        if ($required === null) {
            return $next($request);
        }

        abort_unless($required && $user->hasPermission($required), 403, 'ليس لديك صلاحية الوصول.');

        return $next($request);
    }

    private function permissionFor(Request $request): ?string
    {
        $name = (string) optional($request->route())->getName();
        $method = $request->method();

        if ($name === 'admin.dashboard') {
            return 'dashboard.view';
        }

        if (str_starts_with($name, 'admin.orders.')) {
            return str_contains($name, 'updateStatus') ? 'orders.manage' : 'orders.view';
        }

        if (str_starts_with($name, 'admin.products.')) {
            if (str_contains($name, 'destroy') || str_contains($name, 'delete') || str_contains($name, 'forceDelete')) {
                return 'products.delete';
            }

            if (!in_array($method, ['GET', 'HEAD'], true) || str_contains($name, 'create') || str_contains($name, 'edit') || str_contains($name, 'import') || str_contains($name, 'refresh')) {
                return 'products.manage';
            }

            return 'products.view';
        }

        if (str_starts_with($name, 'admin.categories.')) {
            return 'categories.manage';
        }

        if (str_starts_with($name, 'admin.customers.')) {
            return str_contains($name, 'import') || str_contains($name, 'export') ? 'customers.import_export' : 'customers.view';
        }

        if (str_starts_with($name, 'admin.banners.')) {
            return 'banners.manage';
        }

        if (str_starts_with($name, 'admin.pages.')) {
            return 'pages.manage';
        }

        if (str_starts_with($name, 'admin.footer.')) {
            return 'footer.manage';
        }

        if (str_starts_with($name, 'admin.home-sections.')) {
            return 'home_builder.manage';
        }

        if (str_starts_with($name, 'admin.finance.')) {
            return str_contains($name, 'export') ? 'finance.export' : 'finance.view';
        }

        if (str_starts_with($name, 'admin.reports.')) {
            return 'reports.view';
        }

        if (str_starts_with($name, 'admin.accounting.reports.')) {
            return 'accounting.reports';
        }

        if (str_starts_with($name, 'admin.accounting.sales.')) {
            return 'accounting.sales';
        }

        if (str_starts_with($name, 'admin.accounting.purchases.')) {
            return 'accounting.purchases';
        }

        if (str_starts_with($name, 'admin.accounting.journal.')) {
            return 'accounting.journal';
        }

        if (str_starts_with($name, 'admin.accounting.payments.')) {
            return 'accounting.payments';
        }

        if (str_starts_with($name, 'admin.accounting.')) {
            return 'accounting.view';
        }

        if (str_starts_with($name, 'admin.inventory.') || str_starts_with($name, 'admin.pos.')) {
            return null;
        }

        if (str_starts_with($name, 'admin.users.permissions.')) {
            return 'users.permissions';
        }

        return 'dashboard.view';
    }
}
