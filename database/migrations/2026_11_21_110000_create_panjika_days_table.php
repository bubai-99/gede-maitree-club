<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Panjika: the puja days, parbon and wedding days (with lagna) the committee enters from the
 * club's panjika. Tithi, nakshatra, sunrise and the Bengali date are worked out in the browser.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('panjika_days', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('kind', 10);
            $table->string('name', 120);
            $table->string('name_bn', 120)->nullable();
            $table->unsignedTinyInteger('tithi')->nullable();
            $table->string('lagna', 80)->nullable();
            $table->string('lagna_bn', 80)->nullable();
            $table->foreignId('event_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('panjika_days');
    }
};
