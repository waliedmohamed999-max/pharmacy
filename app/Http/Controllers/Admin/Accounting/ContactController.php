<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceContact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'type' => ['nullable', 'in:customer,vendor,both'],
            'status' => ['nullable', 'in:active,inactive'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = FinanceContact::query()
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when(($filters['status'] ?? null) === 'active', fn ($q) => $q->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('tax_number', 'like', "%{$search}%");
                });
            });

        $summaryQuery = clone $query;
        $contacts = $query->latest()->paginate(20)->withQueryString();

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'customers' => (clone $summaryQuery)->whereIn('type', ['customer', 'both'])->count(),
            'vendors' => (clone $summaryQuery)->whereIn('type', ['vendor', 'both'])->count(),
            'opening_balance' => (float) (clone $summaryQuery)->sum('opening_balance'),
        ];

        return view('admin.accounting.contacts.index', [
            'contacts' => $contacts,
            'summary' => $summary,
            'filters' => $filters,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:customer,vendor,both'],
            'name' => ['required', 'max:255'],
            'phone' => ['nullable', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'tax_number' => ['nullable', 'max:50'],
            'city' => ['nullable', 'max:100'],
            'address' => ['nullable', 'max:1000'],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        FinanceContact::create([
            ...$data,
            'opening_balance' => $data['opening_balance'] ?? 0,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'تم إضافة جهة الاتصال.');
    }
}
