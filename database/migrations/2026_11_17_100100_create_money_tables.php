<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payments, the three books (club, catering, nursery), ledger entries and receipt numbering.
 * Money is stored in whole rupees.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('mode', 10);
            $table->string('upi_ref', 40)->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_no', 20)->nullable()->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_due', function (Blueprint $table) {
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('due_id')->constrained()->cascadeOnDelete();

            $table->primary(['payment_id', 'due_id']);
        });

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->timestamps();
        });

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->restrictOnDelete();
            $table->date('entry_date')->index();
            $table->string('type', 10);
            $table->string('head');
            $table->unsignedInteger('amount');
            $table->string('note')->nullable();
            $table->nullableMorphs('source');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['book_id', 'type']);
        });

        Schema::create('receipt_counters', function (Blueprint $table) {
            $table->string('book_code', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_no')->default(0);
            $table->timestamps();

            $table->primary(['book_code', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_counters');
        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('books');
        Schema::dropIfExists('payment_due');
        Schema::dropIfExists('payments');
    }
};
