<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Days added with "Fill a year's festivals" are worked out from the sun and moon; they stay marked
 * calculated until someone checks them against the club's panjika.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('panjika_days', function (Blueprint $table) {
            $table->boolean('calculated')->default(false)->after('event_id');
        });
    }

    public function down(): void
    {
        Schema::table('panjika_days', function (Blueprint $table) {
            $table->dropColumn('calculated');
        });
    }
};
