<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Maitree Nursery: the plant catalogue (website CMS), stock moves and counter sales.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('category', 20)->index();
            $table->string('pot', 50)->nullable();
            $table->string('pot_bn', 50)->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('member_price');
            $table->integer('stock')->default(0);
            $table->boolean('is_new')->default(false);
            $table->string('light', 10)->nullable();
            $table->string('water', 10)->nullable();
            $table->text('about')->nullable();
            $table->text('about_bn')->nullable();
            $table->text('tip')->nullable();
            $table->text('tip_bn')->nullable();
            $table->string('photo', 50)->nullable();
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_moves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained()->cascadeOnDelete();
            $table->integer('qty');
            $table->string('kind', 10);
            $table->string('ref')->nullable();
            $table->timestamps();
        });

        Schema::create('nursery_sales', function (Blueprint $table) {
            $table->id();
            $table->string('buyer_name')->nullable();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('total');
            $table->string('mode', 10);
            $table->string('receipt_no', 20)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('nursery_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('nursery_sales')->cascadeOnDelete();
            $table->foreignId('plant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('qty');
            $table->unsignedInteger('rate');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_sale_items');
        Schema::dropIfExists('nursery_sales');
        Schema::dropIfExists('stock_moves');
        Schema::dropIfExists('plants');
    }
};
