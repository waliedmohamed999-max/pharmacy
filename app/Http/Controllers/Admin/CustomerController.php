<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $query = Customer::withCount('orders')->withSum('orders', 'total');

        if ($search = request('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(20)->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function exportCsv()
    {
        $filename = 'customers-' . now()->format('Y-m-d-His') . '.csv';
        $columns = ['id', 'name', 'phone', 'email', 'city', 'address', 'is_active', 'orders_count', 'total_spent'];

        return response()->streamDownload(function () use ($columns) {
            $handle = fopen('php://output', 'wb');
            fwrite($handle, chr(239) . chr(187) . chr(191));
            fputcsv($handle, $columns);

            Customer::withCount('orders')->withSum('orders', 'total')->orderBy('id')->chunk(200, function ($customers) use ($handle) {
                foreach ($customers as $customer) {
                    fputcsv($handle, [
                        $customer->id,
                        $customer->name,
                        $customer->phone,
                        $customer->email,
                        $customer->city,
                        $customer->address,
                        $customer->is_active ? 1 : 0,
                        $customer->orders_count,
                        $customer->orders_sum_total ?? 0,
                    ]);
                }
            });

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $rows = $this->readCsvRows($request->file('file')->getRealPath());
        if (empty($rows)) {
            return back()->with('error', 'ملف الاستيراد فارغ أو غير صالح.');
        }

        $imported = 0;
        foreach ($rows as $row) {
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $phone = trim((string)($row['phone'] ?? ''));
            $email = trim((string)($row['email'] ?? ''));

            $customer = Customer::query()
                ->when($phone !== '', fn ($q) => $q->where('phone', $phone))
                ->when($phone === '' && $email !== '', fn ($q) => $q->where('email', $email))
                ->first();

            $data = [
                'name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'email' => $email !== '' ? $email : null,
                'city' => trim((string)($row['city'] ?? '')) ?: null,
                'address' => trim((string)($row['address'] ?? '')) ?: null,
                'is_active' => $this->toBool($row['is_active'] ?? true),
            ];

            if ($customer) {
                $customer->update($data);
            } else {
                Customer::create($data);
            }

            $imported++;
        }

        return back()->with('success', "تم استيراد/تحديث {$imported} عميل من الملف.");
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        if (!$handle) {
            return [];
        }

        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            return [];
        }

        $header = array_map(function ($value) {
            $value = (string)$value;
            $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
            return trim($value);
        }, $header);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count(array_filter($row, fn ($v) => trim((string)$v) !== '')) === 0) {
                continue;
            }
            $rows[] = array_combine($header, array_pad($row, count($header), null));
        }

        fclose($handle);
        return $rows;
    }

    private function toBool(mixed $value): bool
    {
        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 'yes', 'on', 'active'], true);
    }
}
