<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * M7 fees and khata: online payments (Razorpay or UPI with a UTR), waived dues with a
 * reason, reminders, cash/bank accounts and reversals in the khata, and the Razorpay
 * webhook events already handled. Money stays in whole rupees; only the gateway fee
 * that Razorpay reports is kept in paise, exactly as Razorpay sends it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('mode')->index();
            $table->string('reference', 40)->nullable()->unique()->after('gateway');
            $table->string('gateway_order_id', 40)->nullable()->unique()->after('reference');
            $table->string('gateway_payment_id', 40)->nullable()->unique()->after('gateway_order_id');
            $table->string('gateway_method', 20)->nullable()->after('gateway_payment_id');
            $table->unsignedInteger('gateway_fee_paise')->nullable()->after('gateway_method');
            $table->unsignedInteger('gateway_tax_paise')->nullable()->after('gateway_fee_paise');
            $table->string('note')->nullable()->after('status');
            $table->timestamp('submitted_at')->nullable()->after('paid_at');
            $table->timestamp('cancelled_at')->nullable()->after('submitted_at');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->index(['family_id', 'status']);
        });

        // A UPI reference (UTR) can be used by one live payment only. Rejected, failed,
        // expired and cancelled payments give their UTR back.
        DB::statement("CREATE UNIQUE INDEX payments_upi_ref_live_unique ON payments (upi_ref) WHERE upi_ref IS NOT NULL AND status IN ('pending_confirmation', 'confirmed')");

        Schema::table('dues', function (Blueprint $table) {
            $table->string('waived_reason')->nullable()->after('status');
            $table->foreignId('waived_by')->nullable()->after('waived_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable()->after('waived_by');
            $table->index(['status', 'period']);
        });

        Schema::table('families', function (Blueprint $table) {
            $table->timestamp('last_reminded_at')->nullable()->after('joined_on');
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->string('account', 10)->default('cash')->after('type');
            $table->foreignId('reverses_id')->nullable()->unique()->after('recorded_by')->constrained('ledger_entries')->restrictOnDelete();
        });

        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20);
            $table->string('event_id', 100);
            $table->string('event', 60);
            $table->jsonb('payload');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reverses_id');
            $table->dropColumn('account');
        });

        Schema::table('families', function (Blueprint $table) {
            $table->dropColumn('last_reminded_at');
        });

        Schema::table('dues', function (Blueprint $table) {
            $table->dropIndex(['status', 'period']);
            $table->dropConstrainedForeignId('waived_by');
            $table->dropColumn(['waived_reason', 'waived_at']);
        });

        DB::statement('DROP INDEX IF EXISTS payments_upi_ref_live_unique');

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['family_id', 'status']);
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'gateway', 'reference', 'gateway_order_id', 'gateway_payment_id', 'gateway_method',
                'gateway_fee_paise', 'gateway_tax_paise', 'note', 'submitted_at', 'cancelled_at',
            ]);
        });
    }
};
