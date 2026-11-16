<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Events (published from the website CMS), puja chanda, sponsors and gate passes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('title_bn')->nullable();
            $table->string('kind', 20)->index();
            $table->dateTime('starts_at')->index();
            $table->string('time_label', 100)->nullable();
            $table->string('time_label_bn', 100)->nullable();
            $table->string('place')->nullable();
            $table->string('place_bn')->nullable();
            $table->boolean('pass_required')->default(false);
            $table->string('contact_role', 50)->nullable();
            $table->boolean('chanda_box')->default(false);
            $table->text('about')->nullable();
            $table->text('about_bn')->nullable();
            $table->json('schedule')->nullable();
            $table->json('bring')->nullable();
            $table->string('photo', 50)->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('chanda_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->restrictOnDelete();
            $table->string('donor_name');
            $table->string('area', 100)->nullable()->index();
            $table->unsignedInteger('amount');
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('receipt_no', 20)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('sponsors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('amount');
            $table->string('deliverable')->nullable();
            $table->timestamps();
        });

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
    }

    public function down(): void
    {
        Schema::dropIfExists('passes');
        Schema::dropIfExists('sponsors');
        Schema::dropIfExists('chanda_entries');
        Schema::dropIfExists('events');
    }
};
