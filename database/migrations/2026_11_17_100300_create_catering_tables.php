<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maitree Serving: packages (website CMS), orders, the serving roster and payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catering_packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('for')->nullable();
            $table->string('for_bn')->nullable();
            $table->unsignedInteger('rate');
            $table->unsignedInteger('min_guests')->default(0);
            $table->boolean('popular')->default(false);
            $table->json('menu')->nullable();
            $table->json('menu_bn')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('catering_orders', function (Blueprint $table) {
            $table->id();
            $table->string('customer_name');
            $table->string('phone', 20);
            $table->boolean('is_member')->default(false);
            $table->string('occasion')->nullable();
            $table->date('event_date')->index();
            $table->unsignedInteger('guests');
            $table->foreignId('package_id')->nullable()->constrained('catering_packages')->nullOnDelete();
            $table->string('venue')->nullable();
            $table->unsignedInteger('quoted_amount')->nullable();
            $table->string('status', 20)->default('enquiry')->index();
            $table->timestamps();
        });

        Schema::create('catering_roster', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('catering_orders')->cascadeOnDelete();
            $table->string('server_name');
            $table->string('phone', 20)->nullable();
            $table->boolean('is_leader')->default(false);
            $table->unsignedInteger('wage')->default(0);
            $table->boolean('confirmed')->default(false);
            $table->timestamps();
        });

        Schema::create('catering_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('catering_orders')->restrictOnDelete();
            $table->unsignedInteger('amount');
            $table->string('kind', 10);
            $table->string('mode', 10);
            $table->string('receipt_no', 20)->nullable()->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catering_payments');
        Schema::dropIfExists('catering_roster');
        Schema::dropIfExists('catering_orders');
        Schema::dropIfExists('catering_packages');
    }
};
