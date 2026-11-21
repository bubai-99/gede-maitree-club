<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plant types, sun and water are now lists the committee edits (Settings → Plant lists);
 * their keys come from the names typed there, so they can be longer than the old fixed ones.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->string('category', 40)->change();
            $table->string('light', 40)->nullable()->change();
            $table->string('water', 40)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->string('category', 20)->change();
            $table->string('light', 10)->nullable()->change();
            $table->string('water', 10)->nullable()->change();
        });
    }
};
