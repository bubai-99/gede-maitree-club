<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M9 catering and nursery:
 * - catering orders move enquiry → quoted → advance → served → settled (or lost / cancelled), with
 *   the per-guest rate fixed at the quote, payments in the CT series posted to the Catering book,
 *   refunds, the serving team's wages and the order's expenses (ledger entries with the order as source);
 * - nursery counter sales in the NS series posted to the Nursery book, member price for a family,
 *   cancelling a sale, and stock moves for stock in (with cost) and losses.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catering_orders', function (Blueprint $table) {
            $table->foreignId('enquiry_id')->nullable()->after('id')->constrained('enquiries')->nullOnDelete();
            $table->unsignedInteger('rate')->nullable()->after('package_id');
            $table->text('quote_note')->nullable()->after('quoted_amount');
            $table->date('quote_valid_until')->nullable()->after('quote_note');
            $table->timestamp('quoted_at')->nullable()->after('status');
            $table->timestamp('served_at')->nullable()->after('quoted_at');
            $table->timestamp('settled_at')->nullable()->after('served_at');
            $table->timestamp('closed_at')->nullable()->after('settled_at');
            $table->string('closed_reason', 200)->nullable()->after('closed_at');
            $table->foreignId('closed_by')->nullable()->after('closed_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('wages_paid_at')->nullable()->after('closed_by');
            $table->foreignId('created_by')->nullable()->after('wages_paid_at')->constrained('users')->nullOnDelete();
        });

        Schema::table('catering_payments', function (Blueprint $table) {
            $table->string('upi_ref', 40)->nullable()->after('mode');
            $table->string('note', 200)->nullable()->after('upi_ref');
            $table->date('paid_on')->nullable()->after('note');
            $table->foreignId('received_by')->nullable()->after('paid_on')->constrained('users')->nullOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->after('received_by')->constrained('ledger_entries')->nullOnDelete();
        });

        Schema::table('nursery_sales', function (Blueprint $table) {
            $table->foreignId('family_id')->nullable()->after('member_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('subtotal')->default(0)->after('family_id');
            $table->unsignedInteger('discount')->default(0)->after('subtotal');
            $table->string('upi_ref', 40)->nullable()->after('mode');
            $table->foreignId('recorded_by')->nullable()->after('receipt_no')->constrained('users')->nullOnDelete();
            $table->foreignId('ledger_entry_id')->nullable()->after('recorded_by')->constrained('ledger_entries')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('ledger_entry_id');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancel_reason', 200)->nullable()->after('cancelled_by');
        });

        Schema::table('stock_moves', function (Blueprint $table) {
            $table->unsignedInteger('cost')->nullable()->after('kind');
            $table->foreignId('sale_id')->nullable()->after('cost')->constrained('nursery_sales')->nullOnDelete();
            $table->foreignId('recorded_by')->nullable()->after('ref')->constrained('users')->nullOnDelete();
            $table->index(['plant_id', 'kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('stock_moves', function (Blueprint $table) {
            $table->dropIndex(['plant_id', 'kind', 'created_at']);
            $table->dropConstrainedForeignId('sale_id');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropColumn('cost');
        });

        Schema::table('nursery_sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_id');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropConstrainedForeignId('ledger_entry_id');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['subtotal', 'discount', 'upi_ref', 'cancelled_at', 'cancel_reason']);
        });

        Schema::table('catering_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('received_by');
            $table->dropConstrainedForeignId('ledger_entry_id');
            $table->dropColumn(['upi_ref', 'note', 'paid_on']);
        });

        Schema::table('catering_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('enquiry_id');
            $table->dropConstrainedForeignId('closed_by');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['rate', 'quote_note', 'quote_valid_until', 'quoted_at', 'served_at', 'settled_at', 'closed_at', 'closed_reason', 'wages_paid_at']);
        });
    }
};
