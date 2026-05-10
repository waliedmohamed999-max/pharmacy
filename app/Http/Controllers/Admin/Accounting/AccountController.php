<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceAccount;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $roots = FinanceAccount::query()
            ->with(['children.children'])
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();

        $all = FinanceAccount::query()->orderBy('code')->get();

        return view('admin.accounting.accounts.index', [
            'roots' => $roots,
            'allAccounts' => $all,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'exists:finance_accounts,id'],
            'code' => ['required', 'max:30', 'unique:finance_accounts,code'],
            'name' => ['required', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        FinanceAccount::create([
            ...$data,
            'is_active' => $request->boolean('is_active', true),
            'is_system' => false,
        ]);

        return back()->with('success', 'تم إضافة الحساب بنجاح.');
    }
}
