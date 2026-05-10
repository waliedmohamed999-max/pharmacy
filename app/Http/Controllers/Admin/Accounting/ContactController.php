<?php

namespace App\Http\Controllers\Admin\Accounting;

use App\Http\Controllers\Controller;
use App\Models\FinanceContact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index()
    {
        $contacts = FinanceContact::query()->latest()->paginate(20);

        return view('admin.accounting.contacts.index', [
            'contacts' => $contacts,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:customer,vendor,both'],
            'name' => ['required', 'max:255'],
            'phone' => ['nullable', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
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
