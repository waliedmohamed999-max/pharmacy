<?php

namespace App\Services;

use App\Models\FinanceAccount;
use App\Models\FinanceJournalEntry;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;

class AccountingService
{
    public const ACCOUNT_CASH = '1110';
    public const ACCOUNT_BANK = '1120';
    public const ACCOUNT_AR = '1130';
    public const ACCOUNT_INVENTORY = '1140';
    public const ACCOUNT_AP = '2110';
    public const ACCOUNT_SALES = '4100';
    public const ACCOUNT_PURCHASES = '5100';
    public const ACCOUNT_COGS = '5110';
    public const ACCOUNT_INV_GAIN = '4310';
    public const ACCOUNT_INV_LOSS = '5310';

    public function nextNumber(string $table, string $column, string $prefix): string
    {
        $last = DB::table($table)
            ->where($column, 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value($column);

        $next = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $next = ((int) $m[1]) + 1;
        }

        return sprintf('%s%05d', $prefix, $next);
    }

    public function createJournalEntry(array $payload, array $lines): FinanceJournalEntry
    {
        $debitTotal = collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0));
        $creditTotal = collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0));

        if (abs($debitTotal - $creditTotal) > 0.0001) {
            throw new \RuntimeException('Journal entry is not balanced.');
        }

        return DB::transaction(function () use ($payload, $lines) {
            $entry = FinanceJournalEntry::create([
                'number' => $payload['number'] ?? $this->nextNumber('finance_journal_entries', 'number', 'JE-'),
                'entry_date' => $payload['entry_date'],
                'reference_type' => $payload['reference_type'] ?? null,
                'reference_id' => $payload['reference_id'] ?? null,
                'description' => $payload['description'] ?? null,
                'created_by' => $payload['created_by'] ?? null,
            ]);

            foreach ($lines as $line) {
                $entry->lines()->create([
                    'account_id' => $line['account_id'],
                    'contact_id' => $line['contact_id'] ?? null,
                    'line_description' => $line['line_description'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                ]);
            }

            return $entry;
        });
    }

    public function postSalesInvoice(SalesInvoice $invoice, ?int $userId = null): void
    {
        $ar = $this->accountIdByCode(self::ACCOUNT_AR);
        $sales = $this->accountIdByCode(self::ACCOUNT_SALES);

        $this->createJournalEntry(
            [
                'entry_date' => $invoice->invoice_date,
                'reference_type' => 'sales_invoice',
                'reference_id' => $invoice->id,
                'description' => 'قيد فاتورة مبيعات ' . $invoice->number,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => $ar,
                    'contact_id' => $invoice->contact_id,
                    'line_description' => 'مدين عميل',
                    'debit' => $invoice->total,
                    'credit' => 0,
                ],
                [
                    'account_id' => $sales,
                    'contact_id' => $invoice->contact_id,
                    'line_description' => 'إيراد مبيعات',
                    'debit' => 0,
                    'credit' => $invoice->total,
                ],
            ]
        );
    }

    public function postPurchaseInvoice(PurchaseInvoice $invoice, ?int $userId = null): void
    {
        $inventory = $this->accountIdByCode(self::ACCOUNT_INVENTORY);
        $ap = $this->accountIdByCode(self::ACCOUNT_AP);

        $this->createJournalEntry(
            [
                'entry_date' => $invoice->invoice_date,
                'reference_type' => 'purchase_invoice',
                'reference_id' => $invoice->id,
                'description' => 'قيد فاتورة مشتريات ' . $invoice->number,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => $inventory,
                    'contact_id' => $invoice->contact_id,
                    'line_description' => 'زيادة مخزون',
                    'debit' => $invoice->total,
                    'credit' => 0,
                ],
                [
                    'account_id' => $ap,
                    'contact_id' => $invoice->contact_id,
                    'line_description' => 'دائن مورد',
                    'debit' => 0,
                    'credit' => $invoice->total,
                ],
            ]
        );
    }

    public function postSalesCost(int $invoiceId, string $invoiceNumber, string $date, float $costAmount, ?int $contactId = null, ?int $userId = null): void
    {
        if ($costAmount <= 0) {
            return;
        }

        $cogs = $this->accountIdByCode(self::ACCOUNT_COGS);
        $inventory = $this->accountIdByCode(self::ACCOUNT_INVENTORY);

        $this->createJournalEntry(
            [
                'entry_date' => $date,
                'reference_type' => 'sales_invoice_cogs',
                'reference_id' => $invoiceId,
                'description' => 'إثبات تكلفة بضاعة مباعة للفاتورة ' . $invoiceNumber,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => $cogs,
                    'contact_id' => $contactId,
                    'line_description' => 'تكلفة البضاعة المباعة',
                    'debit' => $costAmount,
                    'credit' => 0,
                ],
                [
                    'account_id' => $inventory,
                    'contact_id' => $contactId,
                    'line_description' => 'تخفيض المخزون',
                    'debit' => 0,
                    'credit' => $costAmount,
                ],
            ]
        );
    }

    public function postPayment(
        string $direction,
        string $date,
        float $amount,
        int $cashOrBankAccountId,
        ?int $contactId,
        ?string $referenceType,
        ?int $referenceId,
        ?int $userId = null
    ): void {
        $ar = $this->accountIdByCode(self::ACCOUNT_AR);
        $ap = $this->accountIdByCode(self::ACCOUNT_AP);

        $isIn = $direction === 'in';
        $counterpartyAccount = $isIn ? $ar : $ap;
        $desc = $isIn ? 'تحصيل من عميل' : 'سداد لمورد';

        $this->createJournalEntry(
            [
                'entry_date' => $date,
                'reference_type' => $referenceType ?: 'payment',
                'reference_id' => $referenceId,
                'description' => $desc,
                'created_by' => $userId,
            ],
            [
                [
                    'account_id' => $cashOrBankAccountId,
                    'contact_id' => $contactId,
                    'line_description' => 'نقدية/بنك',
                    'debit' => $isIn ? $amount : 0,
                    'credit' => $isIn ? 0 : $amount,
                ],
                [
                    'account_id' => $counterpartyAccount,
                    'contact_id' => $contactId,
                    'line_description' => $desc,
                    'debit' => $isIn ? 0 : $amount,
                    'credit' => $isIn ? $amount : 0,
                ],
            ]
        );
    }

    public function postInventoryAdjustment(
        string $date,
        float $amount,
        bool $isIncrease,
        string $description,
        ?int $userId = null
    ): void {
        if ($amount <= 0) {
            return;
        }

        $inventory = $this->accountIdByCode(self::ACCOUNT_INVENTORY);
        $offset = $this->accountIdByCode($isIncrease ? self::ACCOUNT_INV_GAIN : self::ACCOUNT_INV_LOSS);

        $this->createJournalEntry(
            [
                'entry_date' => $date,
                'reference_type' => 'inventory_adjustment',
                'description' => $description,
                'created_by' => $userId,
            ],
            $isIncrease
                ? [
                    [
                        'account_id' => $inventory,
                        'debit' => $amount,
                        'credit' => 0,
                        'line_description' => 'زيادة مخزون بالتسوية',
                    ],
                    [
                        'account_id' => $offset,
                        'debit' => 0,
                        'credit' => $amount,
                        'line_description' => 'مكسب تسوية مخزون',
                    ],
                ]
                : [
                    [
                        'account_id' => $offset,
                        'debit' => $amount,
                        'credit' => 0,
                        'line_description' => 'خسارة تسوية مخزون',
                    ],
                    [
                        'account_id' => $inventory,
                        'debit' => 0,
                        'credit' => $amount,
                        'line_description' => 'نقص مخزون بالتسوية',
                    ],
                ]
        );
    }

    public function postInventoryReceipt(string $date, float $amount, string $description, ?int $userId = null): void
    {
        $this->postInventoryAdjustment($date, $amount, true, $description, $userId);
    }

    public function postInventoryIssue(string $date, float $amount, string $description, ?int $userId = null): void
    {
        $this->postInventoryAdjustment($date, $amount, false, $description, $userId);
    }

    public function accountIdByCode(string $code): int
    {
        $id = FinanceAccount::query()->where('code', $code)->value('id');
        if (!$id) {
            throw new \RuntimeException('Required account not found: ' . $code);
        }

        return (int) $id;
    }
}
