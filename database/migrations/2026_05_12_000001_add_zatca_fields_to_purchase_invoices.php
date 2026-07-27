<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('finance_contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('finance_contacts', 'tax_number')) {
                $table->string('tax_number', 32)->nullable()->after('email');
            }
        });

        Schema::table('purchase_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_invoices', 'supplier_invoice_number')) {
                $table->string('supplier_invoice_number')->nullable()->after('number');
            }
            if (!Schema::hasColumn('purchase_invoices', 'supplier_tax_number')) {
                $table->string('supplier_tax_number', 32)->nullable()->after('warehouse_id');
            }
            if (!Schema::hasColumn('purchase_invoices', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(15)->after('discount');
            }
            if (!Schema::hasColumn('purchase_invoices', 'taxable_amount')) {
                $table->decimal('taxable_amount', 12, 2)->default(0)->after('tax_rate');
            }
            if (!Schema::hasColumn('purchase_invoices', 'zatca_qr_payload')) {
                $table->text('zatca_qr_payload')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('purchase_invoices', 'zatca_status')) {
                $table->string('zatca_status')->default('ready')->after('zatca_qr_payload');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_invoices', function (Blueprint $table) {
            foreach (['zatca_status', 'zatca_qr_payload', 'taxable_amount', 'tax_rate', 'supplier_tax_number', 'supplier_invoice_number'] as $column) {
                if (Schema::hasColumn('purchase_invoices', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('finance_contacts', function (Blueprint $table) {
            if (Schema::hasColumn('finance_contacts', 'tax_number')) {
                $table->dropColumn('tax_number');
            }
        });
    }
};
