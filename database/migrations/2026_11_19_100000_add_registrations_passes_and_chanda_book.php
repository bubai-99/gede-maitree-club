<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * M8 events, chanda, passes and the gate scanner:
 * - registration settings, budget, chanda target and cancelling on events;
 * - family registrations, one gate pass per registration (only a SHA-256 hash of the QR token
 *   is searchable; the token itself is kept encrypted so the member app can draw it again);
 * - every gate scan (online or synced from an offline phone), with undo;
 * - the chanda book: areas with their collector, receipts in the CH series, sponsors, cash
 *   handed from collectors to the treasurer, and the event an expense in the khata belongs to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('registration_open')->default(false)->after('pass_required');
            $table->timestamp('registration_closes_at')->nullable()->after('registration_open');
            $table->unsignedInteger('capacity')->nullable()->after('registration_closes_at');
            $table->unsignedSmallInteger('family_limit')->nullable()->after('capacity');
            $table->boolean('members_only')->default(true)->after('family_limit');
            $table->jsonb('budget')->nullable()->after('bring');
            $table->unsignedInteger('chanda_target')->nullable()->after('chanda_box');
            $table->boolean('chanda_public')->default(false)->after('chanda_target');
            $table->timestamp('cancelled_at')->nullable()->after('published_at');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
            $table->string('cancel_reason_bn')->nullable()->after('cancel_reason');
            $table->foreignId('cancelled_by')->nullable()->after('cancel_reason_bn')->constrained('users')->nullOnDelete();
        });

        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('people');
            $table->string('status', 20)->default('registered')->index();
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'family_id']);
        });

        // The M2 passes table (one per member) is replaced by one pass per family registration.
        Schema::dropIfExists('passes');

        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('registration_id')->unique()->constrained('event_registrations')->cascadeOnDelete();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->char('token_hash', 64)->unique();
            $table->text('token');
            $table->unsignedSmallInteger('people');
            $table->string('status', 20)->default('active');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gate', 60)->nullable();
            $table->unsignedSmallInteger('entered_people')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
        });

        Schema::create('pass_scans', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->foreignId('pass_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('family_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gate', 60)->nullable();
            $table->string('method', 10);
            $table->string('result', 20);
            $table->unsignedSmallInteger('people')->default(0);
            $table->boolean('let_in')->default(false);
            $table->boolean('offline')->default(false);
            $table->string('conflict', 20)->nullable();
            $table->timestamp('scanned_at');
            $table->timestamp('undone_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'result']);
            $table->index(['pass_id', 'result']);
        });

        Schema::create('chanda_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('name_bn', 100)->nullable();
            $table->foreignId('collector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('target')->default(0);
            $table->unsignedSmallInteger('houses')->nullable();
            $table->unsignedSmallInteger('visited')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });

        Schema::table('sponsors', function (Blueprint $table) {
            $table->string('name_bn')->nullable()->after('name');
            $table->string('deliverable_bn')->nullable()->after('deliverable');
            $table->string('status', 20)->default('promised')->after('deliverable_bn');
        });

        Schema::create('cash_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('collector_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->unsignedInteger('expected');
            $table->unsignedInteger('counted');
            $table->unsignedSmallInteger('receipts');
            $table->string('note')->nullable();
            $table->timestamps();
        });

        Schema::table('chanda_entries', function (Blueprint $table) {
            $table->dropIndex(['area']);
            $table->dropColumn('area');
        });

        Schema::table('chanda_entries', function (Blueprint $table) {
            $table->foreignId('chanda_area_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            $table->foreignId('family_id')->nullable()->after('chanda_area_id')->constrained()->nullOnDelete();
            $table->foreignId('sponsor_id')->nullable()->after('family_id')->constrained()->nullOnDelete();
            $table->string('donor_phone', 20)->nullable()->after('donor_name');
            $table->boolean('show_name')->default(false)->after('donor_phone');
            $table->string('mode', 10)->default('cash')->after('amount');
            $table->string('upi_ref', 40)->nullable()->after('mode');
            $table->foreignId('recorded_by')->nullable()->after('collected_by')->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable()->after('receipt_no');
            $table->foreignId('ledger_entry_id')->nullable()->unique()->after('received_at')->constrained()->nullOnDelete();
            $table->foreignId('handover_id')->nullable()->after('ledger_entry_id')->constrained('cash_handovers')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable()->after('handover_id');
            $table->foreignId('cancelled_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->string('cancel_reason')->nullable()->after('cancelled_by');

            $table->index(['event_id', 'cancelled_at']);
            $table->index(['collected_by', 'mode', 'handover_id']);
        });

        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('book_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ledger_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::table('chanda_entries', function (Blueprint $table) {
            $table->dropIndex(['event_id', 'cancelled_at']);
            $table->dropIndex(['collected_by', 'mode', 'handover_id']);
            $table->dropConstrainedForeignId('chanda_area_id');
            $table->dropConstrainedForeignId('family_id');
            $table->dropConstrainedForeignId('sponsor_id');
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropConstrainedForeignId('ledger_entry_id');
            $table->dropConstrainedForeignId('handover_id');
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn(['donor_phone', 'show_name', 'mode', 'upi_ref', 'received_at', 'cancelled_at', 'cancel_reason']);
            $table->string('area', 100)->nullable()->index();
        });

        Schema::dropIfExists('cash_handovers');

        Schema::table('sponsors', function (Blueprint $table) {
            $table->dropColumn(['name_bn', 'deliverable_bn', 'status']);
        });

        Schema::dropIfExists('chanda_areas');
        Schema::dropIfExists('pass_scans');
        Schema::dropIfExists('passes');

        Schema::create('passes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('qr_token', 64)->unique();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('scanned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['event_id', 'member_id']);
        });

        Schema::dropIfExists('event_registrations');

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelled_by');
            $table->dropColumn([
                'registration_open', 'registration_closes_at', 'capacity', 'family_limit', 'members_only', 'budget',
                'chanda_target', 'chanda_public', 'cancelled_at', 'cancel_reason', 'cancel_reason_bn',
            ]);
        });
    }
};
