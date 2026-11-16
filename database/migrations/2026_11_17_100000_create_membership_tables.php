<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Families, the people in them, fee plans and monthly dues.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('families', function (Blueprint $table) {
            $table->id();
            $table->string('family_no', 20)->unique();
            $table->string('head_name');
            $table->string('head_name_bn')->nullable();
            $table->string('address')->nullable();
            $table->string('area', 100)->nullable()->index();
            $table->string('area_bn', 100)->nullable();
            $table->string('phone', 20)->nullable()->index();
            $table->string('status', 20)->default('active')->index();
            $table->date('joined_on')->nullable();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->string('relation', 50)->nullable();
            $table->string('relation_bn', 50)->nullable();
            $table->boolean('is_head')->default(false);
            $table->date('dob')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('blood_group', 3)->nullable()->index();
            $table->string('member_no', 30)->nullable()->unique();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('family_id')->nullable()->after('password')->constrained()->nullOnDelete();
        });

        Schema::create('fee_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_bn')->nullable();
            $table->unsignedInteger('amount');
            $table->string('period', 10);
            $table->unsignedInteger('admission_fee')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('dues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('family_id')->constrained()->cascadeOnDelete();
            $table->date('period');
            $table->unsignedInteger('amount');
            $table->string('status', 10)->default('due')->index();
            $table->timestamps();

            $table->unique(['family_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dues');
        Schema::dropIfExists('fee_plans');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('family_id');
        });
        Schema::dropIfExists('members');
        Schema::dropIfExists('families');
    }
};
