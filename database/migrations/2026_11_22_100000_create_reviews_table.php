<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews of the serving team and the nursery, shown on the website (published ones only).
 * Entered by the committee with each customer's permission.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 10)->index();
            $table->unsignedTinyInteger('rating');
            $table->text('body');
            $table->text('body_bn')->nullable();
            $table->string('who', 120);
            $table->string('who_bn', 120)->nullable();
            $table->string('what', 160)->nullable();
            $table->string('what_bn', 160)->nullable();
            $table->date('reviewed_on')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
